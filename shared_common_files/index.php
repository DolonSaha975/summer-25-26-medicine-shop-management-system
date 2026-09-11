<?php
// index.php — front controller: the ONLY entry point (router)

require 'config/config.php';   // sets session cookie params, connects to db
session_start();

require 'helpers/helpers.php';

require 'models/user_model.php';
require 'models/medicine_model.php';
require 'models/order_model.php';
require 'models/supplier_model.php';

require 'controllers/auth_controller.php';
require 'controllers/admin_controller.php';
require 'controllers/pharmacist_controller.php';
require 'controllers/supplier_controller.php';
require 'controllers/customer_controller.php';
require 'controllers/ajax_controller.php';

check_session_timeout();

$page = $_GET['page'] ?? 'home';

/* ---------- Logout ---------- */
if ($page === 'logout') {
    logoutCtrl();
    exit;
}

/* ---------- AJAX endpoint ---------- */
if ($page === 'ajax') {
    ajaxCtrl($conn);
    exit;
}

/* ---------- Redirect if already logged in ---------- */
if (in_array($page, ['login', 'register']) && isLoggedIn()) {
    redirectTo('index.php?page=' . roleHomePage());
}

/* ---------- Dispatch ---------- */
switch ($page) {

    /* Public pages */
    case 'home':             homeCtrl($conn);           break;
    case 'medicine_detail':  medicineDetailCtrl($conn); break;
    case 'login':            loginCtrl($conn);          break;
    case 'register':         registerCtrl($conn);       break;

    /* Shared "my account" pages (any logged-in role) */
    case 'profile':          profileCtrl($conn);        break;
    case 'order_detail':     orderDetailCtrl($conn);    break;

    /* Customer pages */
    case 'cart':             cartCtrl($conn);           break;
    case 'checkout':         checkoutCtrl($conn);       break;
    case 'order_success':    orderSuccessCtrl($conn);   break;
    case 'my_orders':        myOrdersCtrl($conn);       break;
    case 'feedback':         feedbackCtrl($conn);       break;
    case 'rate_store':       storeRatingCtrl($conn);    break;
    case 'my_reviews':       redirectTo('index.php?page=feedback'); break;

    /* Admin pages */
    case 'admin':
    case 'admin_dashboard':   adminDashCtrl($conn);       break;
    case 'admin_medicines':   adminMedicinesCtrl($conn);  break;
    case 'admin_categories':  adminCategoriesCtrl($conn); break;
    case 'admin_customers':   adminCustomersCtrl($conn);  break;
    case 'admin_orders':      adminOrdersCtrl($conn);     break;
    case 'admin_reviews':     adminReviewsCtrl($conn);    break;
    case 'admin_feedback':    adminFeedbackCtrl($conn);   break;
    case 'admin_expiry':      adminExpiryCtrl($conn);     break;
    case 'admin_pharmacists': adminPharmacistsCtrl($conn);break;
    case 'admin_suppliers':   adminSuppliersCtrl($conn);  break;

    /* Pharmacist pages */
    case 'pharmacist':
    case 'pharmacist_dashboard':     pharmacistDashCtrl($conn);          break;
    case 'pharmacist_prescriptions': pharmacistPrescriptionsCtrl($conn); break;
    case 'pharmacist_limits':        pharmacistLimitsCtrl($conn);        break;
    case 'pharmacist_payments':      pharmacistPaymentsCtrl($conn);      break;

    /* Supplier pages */
    case 'supplier':
    case 'supplier_dashboard': supplierDashCtrl($conn);    break;
    case 'supplier_stock':     supplierStockCtrl($conn);   break;
    case 'supplier_sales':     supplierSalesCtrl($conn);   break;
    case 'supplier_ratings':   supplierRatingsCtrl($conn); break;

    default:
        redirectTo('index.php?page=home');
}

mysqli_close($conn);
?>
