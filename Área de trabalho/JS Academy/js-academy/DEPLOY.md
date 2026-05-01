# Deploy Guide — JS Academy

## Pre-deploy Checklist

- [ ] `APP_DEBUG=false` no .env
- [ ] `APP_ENV=production` no .env  
- [ ] APP_KEY gerada: `php artisan key:generate`
- [ ] DB configurado com usuário de apenas SELECT/INSERT/UPDATE/DELETE
- [ ] Redis configurado para session e cache
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --force`
- [ ] `npm run build` (assets Vite)
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] Storage link: `php artisan storage:link`
- [ ] Permissões: `chmod -R 755 storage bootstrap/cache`

## Servidor (Ubuntu/Nginx)

### Nginx config
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/js-academy/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
}
```

### PHP-FPM
- PHP 8.2+, extensões: pdo_pgsql, redis, mbstring, xml, bcmath
- `opcache.enable=1`, `opcache.memory_consumption=256`

## Credenciais Admin Padrão
- Email: admin@jsacademy.com
- Senha: definida no seeder (alterar IMEDIATAMENTE após o primeiro login)
- Painel: /admin

## Post-deploy
- Testar login no /admin
- Verificar /ranking carregando
- Testar completar uma lição na home
- Rodar: `php artisan test --env=production` (apenas feature tests)
