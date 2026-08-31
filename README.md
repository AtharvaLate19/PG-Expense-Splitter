# 🏠 PG Expense Splitter

> **A simple web-based system to manage and split expenses among PG residents.**

## 📌 About the Project

**PG Expense Splitter** is a web application designed to make managing shared expenses in a **Paying Guest (PG)** easier.

The **Admin** can manage PG members, add and split expenses, edit expenses, and update payment status. **Users** can log in and view their PG details, expenses, and payment status.

Each PG is identified using a unique **PG Code**, so users can access only the information related to their own PG group.

---

## ✨ Features

### 👨‍💼 Admin

* Add and remove PG members
* Add individual expenses
* Split common expenses among members
* Edit expenses
* Mark expenses as **Paid/Pending**
* View PG members and expense details
* Manage profile

### 👤 User

* Secure login
* View PG group details
* View personal expenses
* Check **Paid/Pending** status
* Update profile and password
* Delete account

---

## 🛠️ Technologies Used

| Technology         | Purpose                                 |
| ------------------ | --------------------------------------- |
| **HTML**           | Web page structure                      |
| **CSS**            | Styling and UI design                   |
| **JavaScript**     | Client-side interactions and validation |
| **Bootstrap 5**    | Responsive UI components                |
| **PHP**            | Backend and application logic           |
| **MySQL**          | Database                                |
| **MySQLi**         | PHP-MySQL connection                    |
| **Apache / XAMPP** | Local server environment                |

---

## 🗄️ Database

The project uses **MySQL** with the database:

```text
pg_expenses
```

Main tables include:

* `users` — stores Admin and User information
* `groupp` — stores PG group information
* `expenses` — stores expense and payment details

The `pg_code` connects users with their respective PG group.

---

## 📂 Project Structure

```text
PG-Expense-Splitter/
│
├── index.php        # Login page & authentication
├── login.php        # Login-related functionality
├── signup.php       # User registration
├── dashboard.php    # Main dashboard & expense management
├── db.php           # MySQL database connection
│
├── image/
│   └── login.webp   # Login page background
│
└── README.md
```

---

## 🔄 How It Works

```text
User/Admin
    ↓
   Login
    ↓
Authentication
    ↓
Identify PG using PG Code
    ↓
Dashboard
    ↓
┌───────────────┬───────────────┐
│     Admin     │     User      │
├───────────────┼───────────────┤
│ Manage Users  │ View Expenses │
│ Add Expenses  │ View Status   │
│ Split Bills   │ View PG Info  │
│ Update Status │               │
└───────────────┴───────────────┘
          ↓
       MySQL DB
```

---

## 💰 Expense Splitting

The Admin can enter a common expense and the system automatically divides it among the PG residents.

**Example:**

```text
Electricity Bill = ₹4000
Members = 4

₹4000 ÷ 4 = ₹1000 per member
```

The calculated amount is stored in the database and can be tracked as **Paid** or **Pending**.

---

## 🚀 Running the Project

### Requirements

* XAMPP
* Apache
* MySQL
* PHP
* Web Browser

### Steps

1. Install and open **XAMPP**.
2. Start **Apache** and **MySQL**.
3. Place the project inside:

```text
C:\xampp\htdocs\
```

4. Create a MySQL database named:

```text
pg_expenses
```

5. Import the project database/tables into **phpMyAdmin**.
6. Check the database credentials in `db.php`.
7. Open the project through:

```text
http://localhost/PG-Expense-Splitter/
```

---

## 🎯 Project Goal

The goal of **PG Expense Splitter** is to provide a simple and organized way to manage **shared PG expenses**, reduce manual calculations, and make payment tracking easier for both Admins and residents.

---

<p align="center">
  <strong>🏠 PG Expense Splitter</strong><br>
  <em>Split Smart. Track Easy. Live Stress-Free.</em>
</p>
