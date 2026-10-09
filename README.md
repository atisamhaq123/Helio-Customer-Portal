# Helio customer portal

A responsive solar customer portal built with **core PHP 8.2+**, **Bootstrap 5.3.8**, plain JavaScript, and PDO SQLite. No Laravel, PHP framework, Composer, or frontend build step.

## Run locally

Enable `pdo_sqlite` in your PHP installation, then run from this folder:

```powershell
php -S localhost:8080 -t public
```

Open http://localhost:8080 and select **Enter customer demo**.

On Windows, you can also double-click `start.cmd` to start the server.

This workspace also includes a downloaded, checksum-verified PHP testing runtime in `.tools/php` (ignored by Git). If available, use:

```powershell
.\.tools\php\php.exe -d extension_dir=.tools/php/ext -d extension=pdo_sqlite -S localhost:8080 -t public
```

Use `public/` as the web root for Apache or Nginx. Keep `app/`, `storage/`, `.tools/`, and `tests/` outside the served directory. The application creates `storage/helio.sqlite` on first launch; PHP must have write access to that folder. Bootstrap, DM Sans, and Manrope are bundled locally with their licenses, so the interface makes no external asset requests. Bootstrap is pinned to the release and integrity values in the [official documentation](https://getbootstrap.com/docs/5.3/).

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Customer | alex@helio.demo | HelioDemo!2026 |
| Administrator | admin@helio.demo | HelioAdmin!2026 |

## Included

- Responsive dashboard, simulated energy charts with day/week/month controls, energy flow, and CSV report export.
- Two properties with independent installation milestones and capacity-scaled demo generation.
- Installation timeline, invoice history and text downloads, searchable downloadable sample documents.
- Support ticket creation with persisted status and administrator replies.
- Appointment requests with validation, duplicate checks, and administrator confirmation.
- Profile and password updates, session authentication, logout, password hashing, CSRF checks, escaped output, prepared SQL, and customer-scoped record downloads.
- Admin workspace for installation stages, support replies, appointment statuses, and an audit history.

Appointments and displayed dates use **Asia/Karachi (PKT)**. The sample addresses are illustrative.

## Production integration work

This is a working first release with demo records, not a deployed production service. Energy telemetry and savings are simulated. Invoice payments are not processed. Downloads are sample text documents, not customer PDFs. There is no email delivery, file upload, account provisioning, password recovery, or payment-provider integration yet.

Before rollout, connect your inverter and payment providers, implement verified webhooks, supply actual contracts and equipment documents, add customer provisioning and email recovery, and configure HTTPS, backups, monitoring, and server-level login rate limiting. Session-level login throttling is included for the demo but is not a substitute for server-level protection. Replace demo credentials and data. Set `HELIO_DISABLE_DEMO=1` to hide and disable the demo entry button; this does not remove seeded demo accounts. Provision production accounts and replace the demo seeding process before deployment.

Set `HELIO_DB_PATH` to an absolute path to use a different SQLite database. For higher concurrent write traffic, migrate persistence to MySQL/PostgreSQL.

## Validation

```powershell
Get-ChildItem app,public,tests -Filter *.php -Recurse | ForEach-Object { php -l $_.FullName }
python tests/smoke.py
```

The smoke test runs against http://localhost:8080. Run the test server with `HELIO_DB_PATH` pointing to a separate disposable database, because the test creates customer requests and changes their status through the administrator account.

The browser checks in `tests/browser.cjs` use Playwright installed under `.tools/` and the local Google Chrome executable. They exercise chart controls, property switching, document search, mobile navigation, JavaScript errors, and page overflow at 1440px and 390px viewport widths. Screenshots are written to `.tools/`.
