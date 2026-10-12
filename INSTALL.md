# BaseRef Installation and Configuration Guide

This guide provides comprehensive, step-by-step instructions for deploying and configuring **BaseRef** across Windows, macOS, Linux, and Docker environments.

---

## 1. System Requirements

### 1.1 Software Versions
* **PHP**: `>= 8.0` (8.1, 8.2, or 8.3 recommended).
* **Database**: **MySQL** `>= 8.0` or **MariaDB** `>= 10.4` (tested on MariaDB `10.11`).
* **Web Server**: **Apache** `2.4+` (with `mod_rewrite` enabled) or **Nginx** `1.18+` with PHP-FPM.

### 1.2 Required PHP Extensions
* `pdo_mysql` — Database connection via PDO.
* `mbstring` — Multi-byte string manipulation for UTF-8 bibliographic data.
* `curl` — HTTP requests to Google OAuth, OpenAlex, and AI APIs.
* `json` — Serialization and API payload processing.
* `xml` / `SimpleXML` — PubMed XML citation parsing.
* `openssl` — Secure token generation and SSL validation.
* `iconv` — Encoding normalization.
* `session` — Secure user authentication state.

Check your installed PHP version and modules with:
```bash
php -v
php -m | grep -Ei 'pdo_mysql|mbstring|curl|json|xml|openssl|iconv'
```

### 1.3 Recommended PHP `php.ini` Directives
Bibliographic export files from Web of Science or Scopus can be large. Ensure your `php.ini` includes:
```ini
upload_max_filesize = 64M
post_max_size = 80M
memory_limit = 512M
max_execution_time = 300
allow_url_fopen = On
```

---

## 2. Fast Track: Docker Deployment (Recommended)

BaseRef includes a production-ready `Dockerfile` and `docker-compose.yml` with MariaDB 10.11 and PHP 8.2 Apache.

### Step 1: Clone the repository
```bash
git clone https://github.com/rafaelgross/baseref.git
cd baseref
```

