# Ashu Spare Part System — XAMPP Setup Guide

## Prerequisites
- XAMPP installed (PHP 8.1+, Apache, MySQL)
- Composer installed

---

## Step 1 — Copy Project to XAMPP
Copy the entire `Stock` folder to your XAMPP htdocs directory:
```
C:\xampp\htdocs\Stock       (Windows)
/opt/lampp/htdocs/Stock     (Linux)
```

---

## Step 2 — Create Database in phpMyAdmin
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **New** → name it `ashu_spare_part` → click **Create**
3. Select the `ashu_spare_part` database
4. Click **Import** tab → choose `database/ashu_spare_part.sql` → click **Go**

This creates all tables and inserts sample data including:
- 2 vehicle types (Two Wheeler, Three Wheeler)
- 7 Bajaj vehicle models
- 10 spare parts categories
- 10 sample spare parts
- 3 suppliers, 3 customers
- Admin user

---

## Step 3 — Configure Environment
Edit the `.env` file in the project root:
```
APP_NAME="Ashu Spare Part"
APP_URL=http://localhost/Stock/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ashu_spare_part
DB_USERNAME=root
DB_PASSWORD=          ← leave blank for default XAMPP
```

---

## Step 4 — Install Dependencies (one time)
Open terminal in the Stock folder and run:
```bash
composer install
php artisan key:generate
php artisan storage:link
```

---

## Step 5 — Fix Apache mod_rewrite (Windows XAMPP)
In `C:\xampp\apache\conf\httpd.conf`, find and change:
```
AllowOverride None  →  AllowOverride All
```
Then restart Apache.
 
---

## Step 6 — Access the System
Open: `http://localhost/Stock/public`

**Default Login:**
| Field    | Value                   |
|----------|-------------------------|
| Email    | admin@ashusparepart.et  |
| Password | password                |

---

## Folder Structure
```
Stock/
├── app/
│   ├── Http/Controllers/   ← All controllers (Catalog, Inventory, Sales, etc.)
│   ├── Models/             ← Eloquent models
│   ├── Services/           ← StockService (inventory logic)
│   └── Providers/          ← ViewServiceProvider
├── database/
│   ├── migrations/         ← All table migrations
│   └── ashu_spare_part.sql ← Ready-to-import SQL (use this with phpMyAdmin)
├── resources/views/        ← All Blade templates
│   ├── layouts/            ← Master layout + auth layout
│   ├── dashboard/          ← Dashboard with charts
│   ├── catalog/            ← Vehicle Types, Models, Parts, Units
│   ├── inventory/          ← Stock In, Adjustments, Current Stock, History
│   ├── sales/              ← New Sale, History, Returns, Customers
│   ├── purchases/          ← Suppliers, New Purchase, History
│   ├── reports/            ← 7 report pages
│   └── settings/           ← Company, Users, Roles
├── public/
│   ├── css/app.css         ← Custom stylesheet
│   └── js/app.js           ← Custom JavaScript
└── routes/web.php          ← All application routes
```

---

## Alternatively — Use Artisan Migrations
Instead of importing the SQL file, you can run:
```bash
php artisan migrate --seed
```
(Requires MySQL to be running and .env configured)
