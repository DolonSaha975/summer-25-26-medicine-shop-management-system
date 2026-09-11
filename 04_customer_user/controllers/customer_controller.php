<?php
// controllers/customer_controller.php — the shop-facing pages, plus the
// "my account" pages (profile, order detail) that any logged-in role lands
// on when viewing their own info or an order they're involved with.

function homeCtrl($conn) {
    $search     = trim($_GET['q']      ?? '');
    $vendorFlt  = trim($_GET['vendor'] ?? '');
    $typeFlt    = trim($_GET['type']   ?? '');
    $medicines  = searchMedicines($conn, $search, $vendorFlt, $typeFlt);
    $categories = getAllCategories($conn);

    $vr = mysqli_query($conn, "SELECT DISTINCT vendor_name FROM medicines ORDER BY vendor_name ASC");
    $vendors = mysqli_fetch_all($vr, MYSQLI_ASSOC);

    require 'views/customer/home.php';
}

function medicineDetailCtrl($conn) {
    $id  = intval($_GET['id'] ?? 0);
    $med = getMedicineById($conn, $id);
    if (!$med) redirectTo('index.php?page=home');

    $reviewError = $reviewSuccess = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        if (!isCustomer()) redirectTo('index.php?page=login');
        csrf_verify();

        $rating  = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Please select a rating between 1 and 5 stars.';
        } else {
            addReview($conn, $id, $_SESSION['user']['id'], $rating, $comment);
            $reviewSuccess = 'Thank you! Your review has been submitted for approval.';
        }
    }

    $reviews       = getReviewsForMedicine($conn, $id, true);
    $ratingSummary = getMedicineRatingSummary($conn, $id);
    $medicineLimit = getMedicineLimitByMedicineId($conn, $id);

    require 'views/customer/medicine_detail.php';
}

function cartCtrl($conn) {
    if (!isCustomer()) redirectTo('index.php?page=login');
    $items = getCartItems($conn, $_SESSION['user']['id']);
    $total = array_sum(array_map(function ($i) { return $i['price'] * $i['quantity']; }, $items));
    require 'views/customer/cart.php';
}