### Step 2: Configure Environment
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Open `.env` and fill in your Google OAuth credentials (see [Section 6](#6-google-oauth-20-configuration) for setup instructions):
```env
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/callback.php
```

### Step 3: Start Containers
```bash
docker compose up -d
```
* MariaDB will initialize and automatically import `sql/install.sql` on first startup.
* The web application will be accessible at: **`http://localhost:8080/`**.
* To view application logs: `docker compose logs -f web`.
* To stop: `docker compose down`.

> **Security Note on Default Passwords and Exposed Ports:**  
> The bundled `docker-compose.yml` and `.env.example` include placeholder credentials (`DB_ROOT_PASSWORD=baseref_root_secret`, `DB_PASS=baseref_secret_password`) and expose the database port on `33066` for convenient local testing. **In any non-local or internet-exposed environment, you must change these passwords in `.env` and remove or bind the database port to `127.0.0.1:33066`** to prevent unauthorized access.

---

## 3. Manual Installation by Platform

> **Important Host Hierarchy:** BaseRef must be served from the **document root** of a host or virtual host (e.g., `http://localhost/` or `https://baseref.yourdomain.org/`), not a subdirectory (like `http://localhost/baseref/`), because authentication and API routes use root-relative paths.

### 3.1 Windows with XAMPP
1. Download and install [XAMPP](https://www.apachefriends.org/) (PHP 8.x version).
2. Open the **XAMPP Control Panel** and stop Apache if running.
3. Place the `baseref` repository folder into `C:\xampp\htdocs\baseref`.
4. Point Apache's Document Root directly to BaseRef:
   * Open `C:\xampp\apache\conf\httpd.conf`.
   * Change:
     ```apache
     DocumentRoot "C:/xampp/htdocs/baseref"
     <Directory "C:/xampp/htdocs/baseref">
     ```
5. Enable required extensions in `C:\xampp\php\php.ini`:
   * Ensure `extension=curl`, `extension=mbstring`, `extension=openssl`, and `extension=pdo_mysql` are uncommented (no leading semicolon `;`).
   * Set `upload_max_filesize = 64M` and `post_max_size = 80M`.
6. Start **Apache** and **MySQL** in the XAMPP Control Panel.
7. Access the app at `http://localhost/` (phpMyAdmin remains accessible at `http://localhost/phpmyadmin/`).

### 3.2 macOS with MAMP
1. Install [MAMP](https://www.mamp.info/) and ensure PHP 8.x is selected under **MAMP → Preferences → PHP**.
2. Clone or copy `baseref` into `/Applications/MAMP/htdocs/baseref`.
3. In **MAMP → Preferences → Server**, set **Document Root** to `/Applications/MAMP/htdocs/baseref`.
4. Default MAMP ports:
   * Apache: `8888` (Site URL: `http://localhost:8888/`)
   * MySQL: `8889` (Set `DB_PORT = '8889'` in `config/database.php`).
5. Start the MAMP servers.

### 3.3 Linux (Ubuntu / Debian) with Apache
1. Install Apache, MariaDB, and PHP packages:
   ```bash
   sudo apt update
   sudo apt install -y apache2 mariadb-server php libapache2-mod-php \
                       php-mysql php-mbstring php-curl php-xml php-json
   ```
2. Deploy code to `/var/www/baseref`:
   ```bash
   sudo git clone https://github.com/rafaelgross/baseref.git /var/www/baseref
   sudo chown -R www-data:www-data /var/www/baseref
   sudo chmod -R 755 /var/www/baseref
   ```
3. Create the VirtualHost file `/etc/apache2/sites-available/baseref.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName baseref.example.org
       DocumentRoot /var/www/baseref
       DirectoryIndex index.php

       <Directory /var/www/baseref>
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/baseref_error.log
       CustomLog ${APACHE_LOG_DIR}/baseref_access.log combined
   </VirtualHost>
   ```
4. Enable `mod_rewrite`, activate the site, and reload Apache:
   ```bash
   sudo a2enmod rewrite
   sudo a2ensite baseref.conf
   sudo a2dissite 000-default.conf
   sudo systemctl reload apache2
   ```

### 3.4 Linux with Nginx and PHP-FPM
1. Install Nginx, MariaDB, and PHP-FPM:
   ```bash
   sudo apt update
   sudo apt install -y nginx mariadb-server php-fpm php-mysql php-mbstring php-curl php-xml
   ```
2. Create `/etc/nginx/sites-available/baseref`:
   ```nginx
   server {
       listen 80;
       server_name baseref.example.org;
       root /var/www/baseref;
       index index.php;
       client_max_body_size 80M;

       # Block web access to protected directories and configuration files
       location ~ ^/(config|sql|lib)(/|$) { deny all; }
       location ~* \.(sql|md)$|\.example\.php$ { deny all; }
       location ~ /\.(git|env|ht) { deny all; }

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/run/php/php8.2-fpm.sock; # Adjust to your PHP version
           fastcgi_read_timeout 300;
       }
   }
   ```
3. Enable and test configuration:
   ```bash
   sudo ln -s /etc/nginx/sites-available/baseref /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

---

## 4. Database Setup & Initialization

### 4.1 Create Database and User
Log in to MySQL/MariaDB as root:
```bash
sudo mysql -u root -p
```
Execute the following statements:
```sql
CREATE DATABASE prisma_slr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'baseref'@'localhost' IDENTIFIED BY 'YourStrongPasswordHere';
GRANT ALL PRIVILEGES ON prisma_slr.* TO 'baseref'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4.2 Import the Unified Schema
Import the single unified installation script `sql/install.sql`:
```bash
mysql -u baseref -p prisma_slr < sql/install.sql
```
*(On Windows XAMPP: `C:\xampp\mysql\bin\mysql.exe -u baseref -p prisma_slr < sql\install.sql`)*

---

## 5. Configuration Files (`config/`)

BaseRef configurations can be provided either via **environment variables** (ideal for Docker / 12-factor apps) or via local PHP configuration files copied from templates.

### 5.1 `config/database.php`
Copy template:
```bash
cp config/database.example.php config/database.php
```
Variables defined:
| Constant / Variable | Environment Variable Fallback | Default Value | Description |
|---|---|---|---|
| `DB_HOST` | `getenv('DB_HOST')` | `'127.0.0.1'` | Hostname or IP of database server. Use `db` in Docker. |
| `DB_PORT` | `getenv('DB_PORT')` | `'3306'` | Database port (`3306` standard; `8889` for MAMP). |
| `DB_NAME` | `getenv('DB_NAME')` | `'prisma_slr'` | Name of database schema. |
| `DB_USER` | `getenv('DB_USER')` | `'your_db_user'` | Dedicated MySQL database user. |
| `DB_PASS` | `getenv('DB_PASS')` | `'your_db_password'` | Password for database user. |
| `DB_CHARSET` | `getenv('DB_CHARSET')` | `'utf8mb4'` | Unicode character set. |

### 5.2 `config/auth.php`
Copy template:
```bash
cp config/auth.example.php config/auth.php
```
Variables defined:
| Constant / Variable | Environment Variable Fallback | Default Value | Description |
|---|---|---|---|
| `GOOGLE_CLIENT_ID` | `getenv('GOOGLE_CLIENT_ID')` | `''` | OAuth 2.0 Web Client ID from Google Cloud Console. |
| `GOOGLE_CLIENT_SECRET` | `getenv('GOOGLE_CLIENT_SECRET')` | `''` | OAuth 2.0 Web Client Secret from Google Cloud Console. |
| `GOOGLE_REDIRECT_URI` | `getenv('GOOGLE_REDIRECT_URI')` | `'http://localhost/auth/callback.php'` | Full callback URI. Must match Google Cloud settings exactly. |
| `GOOGLE_SCOPES` | `getenv('GOOGLE_SCOPES')` | `'openid email profile'` | Requested OAuth permissions. |
| `SESSION_LIFETIME` | `getenv('SESSION_LIFETIME')` | `28800` (8 hours) | Cookie lifetime in seconds. |

### 5.3 `config/ai.php` and `config/ai.local.php` (Optional)
To enable AI screening assistance, create `config/ai.local.php`:
```php
<?php
define('AI_PROVIDER', 'groq');        // 'groq', 'gemini', or 'anthropic'
define('AI_API_KEY',  'gsk_...');     // Your API Key
define('AI_MODEL',    'openai/gpt-oss-20b'); // Model override (optional)
define('AI_DAILY_LIMIT', 100);        // Daily suggestions per installation (0 = unlimited)
```

---

## 6. Google OAuth 2.0 Configuration

BaseRef uses Google Sign-In for zero-maintenance user management.

1. **Open Google Cloud Console**: Navigate to [https://console.cloud.google.com/](https://console.cloud.google.com/) and create or select a project (e.g., `BaseRef`).
2. **Configure OAuth Consent Screen**:
   * Go to **APIs & Services → OAuth consent screen**.
   * User Type: Select **External**.
   * App name: `BaseRef`.
   * Developer and user support email: Enter your email address.
   * Scopes: Add `.../auth/userinfo.email`, `.../auth/userinfo.profile`, and `openid`.
   * Test Users: While the publishing status is "Testing", add the Google email addresses allowed to sign in. Click **Publish App** when ready for public use.
3. **Create Credentials**:
   * Go to **APIs & Services → Credentials → Create Credentials → OAuth Client ID**.
   * Application type: **Web application**.
   * **Authorized JavaScript origins**:
     * For Local XAMPP / Apache: `http://localhost`
     * For Local MAMP: `http://localhost:8888`
     * For Docker: `http://localhost:8080`
     * For Production: `https://baseref.yourdomain.org`
   * **Authorized redirect URIs**:
     * For Local XAMPP / Apache: `http://localhost/auth/callback.php`
     * For Local MAMP: `http://localhost:8888/auth/callback.php`
     * For Docker: `http://localhost:8080/auth/callback.php`
     * For Production: `https://baseref.yourdomain.org/auth/callback.php`
4. **Copy Credentials**:
   * Copy the generated **Client ID** and **Client Secret** into your `config/auth.php` or `.env` file.

---

## 7. Optional External Services

| Service | Setup Location | Description |
|---|---|---|
| **OpenAlex API** | `api/enrich.php` (`OPENALEX_MAILTO`) | Free DOI metadata enrichment. Enter your email address to use the Polite Pool (faster response times). |
| **MyMemory** | `api/translate.php` (`TRANSLATE_CONTACT_EMAIL`) | Free translation proxy for abstracts and exclusion reasons. Provide your email for higher daily query limits. |
| **Groq / Gemini / Anthropic** | `config/ai.local.php` or `.env` | AI-suggested inclusion/exclusion classifications for title/abstract screening. |

---

## 8. Common Troubleshooting

### 8.1 "Error 400: redirect_uri_mismatch"
* The URL configured in `GOOGLE_REDIRECT_URI` does not match character-for-character the URI registered in the Google Cloud Console. Check for differences in protocol (`http` vs `https`), port numbers (`:8080`), or trailing slashes.

### 8.2 MySQL / MariaDB Port Conflicts
* If XAMPP or MAMP cannot start MySQL, another service might be occupying port 3306.
* Verify port bindings with `netstat -ano | grep 3306` (Windows) or `lsof -i :3306` (Linux/macOS).
* For MAMP, ensure `DB_PORT` in `config/database.php` is explicitly set to `8889`.

### 8.3 403 Forbidden Errors
* The `.htaccess` file intentionally blocks direct browser access to `/config`, `/sql`, `/lib`, and `*.md` files. This is expected security behavior.
* If the root application returns 403, check file ownership: `sudo chown -R www-data:www-data /var/www/baseref`.

### 8.4 Missing PHP Extensions
* If you see blank screens or PDO exceptions:
  ```bash
  sudo apt install php-mysql php-mbstring php-curl php-xml
  sudo systemctl restart apache2 # or php-fpm
  ```
