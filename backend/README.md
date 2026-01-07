# AI Email Campaign Builder + Sender API (Laravel 12)

Backend-only Laravel 12 API for multi-tenant email campaign management. API prefix: `/api/v1`.

## Requirements
- PHP 8.2+
- Composer 2.x
- MySQL 8.x
- Redis (queues + cache)
- Mailgun account (for provider adapter)

## Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### Database (MySQL)
1. Create database (example):
   ```sql
   CREATE DATABASE email_campaign CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Update `.env` with `DB_*` credentials.
3. Run migrations + seeders:
   ```bash
   php artisan migrate --seed
   ```

### Sanctum
Sanctum is used for API token authentication.
```bash
php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
php artisan migrate
```

### Queue (Redis)
Queues are Redis-compatible and ready for async jobs.
```bash
php artisan queue:work
```

### Mailgun
Set these in `.env`:
```
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain
MAILGUN_SECRET=your-key
MAILGUN_ENDPOINT=api.mailgun.net
```

### Storage
- Local storage for development via `FILESYSTEM_DISK=local`.
- S3 compatible settings are available in `.env.example` for production.

## Notes
- Multi-tenant scoping is enforced via workspace middleware.
- Use the provided API endpoints under `/api/v1`.
