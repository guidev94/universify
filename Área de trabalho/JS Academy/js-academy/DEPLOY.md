# 🚀 Guia de Deploy - JS Academy

Este guia explica como hospedar a plataforma **JS Academy** em servidores profissionais (VPS) e opções gratuitas ideais para Laravel.

---

## ☁️ 1. Hospedagem Grátis (Recomendado)

Diferente de sites estáticos, o Laravel exige um servidor que rode PHP e um banco de dados persistente. **Vercel e Netlify não são ideais** para o backend Laravel, embora seja possível com hacks.

### Melhores Opções:
1.  **Railway.app (O melhor):** Oferece deploy automático via GitHub e banco PostgreSQL integrado.
2.  **Render.com:** Possui plano grátis para instâncias Web e bancos de dados.
3.  **Fly.io:** Excelente para performance global.

### Como subir no Railway:
1.  Conecte seu GitHub ao Railway.
2.  Crie um novo projeto e selecione este repositório.
3.  Vá em **Variables** e adicione:
    *   `APP_KEY`: Gere com `php artisan key:generate --show`.
    *   `APP_ENV`: `production`
    *   `DB_CONNECTION`: `pgsql`
4.  O Railway irá ler seu `composer.json` e `package.json` e fazer o deploy automaticamente.

---

## 🖥️ 2. Deploy em VPS (Ubuntu + Nginx)

Esta é a opção mais robusta para escala real.

### Servidor Nginx (Vhost):
Aponte o `root` para a pasta `/public` do projeto:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /var/www/js-academy/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
    }
}
```

### Comandos Essenciais:
```bash
# Permissões de escrita
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Instalação limpa
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan migrate --force
php artisan optimize
```

---

## 🛠️ FAQ (Perguntas Frequentes)

**Posso rodar na Vercel?**  
Sim, mas precisará instalar o pacote `vercel-php` e configurar um arquivo `vercel.json`. Para este projeto (que usa banco de dados e Monaco Editor), recomendamos Railway ou VPS.

**Como atualizar o código no ar?**  
Basta fazer um `git pull` no servidor e rodar `php artisan optimize`.

---
*Desenvolvido com foco em Performance, Segurança e Clean Code.*
