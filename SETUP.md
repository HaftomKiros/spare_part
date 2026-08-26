# Abush Spare Part System — Setup Guide

---

## A) LOCAL SETUP (XAMPP)

### Prerequisites
- XAMPP installed (PHP 8.1+, Apache, MySQL)
- Composer installed

### Step 1 — Copy Project
Copy the entire `Stock` folder to XAMPP htdocs:
```
C:\xampp\htdocs\Stock       (Windows)
/opt/lampp/htdocs/Stock     (Linux)
```

### Step 2 — Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **New** → name it `Abush_spare_part` → click **Create**
3. Click **Import** → choose `database/ashu_spare_part.sql` → click **Go**

### Step 3 — Configure .env
```env
APP_NAME="Abush Spare Part"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/Stock/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Abush_spare_part
DB_USERNAME=root
DB_PASSWORD=
```

### Step 4 — Install Dependencies
```bash
cd Stock
composer install
php artisan key:generate
php artisan storage:link
```

### Step 5 — Fix Apache mod_rewrite
In `C:\xampp\apache\conf\httpd.conf`:
```
AllowOverride None  →  AllowOverride All
```
Restart Apache.

### Step 6 — Access
Open: `http://localhost/Stock/public`

**Default Login:**
| Field    | Value                    |
|----------|--------------------------|
| Email    | admin@Abushsparepart.et  |
| Password | password                 |

---

## B) CPANEL SHARED HOSTING DEPLOYMENT

### Folder Structure on Server

```
/home/yourusername/          ← cPanel home
    Stock/                   ← Upload entire project here (NOT inside public_html)
        app/
        bootstrap/
        config/
        database/
        resources/
        routes/
        storage/
        vendor/
        public/
        .env
        ...
    public_html/             ← Web root (what visitors see)
        index.php            ← Upload public_html_index.php here (renamed)
        .htaccess            ← Upload public_html_htaccess.txt here (renamed)
        css/                 ← Copy from Stock/public/css/
        js/                  ← Copy from Stock/public/js/
        favicon.ico          ← Copy from Stock/public/ if exists
```

---

### Step 1 — Upload Project Files

Using **cPanel File Manager** or **FTP (FileZilla)**:

1. Upload the entire `Stock` folder to `/home/yourusername/Stock/`
   - Do NOT upload inside `public_html`
   - The `Stock` folder sits next to `public_html`

2. Upload `public_html_index.php` to `public_html/index.php`
   - Rename it to `index.php`

3. Upload `public_html_htaccess.txt` to `public_html/.htaccess`
   - Rename it to `.htaccess`

4. Copy these from `Stock/public/` into `public_html/`:
   - `css/` folder
   - `js/` folder
   - `mix-manifest.json` (if exists)

---

### Step 2 — Create Database in cPanel

1. Go to cPanel → **MySQL Databases**
2. Create a new database (e.g. `yourusername_spare`)
3. Create a new user (e.g. `yourusername_admin`) with a strong password
4. Add user to database with **ALL PRIVILEGES**
5. Go to **phpMyAdmin** → select your database → **Import**
6. Import `database/ashu_spare_part.sql`

---

### Step 3 — Configure .env on Server

Edit `/home/yourusername/Stock/.env`:

```env
APP_NAME="Abush Spare Part"
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yourusername_spare
DB_USERNAME=yourusername_admin
DB_PASSWORD=your_strong_password

SESSION_DRIVER=file
CACHE_DRIVER=file
```

---

### Step 4 — Set Folder Permissions

In cPanel File Manager or via SSH:
```
Stock/storage/           → 775
Stock/storage/app/       → 775
Stock/storage/framework/ → 775
Stock/storage/logs/      → 775
Stock/bootstrap/cache/   → 775
```

In cPanel File Manager:
- Right-click each folder → **Change Permissions** → check all boxes for Owner and Group

---

### Step 5 — Run Artisan Commands

If your host has **SSH access** (Terminal in cPanel):
```bash
cd ~/Stock
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

If NO SSH access (most shared hosts):
- Generate key locally: `php artisan key:generate --show`
- Copy the output and paste it into `.env` as `APP_KEY=`
- Skip caching commands (app works without them, just slightly slower)
- For `storage:link` — upload the file `storage_link.php` (see below) to `public_html/`, visit it once in your browser, then **delete it immediately**

---

### Step 6 — Check PHP Version

In cPanel → **MultiPHP Manager** or **PHP Selector**:
- Set PHP version to **8.1 or higher**
- Required extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`

---

### Step 7 — Test

Visit: `https://yourdomain.com`

If you see a blank page or error:
1. Temporarily set `APP_DEBUG=true` in `.env` to see the error
2. Check `Stock/storage/logs/laravel.log` for errors
3. Set back to `APP_DEBUG=false` after fixing

---

### Common cPanel Issues & Fixes

| Problem | Fix |
|---|---|
| 500 Server Error | Check `storage/logs/laravel.log` |
| Blank white page | Set `APP_DEBUG=true` temporarily |
| CSS/JS not loading | Make sure `css/` and `js/` are in `public_html/` |
| Database error | Check DB credentials in `.env` |
| Permission denied | Set `storage/` and `bootstrap/cache/` to 775 |
| Class not found | Run `composer install` or upload `vendor/` folder |
| Session error | Set `SESSION_DRIVER=file` in `.env` |

---

### Files to NEVER upload to server
```
.git/
node_modules/
.env.example
```

### Files to upload but keep private
```
.env          ← must be outside public_html (it is, inside Stock/)
vendor/       ← upload this (required, ~50MB)
storage/      ← upload this (keep contents, clear logs)
```

---

## Default Login (both local and production)

| Field    | Value                    |
|----------|--------------------------|
| Email    | admin@Abushsparepart.et  |
| Password | password                 |

**Change the password immediately after first login on production!**
