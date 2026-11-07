# ReliefGrid — Humanitarian Aid Distribution and Settlement Resilience Platform

CSE470 Software Engineering project (Laravel 12 · Blade + Bootstrap 5 · PostgreSQL · Laravel Breeze).

This commit contains the project foundation: authentication (Breeze), five staff roles with Policies,
administrator user management, and the shared reference data (settlement blocks and item catalogue).
Features are added in the following commits.

## Quick start

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # set DB_* to your PostgreSQL credentials
php artisan migrate --seed
php artisan serve
```

Demo accounts (password `password`): admin@reliefgrid.test, coordinator@reliefgrid.test,
field@reliefgrid.test, warehouse@reliefgrid.test, assessor@reliefgrid.test.
