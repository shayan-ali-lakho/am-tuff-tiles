# AM Tuff Tiles

E-commerce website for AM Tuff Tiles (tuff tiles, doors, gardens, metal gates, roof ceilings and more).
Plain PHP 8 + MySQL, built to run on Hostinger shared hosting.

## Structure

```
app/            PHP code (not reachable from the web)
  Controllers/  one class per area of the site (Admin/ for the admin panel)
  Core/         Router, Env loader, Database (PDO), Auth, Csrf, ImageUploader
  Models/       database access (Contact, LoginAttempt, Category, Product, ProductImage)
  Views/        page templates and layouts
  routes.php    URL -> controller map
bin/            command-line scripts (migrate.php, create-admin.php)
config/         settings read from .env
database/
  migrations/   numbered SQL files applied by bin/migrate.php
public/         the only web-facing folder: index.php, assets, uploads
storage/
  logs/         error log (app.log)
  sessions/     PHP session files (private, not in Git)
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
`database/migrations/004_add_something.sql` instead of editing an old one.

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
| `password_resets` | Hashed reset tokens with expiry (used once password reset by email is built) |
| `settings` | Shop details and delivery charge, editable by the admin |
| `login_attempts` | Failed logins, used to slow down password guessing |

Conventions:

- Money is stored as whole numbers in paisa (PKR 1,250 = `125000`). `money()` formats it for display.
- Order statuses: `pending`, `confirmed`, `completed`, `cancelled`. Payment is cash on delivery.
- Monthly reports count revenue from `completed` orders by `completed_at`, and orders received by `placed_at`.
- The database clock is set to the site timezone (`APP_TIMEZONE`, default Asia/Karachi) on every connection.
- Deleting a product removes its images but keeps old orders readable. Contacts with orders cannot be deleted.

## Accounts and admin access

- Visitors can register at `/register` and log in at `/login`. New accounts are always customers.
- Everything under `/admin` requires a logged-in user whose `portal_role` is `admin`. The router enforces this
  for every `/admin` URL, so new admin pages are protected automatically. Guests are sent to the login page,
  customers get a 403. The role is read from the database on every request, so changing it takes effect at once.
- Every POST form must include `<?= csrf_field() ?>`. The router rejects any POST without a valid token (419).
- Passwords are stored with `password_hash` (bcrypt). Login is limited to 5 failed attempts per email and IP
  address per 15 minutes (plus wider limits per IP and per email).
- Sessions last 8 hours without activity. Session files are kept in `storage/sessions`.

Make someone an admin (choose one):

1. **phpMyAdmin (no terminal):** the person registers on the site first. Then open phpMyAdmin > your database >
   `contacts` > Browse, click Edit on their row, set `portal_role` to `admin` and save.
2. **Command line:** `php bin/create-admin.php` asks for an email (creates the account or promotes an
   existing one) and for a password, which is typed on the server and never stored in Git.

Not built yet: password reset by email (needs mail sending set up).

## Admin: products and categories

- **Products** (`/admin/products`): search and filter by category or status (visible, hidden, out of stock), add,
  edit, hide or show, delete. Each product has a name, category, price in PKR (stored in paisa), size, material,
  stock quantity, short and full description, "visible in the shop" and "featured" switches, and up to 8 photos.
- **Categories** (`/admin/categories`): add, rename, reorder, turn off, delete (only when no products use it).
  A category's link name (slug) is created once and never changes.
- Hiding a product keeps it; deleting removes it and its photos. Past orders keep their own copy of the name and price.

Photo uploads (`app/Core/ImageUploader.php`): the real file type is checked (JPG, PNG or WebP only), every photo is
decoded and re-encoded as a new JPEG (so nothing hidden in the original survives), turned upright using the phone's
rotation flag, resized to 1600 px on the longest side with a 640 px thumbnail, and saved under a random name in
`public/uploads/products/`. The uploads folder refuses to run scripts (`public/uploads/.htaccess`). Photos are not
in Git. WebP needs PHP's GD with WebP support; if the server lacks it the admin sees a clear message.

To run the site with larger photos, PHP needs `upload_max_filesize` and `post_max_size` of at least 12M and 40M
(Hostinger's defaults are higher).

## Shop (public)

- `/shop`: filter bar directly under the menu (search, category, price range, size, material, in stock, sort), 12 products per page.
  Filters live in the URL, so a filtered page can be shared. Every value is checked against real data; unknown values are ignored.
- `/product/{slug}`: photo gallery, price, stock, details and related products.
- Only active products in active categories are ever shown. The product page also shows contact buttons
  (WhatsApp, call, email) taken from `SHOP_PHONE`, `SHOP_WHATSAPP` and `SHOP_EMAIL` in `.env`.
- The Home page shows the active categories and featured products, and still opens if the database is down.

## Cart and checkout (cash on delivery)

- Cart (`/cart`): kept in the session as product id and quantity only. Names, prices and stock are read from the database every
  time, so prices cannot be changed by the visitor. Hidden or sold-out items are removed and quantities lowered, with a notice.
- Checkout (`/checkout`): login required (the cart survives login or registration). Delivery details are validated, then the order
  is saved in one transaction that locks the products, checks and reduces stock, and stores a name/price snapshot of every line.
  The last unit can never be sold twice.
- Delivery charge: `settings.delivery_charge_paisa` (0 = free).
- `/order/{number}` (only the owner can open it) and `/orders` (My orders). Order numbers look like `AM-261008-1A2B3C`.

## Admin: orders and monthly report

- `/admin/orders`: tabs Received (new + confirmed), Completed, Cancelled, All; search by order number, name or phone; 20 per page.
- Order page: customer, items, totals, timeline and status buttons. Allowed moves: new -> confirmed / completed / cancelled,
  confirmed -> completed / cancelled. Completed and cancelled orders are final. Cancelling puts the stock back (once).
- `/admin/reports?month=YYYY-MM`: money earned (completed orders, counted by the day they were completed), orders received
  (counted by the day they were placed), best sellers, day-by-day table, and a CSV download of the month's completed orders.
  CSV cells that start with `= + - @` get a leading apostrophe so Excel cannot run them as formulas.
- The dashboard shows live order, product and this-month numbers.

## Emails and security headers

- New order: an email goes to `SHOP_EMAIL` (with a link to the order in the admin panel) and a confirmation to the customer.
  Emails use PHP `mail()` from `orders@your-domain`. A mail problem never stops an order. Locally (`APP_ENV=local`) nothing is sent;
  messages are written to `storage/logs/mail.log`.
- Every page sends a Content-Security-Policy, X-Frame-Options, nosniff and Referrer-Policy, plus HSTS over HTTPS.

## Forgot password

- `/forgot-password` emails a one-hour, single-use link (`/reset-password/{token}`). Only a hash of the token is stored.
- The page gives the same answer for every email address, so nobody can find out who has an account. Up to 3 links per account per hour.
- Setting a new password burns all open links for that account.

## Admin: users

- `/admin/users`: search accounts and press "Make admin" or "Remove admin". The person must first create an account on the site.
- Safe by design: nobody can remove their own admin access, the shop always keeps at least one admin, and a turned-off account cannot be made admin.
  Role changes take effect on the person's next click (no re-login needed).

## SEO

- Every page has a title, description, canonical address, Open Graph / Twitter share tags and one h1.
- Structured data (JSON-LD): the shop as a business (Home, About), each product (price in PKR, stock) and breadcrumbs.
- `/sitemap.xml` is built from the database (home, shop, categories with products, every visible product). `/robots.txt` hides
  private areas and points to the sitemap.
- Private pages (admin, login, cart, checkout, orders, errors) send `noindex`. Shop searches, sorts and extra filters are also
  `noindex, follow` and point their canonical to the plain category page, so Google lists one clean page per category.
- `SITE_NOINDEX=true` in `.env` hides the whole site from search engines (handy while testing); remove it to go live.
- Speed: gzip and long browser caching for images, CSS and JS (CSS/JS links change with every deploy).

## Deploy on Hostinger (Git)

1. hPanel > Advanced > Git: connect this repo, branch `main`, deploy into the site folder (`public_html`).
2. hPanel > Databases > Management: create a MySQL database and user.
3. On the server, create a `.env` file in the site folder (same level as `.htaccess`) based on `.env.example`
   with `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain` and the database details.
   `.env` is never stored in Git.
4. Create the tables: either connect over SSH, go to the site folder and run `php bin/migrate.php`, or import
   the migration files from `database/migrations/` in number order in phpMyAdmin (Import tab).
   Running `bin/migrate.php` afterwards is still safe.
5. In hPanel set PHP to 8.2 or newer and turn on Force HTTPS.
6. Pushing to `main` redeploys code. After a deploy that adds migration files, apply them as in step 4.
   Uploaded product images in `public/uploads/` are not in Git, so they stay put.

## Roadmap

- [x] 1. Project skeleton, router, base layout, Home and About
- [x] 2. Database schema and seed data
- [x] 3. Auth: register, login, roles (`portal_role` on `contacts`), protected admin area
- [ ] 4. Layout polish, full Home and About content
- [x] 5. Shop with filters and product page
- [x] 6. Cart and checkout (cash on delivery, PKR)
- [x] 7. Admin panel: products, categories, orders and monthly report
- [x] 8. Security pass: headers, order emails, forgot/reset password
- [ ] 9. Production deployment checks
