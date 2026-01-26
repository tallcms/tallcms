---
title: "Installation Guide"
slug: "installation"
audience: "all"
category: "getting-started"
order: 10
time: 10
---

# Installation Guide

TallCMS can be installed in two ways: as a **standalone application** (full CMS) or as a **Filament plugin** (add to existing app).

## System Requirements

| Requirement | Version |
|-------------|---------|
| **PHP** | 8.2 or higher |
| **Laravel** | 11.0 or 12.0 |
| **Filament** | 4.0 |
| **Database** | MySQL 8.0+, MariaDB 10.3+, or SQLite |
| **Web Server** | Apache 2.4+ or Nginx 1.18+ |
| **Node.js** | 18.0+ (for asset compilation) |

### Required PHP Extensions

- OpenSSL
- PDO (with MySQL/SQLite driver)
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo
- GD or Imagick

---

## Standalone Installation

The standalone installation gives you a complete CMS with themes, plugins, web installer, and auto-updates.

### Method 1: Composer Create-Project (Recommended)

```bash
# Create new project
composer create-project tallcms/tallcms my-site

# Navigate to project
cd my-site

# Install frontend dependencies and build assets
npm install && npm run build

# Start development server
php artisan serve
```

Visit `http://localhost:8000/install` to complete the web installer.

### Method 2: Manual Download