function checkoutCtrl($conn) {
    if (!isCustomer()) redirectTo('index.php?page=login');

    $userId = $_SESSION['user']['id'];
    $items  = getCartItems($conn, $userId);
    if (empty($items)) redirectTo('index.php?page=cart');

    $total = array_sum(array_map(function ($i) { return $i['price'] * $i['quantity']; }, $items));
    $user  = getUserById($conn, $userId);
    $error = '';
    $step  = $_GET['step'] ?? 'address'; // address | invoice | payment

    if ($step === 'address' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $deliveryType = ($_POST['delivery_type'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';

        if ($deliveryType === 'pickup') {
            $pickupTime = trim($_POST['pickup_time'] ?? '');
            if ($pickupTime === '') {
                $error = 'Please choose a pickup date & time.';
            } else {
                $_SESSION['checkout_address']      = 'Store Pickup — MediShop Main Branch, Dhaka';
                $_SESSION['checkout_delivery_type'] = 'pickup';
                $_SESSION['checkout_pickup_time']   = $pickupTime;
                redirectTo('index.php?page=checkout&step=invoice');
            }
        } else {
            $addr = trim($_POST['shipping_address'] ?? '');
            if ($addr === '') {
                $error = 'Shipping address is required.';
            } else {
                $_SESSION['checkout_address']      = $addr;
                $_SESSION['checkout_delivery_type'] = 'delivery';
                $_SESSION['checkout_pickup_time']   = null;
                redirectTo('index.php?page=checkout&step=invoice');
            }
        }
    }

    if ($step === 'invoice' && !isset($_SESSION['checkout_address'])) {
        redirectTo('index.php?page=checkout&step=address');
    }

    if ($step === 'payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $addr         = $_SESSION['checkout_address'] ?? '';
        $deliveryType = $_SESSION['checkout_delivery_type'] ?? 'delivery';
        $pickupTime   = $_SESSION['checkout_pickup_time'] ?? null;
        $method       = trim($_POST['payment_method'] ?? '');

        if ($addr === '') redirectTo('index.php?page=checkout&step=address');
        if ($method === '') { $error = 'Please select a payment method.'; $step = 'payment'; }
        else {
            foreach ($items as $item) {
                if ($item['quantity'] > $item['availability']) {
                    $error = "'{$item['name']}' has only {$item['availability']} units in stock.";
                    $step  = 'address';
                    require 'views/customer/checkout.php';
                    return;
                }
            }
            $orderId = createOrder($conn, $userId, $total, $addr, $method, $deliveryType, $pickupTime);
            if ($orderId) {
                foreach ($items as $item) {
                    addOrderItem($conn, $orderId, $item['medicine_id'], $item['quantity'], $item['price']);
                    reduceStock($conn, $item['medicine_id'], $item['quantity']);
                }
                createPayment($conn, $orderId, $total, $method);
                autoCreatePrescriptionsForOrder($conn, $orderId, $userId, $items);
                clearCart($conn, $userId);
                unset($_SESSION['checkout_address'], $_SESSION['checkout_delivery_type'], $_SESSION['checkout_pickup_time']);
                redirectTo('index.php?page=order_success&order_id=' . $orderId);
            } else {
                $error = 'Order placement failed. Please try again.';
            }
        }
    }

    require 'views/customer/checkout.php';
}

function orderSuccessCtrl($conn) {
    if (!isCustomer()) redirectTo('index.php?page=login');
    $orderId = intval($_GET['order_id'] ?? 0);
    $order   = getOrderById($conn, $orderId);
    if (!$order || $order['user_id'] != $_SESSION['user']['id']) redirectTo('index.php?page=home');
    $orderItems = getOrderItems($conn, $orderId);
    require 'views/customer/order_success.php';
}

function myOrdersCtrl($conn) {
    if (!isCustomer()) redirectTo('index.php?page=login');
    $orders = getOrdersByUser($conn, $_SESSION['user']['id']);
    require 'views/customer/my_orders.php';
}

function feedbackCtrl($conn) {
    if (!isCustomer()) redirectTo('index.php?page=login');

    $userId = $_SESSION['user']['id'];
    $action = $_GET['action'] ?? '';
    $error  = $success = '';
    $editingFeedback = null;
    $editingReview   = null;

    // ---------- Feedback: create ----------
    if ($action === 'feedback_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($subject === '') $subject = 'General Feedback'; // subject is optional
        if ($message === '') {
            $error = 'Please write a message before sending.';
        } else {
            addFeedback($conn, $userId, $subject, $message);
            $success = 'Thanks! Your feedback has been sent to our team.';
        }
    }

    // ---------- Feedback: edit / update / delete ----------
    if ($action === 'feedback_edit') {
        $editingFeedback = getFeedbackById($conn, intval($_GET['id'] ?? 0));
        if (!$editingFeedback || $editingFeedback['customer_id'] != $userId) redirectTo('index.php?page=feedback');
    }

    if ($action === 'feedback_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id       = intval($_GET['id'] ?? 0);
        $existing = getFeedbackById($conn, $id);
        $subject  = trim($_POST['subject'] ?? '');
        $message  = trim($_POST['message'] ?? '');
        if ($subject === '') $subject = 'General Feedback';

        if (!$existing || $existing['customer_id'] != $userId) {
            redirectTo('index.php?page=feedback');
        } elseif ($message === '') {
            $error = 'Please write a message before saving.';
            $editingFeedback = $existing;
        } else {
            updateFeedback($conn, $id, $subject, $message);
            redirectTo('index.php?page=feedback&msg=feedback_updated');
        }
    }

    if ($action === 'feedback_delete') {
        csrf_verify();
        $id       = intval($_GET['id'] ?? 0);
        $existing = getFeedbackById($conn, $id);
        if ($existing && $existing['customer_id'] == $userId) {
            deleteFeedback($conn, $id);
        }
        redirectTo('index.php?page=feedback&msg=feedback_deleted');
    }

    // ---------- Reviews: create ----------
    if ($action === 'review_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $medId   = intval($_POST['medicine_id'] ?? 0);
        $rating  = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($medId === 0) {
            $error = 'Please choose a medicine to review.';
        } elseif ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5 stars.';
        } else {
            addReview($conn, $medId, $userId, $rating, $comment);
            $success = 'Thanks! Your review has been submitted for approval.';
        }
    }

    // ---------- Reviews: edit / update / delete ----------
    if ($action === 'review_edit') {
        $editingReview = getReviewById($conn, intval($_GET['id'] ?? 0));
        if (!$editingReview || $editingReview['customer_id'] != $userId) redirectTo('index.php?page=feedback');
    }

    if ($action === 'review_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $id      = intval($_GET['id'] ?? 0);
        $review  = getReviewById($conn, $id);
        $rating  = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if (!$review || $review['customer_id'] != $userId) {
            redirectTo('index.php?page=feedback');
        } elseif ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5 stars.';
            $editingReview = $review;
        } else {
            updateReview($conn, $id, $rating, $comment);
            redirectTo('index.php?page=feedback&msg=review_updated');
        }
    }

    if ($action === 'review_delete') {
        csrf_verify();
        $id     = intval($_GET['id'] ?? 0);
        $review = getReviewById($conn, $id);
        if ($review && $review['customer_id'] == $userId) {
            deleteReview($conn, $id);
        }
        redirectTo('index.php?page=feedback&msg=review_deleted');
    }

    // ---------- Lists (with search) ----------
    $fq = trim($_GET['fq'] ?? '');
    $allFeedback = array_values(array_filter(getAllFeedback($conn), function ($f) use ($userId) {
        return $f['customer_id'] == $userId;
    }));
    $myFeedback = $fq === '' ? $allFeedback : array_values(array_filter($allFeedback, function ($f) use ($fq) {
        return stripos($f['subject'], $fq) !== false || stripos($f['message'], $fq) !== false;
    }));

    $rq = trim($_GET['rq'] ?? '');
    $allReviews = getReviewsByCustomer($conn, $userId);
    $myReviews = $rq === '' ? $allReviews : array_values(array_filter($allReviews, function ($r) use ($rq) {
        return stripos($r['medicine_name'], $rq) !== false || stripos($r['comment'] ?? '', $rq) !== false;
    }));

    $medicines = getAllMedicines($conn);

    require 'views/customer/reviews_feedback.php';
}

