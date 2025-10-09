# PHP Login & Register System (XAMPP)

A simple and secure login/register system built with **PHP** and **MySQL**, designed to run locally using **XAMPP**.

---

## ⚙️ Features
- Secure password hashing (`password_hash`, `password_verify`)
- Session-based login system
- Simple and Tailwind-ready front-end
- Works offline via XAMPP
- Clean, modular PHP structure

---

## 🛠 Setup

1. Start Apache and MySQL in **XAMPP**
2. Open **phpMyAdmin** and run this SQL:

```sql
CREATE DATABASE user_system;
USE user_system;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL
);
```

3. Copy `config.example.php` to `config.php` and set your MySQL credentials.
4. Place this folder in `C:\xampp\htdocs\`
5. Open in browser: [http://localhost/user-auth-php](http://localhost/user-auth-php)

---

## 🧩 Files
| File | Description |
|------|--------------|
| `register.php` | Registration form and logic |
| `login.php` | Login form and logic |
| `logout.php` | Logout and session destroy |
| `dashboard.php` | Protected page after login |
| `config.example.php` | Example DB config |
| `index.php` | Redirects to login |

---

## 🧠 License
MIT License
