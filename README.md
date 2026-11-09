# NEWSLANE

Independent journalism from Malawi, Africa and the world. **Your World. Your News.**

NEWSLANE is a Laravel 13 newsroom application built for PHP 8.3+ and MySQL. Its public site and editorial control centre share the same content, moderation and publishing workflows.

## What is included

- Responsive homepage with breaking-news ticker, automatically rotating featured stories, thumbnails in most-read lists, editor-selected category sections, privacy-enhanced live video, category navigation and search.
- Story publishing and editing with category, slug, featured image, accessibility text, SEO description, draft/published state and scheduled publication time.
- Reader comments held for editorial approval, rate-limited story likes, native/social sharing and double-opt-in email subscriptions.
- A public contact form that routes inquiries, advertising proposals and original story submissions to the right team; attachments are stored privately and accessible only to administrators.
- Live event pages with attributed, timestamped updates.
- Advertisement management with image upload, destination validation, placement and campaign dates.
- Optional visible reporting credits and source links; category pages and scheduled stories appear in the sitemap.
- Protected editorial access: no public registration, session regeneration, throttled sign-in, editor/administrator roles, administrator-managed staff access and an interactive strong-password admin creation command.
- Canonical URLs, Open Graph/Twitter metadata, NewsArticle structured data, sitemap and robots rules.
- Supplied brand board at `public/images/newslane-brand-board.png`; compact NEWSLANE mark at `public/images/newslane-mark.svg`.

## Local setup (WAMP)

1. Use PHP 8.3 or later and enable `openssl`, `mbstring`, `fileinfo`, `curl` and `gd`. The Composer lock targets PHP 8.3, which also works with WAMP's default PHP 8.3.
2. For production, create a MySQL database named `newslane` and a dedicated database user; use that user's credentials in the environment. Never use MySQL root credentials in production.
3. Copy `.env.example` to `.env` for a MySQL installation. Set `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` and working SMTP settings. Keep `APP_DEBUG=false`, set `SESSION_SECURE_COOKIE=true` and use HTTPS in production.
4. From the project directory, run:

   ```powershell
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   php artisan news:admin:create "News Editor" editor@newslane.com
   ```

   The admin command asks for a password without echoing it and enforces a 12-character mixed-case, numeric and symbolic password. There is intentionally no seeded or default admin password.
5. In Apache, point a virtual host's `DocumentRoot` to this project's `public` directory and enable URL rewriting. Do not expose the project root, `.env`, `vendor` or storage directly.
6. Configure a real mail transport before accepting newsletter subscriptions. Subscribers must confirm by email; local `MAIL_MAILER=log` writes confirmation links to `storage/logs/laravel.log`.
7. Configure `MAIL_FROM_ADDRESS=info@newslane.africa` and ensure each contact address can receive email. Contact enquiries are also stored in the administrator-only **Inquiries** area. Review privacy, retention and submission-consent policies before accepting real submissions.

For a quick WAMP development install, SQLite avoids requiring a database server: use `DB_CONNECTION=sqlite`, set `DB_DATABASE` to `database/database.sqlite`, enable `pdo_sqlite`, create the empty file, then run `php artisan migrate --seed`. You still need `php artisan news:admin:create "News Editor" editor@newslane.com` to create the first staff login.

For a quick local-only preview after configuring the database, `php artisan serve` is also available. Set `APP_URL` to match the URL you use so signed newsletter links and canonical metadata work correctly.

## Editorial workflow

- Sign in at `/admin/login`. An administrator account is created from the command above.
- Add or reorder sections in **Categories**; choose whether they appear in navigation, on the homepage or both. Seeded sections include Malawi, Politics, Africa, World, International, Business, Health, Sports, Entertainment, Technology, Jobs & Internships, Culture and Opinion.
- Create an article, add its headline, summary, plain-text body and an optional cover image, then publish or save it as a draft. Article URLs use `/{slug}`.
- Reader comments are private to the editorial desk until approved. Likes use a random, encrypted browser cookie; no raw IP address is stored.
- Manage homepage ads and live coverage from their dashboard sections. Live updates publish immediately with the signed-in editor's name and timestamp.
- The **Contact NEWSLANE** page routes general inquiries to `info@newslane.africa`, advertising to `sales@newslane.africa` and original story submissions to `editor@newslane.africa`. A submission appears as unreviewed content; editors still review and publish stories themselves.
- YouTube live-video embeds accept privacy-enhanced `youtube-nocookie.com` embed URLs only. Add legally cleared footage and imagery through the editorial tools.

## Publishing and source rights

The newsroom is intentionally seeded with **categories only**, not fabricated news, comments or advertising. Real content must be researched, verified and supplied by editors or obtained through an agreement with its publisher. Do not copy, scrape, machine-rewrite or republish articles, photographs, archive stories or advertisements from Malawi News, Times 360 Malawi, The Nation, BBC, Reuters or other publications without the relevant licences and permission. Instead, use authorised feeds or syndication contracts; link to and accurately attribute original reporting; obtain rights for every photograph/video; and write original, fact-checked coverage. The editor's source and image-credit fields and submission rights-confirmation are designed to support that workflow, not replace permission or fact-checking.

To build a genuine archive, import a *licensed* archive with its dates, image rights, original source, author, attribution and publication URLs. The database and story editor support dated publications, featured-image sharing previews and scheduled publishing. Do not backdate unverified or newly written material.

## Verification

Run the full application test suite with:

```powershell
php artisan test
```

Production deployment still needs environment-specific SMTP, HTTPS, database backups, monitoring and a security review (including whether the newsroom requires MFA, audit retention or formal approval workflows).
