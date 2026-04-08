# MatchDay.ro - Documentație Arhitectură

## Cuprins
1. [Prezentare Generală](#prezentare-generală)
2. [Structura Proiectului](#structura-proiectului)
3. [Diagrama Entități (ERD)](#diagrama-entități-erd)
4. [Fluxuri Aplicație](#fluxuri-aplicație)
5. [Arhitectura Request Flow](#arhitectura-request-flow)
6. [API Reference](#api-reference)
7. [Configurare](#configurare)
8. [Securitate](#securitate)

---

## Prezentare Generală

**MatchDay.ro** este o platformă de știri despre fotbal construită în PHP nativ.

### Stack Tehnologic

| Component | Tehnologie |
|-----------|------------|
| Backend | PHP 8.x |
| Database | MySQL 8 / SQLite |
| Frontend | HTML5, CSS3, JavaScript |
| CSS Framework | Bootstrap 5.3 |
| Icons | Font Awesome 6 |
| Testing | PHPUnit 10 |
| CI/CD | GitHub Actions |

### Principii Design

- **MVC Light** - Separare logică în includes/, config/, admin/
- **Security First** - CSRF, XSS prevention, prepared statements
- **Cache Layer** - File-based caching pentru performanță
- **Mobile First** - Responsive design, PWA-ready

---

## Structura Proiectului

```
matchday/
├── admin/                    # Panel administrare
│   ├── dashboard.php         # Dashboard principal
│   ├── posts.php             # CRUD articole
│   ├── comments.php          # Moderare comentarii
│   ├── polls.php             # Administrare sondaje
│   ├── kpis.php              # Dashboard KPIs
│   ├── logs.php              # Vizualizare loguri
│   ├── audit-log.php         # Log acțiuni utilizatori
│   ├── editorial-guide.php   # Ghid editorial
│   └── ...
│
├── assets/
│   ├── css/
│   │   ├── style.css         # CSS principal
│   │   ├── admin.css         # CSS admin
│   │   └── design-system.css # Variabile CSS
│   ├── js/
│   │   └── main.js           # JavaScript principal
│   ├── images/
│   └── uploads/              # Fișiere încărcate
│
├── config/
│   ├── config.php            # Constante globale
│   ├── database.php          # Conexiune DB (Singleton)
│   ├── cache.php             # Configurare cache
│   ├── security.php          # Clase securitate
│   ├── error_handler.php     # Handler erori globale
│   └── validator.php         # Validare input
│
├── includes/
│   ├── Post.php              # Model Post
│   ├── Comment.php           # Model Comment
│   ├── User.php              # Model User
│   ├── Poll.php              # Model Poll
│   ├── Category.php          # Model Category
│   ├── Ad.php                # Model Ads
│   ├── Stats.php             # Statistici
│   ├── Logger.php            # Logging multi-canal
│   └── ...
│
├── data/
│   ├── cache/                # Cache files
│   ├── logs/                 # Log files
│   ├── polls/                # Poll JSON files
│   ├── templates/            # Editorial templates
│   └── backups/              # Database backups
│
├── tests/
│   ├── Unit/                 # Unit tests
│   └── Integration/          # Integration tests
│
├── posts/                    # Articole (dynamic route)
├── cron/                     # Scheduled tasks
└── vendor/                   # Composer dependencies
```

---

## Diagrama Entități (ERD)

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     USERS       │     │     POSTS       │     │   CATEGORIES    │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id (PK)         │     │ id (PK)         │     │ id (PK)         │
│ username        │──┐  │ title           │  ┌──│ name            │
│ email           │  │  │ slug            │  │  │ slug            │
│ password (hash) │  │  │ content         │  │  │ description     │
│ role            │  │  │ excerpt         │  │  │ color           │
│ status          │  │  │ author_id (FK) ─┼──┘  └─────────────────┘
│ created_at      │  │  │ category_id(FK)─┼──┘
│ last_login      │  │  │ featured_image  │
└─────────────────┘  │  │ status          │
                     │  │ views           │
                     │  │ is_sponsored    │
                     │  │ meta_title      │
                     │  │ meta_desc       │
                     │  │ published_at    │
                     │  │ created_at      │
                     │  │ updated_at      │
                     │  └────────┬────────┘
                     │           │
                     │           │ 1:N
                     │           ▼
                     │  ┌─────────────────┐
                     │  │    COMMENTS     │
                     │  ├─────────────────┤
                     │  │ id (PK)         │
                     │  │ post_id (FK)   ─┼────────┐
                     │  │ parent_id (FK) ─┼───┐    │ (self-reference)
                     └──│ user_id (FK)    │   │    │
                        │ author_name     │   │    │
                        │ author_email    │   │    │
                        │ content         │◀──┘    │
                        │ likes           │        │
                        │ status          │◀───────┘
                        │ ip_address      │
                        │ created_at      │
                        └─────────────────┘

┌─────────────────┐     ┌─────────────────┐
│     POLLS       │     │ NEWSLETTER_SUBS │
├─────────────────┤     ├─────────────────┤
│ id (PK)         │     │ id (PK)         │
│ question        │     │ email           │
│ options (JSON)  │     │ status          │
│ active          │     │ token           │
│ category_id     │     │ created_at      │
│ expires_at      │     │ confirmed_at    │
│ created_at      │     └─────────────────┘
└─────────────────┘

┌─────────────────┐     ┌─────────────────┐
│   SUBMISSIONS   │     │      ADS        │
├─────────────────┤     ├─────────────────┤
│ id (PK)         │     │ id (PK)         │
│ title           │     │ name            │
│ content         │     │ position        │
│ author_name     │     │ image           │
│ author_email    │     │ url             │
│ category        │     │ impressions     │
│ status          │     │ clicks          │
│ token           │     │ status          │
│ reviewer_id(FK) │     │ start_date      │
│ review_notes    │     │ end_date        │
│ created_at      │     │ created_at      │
└─────────────────┘     └─────────────────┘
```

---

## Fluxuri Aplicație

### Flux Publicare Articol

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│  DRAFT   │───▶│ PREVIEW  │───▶│ PUBLISH  │───▶│   LIVE   │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
     │               │               │               │
     ▼               ▼               ▼               ▼
 Auto-save      Verificare      Cache clear     Indexare
 localStorage   SEO check       Sitemap update  Social share
                Imagine req.
```

### Flux Comentariu + Moderare

```
┌────────────┐    ┌────────────┐    ┌────────────┐
│ User scrie │───▶│ Validare   │───▶│   SPAM?    │
└────────────┘    │ - CSRF     │    └─────┬──────┘
                  │ - Content  │          │
                  │ - Rate     │     NO   │   YES
                  │ - Length   │          │    │
                  └────────────┘          │    │
                                          ▼    ▼
                                   ┌──────────┐ ┌──────────┐
                                   │ PENDING  │ │ REJECTED │
                                   └────┬─────┘ └──────────┘
                                        │
                       ┌────────────────┴────────────────┐
                       │ Moderation ON?                  │
                       ▼                                 ▼
                  ┌──────────┐                    ┌──────────┐
                  │  REVIEW  │───────────────────▶│ APPROVED │
                  └──────────┘                    └──────────┘
```

### Flux Contribuții Externe

```
┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
│ SUBMIT  │──▶│ PENDING │──▶│REVIEWING│──▶│APPROVED │──▶│PUBLISHED│
└─────────┘   └─────────┘   └─────────┘   └─────────┘   └─────────┘
     │             │             │             │             │
     ▼             ▼             ▼             ▼             ▼
 Token gen    Email notif   Editor claim   Feedback      Convert to
 Rate check      Admin         Review         Email         Post
```

---

## Arhitectura Request Flow

```
┌─────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Browser   │────▶│     Apache      │────▶│    PHP File     │
│   Client    │     │   .htaccess     │     │  (entry point)  │
└─────────────┘     │   mod_rewrite   │     └────────┬────────┘
                    └─────────────────┘              │
                                                     ▼
                                          ┌─────────────────┐
                                          │    config/      │
                                          │  config.php     │
                                          │  database.php   │
                                          │  security.php   │
                                          └────────┬────────┘
                                                   │
                    ┌──────────────────────────────┼──────────────────────────────┐
                    ▼                              ▼                              ▼
          ┌─────────────────┐          ┌─────────────────┐          ┌─────────────────┐
          │   includes/     │          │    admin/       │          │   API files     │
          │   Post.php      │          │   dashboard     │          │ comments_api.php│
          │   Comment.php   │          │   posts.php     │          │ polls_api.php   │
          │   User.php      │          │   etc...        │          │ etc...          │
          └────────┬────────┘          └────────┬────────┘          └────────┬────────┘
                   │                            │                            │
                   └────────────────────────────┼────────────────────────────┘
                                                ▼
                                     ┌─────────────────────┐
                                     │      Database       │
                                     │   MySQL / SQLite    │
                                     └─────────────────────┘
```

---

## API Reference

### Comments API (`/comments_api.php`)

#### GET - Load Comments
```
GET /comments_api.php?post_id=123
```

Response:
```json
{
    "success": true,
    "comments": [
        {
            "id": 1,
            "author_name": "Ion",
            "content": "Articol excelent!",
            "likes": 5,
            "created_at": "2025-01-15 10:30:00",
            "replies": []
        }
    ],
    "total": 1
}
```

#### POST - Add Comment
```
POST /comments_api.php
Content-Type: application/json

{
    "post_id": 123,
    "author_name": "Ion",
    "author_email": "ion@example.com",
    "content": "Comentariul meu",
    "parent_id": null,
    "csrf_token": "..."
}
```

#### POST - Like Comment
```
POST /comments_api.php
Content-Type: application/json

{
    "action": "like",
    "comment_id": 1,
    "csrf_token": "..."
}
```

---

### Polls API (`/polls_api.php`)

#### GET - Get Poll
```
GET /polls_api.php?id=1
```

Response:
```json
{
    "success": true,
    "poll": {
        "id": 1,
        "question": "Cine va câștiga campionatul?",
        "options": ["CFR Cluj", "FCSB", "U Craiova"],
        "votes": [45, 35, 20],
        "total_votes": 100
    }
}
```

#### POST - Vote
```
POST /polls_api.php
Content-Type: application/json

{
    "action": "vote",
    "poll_id": 1,
    "option_index": 0
}
```

---

### Live Scores API (`/livescores_api.php`)

#### GET - Get Live Matches
```
GET /livescores_api.php?status=live
```

Response:
```json
{
    "success": true,
    "matches": [
        {
            "id": 1,
            "competition": "Liga 1",
            "home_team": "CFR Cluj",
            "away_team": "FCSB",
            "home_score": 2,
            "away_score": 1,
            "status": "LIVE",
            "minute": 67
        }
    ]
}
```

---

### RSS Feed (`/rss.php`)

```
GET /rss.php
GET /rss.php?category=liga-1
```

Returns: XML RSS 2.0 feed

---

### Sitemap (`/sitemap.php`)

```
GET /sitemap.php
```

Returns: XML Sitemap following Google standards

---

## Configurare

### Variabile de Mediu (config/config.php)

| Constant | Descriere | Valoare implicită |
|----------|-----------|-------------------|
| `SITE_NAME` | Numele site-ului | MatchDay.ro |
| `SITE_URL` | URL-ul complet | https://matchday.ro |
| `ENVIRONMENT` | Mediu runtime | development/production |
| `DEBUG_MODE` | Afișare erori | false |
| `CACHE_ENABLED` | Cache activ | true |
| `CACHE_TTL` | Timp cache (secunde) | 3600 |
| `COMMENTS_MODERATION` | Pre-moderare | false |
| `SMTP_HOST` | Server email | mail.matchday.ro |
| `ALERT_ENABLED` | Alerte email | true |
| `ALERT_EMAIL` | Email pentru alerte | admin@matchday.ro |

### Database (config/database.php)

```php
define('DB_TYPE', 'mysql');  // 'mysql' sau 'sqlite'
define('DB_HOST', 'localhost');
define('DB_NAME', 'matchday');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## Securitate

### Măsuri Implementate

1. **CSRF Protection**
   - Token unic per sesiune
   - Verificare pe toate formularele POST

2. **XSS Prevention**
   - `htmlspecialchars()` pe output
   - Content Security Policy headers

3. **SQL Injection Protection**
   - Prepared statements exclusiv
   - Validare input strict

4. **Rate Limiting**
   - Comentarii: 5/minut per IP
   - API calls: 60/minut per IP
   - Login: 5 încercări / 15 minute

5. **Password Security**
   - Bcrypt hashing
   - Minimum 8 caractere

6. **Session Security**
   - Session regeneration la login
   - Secure + HttpOnly cookies

### Configurare Security Headers

```php
// Setate în error_handler.php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: same-origin');
```

---

## Logging

### Canale Disponibile

| Canal | Fișier | Folosire |
|-------|--------|----------|
| error | data/logs/error-YYYY-MM-DD.log | Erori PHP |
| audit | data/logs/audit-YYYY-MM-DD.log | Acțiuni utilizatori |
| security | data/logs/security-YYYY-MM-DD.log | Evenimente securitate |
| api | data/logs/api-YYYY-MM-DD.log | Apeluri API |

### Exemplu Logging

```php
Logger::error('Eroare conexiune DB', ['error' => $e->getMessage()]);
Logger::audit('POST_CREATE', ['post_id' => 123, 'user_id' => 1]);
Logger::security('LOGIN_FAILED', ['ip' => $_SERVER['REMOTE_ADDR']]);
```

---

## Health Check

```
GET /health.php
```

Response când totul e OK:
```json
{
    "status": "healthy",
    "checks": {
        "database": "ok",
        "cache_directory": "ok",
        "logs_directory": "ok",
        "uploads_directory": "ok"
    },
    "version": "2.0.0"
}
```

---

## Deployment

### Checklist Pre-Deploy

- [ ] Setează `ENVIRONMENT = 'production'`
- [ ] Setează `DEBUG_MODE = false`
- [ ] Verifică credențiale DB
- [ ] Configurează SMTP pentru email
- [ ] Verifică permisiuni directoare (data/, assets/uploads/)
- [ ] Rulează teste: `composer test`
- [ ] Clear cache

### CI/CD

GitHub Actions workflow în `.github/workflows/tests.yml`:
- Rulează PHPUnit tests la fiecare push
- Verifică PHP 8.x
- Cache composer dependencies

---

*Documentație generată: Ianuarie 2025*
*Versiune: 2.0.0*
