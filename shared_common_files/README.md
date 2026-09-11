# Medicine Shop Management System (PHP + MySQL, MVC)

A  project for a 4-role medicine shop system: **admin, pharmacist, supplier, customer**.
Written in plain PHP with procedural `mysqli` and prepared statements. No frameworks,
no Composer, no build step. Copy it into XAMPP and it runs.

---

## 1. Install (XAMPP)

1. Copy the `medicine_shop_management` folder into `C:\xampp\htdocs\` so it becomes `htdocs/medicine_shop_management/`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open `http://localhost/phpmyadmin` → **Import** → choose `database.sql` → **Go**.
4. Open `http://localhost/medicine_shop_management/`.
5. Sign in as the default admin: **admin@medicine.com / admin123**

The admin account is created automatically the first time a page loads
(see the bottom of `config/config.php`). Everyone else signs up on the register page.

If your MySQL uses a password, edit the `mysqli_connect(...)` line in `config/config.php`.

---

## 2. Folder structure

```
medicine_shop_management/
├── index.php                  Front controller: the ONLY entry point (router)
├── database.sql               Schema + a few sample medicines
├── README.md
│
├── config/
│   └── config.php             DB connection, session cookie settings, app constants
│
├── helpers/
│   └── helpers.php            esc(), CSRF, login guards, flash messages, session timeout
│
├── models/                    M — every SQL query lives here
│   ├── user_model.php         all 4 roles (one users table), staff accounts
│   ├── medicine_model.php     categories, catalogue, reviews, expiry/damage
│   ├── order_model.php        cart, orders, payments, prescriptions, limits, feedback, ratings
│   └── supplier_model.php     stock supplies, low stock, sale history
│
├── controllers/               C — request handling, validation, decisions
│   ├── auth_controller.php    login / register / logout
│   ├── admin_controller.php
│   ├── pharmacist_controller.php
│   ├── supplier_controller.php
│   ├── customer_controller.php
│   └── ajax_controller.php    all JSON endpoints
│
├── views/                     V — HTML only
│   ├── partials/               navbar.php (one per role)
│   ├── auth/                   login.php, register.php
│   ├── admin/                  dashboard, medicines, categories, customers, orders,
│   │                              reviews, feedback, expiry, pharmacists, suppliers
│   ├── pharmacist/              dashboard, prescriptions, limits, payments
│   ├── supplier/                dashboard, stock, sales, ratings
│   └── customer/                home, medicine_detail, cart, checkout, order_success,
│                                   my_orders, reviews_feedback, store_rating,
│                                   profile, order_detail
│
├── assets/
│   ├── css/style.css
│   └── js/app.js               escapeHtml(), ajaxTable(), validateRequired()
│
└── uploads/
    ├── medicines/
    └── profiles/
```

**The MVC rule used throughout:** a view never runs a query, and a model never
prints HTML. The controller sits in the middle: it reads `$_POST`, validates,
calls the model, then `require`s the view.

---

## 3. How the router works

Every URL looks like this:

```
index.php?page=<dashboard>&action=<what to do>&id=<row id>
```

| URL                                                            | What happens                        |
| ---------------------------------------------------------------| ------------------------------------ |
| `index.php?page=login`                                         | Login page (role tabs + Remember Me) |
| `index.php?page=register`                                      | Signup page (customer/pharmacist/supplier) |
| `index.php?page=admin`                                         | Admin dashboard                      |
| `index.php?page=admin_medicines&action=edit&id=4`               | Load medicine 4 into the form        |
| `index.php?page=pharmacist_prescriptions&action=verify&id=2`    | Open the verification form for record 2 |
| `index.php?page=feedback&action=review_delete&id=7&csrf_token=…`| Delete review 7                      |
| `index.php?page=ajax&type=search_medicines&q=napa`               | Returns JSON                         |
| `index.php?page=logout`                                         | Sign out                             |

`index.php` loads config → helpers → all four model files → all six controller
files, checks the session timeout, then sends the request to one controller
function. Every controller function starts with a role check (`isAdmin()`,
`isPharmacist()`, `isSupplier()`, `isCustomer()`) before it does anything else.

---

## 4. The four roles

Each role owns its own area of the database and does full **Create, Read,
Update, Delete and Search** there. The form sits at the top of the page; the
searchable table sits below it. Clicking **Edit** reloads the same page with
the row loaded into that same form.

