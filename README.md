# InventoryPro Backend

This is the backend API for InventoryPro, a full-stack inventory management app.

## link: [Frontend Repository for InventoryPro](https://github.com/BD-YASSINE/InventoryPro)

## ⚙️ Technologies Used

- PHP (REST API)
- MySQL (with XAMPP)
- Session-based Authentication

## 📦 Getting Started

### 1. Clone the Backend

```bash
git clone https://github.com/BD-YASSINE/inventory-backend.git
cd inventory-backend
```

### 2. Set Up the Server

- Start Apache and MySQL using XAMPP.
- Import the provided SQL file into phpMyAdmin to set up the database.

### 3. Configure the Backend

Ensure your backend `.env` or config file includes:

```php
define('FRONTEND_URL', 'http://localhost:5173'); // Or your frontend URL
```

### 4. File Structure

- `/api`: All REST API endpoints
- `/config`: DB and global config
- `/helpers`: CORS and response handlers

## 📄 License

See [LICENSE](../LICENSE)

## 👤 Author

Made by [**ERROR**](https://github.com/BD-YASSINE)

## 📞 Contact

linkedin: [My LinkedIn](https://www.linkedin.com/in/yassine-badri-0279a7342/)

---

© 2025 InventoryPro. All rights reserved.
