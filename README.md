<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Deploy to Render

This project includes a Docker setup for a Render Web Service and uses PostgreSQL in production.

1. Create a PostgreSQL database on Render and copy its **Internal Database URL**.
2. Create a **Web Service** from this repository, select **Docker**, and use the root `Dockerfile`.
3. Configure these environment variables on the web service:

   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY` from `php artisan key:generate --show`
   - `APP_URL` set to the deployed Render URL
   - `DB_CONNECTION=pgsql`
   - `DB_URL` set to the PostgreSQL **Internal Database URL**

   Keep `APP_KEY` and the database URL secret; do not commit them to the repository.
   The application honors Render's forwarded HTTPS protocol and port when generating URLs.
4. Leave **Docker Command** and **Pre-Deploy Command** blank. The container entrypoint runs database migrations and starts Apache.
5. To create the initial administrator without shell access, temporarily set `ADMIN_NAME`, `ADMIN_USERNAME`, and `ADMIN_PASSWORD` as service environment variables. The entrypoint runs `php artisan app:sync-admin` on startup; remove these variables after signing in so the password is not reset on later restarts.

The container serves Laravel's `public` directory on Render's `PORT`. Product uploads are stored under `public/uploads`; configure persistent or external file storage before relying on uploaded files in production.

### Import existing local data

To copy the local SQLite catalog and sales records into an empty Render PostgreSQL database, install the one-time helper dependency and run:

```sh
python -m pip install -r scripts/requirements-render-import.txt
python scripts/import_render_data.py
```

The script asks for the Render **External Database URL** with hidden input. Do not share or commit that URL. It imports categories, brands, products, price history, transactions, and transaction items only; it refuses to import if those Render tables already contain data. It does not import users, Gmail settings, sessions, or temporary tokens. Product images are included in `public/uploads/products`; future uploads still need persistent or external storage.

## Local account setup

After installing dependencies, configuring `.env`, and running database migrations, create the first administrator account locally:

```sh
php artisan app:create-admin
```

The command prompts for the administrator name, username, and password without storing a default password in the project. Sign in at `/login` with that account. Administrators can create staff accounts from Staff Accounts; inactive staff cannot sign in or continue using an existing session.

To update the credentials of an existing administrator without deleting the account, run:

```sh
php artisan app:update-admin
```

Enter the current administrator username, then the new account details when prompted. The password prompt does not display typed characters; this is expected.

Administrators create staff accounts using a `@gmail.com` address. Gmail sending must be configured from Profile before creating an account. The staff member receives their username and a one-time password setup link that expires after 60 minutes; they must choose their own password before they can sign in. Administrators can resend the setup email from Staff Accounts.

Administrators can save a recovery email on the Profile page; changing it requires the current password. To configure Gmail delivery, open Profile → Gmail sending setup and enter the Gmail address and a Google App Password (not the regular Google password), then confirm the Kulaza account password. The App Password is stored encrypted in the database and is never shown again. Laravel's `APP_KEY` must remain private and backed up because it is used to decrypt the saved credential. On the login page, use **Forgot password?** to request a time-limited reset link. The local `app:update-admin` command remains available if email recovery is unavailable.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
