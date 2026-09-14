# 🛒 LocalKart – Multi-Vendor E-Commerce Platform  

## 📌 Overview  
LocalKart is a full-stack multi-vendor e-commerce web application designed to connect customers with local shops. It allows users to browse products shop-wise, apply smart filters, and interact with an AI-powered chatbot for real-time assistance.  

The platform aims to digitally empower local businesses while providing users with a smooth and intuitive shopping experience.  

---

## 🚀 Features  

### 👤 Customer Features  
- Browse products from different local shops  
- Filter products based on shop names  
- View shop name on each product  
- Add to cart and manage orders  
- Chatbot support for instant queries  

### 🏪 Shopkeeper Features  
- Add, update, and delete products  
- Manage inventory  
- View and handle customer orders  
- Separate dashboard with dedicated interface  

### 🛠️ Admin Features  
- Manage users and shopkeepers  
- Monitor all products and orders  
- Control platform activities  
- Access complete admin dashboard  

---

## 🔐 Authentication & Authorization  
- Role-based login system  
- Separate dashboards for:
  - Customer  
  - Shopkeeper  
  - Admin  
- Session management using PHP  
- Secure user authentication with MySQL database  

---

## 🤖 Chatbot Integration  
- AI-based chatbot for real-time assistance  
- Helps users with:
  - Product-related queries  
  - Order tracking  
  - General support  

---

## 🧱 Tech Stack  

**Frontend:**  
- HTML  
- CSS  
- JavaScript  

**Backend:**  
- PHP  

**Database:**  
- MySQL (XAMPP Server)  

**Tools:**  
- Git & GitHub  

---

## ⚙️ Installation & Setup  

1. Clone the repository
```bash
git clone https://github.com/your-username/LocalKart.git
```
2. Move the project to XAMPP htdocs
```bash
C:/xampp/htdocs/LocalKart
```
3. Start XAMPP
- Apache ✅
- MySQL ✅
4. Import Database
- Open phpMyAdmin
- Import your .sql file
5. Run the project
```bash
http://localhost/LocalKart
```

---
## 📊 Key Highlights
- Multi-vendor e-commerce system
- Shop-based product filtering
- Product-level shop name display
- Role-based dashboards with different capabilities
- AI chatbot for enhanced user experience
- Responsive and scalable design
---
## 🎯 Future Enhancements
- Payment gateway integration
- Advanced search & recommendation system
- Mobile optimization improvements
- Notification system
---
## 👨‍💻 Author
- Shubham Giri
  - Computer Engineering Student
  - Full-Stack Developer
---
## ⭐ Contribute
- Feel free to fork this repository and contribute!
---
## 📜 License
- This project is for educational purposes only.
=======
# LocalKart

A full-stack multi-vendor e-commerce platform connecting customers with local shops.

## Features

- Product catalog with per-vendor storefronts and filtering
- Shopping cart and checkout flow
- Order processing and order history
- Role-based dashboards for admins, shopkeepers, and customers
- Helpdesk ticket system
- AI-powered customer support chatbot (Google Gemini API), with a fast-path lookup
  for direct product/price questions before falling back to the model

## Tech stack

- PHP, MySQL (PDO + mysqli)
- Vanilla HTML/CSS/JS on the frontend
- Google Gemini API for the chatbot

## Setup

1. Import `database.sql` into MySQL (creates the `multi_vendor_ecommerce` database).
2. Copy `.env.example` to `.env` and fill in your database credentials and a Gemini API key
   (free tier, no card required, from https://aistudio.google.com/apikey). The app runs
   fine with the chatbot disabled if `GEMINI_API_KEY` is left blank.
3. Point a PHP/Apache server (e.g. XAMPP) at this folder and open `index.php`.

Configuration is read entirely from environment variables via `.env` (loaded by a small
built-in loader in `config.php` — no Composer dependency required). `.env` itself is
git-ignored; only `.env.example` (with placeholder values) is committed.

### Environment Configuration

Create a `.env` file in the project root using `.env.example` as a template:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=multi_vendor_ecommerce
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=

## Security notes

- All database queries use prepared statements (PDO/mysqli), including the shopping cart's
  `IN (...)` lookup, which binds cart item IDs as parameters rather than interpolating them
  into the SQL string.
- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- File uploads in `add_product.php` are currently validated by extension only, not file
  content/MIME type — adding a `getimagesize()` or MIME check is a reasonable next hardening
  step for anyone extending this.

## Known limitations / next steps

- No automated tests yet.
- Product image uploads are stored directly under `uploads/products/` with no size/rate
  limiting.