| Role           | Manages (CRUD)                          | Feature 1                        | Feature 2                            | Feature 3                             |
| -------------- | ---------------------------------------- | --------------------------------- | ------------------------------------- | -------------------------------------- |
| **Admin**      | Medicines, categories, customers, orders | Review moderation (approve/reject)| Expiry & damaged-stock tracking       | Feedback inbox with replies            |
| **Pharmacist** | Prescription records, purchase limits    | Prescription verification         | Purchase limit per medicine, per customer | Payment confirmation                |
| **Supplier**   | Stock supply records                     | Low-stock alerts                  | Sale history (by medicine + detailed) | Store rating moderation                |
| **Customer**   | My reviews, my feedback, my orders       | Pickup or home delivery at checkout | Medicine reviews & ratings          | Feedback with replies, plus a store rating |

No feature appears on two dashboards.

### How the roles connect

- A **customer** checks out with a medicine marked "requires prescription" → a
pending record appears on the **pharmacist**'s Prescriptions page automatically.
- The pharmacist verifies or rejects it, and separately marks the order's
payment as paid or refunded.
- A **supplier** logs a stock supply → the medicine's stock count goes up right
away; editing or deleting that record adjusts the stock back correctly.
- When a medicine's stock drops to its threshold, it shows up as a low-stock
alert on the supplier's dashboard.
- A customer's review goes to the **admin** for approval before it's shown
publicly on the medicine's page; editing a review sends it back for approval.
- A customer's feedback and store rating are visible to the admin and supplier
respectively — the admin can reply to feedback, which the customer then sees.
- The admin can also create pharmacist and supplier accounts directly, on top
of those two roles being able to self-register.

---

## 5. Requirement checklist

| Requirement                 | Where to look                                                  |
| ---------------------------- | --------------------------------------------------------------- |
| **MVC**                      | `models/`, `controllers/`, `views/`, routed by `index.php`      |
| **DB (MySQLi procedural)**   | every function in `models/` uses `mysqli_prepare`               |
| **Auth (session + cookie)**  | `controllers/auth_controller.php`, `helpers/helpers.php`        |
| **PHP validation**           | the `if / elseif` chain at the top of every controller action   |
| **JS validation**            | `required` attributes + inline checks, `assets/js/app.js`       |
| **AJAX / JSON**               | `controllers/ajax_controller.php` + the search boxes on every list page |
| **UI (HTML/CSS)**            | `views/`, `assets/css/style.css` (no framework)                 |
| **Basic web security**       | see section 6                                                   |
| **Feature completeness**     | CRUD + search + 3 features per role                             |

---

## 6. Security, and why each piece is there

| Attack             | Defence                                                             | File                                |
| ------------------- | --------------------------------------------------------------------| ------------------------------------ |
| SQL injection       | Prepared statements everywhere — user text is never glued into SQL  | all `models/`                       |
| Stolen passwords    | `password_hash()` on save, `password_verify()` on login             | `user_model.php`                    |
| XSS (server)        | `esc()` wraps every value printed into HTML                         | `helpers.php`, all views            |
| XSS (client)        | `escapeHtml()` before any AJAX row is inserted                      | `app.js`                            |
| CSRF                | A secret token in every POST form and every delete/approve/reject link | `helpers.php`, all views         |
| Session fixation    | `session_regenerate_id(true)` right after a successful login        | `auth_controller.php`               |
| Cookie theft        | `httponly` + `samesite=Lax` on the session cookie                   | `config.php`                        |
| Idle machines       | Automatic sign-out after 30 minutes                                 | `check_session_timeout()`           |
| Wrong role          | Every controller function checks its role before doing anything     | every file in `controllers/`        |
| Wrong portal        | Logging into the wrong role tab is rejected even with correct credentials | `auth_controller.php`          |
| URL tampering       | A customer can only edit/delete rows they own (`WHERE … AND customer_id = ?`) | `medicine_model.php`, `order_model.php` |
| Username guessing   | Wrong email and wrong password give the same message                | `auth_controller.php`               |

Two things worth saying out loud to students:

1. **JavaScript validation is a convenience, not a defence.** Anyone can turn
JavaScript off. That is why every controller repeats the checks in PHP.
2. **"Remember me" only refills the email**, never the password.

---

## 7. Settings you can change

All in `config/config.php`:

```
define('LOW_STOCK_DEFAULT', 10);     // a medicine at/below this shows the low-stock alert
define('SESSION_TIMEOUT',   1800);   // idle sign-out, in seconds
define('REMEMBER_COOKIE',   'medishop_remember'); // "remember me" cookie name
```

---

## 8. Test accounts

| Role       | Email                        | Password   |
| ----------- | ----------------------------- | ---------- |
| Admin       | `admin@medicine.com`          | `admin123` |
| Pharmacist  | sign up on the register page  |            |
| Supplier    | sign up on the register page  |            |
| Customer    | sign up on the register page  |            |

Nobody can sign up as an admin — the register page only accepts the other three
roles, and the controller checks that list again on the server. Additional
pharmacist and supplier accounts can also be created by an existing admin.
