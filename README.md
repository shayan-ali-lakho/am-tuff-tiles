# AM Tuff Tiles

E-commerce website for AM Tuff Tiles (tuff tiles, doors, gardens, metal gates, roof ceilings and more).
Plain PHP 8 + MySQL, built to run on Hostinger shared hosting.

## Structure

```
app/            PHP code (not reachable from the web)
  Controllers/  one class per area of the site
  Core/         Router, Env loader
  Views/        page templates and layouts
  routes.php    URL -> controller map
config/         settings read from .env
database/       SQL schema and seed files (added in step 2)
public/         the only web-facing folder: index.php, assets, uploads
storage/logs/   error log (app.log)
.htaccess       sends every request to public/ and blocks private files
```

## Run locally

Requires PHP 8.1+ with `pdo_mysql`, `mbstring`, `gd` and `fileinfo`.

```bash
cp .env.example .env        # then edit values
php -S localhost:8000 -t public public/index.php
```

Open http://localhost:8000

## Deploy on Hostinger (Git)

1. hPanel > Advanced > Git: connect this repo, branch `main`, deploy into the site folder (`public_html`).
2. On the server, create a `.env` file in the site folder (same level as `.htaccess`) based on `.env.example`
   with `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain` and the database details.
   `.env` is never stored in Git.
3. In hPanel set PHP to 8.2 or newer and turn on Force HTTPS.
4. Pushing to `main` redeploys; uploaded product images in `public/uploads/` are not in Git, so they stay put.

## Roadmap

- [x] 1. Project skeleton, router, base layout, Home and About
- [ ] 2. Database schema and seed data
- [ ] 3. Auth: register, login, roles (`portal_role` on `contacts`)
- [ ] 4. Layout polish, full Home and About content
- [ ] 5. Shop with filters and product page
- [ ] 6. Cart and checkout (cash on delivery, PKR)
- [ ] 7. Admin panel: orders, products, monthly report
- [ ] 8. Security pass and testing
- [ ] 9. Production deployment checks
