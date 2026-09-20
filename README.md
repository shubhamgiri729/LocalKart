# 🛒 LocalKart — Multi-Vendor E-Commerce Platform

## 📌 Overview
LocalKart is a full-stack multi-vendor e-commerce web application that connects customers with
local shops. Customers can browse products vendor by vendor, filter and search, check out with
Razorpay or Cash on Delivery, and get instant help from an AI-powered chatbot.

## 🚀 Features

### 👤 Customer
- Browse products across vendors, filter by category/price/store
- Cart, checkout (Razorpay or COD), and order history
- Helpdesk ticketing and an AI chatbot for quick questions

### 🏪 Shopkeeper
- Add, edit, and delete products (with image upload)
- Dashboard with per-vendor order list, dispatch/delivery status, and shipping address
- Respond to helpdesk tickets

### 🛠️ Admin
- Manage users, vendors (verify/unverify), and categories
- Marketplace-wide view of orders and helpdesk activity

## 🔐 Authentication & Authorization
- Role-based login (admin / shopkeeper / customer) with separate dashboards
- PHP sessions, CSRF tokens on every POST form, and rate-limited login attempts
- Passwords hashed with `password_hash()` / verified with `password_verify()`

## 🤖 Chatbot Integration
AI-based chatbot (Google Gemini API) for product questions, order help, and general support,
with a fast-path lookup for direct product/price questions before falling back to the model.

## 🧱 Tech Stack
- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL (tested against MariaDB via XAMPP)
- **Payments:** Razorpay
- **Tools:** Git & GitHub

## ⚙️ Installation & Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/LocalKart.git
   ```
2. Move the project into your XAMPP `htdocs` folder, e.g. `C:/xampp/htdocs/LocalKart`.
3. Start XAMPP — Apache ✅ and MySQL ✅.
4. Import the database: open phpMyAdmin and import `database.sql` (creates the
   `multi_vendor_ecommerce` database). If you're upgrading an existing install instead of
   starting fresh, run the numbered `migration_*.sql` files in this folder against your
   existing database — each one documents exactly what it changes.
5. Copy `.env.example` to `.env` and fill in your database credentials and (optionally) a
   Gemini API key (free tier, no card required, from https://aistudio.google.com/apikey) and
   Razorpay test keys. The app runs fine with the chatbot/Razorpay disabled if those are left
   blank — Cash on Delivery still works either way.
6. Open `http://localhost/LocalKart`.

Configuration is read entirely from environment variables via `.env` (loaded by a small
built-in loader in `config.php` — no Composer dependency required). `.env` itself is
git-ignored; only `.env.example` (with placeholder values) is committed.

### Environment Configuration

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=multi_vendor_ecommerce

GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=

# Base URL of the site — used to build absolute links (e.g. the password
# reset link sent by email). IMPORTANT: in production set this to your real
# public URL (e.g. https://yourdomain.com) with no trailing slash.
APP_URL=http://localhost/LocalKart

# SMTP — used for the password reset email.
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_FROM_EMAIL=no-reply@localkart.test
SMTP_FROM_NAME=LocalKart

CONTACT_EMAIL=

# Razorpay — from your Razorpay Dashboard > Settings > API Keys.
RAZORPAY_KEY_ID=rzp_test_your_key_id
RAZORPAY_KEY_SECRET=your_key_secret
```

## 🔒 Security notes
- All database queries use prepared statements (PDO), including the cart's `IN (...)` lookup,
  which binds item IDs as parameters rather than interpolating them into the SQL string.
- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- Login is rate-limited per IP (5 failed attempts / 15 minutes) via the `login_attempts` table,
  to slow down password-guessing against `login.php`.
- Product image uploads in `add_product.php` are validated by extension, real MIME type
  (`finfo`), and `getimagesize()` — not by extension alone — and are stored under a
  randomly generated filename.
- CSRF tokens are required on every state-changing POST request.

## 📦 Data model notes
- `orders` carries the shipping address the customer entered at checkout
  (`shipping_name`, `shipping_email`, `shipping_address`, `shipping_city`, `shipping_zip`,
  `shipping_country`) — shown to shopkeepers on their dashboard and order-status page.
- `orders.status` is a derived summary ('pending' / 'dispatched' / 'delivered') computed from
  the per-vendor `order_items.status` values, so one vendor dispatching their part of a
  multi-vendor order doesn't show as dispatched for every other vendor's part. See
  `recomputeOrderStatus()` in `config.php`.

## 🎯 Future enhancements
- Automated tests
- Advanced search & recommendation system
- Order tracking / delivery notifications
- Mobile optimization improvements

## 👨‍💻 Author
Shubham Giri — Computer Engineering Student, Full-Stack Developer

## ⭐ Contribute
Feel free to fork this repository and contribute!

## 📜 License
This project is for educational purposes only.
