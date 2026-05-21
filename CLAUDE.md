# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

DMCA Panel — a PHP/MySQL web app for DMCA copyright infringement report management with Rustracker BitTorrent tracker blacklist API integration. No frameworks, no package manager, no build step. Vanilla PHP + vanilla JS.

## Deployment

- Requires PHP 7.4+ with `pdo_mysql` and `curl` extensions
- Requires a MySQL 5.7+ / MariaDB 10.3+ database
- First visit to any page auto-redirects to `/install.php` (WordPress-style: `config.php` absence triggers installation)
- Installer creates database, tables, admin account, and writes `config.php`
- `config-sample.php` can also be manually copied to `config.php`

## Running locally

```bash
php -S localhost:8080
# Then open http://localhost:8080
```

For Apache, `.htaccess` blocks direct access to `config.php` and `schema.sql`.

## Architecture

```
Browser                    PHP Backend                    External
─────                      ───────────                    ────────
/public pages                                                  
  index.php ──────┐                                           
  install.php     ├── includes/functions.php ─── MySQL         
                  │       (h, getDB, csrf, rate_limit)         
/admin                                                     
  login.php ── session auth                                    
  index.php ── CRUD + review ────────────── Rustracker API     
                  │                    (POST /api/blacklist)   
/api                                                      
  submit.php  ── JSON API (public)                              
  reports.php ── JSON API (Bearer auth)                        
  review.php  ── JSON API (Bearer auth)                        
```

**Key architectural rules:**

- `config.php` is the single source of truth for all settings (`DB_*`, `ADMIN_*`, `RUSTRACKER_*`). Never hardcode credentials anywhere else.
- `includes/functions.php` provides `getDB()` (PDO singleton), `h()` (HTML escape), `csrf_token()`/`csrf_verify()`, and rate limit helpers. All PHP pages include it.
- Admin authentication is session-based (`$_SESSION['admin_logged_in']`). Password hash stored in `ADMIN_PASS_HASH` constant.
- API auth uses Bearer token matching `RUSTRACKER_TOKEN`.
- All SQL queries use PDO prepared statements with named parameters — never interpolate user input into SQL strings.
- Table name is `DB_PREFIX . 'dmca_reports'`, where prefix defaults to empty string.

## UI design constraints (Flat Design)

- Colors: `#2563EB` primary, `#1D4ED8` hover, `#F8FAFC` bg, `#FFFFFF` card, `#1E293B` text, `#64748B` secondary, `#E2E8F0` border
- **No**: box-shadow, text-shadow, gradients, border-radius > 6px, transitions > 0.2s
- **Yes**: solid color blocks, line separators, whitespace hierarchy, 4px-radius buttons
- Vanilla JS only (`assets/app.js`). No framework imports.
- Use CSS classes from `assets/style.css` — never inline styles.

## Review workflow

Admin clicks "通过" → AJAX POST with `action=approve` → server calls Rustracker `POST /api/blacklist` with Bearer token and `{"info_hash": "..."}` → updates row status to `approved` in-place.

Admin clicks "驳回" → inline reject form expands → enter reason → AJAX POST with `action=reject`.

Processed reports can be reopened (`action=reopen` → status back to `pending`).

## Database

Single table `dmca_reports` (see `schema.sql`). Status column: `pending` | `approved` | `rejected`. Info hash is `CHAR(40)`.
