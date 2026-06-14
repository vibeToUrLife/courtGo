# CourtGo — Setup & Learning Guide (from zero)

A plain-language record of **exactly how this project was set up**, so you can understand and repeat it. Read top to bottom.

---

## 1. The tools we use (and what each one is)

| Tool | What it is | Why we use it |
|------|-----------|----------------|
| **PHP 8.4** | The programming language Laravel runs on | The "engine" |
| **Composer** | PHP's package manager | Installs Laravel + libraries |
| **Laravel 13** | A PHP web framework | Does most of the hard work (routing, database, auth) |
| **Laravel Herd** | A Windows app that bundles PHP + Composer + a web server + Node | The easiest way to get PHP on Windows — one installer |
| **Node.js + npm** | JavaScript runtime + package manager | Builds the CSS/JS for the pages |
| **MySQL 8** (via **Laragon**) | The database server | Stores users, courts, bookings |
| **Livewire 4 + Flux UI + Tailwind 4** | Build interactive pages using mostly PHP | So you don't need React/Vue |
| **Laravel Fortify** | Login/registration engine (comes with the starter kit) | Ready-made auth |
| **Laravel Socialite** | "Login with Google/Facebook/…" | Google sign-in |
| **Pest** | The testing tool | Writes automated tests |
| **Git** | Version control | Saves snapshots ("commits") of your code |

---

## 2. From zero → working app (the full path)

### Step A — Install the tools (one time, on your PC)
1. **Laravel Herd** → https://herd.laravel.com/windows — run the installer **as administrator**, then **open the Herd app once** (this is what actually downloads PHP and adds the `php` command to your system).
2. **Node.js LTS** → https://nodejs.org (Herd also bundles Node).
3. **MySQL** → install **Laragon** (https://laragon.org) which bundles MySQL with a friendly window. (You also had XAMPP; we used Laragon.)

> **Why "open Herd once" matters:** installing Herd only copies the app. PHP itself is downloaded the first time you launch the Herd window. Until then, `php --version` fails. **Also: after Herd updates your PATH, you must open a NEW terminal** — old terminals don't see the change. (This is the exact problem you hit.)

### Step B — Start MySQL
Open **Laragon** → click **Start All**. (The first time, it creates the database files; the login is user `root` with **no password** by default.)

### Step C — Create the Laravel app
In a terminal (any folder, e.g. `C:\dev`):
```bash
laravel new courtgo
```
It asks a few questions — we chose:
- **Starter kit:** Livewire
- **Authentication:** Laravel's built-in
- **Testing:** Pest
- **Database:** MySQL

This downloads Laravel + all libraries and builds the starter app (login/register pages included).

### Step D — Point the app at your database
Edit the file **`.env`** in the project:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courtgo
DB_USERNAME=root
DB_PASSWORD=
```
Create the database (one time):
```bash
mysql -u root -e "CREATE DATABASE courtgo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step E — Build the database tables & run the app
```bash
php artisan migrate     # creates the tables
php artisan serve       # starts the website at http://127.0.0.1:8000
```
Open **http://127.0.0.1:8000** in your browser — you can register and log in.

---

## 3. Exactly what we did on YOUR machine

1. Verified the tools: `php --version` (8.4.22), `composer --version`, `laravel --version`, `node --version`.
2. Created the app with: `laravel new courtgo_tmp --livewire --database=mysql --pest --no-interaction`, then **moved it into `C:\dev\CourtGo`** (out of OneDrive, because OneDrive syncing Laravel's huge `vendor`/`node_modules` folders causes errors).
3. Initialized & started **Laragon's MySQL** and created the `courtgo` database.
4. Edited `.env` (database name `courtgo`, app name `CourtGo`).
5. Ran `php artisan migrate` → tables created.
6. Ran `php artisan test` → 33 starter tests passed (app healthy).
7. Built **Phase 1** features (see section 5), committing after each piece.

---

## 4. Everyday commands (cheat sheet)

Run these in a terminal opened **inside `C:\dev\CourtGo`**:
```bash
php artisan serve          # start the website (Ctrl+C to stop)
php artisan migrate         # apply new database changes
php artisan test            # run all tests
php artisan migrate:fresh --seed   # wipe & rebuild DB, then add admin/test data
npm run dev                 # (optional) live-rebuild CSS/JS while editing pages
git add -A && git commit -m "message"   # save a snapshot
```
**If `php` is "not found":** open a NEW terminal (Herd updated your PATH).
**If you get a database connection error:** start MySQL in **Laragon → Start All**.

---

## 5. What Phase 1 added (the files we wrote)

| File | What it does |
|------|--------------|
| `app/Enums/UserRole.php` | Defines the 3 roles: customer / owner / admin |
| `database/migrations/..._add_role_and_google_to_users_table.php` | Adds `role` + `google_id` columns to users |
| `app/Models/User.php` | Tells the User about the new fields + default role |
| `app/Http/Middleware/EnsureUserHasRole.php` | Blocks pages unless the user has the right role |
| `bootstrap/app.php` | Registers the `role` guard so routes can use `role:owner` |
| `routes/web.php` | Added a test `/owner` route + Google login routes |
| `database/seeders/AdminUserSeeder.php` | Creates the admin account |
| `app/Http/Controllers/Auth/GoogleController.php` | Handles "Sign in with Google" |
| `config/services.php` | Google credentials slot |
| `resources/views/pages/auth/login.blade.php` | Added the "Sign in with Google" button |
| `tests/Feature/*Test.php` | Automated tests proving it all works |

**Admin login:** `admin@courtgo.test` / `password`

---

## 6. How the project is organized (key folders)

```
C:\dev\CourtGo\
├─ app/          ← your PHP code (Models, Controllers, Middleware, Enums)
├─ routes/       ← which URL goes where (web.php)
├─ resources/    ← the pages (views) and CSS/JS
├─ database/     ← migrations (table designs) + seeders (starter data)
├─ config/       ← settings
├─ tests/        ← automated tests
├─ public/       ← the web entry point + built CSS/JS
├─ .env          ← YOUR secrets/settings (never shared)
└─ docs/         ← the design spec + plans + this guide
```

---

## 7. Is the app finished? (read this!)

**No — Phase 1 is the foundation only.** What's done vs. what's left:

**✅ Done (Phase 1 — Foundation & Auth):** the app runs, people can register/log in (email + Google), roles exist, admin is seeded.

**🔲 Not built yet (the actual CourtGo features):**
- **Phase 2** — Owners add venues & courts
- **Phase 3** — Owners set weekly session schedules + block dates
- **Phase 4** — Owner subscriptions (Stripe Cashier) + connecting their bank (Stripe Connect)
- **Phase 5** — Customers browse, book & **pay** (the booking + payment system)
- **Phase 6** — Admin panel to manage owners and pricing

So the **Laravel framework and login system are complete**, but the **court-booking business logic is still to come** (Phases 2–6). The full plan for each phase is in `docs/superpowers/plans/`.