1. Download the latest release from [tallcms.com](https://tallcms.com) or [GitHub Releases](https://github.com/tallcms/tallcms/releases)
2. Extract the archive to your web server directory
3. Set your web server's document root to the `public/` directory
4. Visit your domain in a browser
5. Follow the setup wizard

### Method 3: Git Clone (For Contributors)

```bash
# Clone the repository
git clone https://github.com/tallcms/tallcms.git
cd tallcms

# Install PHP dependencies
composer install

# Install frontend dependencies and build assets
npm install && npm run build

# Start development server with hot reload
composer dev
```

Visit `http://localhost:8000/install` to complete setup.

### Web Installer

The web installer guides you through:

1. **System Check** - Verifies PHP version, extensions, and permissions
2. **Database Setup** - Configure MySQL, MariaDB, or SQLite connection
3. **Admin Account** - Create your administrator user
4. **Site Settings** - Set site name, contact email, timezone
5. **Mail Configuration** - SMTP, Amazon SES, or PHP Mail
6. **Cloud Storage** (Optional) - S3-compatible storage setup

### Manual Configuration (Alternative)

If you prefer command-line setup instead of the web installer:

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your .env file with database credentials
# Then run migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link

# Create admin user interactively
php artisan make:user
```

### Post-Installation

After installation, you can:

1. Access the admin panel at `/admin`
2. Create pages and posts in **Content**
3. Configure site settings in **Settings**
4. Customize your theme in **Appearance > Themes**

---

## Plugin Installation

Add CMS features to your existing Laravel/Filament application.

> **Note:** TallCMS v2.x requires Filament 4.x (not Filament 5) because filament-shield doesn't yet have a Filament 5 compatible release.

### Step 1: Install the Package

```bash
composer require tallcms/cms
```

This will install TallCMS and Filament 4.x as dependencies.

### Step 2: Set Up Filament Panel

If you don't have a Filament panel yet:

```bash
php artisan filament:install --panels
```

### Step 3: Configure User Model

Your `User` model must use the `HasRoles` trait from Spatie Permission:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    // ...
}
```

### Step 4: Register the Plugin

Add `TallCmsPlugin` to your panel provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`):

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use TallCms\Cms\TallCmsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // ... other configuration
            ->plugin(TallCmsPlugin::make());
    }
}
```

### Step 5: Run the Installer

```bash
php artisan tallcms:install
```

This command will:
- Check prerequisites (HasRoles trait, panel provider, etc.)
- Publish and run migrations
- Set up roles and permissions
- Create your admin user interactively

### Selective Features

Disable components you don't need:

```php
->plugin(
    TallCmsPlugin::make()
        ->withoutPosts()           // Disable blog posts
        ->withoutCategories()      // Disable categories
        ->withoutContactSubmissions()  // Disable contact form submissions
)
```

---

## Frontend Routes (Plugin Mode)

Frontend routes are **disabled by default** in plugin mode to avoid conflicts with your existing routes.

### Enable CMS Routes

Add to your `.env` file:

```env
TALLCMS_ROUTES_ENABLED=true
```

This registers:
- `/` - Homepage (if a page is marked as homepage)
- `/{slug}` - CMS pages by slug

Routes automatically exclude common paths: `/admin`, `/api`, `/livewire`, `/storage`, etc.

### Homepage Conflict

When `TALLCMS_ROUTES_ENABLED=true` without a prefix, TallCMS registers the `/` route. Laravel loads your app's `routes/web.php` after package routes, so you must either:

**Option A:** Remove the default `/` route from `routes/web.php`:

```php
// Remove or comment out:
// Route::get('/', function () {
//     return view('welcome');
// });
```

**Option B:** Use a route prefix:

```env
TALLCMS_ROUTES_PREFIX=cms
```

This changes routes to `/cms` (homepage) and `/cms/{slug}` (pages).

### Configure Homepage

In the admin panel, edit a CMS page and check "Set as Homepage" to serve it at the root URL.

### Publish Assets

For frontend styling, publish the TallCMS assets:

```bash
php artisan vendor:publish --tag=tallcms-assets
```

### Alpine.js Requirement

TallCMS frontend pages require Alpine.js. Most Laravel apps include it via Livewire. If loading Alpine separately, ensure it loads before `tallcms.js` (components use `alpine:init`).

---

## Cloud Storage Setup

TallCMS supports S3-compatible cloud storage for media uploads.

### Supported Providers

- Amazon S3
- DigitalOcean Spaces
- Cloudflare R2
- Backblaze B2
- Wasabi
- MinIO (self-hosted)
- Any S3-compatible provider

### Configuration

Add to your `.env` file:

```env
# Storage credentials
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name

# Enable S3 storage
FILESYSTEM_DISK=s3

# For non-AWS providers, add endpoint:
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
```

### Provider Examples

**DigitalOcean Spaces:**
```env
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=my-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
FILESYSTEM_DISK=s3
```

**Cloudflare R2:**
```env
AWS_ACCESS_KEY_ID=your-r2-access-key
AWS_SECRET_ACCESS_KEY=your-r2-secret-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=my-bucket
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
FILESYSTEM_DISK=s3
```

**MinIO (Self-Hosted):**
```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

### Bucket Configuration

Ensure your bucket:
- Allows public read access for uploaded files
- Has CORS configured for direct uploads (if needed)

---

## Mail Configuration

### SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Amazon SES

```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Uses same AWS credentials as S3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
```

> **Note:** Amazon SES starts in sandbox mode. You must verify your domain/email and request production access.

---

## Web Server Configuration

### Apache

Ensure `mod_rewrite` is enabled and the `.htaccess` file in `public/` is being processed:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/tallcms/public

    <Directory /var/www/tallcms/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/tallcms/public;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### File Permissions

```bash
# Set ownership (adjust user/group as needed)
sudo chown -R www-data:www-data /var/www/tallcms

# Set directory permissions
sudo find /var/www/tallcms -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/tallcms -type f -exec chmod 644 {} \;

# Make storage and cache writable
sudo chmod -R 775 /var/www/tallcms/storage
sudo chmod -R 775 /var/www/tallcms/bootstrap/cache
```

---

## Upgrading

### Standalone Mode

Use the built-in update system in **Settings > System Updates**, or via CLI:

```bash
# Check for updates (dry run)
php artisan tallcms:update --dry-run

# Update to latest version
php artisan tallcms:update

# Update to specific version
php artisan tallcms:update --target=2.5.0
```

### Plugin Mode

Update via Composer:

```bash
composer update tallcms/cms
php artisan migrate
php artisan view:clear
```

---

## Common Pitfalls

**"Installation already complete"**
Delete `storage/installer.lock` from project root, or set `INSTALLER_ENABLED=true` in `.env`.

**"Database connection failed"**
Verify database credentials in installer form. Ensure database server is running and the database exists.

**"Permission denied" during installation**
Ensure `storage/` and `bootstrap/cache/` are writable. Run `chmod -R 775 storage bootstrap/cache`.

**"Cannot access admin panel"**
Complete the web installer first. Verify your user has an active role. Check `/admin` URL is correct.

**"403 Forbidden"**
Clear permission cache: `php artisan permission:cache-reset`. Verify user has appropriate role.

**"CMS resources not appearing"**
Ensure `TallCmsPlugin::make()` is registered in your panel provider. Run `php artisan migrate` to create the CMS tables. Clear config cache: `php artisan config:clear`.

---

## Next Steps

- [Create your first page](first-page)
- [Publish your first post](first-post)
- [Set up navigation menus](quick-menus)
