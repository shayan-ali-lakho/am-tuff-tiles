# AM Tuff Tiles

E-commerce website for AM Tuff Tiles (tuff tiles, doors, gardens, metal gates, roof ceilings and more).
Plain PHP 8 + MySQL, built to run on Hostinger shared hosting.

## Structure

```
app/            PHP code (not reachable from the web)
  Controllers/  one class per area of the site
  Core/         Router, Env loader, Database (PDO)
  Views/        page templates and layouts
  routes.php    URL -> controller map
bin/            command-line scripts (migrate.php)
config/         settings read from .env
database/
  migrations/   numbered SQL files applied by bin/migrate.php
public/         the only web-facing folder: index.php, assets, uploads
storage/logs/   error log (app.log)
.htaccess       sends every request to public/ and blocks private files
```

## Run locally

Requires PHP 8.1+ with `pdo_mysql`, `mbstring`, `gd` and `fileinfo`, and a MySQL or MariaDB database.

```bash
cp .env.example .env        # then edit values, including DB_*
php bin/migrate.php         # creates the tables and starting data
php -S localhost:8000 -t public public/index.php
```

Open http://localhost:8000

## Database

Run `php bin/migrate.php` any time: it applies only the migration files that have not run yet and
records them in `schema_migrations`. To change the database later, add a new numbered file such as
`database/migrations/003_add_something.sql` instead of editing an old one.

Migration file rules: every statement ends with `;` at the end of a line, no `;` inside text values,
and statements should be safe to run twice (`CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`).

| Table | Purpose |
|---|---|
| `contacts` | Customers and staff. `portal_role` (`customer` / `admin`) controls access to the admin panel |
| `categories` | Product groups (Tuff Tiles, Doors, Gardens, Metal Gates, Roof Ceilings) |
| `products` | Name, slug, description, price, size, material, stock. Hide with `is_active = 0` instead of deleting |
| `product_images` | Several images per product, one marked primary |
| `orders` | One row per order: customer and delivery details copied at checkout, status, totals, timestamps |
| `order_items` | Lines of an order with the product name and price at time of purchase |
| `password_resets` | Hashed reset tokens with expiry |
| `settings` | Shop details and delivery charge, editable by the admin |

Conventions:

- Money is stored as whole numbers in paisa (PKR 1,250 = `125000`). `money()` formats it for display.
- Order statuses: `pending`, `confirmed`, `completed`, `cancelled`. Payment is cash on delivery.
- Monthly reports count revenue from `completed` orders by `completed_at`, and orders received by `placed_at`.
- The database clock is set to the site timezone (`APP_TIMEZONE`, default Asia/Karachi) on every connection.
- Deleting a product removes its images but keeps old orders readable. Contacts with orders cannot be deleted.

## Deploy on Hostinger (Git)

1. hPanel > Advanced > Git: connect this repo, branch `main`, deploy into the site folder (`public_html`).
2. hPanel > Databases > Management: create a MySQL database and user.
3. On the server, create a `.env` file in the site folder (same level as `.htaccess`) based on `.env.example`
   with `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain` and the database details.
   `.env` is never stored in Git.
4. Connect over SSH, go to the site folder and run `php bin/migrate.php`.
   (Alternative: import `database/migrations/001_*.sql` then `002_*.sql` in phpMyAdmin; running
   `bin/migrate.php` afterwards is still safe.)
5. In hPanel set PHP to 8.2 or newer and turn on Force HTTPS.
6. Pushing to `main` redeploys code. After a deploy that adds migration files, run `php bin/migrate.php` again.
   Uploaded product images in `public/uploads/` are not in Git, so they stay put.

## Roadmap

- [x] 1. Project skeleton, router, base layout, Home and About
- [x] 2. Database schema and seed data
- [ ] 3. Auth: register, login, roles (`portal_role` on `contacts`)
- [ ] 4. Layout polish, full Home and About content
- [ ] 5. Shop with filters and product page
- [ ] 6. Cart and checkout (cash on delivery, PKR)
- [ ] 7. Admin panel: orders, products, monthly report
- [ ] 8. Security pass and testing
- [ ] 9. Production deployment checks
