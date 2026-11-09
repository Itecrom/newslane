# NEWSLANE

Independent journalism from Malawi, Africa and the world. **Your World. Your News.**

NEWSLANE is a Laravel 13 newsroom application built for PHP 8.3+ and MySQL. Its public site and editorial control centre share the same content, moderation and publishing workflows.

## What is included

- Responsive homepage with breaking-news ticker, featured and latest stories, category navigation, search and live coverage.
- Story publishing and editing with category, slug, featured image, accessibility text, SEO description, draft/published state and scheduled publication time.
- Reader comments held for editorial approval, rate-limited story likes, native/social sharing and double-opt-in email subscriptions.
- Live event pages with attributed, timestamped updates.
- Advertisement management with image upload, destination validation, placement and campaign dates.
- Protected editorial access: no public registration, session regeneration, throttled sign-in, editor/administrator roles, administrator-managed staff access and an interactive strong-password admin creation command.
- Canonical URLs, Open Graph/Twitter metadata, NewsArticle structured data, sitemap and robots rules.
- Supplied brand board at `public/images/newslane-brand-board.png`; compact NEWSLANE mark at `public/images/newslane-mark.svg`.

## Local setup (WAMP)

1. Use PHP 8.3 or later and enable `openssl`, `mbstring`, `fileinfo`, `curl`, `pdo_mysql` and `gd`.
2. Create a MySQL database named `newslane` and a dedicated database user. Do not use MySQL root credentials outside a local WAMP development environment.
3. Copy `.env.example` to `.env`. Set `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` and working SMTP settings. Keep `APP_DEBUG=false`, set `SESSION_SECURE_COOKIE=true` and use HTTPS in production.
4. From the project directory, run:

   ```powershell
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   php artisan news:admin:create "News Editor" editor@newslane.com
   ```

   The admin command asks for a password without echoing it and enforces a 12-character mixed-case, numeric and symbolic password. There is intentionally no seeded or default admin password.
5. In Apache, point a virtual host's `DocumentRoot` to this project's `public` directory and enable URL rewriting. Do not expose the project root, `.env`, `vendor` or storage directly.
6. Configure a real mail transport before accepting newsletter subscriptions. Subscribers must confirm by email; local `MAIL_MAILER=log` writes confirmation links to `storage/logs/laravel.log`.

For a quick local-only preview after configuring the database, `php artisan serve` is also available. Set `APP_URL` to match the URL you use so signed newsletter links and canonical metadata work correctly.

## Editorial workflow

- Sign in at `/admin/login`. An administrator account is created from the command above.
- Add or reorder sections in **Categories**; menu-visible categories appear in the public navigation.
- Create an article, add its headline, summary, plain-text body and an optional cover image, then publish or save it as a draft. Article URLs use `/{slug}`.
- Reader comments are private to the editorial desk until approved. Likes use a random, encrypted browser cookie; no raw IP address is stored.
- Manage homepage ads and live coverage from their dashboard sections. Live updates publish immediately with the signed-in editor's name and timestamp.

## Verification

Run the focused newsroom tests with:

```powershell
php artisan test --compact tests/Feature/NewsroomTest.php
```

Production deployment still needs environment-specific SMTP, HTTPS, database backups, monitoring and a security review (including whether the newsroom requires MFA, audit retention or formal approval workflows).