function storeRatingCtrl($conn) {
    if (!isCustomer()) redirectTo('index.php?page=login');

    $error = $success = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $rating  = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5 stars.';
        } else {
            addStoreRating($conn, $_SESSION['user']['id'], $rating, $comment);
            $success = 'Thanks for rating us!';
        }
    }

    $summary = getStoreRatingSummary($conn);
    require 'views/customer/store_rating.php';
}

// ---------- Shared "my account" pages ----------

function profileCtrl($conn) {
    if (!isLoggedIn()) redirectTo('index.php?page=login');

    $user  = getUserById($conn, $_SESSION['user']['id']);
    $error = $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $name    = trim($_POST['name']    ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '') {
            $error = 'Name is required.';
        } else {
            $picPath = handleImageUpload('profile_picture', 'uploads/profiles/');
            if (updateUserProfile($conn, $user['id'], $name, $phone, $address, $picPath)) {
                $_SESSION['user']['name'] = $name;
                $user    = getUserById($conn, $user['id']);
                $success = 'Profile updated successfully.';
            } else {
                $error = 'Update failed.';
            }
        }
    }
    require 'views/customer/profile.php';
}

function orderDetailCtrl($conn) {
    if (!isLoggedIn()) redirectTo('index.php?page=login');
    $orderId = intval($_GET['id'] ?? 0);
    $order   = getOrderById($conn, $orderId);

    if (!$order) redirectTo('index.php?page=home');
    if (isCustomer() && $order['user_id'] != $_SESSION['user']['id']) redirectTo('index.php?page=my_orders');

    $orderItems         = getOrderItems($conn, $orderId);
    $customer           = getUserById($conn, $order['user_id']);
    $orderPrescriptions = getPrescriptionsForOrder($conn, $orderId);
    require 'views/customer/order_detail.php';
}
?>
