# Guia de Deploy - JS Academy

Este documento detalha os passos para realizar o deploy do JS Academy em um ambiente de produção (Ubuntu/Debian com Nginx, PHP 8.3+ e MySQL/Postgres).

## 1. Servidor Web (Nginx)

Configure o host virtual do Nginx apontando para a pasta `public/`:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /home/programador05/Área de trabalho/JS Academy/js-academy/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 2. SSL (Certbot)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com
```

## 3. PHP & Composer

Instale as dependências de produção:

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Banco de Dados

Rode as migrações:

```bash
php artisan migrate --force
```

## 5. Filas (Queues) com Supervisor

Configure o Supervisor para manter o `queue:work` rodando:

```ini
[program:js-academy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/programador05/Área de trabalho/JS Academy/js-academy/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/home/programador05/Área de trabalho/JS Academy/js-academy/storage/logs/worker.log
stopwaitsecs=3600
```

## 6. Vite (Compilação de Assets)

No servidor (ou CI/CD):

```bash
npm install
npm run build
```

## 7. Agendador (Cron)

Adicione ao crontab do usuário:

```cron
* * * * * cd /home/programador05/Área de trabalho/JS Academy/js-academy && php artisan schedule:run >> /dev/null 2>&1
```
