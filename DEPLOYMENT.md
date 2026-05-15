# Deployment Guide — AndBlog

Panduan deployment lengkap untuk 3 platform berbeda. Pilih sesuai infrastruktur yang Anda miliki.

| Platform | Cocok untuk | Effort awal | Maintenance |
|----------|-------------|-------------|-------------|
| [**VPS Ubuntu**](#1-vps-ubuntu-2204--2404) | Full kontrol, scale-up mudah, traffic stabil | 2-3 jam | Manual update OS/PHP/PG |
| [**Docker / docker-compose**](#2-docker--docker-compose) | Konsistensi env, multi-server, CI/CD | 1-2 jam | Image rebuild saat update |
| [**aaPanel di Armbian**](#3-aapanel-di-linux-armbian) | Single board (Orange Pi, Rock Pi), GUI panel | 1-2 jam | Auto via aaPanel UI |

---

## Pre-Flight Checklist (semua platform)

Sebelum mulai, siapkan:

- [ ] **Domain name** dengan DNS yang bisa Anda kontrol (Cloudflare/Namecheap/dll)
- [ ] **Server / VPS / SBC** dengan minimal: 1 GB RAM, 10 GB disk, akses SSH/root
- [ ] **PHP ≥ 8.4** tersedia di target OS — project butuh 8.4+ karena dependency lock (`symfony/console v8`, `symfony/var-dumper v8`). PHP 8.3 akan gagal saat `composer install`.
- [ ] **Resend account** dengan domain verified — lihat [README.md](README.md#mail-provider-resend)
- [ ] **Sentry account** (gratis) dengan DSN siap — lihat [README.md](README.md#error-monitoring-sentry)
- [ ] **Gemini API key baru** — rotate dari Google AI Studio (key di local dev sudah bocor di chat history, jangan reuse)
- [ ] **Source code di GitHub** (atau bisa upload manual via SCP)

Generate `APP_KEY` baru sebelum deploy (jangan reuse dari local):

```bash
php artisan key:generate --show
# Output: base64:xxxxxxxxxxxxxxxxxxx=
# Copy nilai ini, paste ke .env production nanti
```

---

# 1. VPS Ubuntu 22.04 / 24.04

Asumsi: Ubuntu 22.04 LTS atau 24.04 LTS, akses root via SSH, domain sudah pointing ke IP server.

## 1.1 Initial Server Hardening

```bash
# Login sebagai root, update sistem
apt update && apt upgrade -y

# Buat user non-root untuk deployment
adduser deploy
usermod -aG sudo deploy

# Setup SSH key untuk user deploy (jalankan dari LOCAL Anda)
ssh-copy-id deploy@your-server-ip

# Disable root SSH + password login (di server)
nano /etc/ssh/sshd_config
# Set:
#   PermitRootLogin no
#   PasswordAuthentication no
systemctl restart ssh

# Firewall
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable

# Fail2ban untuk anti SSH brute-force
apt install -y fail2ban
systemctl enable --now fail2ban
```

Mulai sekarang, login pakai user `deploy`:

```bash
ssh deploy@your-server-ip
```

## 1.2 Install PHP 8.4 + Extensions

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y \
    php8.4 php8.4-fpm php8.4-cli \
    php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-zip \
    php8.4-gd php8.4-curl php8.4-intl php8.4-bcmath \
    php8.4-fileinfo php8.4-opcache

# Tweak php.ini untuk production
sudo nano /etc/php/8.4/fpm/php.ini
# Cari & set:
#   memory_limit = 256M
#   upload_max_filesize = 10M
#   post_max_size = 12M
#   max_execution_time = 60
#   opcache.enable = 1
#   opcache.memory_consumption = 128
#   opcache.max_accelerated_files = 10000
#   opcache.validate_timestamps = 0   ← penting di production

sudo systemctl restart php8.4-fpm
```

> **Catatan PHP version**: project ini butuh **PHP ≥ 8.4** karena beberapa dependency lock (mis. `symfony/console v8`, `symfony/var-dumper v8`) require 8.4+. PHP 8.3 akan gagal saat `composer install` dengan error platform requirement.

## 1.3 Install PostgreSQL 16

```bash
sudo apt install -y postgresql-16 postgresql-contrib-16

# Buat database + user
sudo -u postgres psql <<EOF
CREATE DATABASE blog_laravel;
CREATE USER andblog WITH PASSWORD 'STRONG-PASSWORD-HERE-CHANGE-ME';
GRANT ALL PRIVILEGES ON DATABASE blog_laravel TO andblog;
ALTER DATABASE blog_laravel OWNER TO andblog;
\c blog_laravel
GRANT ALL ON SCHEMA public TO andblog;
EOF

# Test koneksi
psql -U andblog -d blog_laravel -h 127.0.0.1
# Masukkan password, harus berhasil. Ketik \q untuk keluar.
```

## 1.4 Install Composer + Node.js

```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify
php --version       # 8.3.x
composer --version  # 2.x
node --version      # v20.x
```

## 1.5 Install Nginx + Certbot

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
```

## 1.6 Deploy Source Code

```bash
# Buat folder webroot
sudo mkdir -p /var/www
sudo chown deploy:deploy /var/www

# Clone dari GitHub (replace dengan repo Anda)
cd /var/www
git clone https://github.com/your-username/project-blog.git andblog
cd andblog

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# Setup environment
cp .env.example .env
nano .env
```

Isi `.env` dengan nilai production:

```env
APP_NAME="AndBlog"
APP_ENV=production
APP_KEY=base64:PASTE-NEW-KEY-HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blog_laravel
DB_USERNAME=andblog
DB_PASSWORD=STRONG-PASSWORD-HERE-CHANGE-ME

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public

MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

GEMINI_API_KEY=NEW-KEY-FROM-GOOGLE-AI-STUDIO
GEMINI_MODEL=gemini-2.5-flash

SENTRY_LARAVEL_DSN=https://...@...ingest.sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.1
```

```bash
# Generate key bila belum
php artisan key:generate

# Migrate + seed (CATATAN: pakai ProductionSeeder, BUKAN db:seed default)
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force

# Buat admin user via tinker
php artisan tinker
>>> App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'admin@yourdomain.com',
...     'password' => 'STRONG-ADMIN-PASSWORD',
...     'role' => 'admin',
...     'email_verified_at' => now(),
... ]);
>>> exit

# Symlink storage
php artisan storage:link

# Permission
sudo chown -R deploy:www-data /var/www/andblog
sudo chmod -R 755 /var/www/andblog
sudo chmod -R 775 /var/www/andblog/storage /var/www/andblog/bootstrap/cache

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 1.7 Konfigurasi Nginx

```bash
sudo nano /etc/nginx/sites-available/andblog
```

Isi:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/andblog/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static asset caching (Vite hashed filenames - aman cache lama)
    location ~* \.(?:css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/andblog /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # hapus default config
sudo nginx -t                              # test config
sudo systemctl reload nginx

# Setup SSL via Let's Encrypt
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
# Ikuti prompt, pilih "redirect" agar auto upgrade HTTP → HTTPS

# Test renewal otomatis
sudo certbot renew --dry-run
```

## 1.8 Queue Worker via Systemd

```bash
sudo nano /etc/systemd/system/andblog-queue.service
```

Isi:

```ini
[Unit]
Description=AndBlog Queue Worker
After=network.target postgresql.service

[Service]
User=deploy
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/andblog/artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 --max-time=3600
StandardOutput=append:/var/log/andblog-queue.log
StandardError=append:/var/log/andblog-queue.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo touch /var/log/andblog-queue.log
sudo chown deploy:deploy /var/log/andblog-queue.log

sudo systemctl daemon-reload
sudo systemctl enable --now andblog-queue
sudo systemctl status andblog-queue   # harus "active (running)"
```

## 1.9 Scheduler Cron (jika nanti pakai)

Saat ini AndBlog tidak punya scheduled task, tapi setup biar siap:

```bash
crontab -u deploy -e
```

Tambahkan:

```cron
* * * * * cd /var/www/andblog && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 1.10 Database Backup

```bash
sudo nano /home/deploy/backup-andblog.sh
```

Isi:

```bash
#!/bin/bash
set -e

BACKUP_DIR="/var/backups/andblog"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
PGPASSWORD='STRONG-PASSWORD-HERE-CHANGE-ME' \
  pg_dump -U andblog -h 127.0.0.1 blog_laravel \
  | gzip > "$BACKUP_DIR/blog_laravel_$TIMESTAMP.sql.gz"

# Retain 14 hari
find "$BACKUP_DIR" -name "blog_laravel_*.sql.gz" -mtime +14 -delete

# (Optional) upload ke S3/R2/B2 — pakai rclone
# rclone copy "$BACKUP_DIR/blog_laravel_$TIMESTAMP.sql.gz" remote:andblog-backups/
```

```bash
sudo mkdir -p /var/backups/andblog
sudo chown deploy:deploy /var/backups/andblog
chmod +x /home/deploy/backup-andblog.sh

# Schedule daily 02:00
crontab -u deploy -e
```

Tambah:

```cron
0 2 * * * /home/deploy/backup-andblog.sh
```

## 1.11 Smoke Test

```bash
# Health check endpoint
curl -I https://yourdomain.com/up
# Harus: HTTP/2 200

# Homepage
curl -I https://yourdomain.com
# Harus: HTTP/2 200, plus headers X-Frame-Options + Strict-Transport-Security

# Test queue worker bisa eksekusi job (kirim email verifikasi)
# Buka https://yourdomain.com/register, daftar dengan email Anda
# Cek inbox dalam 1-2 menit
```

## 1.12 Update Deployment

Untuk deploy versi baru (post-launch):

```bash
cd /var/www/andblog
php artisan down --message="Maintenance singkat"

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:cache route:cache view:cache event:cache
sudo systemctl restart andblog-queue php8.4-fpm

php artisan up
```

---

# 2. Docker / docker-compose

Asumsi: Anda punya server dengan Docker + docker-compose terinstall, mau deployment portable yang bisa di-replicate antar environment.

## 2.1 Install Docker (sekali saja)

```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
newgrp docker

# Verify
docker --version
docker compose version
```

## 2.2 File Structure

Buat 4 file di root proyek (sebelum di-commit ke repo):

```
project-blog/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
└── docker/
    ├── nginx.conf
    ├── php-fpm.conf
    ├── supervisord.conf
    └── entrypoint.sh
```

### `Dockerfile`

```dockerfile
FROM php:8.4-fpm-alpine AS base

# System deps
RUN apk add --no-cache \
    bash \
    git \
    curl \
    nginx \
    supervisor \
    postgresql-client \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    nodejs \
    npm

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
       pdo_pgsql pgsql gd intl zip bcmath opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first untuk layer cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy source + finalize composer
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Build assets
RUN npm ci && npm run build && rm -rf node_modules

# Nginx + supervisor configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### `.dockerignore`

```
.git
.github
node_modules
vendor
storage/logs/*.log
storage/framework/cache/data/*
storage/framework/sessions/*
storage/framework/views/*
.env
.env.example
.env.testing
*.md
tests
.phpunit.result.cache
.idea
.vscode
```

### `docker/nginx.conf`

```nginx
worker_processes auto;
error_log /dev/stderr;
pid /run/nginx.pid;

events { worker_connections 1024; }

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    sendfile on;
    keepalive_timeout 65;
    client_max_body_size 12M;

    access_log /dev/stdout;

    server {
        listen 80 default_server;
        root /var/www/html/public;
        index index.php;
        charset utf-8;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-Content-Type-Options "nosniff";
        add_header Referrer-Policy "strict-origin-when-cross-origin";

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.(?!well-known).* { deny all; }

        location ~* \.(?:css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            access_log off;
        }
    }
}
```

### `docker/php-fpm.conf`

```ini
[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 1000

clear_env = no
catch_workers_output = yes
decorate_workers_output = no
```

### `docker/supervisord.conf`

```ini
[supervisord]
nodaemon=true
user=root
logfile=/dev/null
logfile_maxbytes=0

[program:php-fpm]
command=php-fpm --nodaemonize
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g 'daemon off;'
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:queue]
command=php /var/www/html/artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopwaitsecs=120
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

### `docker/entrypoint.sh`

```bash
#!/bin/bash
set -e

cd /var/www/html

# Wait for database (max 30s)
echo "Waiting for PostgreSQL..."
for i in {1..30}; do
    if pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" >/dev/null 2>&1; then
        echo "PostgreSQL ready"
        break
    fi
    sleep 1
done

# Migrate + cache
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Permission ulang (volume mount kadang reset)
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
```

### `docker-compose.yml`

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: andblog:latest
    restart: unless-stopped
    depends_on:
      postgres:
        condition: service_healthy
    environment:
      APP_NAME: AndBlog
      APP_ENV: production
      APP_KEY: ${APP_KEY}
      APP_DEBUG: "false"
      APP_URL: ${APP_URL}
      LOG_CHANNEL: stack
      LOG_STACK: daily
      LOG_LEVEL: warning
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      SESSION_DRIVER: database
      SESSION_SECURE_COOKIE: "true"
      CACHE_STORE: database
      QUEUE_CONNECTION: database
      FILESYSTEM_DISK: public
      MAIL_MAILER: resend
      RESEND_API_KEY: ${RESEND_API_KEY}
      MAIL_FROM_ADDRESS: ${MAIL_FROM_ADDRESS}
      MAIL_FROM_NAME: AndBlog
      GEMINI_API_KEY: ${GEMINI_API_KEY}
      GEMINI_MODEL: gemini-2.5-flash
      SENTRY_LARAVEL_DSN: ${SENTRY_LARAVEL_DSN}
    volumes:
      - storage_data:/var/www/html/storage
      - public_storage:/var/www/html/public/storage
    ports:
      - "80:80"
    networks: [andblog]

  postgres:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME} -d ${DB_DATABASE}"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks: [andblog]

  # Backup container - daily pg_dump
  backup:
    image: postgres:16-alpine
    restart: unless-stopped
    depends_on: [postgres]
    environment:
      PGPASSWORD: ${DB_PASSWORD}
    volumes:
      - ./backups:/backups
    entrypoint: >
      sh -c 'while true; do
        TS=$$(date +%Y%m%d-%H%M%S);
        pg_dump -h postgres -U ${DB_USERNAME} ${DB_DATABASE} | gzip > /backups/db_$$TS.sql.gz;
        find /backups -name "db_*.sql.gz" -mtime +14 -delete;
        sleep 86400;
      done'
    networks: [andblog]

volumes:
  postgres_data:
  storage_data:
  public_storage:

networks:
  andblog:
    driver: bridge
```

## 2.3 Deploy

```bash
# Clone repo
git clone https://github.com/your-username/project-blog.git andblog
cd andblog

# Buat .env untuk docker-compose (BUKAN Laravel .env, ini compose env)
nano .env
```

Isi `.env`:

```env
APP_KEY=base64:PASTE-NEW-KEY
APP_URL=https://yourdomain.com
DB_DATABASE=blog_laravel
DB_USERNAME=andblog
DB_PASSWORD=STRONG-PASSWORD-CHANGE-ME
RESEND_API_KEY=re_xxxxxx
MAIL_FROM_ADDRESS=noreply@yourdomain.com
GEMINI_API_KEY=your-gemini-key
SENTRY_LARAVEL_DSN=https://...sentry.io/...
```

Build + run:

```bash
docker compose build
docker compose up -d

# Tunggu ~30 detik untuk migration jalan
docker compose logs -f app

# Setelah container "app" idle (artinya migrate done), buat admin user
docker compose exec app php artisan tinker
>>> App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'admin@yourdomain.com',
...     'password' => 'STRONG-ADMIN-PASSWORD',
...     'role' => 'admin',
...     'email_verified_at' => now(),
... ]);
>>> exit

# Run ProductionSeeder (categories + tags + settings)
docker compose exec app php artisan db:seed --class=ProductionSeeder --force
```

## 2.4 SSL/HTTPS dengan Reverse Proxy

Container Docker hanya expose port 80. Untuk HTTPS, tambah reverse proxy:

**Opsi A — Caddy** (paling mudah, auto-HTTPS):

```bash
# Di host, install Caddy
sudo apt install -y caddy

sudo nano /etc/caddy/Caddyfile
```

Isi:

```caddyfile
yourdomain.com, www.yourdomain.com {
    reverse_proxy localhost:80
}
```

```bash
sudo systemctl reload caddy
# SSL otomatis via Let's Encrypt
```

**Opsi B — Cloudflare Tunnel** (zero exposed port):

Lihat [docs Cloudflare](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/get-started/).

## 2.5 Update Deployment

```bash
cd andblog
git pull
docker compose build app
docker compose up -d app  # rebuild + restart hanya container app
```

---

# 3. aaPanel di Linux Armbian

Asumsi: Single Board Computer (Orange Pi 5, Rock Pi 4, Raspberry Pi 4) dengan Armbian, aaPanel sudah terinstall via `wget -O install.sh https://www.aapanel.com/script/install_6.0_en.sh && bash install.sh`.

⚠️ **Catatan ARM**: Pastikan SBC Anda ARM64 (aarch64). 32-bit ARM (armhf) tidak didukung PostgreSQL 16 dan beberapa PHP extension.

```bash
uname -m
# Harus output: aarch64
```

## 3.1 Persiapan via aaPanel UI

Buka aaPanel di browser: `http://your-server-ip:8888`

### Install Software (App Store)

Di **App Store**, install:

- **Nginx** versi 1.24+
- **PHP 8.4** (kalau hanya ada 8.3 atau lebih lama di list, lihat 3.2 untuk install manual)
- **PostgreSQL 16** (kalau aaPanel hanya kasih MySQL/MariaDB di list, lihat 3.3)
- **Supervisor Manager** (untuk queue worker)

### PHP Extensions

**App Store** → klik **Setting** di PHP 8.4 → tab **Install extensions**, install:

- `pgsql`
- `pdo_pgsql`
- `mbstring` (biasanya sudah)
- `gd`
- `intl`
- `zip`
- `bcmath`
- `opcache`
- `fileinfo`

> **Catatan**: `tokenizer` adalah extension built-in PHP 8.x — selalu aktif, tidak perlu install. Kalau tidak muncul di list extension aaPanel, itu wajar.

> **Kalau extension tidak muncul di tab "Install extensions" aaPanel** atau install gagal: install system library dulu via SSH:
> ```bash
> sudo apt update && sudo apt install -y \
>   libpng-dev libwebp-dev libjpeg-turbo-dev libfreetype6-dev \
>   libzip-dev libicu-dev
> ```
> Lalu ulangi install extension dari aaPanel UI. Verifikasi dengan:
> ```bash
> /www/server/php/84/bin/php -m | sort
> ```

Disable functions yang konflik (di tab **Disabled functions**, hapus jika ada): `proc_open`, `proc_close`, `pcntl_*` — Laravel butuh.

### PHP Composer

**App Store** → cari **Composer** → install. Atau manual via SSH:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Node.js

aaPanel App Store ada **PM2 Manager**, tapi untuk build assets lebih clean install Node manual:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 3.2 Install PHP 8.4 Manual (jika tidak ada di App Store)

aaPanel App Store kadang baru sampai PHP 8.3 di Armbian. Project ini butuh **PHP 8.4+**, jadi mungkin Anda harus install manual via apt + ondrej PPA:

```bash
sudo apt update
sudo apt install -y software-properties-common

# Repository ondrej (kompatibel sebagian besar Armbian Debian/Ubuntu)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y \
    php8.4 php8.4-fpm \
    php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-zip \
    php8.4-gd php8.4-curl php8.4-intl php8.4-bcmath \
    php8.4-fileinfo php8.4-opcache

# Aktifkan php8.4-fpm di aaPanel: Software Manager → custom binary path
sudo systemctl enable --now php8.4-fpm
```

> **Penting**: PHP versi di aaPanel Site setting harus tetap di-set ke versi yang ter-link, atau gunakan custom binary path. Kalau site setting hanya bisa pilih PHP 8.3 dari aaPanel, override Nginx config secara manual untuk fastcgi_pass ke socket PHP 8.4 manual install.

## 3.3 Install PostgreSQL 16 Manual

aaPanel default ke MySQL/MariaDB. Untuk PG:

```bash
# Untuk Armbian berbasis Debian 12/Ubuntu 22.04
sudo apt install -y curl ca-certificates
sudo install -d /usr/share/postgresql-common/pgdg
sudo curl -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
  --fail https://www.postgresql.org/media/keys/ACCC4CF8.asc
sudo sh -c 'echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
sudo apt update
sudo apt install -y postgresql-16

sudo systemctl enable --now postgresql

# Buat database (sama dengan section VPS Ubuntu 1.3)
sudo -u postgres psql <<EOF
CREATE DATABASE andblog;
CREATE USER andreandrian WITH PASSWORD 'W3lc0m3t0My@ndv3rse!&';
GRANT ALL PRIVILEGES ON DATABASE blog_laravel TO andblog;
ALTER DATABASE blog_laravel OWNER TO andblog;
\c blog_laravel
GRANT ALL ON SCHEMA public TO andblog;
EOF
```

## 3.4 Buat Site di aaPanel

aaPanel UI → **Website** → **Add site**:

- **Domain**: `yourdomain.com`
- **Description**: `andblog`
- **Document root**: biarkan default `/www/wwwroot/yourdomain.com`
- **Database**: `Don't create` (kita pakai PG, bukan MySQL aaPanel)
- **PHP version**: pilih `PHP-83`
- **Click Submit**

aaPanel akan generate folder `/www/wwwroot/yourdomain.com`.

## 3.5 Deploy Source Code

```bash
cd /www/wwwroot/yourdomain.com

# Hapus default index.html
rm -f index.html 404.html

# Clone repo (overwrite folder)
cd /www/wwwroot
sudo rm -rf yourdomain.com
sudo git clone https://github.com/your-username/project-blog.git yourdomain.com
cd yourdomain.com

# Permission untuk www user (aaPanel pakai user `www`)
sudo chown -R www:www /www/wwwroot/yourdomain.com

# Switch ke user www untuk install
sudo -u www composer install --no-dev --optimize-autoloader --no-interaction
sudo -u www npm ci
sudo -u www npm run build

# Setup env
sudo -u www cp .env.example .env
sudo -u www nano .env
```

Isi `.env` sesuai section VPS 1.6 (sama persis), pastikan `DB_HOST=127.0.0.1` dan password PG match.

```bash
sudo -u www php artisan key:generate
sudo -u www php artisan migrate --force
sudo -u www php artisan db:seed --class=ProductionSeeder --force
sudo -u www php artisan storage:link

# Buat admin user
sudo -u www php artisan tinker
>>> App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'admin@yourdomain.com',
...     'password' => 'STRONG-PASSWORD',
...     'role' => 'admin',
...     'email_verified_at' => now(),
... ]);
>>> exit

# Optimize
sudo -u www php artisan config:cache
sudo -u www php artisan route:cache
sudo -u www php artisan view:cache
sudo -u www php artisan event:cache

# Permission storage
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www:www storage bootstrap/cache
```

## 3.6 Konfigurasi Nginx via aaPanel

aaPanel UI → **Website** → klik **Setting** site Anda → tab **Site directory**:

- **Site directory** → ubah ke `/www/wwwroot/yourdomain.com/public` (penting! tambahkan `/public`)

Tab **URL rewrite** → pilih dropdown **laravel5**, atau paste manual:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Tab **Config file** (di bawah field URL rewrite) — tambahkan dalam `server { ... }` block (sebelum `location / { ... }`):

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Referrer-Policy "strict-origin-when-cross-origin";
client_max_body_size 12M;

location ~* \.(?:css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

Save → aaPanel auto-reload Nginx.

## 3.7 SSL via aaPanel

**Site setting** → tab **SSL** → pilih **Let's Encrypt** → checklist domain → **Apply**.

aaPanel handle renewal otomatis. Pastikan **Force HTTPS** ON setelah cert generated.

## 3.8 Queue Worker via Supervisor Manager

aaPanel UI → **App Store** → **Supervisor Manager** (kalau belum, install dulu).

Buka **Supervisor Manager** → **Add daemon**:

- **Name**: `andblog-queue`
- **Run user**: `www`
- **Run directory**: `/www/wwwroot/yourdomain.com`
- **Start command**:
  ```
  php artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 --max-time=3600
  ```
- **Start number of processes**: `1`

Click **Confirm** → status harus jadi `Running`.

## 3.9 Cron untuk Scheduler

aaPanel UI → **Cron** → **Add task**:

- **Type**: `Shell script`
- **Name**: `andblog-scheduler`
- **Cycle**: `every minute`
- **Run user**: `www`
- **Script content**:
  ```bash
  cd /www/wwwroot/yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
  ```

## 3.10 Database Backup via aaPanel

aaPanel UI → **Cron** → **Add task**:

- **Type**: `Shell script`
- **Name**: `andblog-db-backup`
- **Cycle**: `Daily, 02:00`
- **Run user**: `root`
- **Script content**:
  ```bash
  BACKUP_DIR=/www/backup/database
  TS=$(date +%Y%m%d-%H%M%S)
  mkdir -p $BACKUP_DIR
  PGPASSWORD='STRONG-PASSWORD' pg_dump -U andblog -h 127.0.0.1 blog_laravel | gzip > $BACKUP_DIR/blog_laravel_$TS.sql.gz
  find $BACKUP_DIR -name "blog_laravel_*.sql.gz" -mtime +14 -delete
  ```

aaPanel punya fitur **Backup to remote** (S3, Google Drive, OneDrive) di **Settings**. Aktifkan untuk off-site backup.

## 3.11 Smoke Test (sama dengan VPS)

```bash
curl -I https://yourdomain.com/up
curl -I https://yourdomain.com
```

## 3.12 Update Deployment via aaPanel

aaPanel UI → **Website** → klik nama site → tab **Run command** (atau via SSH):

```bash
cd /www/wwwroot/yourdomain.com
sudo -u www php artisan down --message="Maintenance singkat"
sudo -u www git pull origin main
sudo -u www composer install --no-dev --optimize-autoloader --no-interaction
sudo -u www npm ci && sudo -u www npm run build
sudo -u www php artisan migrate --force
sudo -u www php artisan config:cache route:cache view:cache event:cache
sudo -u www php artisan up
```

Lalu restart queue worker dari **Supervisor Manager**.

---

## Troubleshooting Umum (semua platform)

| Masalah | Diagnosa | Solusi |
|---------|----------|--------|
| 500 error setelah deploy, log kosong | `APP_DEBUG=false` + log channel salah | Set `LOG_CHANNEL=stack`, cek `storage/logs/laravel-{date}.log`, sementara `APP_DEBUG=true` untuk debug awal saja |
| `Permission denied` saat write storage | Owner salah | `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache` (sesuaikan user dengan platform) |
| `Class "X" not found` setelah `composer install` | Autoload belum re-generate | `composer dump-autoload --optimize` |
| Email verifikasi tidak kirim | Queue worker mati / Resend domain belum verified | Cek `systemctl status andblog-queue` (VPS) atau Supervisor Manager (aaPanel). Cek Resend dashboard domain status |
| Migration `update_posts_add_pending_rejected_status` gagal | DB bukan PostgreSQL | Project butuh PG (lihat `SYSTEM_MAP.md`). Migration ini PG-specific |
| Static asset 404 (CSS/JS) | `npm run build` belum jalan / Vite manifest hilang | `npm ci && npm run build` di server |
| `php artisan storage:link` failed | Symlink sudah ada / permission | `rm public/storage` lalu re-run |
| File upload >2MB ditolak | `client_max_body_size` Nginx + `upload_max_filesize` PHP | Set keduanya ke 12M, restart php-fpm + nginx |
| HSTS / security headers tidak muncul | Cache config aktif | `php artisan config:clear && php artisan config:cache` |
| Sentry tidak terima event | DSN salah / firewall block | Test dengan `php artisan tinker` lalu `\Sentry\captureMessage('test')` |

---

## Checklist Final Pre-Launch

Setelah selesai deployment di platform manapun:

- [ ] `https://yourdomain.com/up` → 200 OK
- [ ] `https://yourdomain.com` → render homepage tanpa error
- [ ] Login admin (`https://yourdomain.com/login`) berhasil
- [ ] Buat post test → publish → muncul di blog
- [ ] Register user baru → email verifikasi mendarat di inbox
- [ ] Forgot password → reset link mendarat di inbox
- [ ] Upload media (jpeg/png) → muncul di media library
- [ ] SVG upload → ditolak (security)
- [ ] Trigger 500 error sengaja → muncul di Sentry dashboard
- [ ] Cek `pg_dump` cron jalan (lihat backup folder setelah 24 jam)
- [ ] Test SSL: https://www.ssllabs.com/ssltest/analyze.html?d=yourdomain.com → minimal grade A
- [ ] `APP_DEBUG=false`, `APP_ENV=production` di `.env` server
- [ ] `php artisan tinker` → cek `config('app.debug')` return `false`

Kalau semua ✅, deployment sukses.

---

## Lihat Juga

- [`README.md`](README.md) — Quick start dev + setup Resend & Sentry detail
- [`SYSTEM_MAP.md`](SYSTEM_MAP.md) — Arsitektur project lengkap
- [`OPTIMIZATION-REPORT.md`](OPTIMIZATION-REPORT.md) — Status optimization items + log eksekusi
