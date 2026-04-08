# MatchDay.ro - Plan Profesionalizare 2026

## 📋 Obiectiv

Transformarea MatchDay.ro dintr-un CMS funcțional într-o **platformă profesională cu standarde de produs**, incluzând testare, monitoring, deployment automatizat, design system și strategie editorială/monetizare.

---

## 🗓️ Timeline Estimat

| Fază | Durată | Prioritate | Status |
|------|--------|------------|--------|
| Faza 6: Testing & QA | 2-3 săptămâni | 🔴 Critică | 🟡 75% (173 tests) |
| Faza 7: Logging & Monitoring | 1-2 săptămâni | 🔴 Critică | 🟡 60% (Logger+Health) |
| Faza 8: CI/CD Pipeline | 1-2 săptămâni | 🟠 Înaltă | ⬜ 0% |
| Faza 9: Design System | 2 săptămâni | 🟡 Medie | ⬜ 0% |
| Faza 10: KPIs & Analytics | 1 săptămână | 🟡 Medie | ⬜ 0% |
| Faza 11: Strategie Editorială | 1 săptămână | 🟡 Medie | ⬜ 0% |
| Faza 12: Monetizare | 1-2 săptămâni | 🟢 Normală | ⬜ 0% |
| Faza 13: Documentație Arhitectură | 1 săptămână | 🟢 Normală | ⬜ 0% |
| Faza 14: Plan Scalare | 1 săptămână | 🟢 Normală | ⬜ 0% |

**Total estimat: 10-14 săptămâni**

---

## 🔧 Faza 6: Testing & QA

### 6.1 Unit Testing (PHPUnit)

**Fișiere de creat:**
```
tests/
├── Unit/
│   ├── PostTest.php
│   ├── CommentTest.php
│   ├── UserTest.php
│   ├── PollTest.php
│   ├── SubmissionTest.php
│   ├── LiveScoresTest.php
│   ├── SecurityTest.php
│   └── CacheTest.php
├── Integration/
│   ├── CommentWorkflowTest.php
│   ├── SubmissionWorkflowTest.php
│   ├── LiveScoresApiTest.php
│   └── AuthenticationTest.php
├── bootstrap.php
└── phpunit.xml
```

**Teste prioritare:**
- [x] `SecurityTest` - CSRF, XSS, SQL injection ✅ (28 tests, 44 assertions)
- [x] `PostTest` - CRUD articole, slug generation ✅ (30 tests, 65 assertions)
- [x] `CommentTest` - creare, nested replies, likes, spam detection ✅ (27 tests, 51 assertions)
- [x] `UserTest` - autentificare, roluri, parole ✅ (33 tests, 53 assertions)
- [x] `PollTest` - vot, prevenire vot dublu ✅ (33 tests, 83 assertions)
- [ ] `SubmissionTest` - workflow pending→approved→published
- [ ] `LiveScoresTest` - cache, API fallback
- [x] `CacheTest` - get/set, TTL, expiration ✅ (22 tests, 39 assertions)

**📊 Progres Unit Testing: 173 tests, 335 assertions - PASS ✅**

**Configurare PHPUnit:**
```xml
<!-- phpunit.xml -->
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory>includes</directory>
            <directory>config</directory>
        </include>
    </coverage>
</phpunit>
```

### 6.2 Integration Testing

**API Endpoints de testat:**
| Endpoint | Metode | Teste |
|----------|--------|-------|
| `/comments_api.php` | POST, GET | Create, List, Like, Reply |
| `/polls_api.php` | POST, GET | Vote, Results |
| `/livescores_api.php` | GET | List, Filter by competition |
| `/search-suggestions.php` | GET | Query, Empty results |

### 6.3 UI Testing (Playwright)

**Fișiere:**
```
e2e/
├── tests/
│   ├── homepage.spec.ts
│   ├── article.spec.ts
│   ├── comments.spec.ts
│   ├── polls.spec.ts
│   ├── admin-login.spec.ts
│   ├── admin-posts.spec.ts
│   └── contribute.spec.ts
├── playwright.config.ts
└── package.json
```

**Scenarii critice:**
- [ ] Homepage loads, articole afișate corect
- [ ] Articol - comentarii funcționează
- [ ] Sondaj - vot și afișare rezultate
- [ ] Admin login - autentificare corectă
- [ ] Admin - creare/editare articol
- [ ] Contribute - trimitere și tracking

### 6.4 QA Checklist pentru Release

```markdown
## Pre-Release Checklist

### Funcționalitate
- [ ] Toate paginile publice încarcă fără erori
- [ ] Admin panel accesibil și funcțional
- [ ] Formulare funcționează (contact, comentarii, sondaje)
- [ ] Upload imagini funcționează
- [ ] Căutare returnează rezultate corecte

### Securitate
- [ ] CSRF tokens active pe toate formularele
- [ ] Rate limiting funcțional
- [ ] Inputuri sanitizate
- [ ] Fără erori PHP expuse

### Performanță
- [ ] Timp încărcare < 3s
- [ ] Cache activ
- [ ] Imagini optimizate

### SEO
- [ ] Meta tags prezente
- [ ] Sitemap valid
- [ ] RSS feed funcțional

### Mobile
- [ ] Responsive pe toate breakpoints
- [ ] Touch-friendly
```

---

## 📈 Faza 7: Logging & Monitoring

### ✅ 7.1 Error Logging - COMPLET

**Structură implementată:**
```
data/logs/
├── app.log            # Application logs ✅
├── error.log          # PHP errors ✅
├── security.log       # Failed logins, CSRF failures ✅
├── api.log            # External API calls ✅
├── audit.log          # Admin actions ✅
└── performance.log    # Slow queries, timeouts ✅
```

**Clasa Logger implementată:** `includes/Logger.php` ✅

### 7.2 Admin Audit Log

**Acțiuni de logat:**
| Acțiune | Detalii |
|---------|---------|
| POST_CREATE | post_id, title |
| POST_UPDATE | post_id, changes |
| POST_DELETE | post_id, title |
| USER_CREATE | user_id, username |
| USER_DELETE | user_id |
| COMMENT_DELETE | comment_id |
| SUBMISSION_APPROVE | submission_id |
| SUBMISSION_REJECT | submission_id |
| SETTINGS_CHANGE | key, old_value, new_value |
| LOGIN_SUCCESS | user_id, IP |
| LOGIN_FAILED | username, IP |

**Admin Panel:**
- Pagină `/admin/audit-log.php` cu filtre și export

### ✅ 7.3 Health Checks - COMPLET

**Endpoint implementat:** `/health.php` ✅
```json
{
    "status": "healthy",
    "timestamp": "2026-04-08T12:00:00Z",
    "checks": {
        "database": { "status": "ok", "latency_ms": 5 },
        "cache": { "status": "ok" },
        "disk_space": { "status": "ok", "free_gb": 12.5 },
        "uploads": { "status": "ok" },
        "logs": { "status": "ok" },
        "php": { "status": "ok", "version": "8.1.34" }
    },
    "version": "2.0.0"
}
```

**Admin Log Viewer:** `/admin/logs.php` ✅

### 7.4 Uptime Monitoring

**Opțiuni externe:**
- UptimeRobot (gratuit, 5 min interval)
- Better Uptime
- Pingdom

**Monitorizare:**
- Homepage: check HTTP 200
- Health endpoint: parse JSON status
- Admin login: check form prezent

### 7.5 Alertare

**Canale:**
- Email pentru critical errors
- Telegram bot pentru warning+
- Dashboard în admin

---

## 🚀 Faza 8: CI/CD Pipeline

### 8.1 Git Flow

```
main          ────●────●────●────●──── (production)
                  │    │    ↑
staging       ────●────●────●──────── (pre-production)
                  │    ↑
develop       ────●────●────●────●──── (development)
              ↗   ↑
feature/xyz   ●───●
```

**Branches:**
| Branch | Scop |
|--------|------|
| `main` | Producție (auto-deploy) |
| `staging` | Pre-producție (QA) |
| `develop` | Development activ |
| `feature/*` | Funcționalități noi |
| `hotfix/*` | Fixuri urgente |

### 8.2 GitHub Actions

**`.github/workflows/ci.yml`**
```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [main, staging, develop]
  pull_request:
    branches: [main, staging]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: PHP Lint
        run: find . -name "*.php" -exec php -l {} \;
      
  test:
    runs-on: ubuntu-latest
    needs: lint
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: pdo, pdo_mysql, pdo_sqlite
      - name: Install dependencies
        run: composer install
      - name: Run PHPUnit
        run: vendor/bin/phpunit
        
  deploy-staging:
    runs-on: ubuntu-latest
    needs: test
    if: github.ref == 'refs/heads/staging'
    steps:
      - name: Deploy to Staging
        run: |
          # SSH deploy to staging server
          
  deploy-production:
    runs-on: ubuntu-latest
    needs: test
    if: github.ref == 'refs/heads/main'
    steps:
      - name: Deploy to Production
        run: |
          # SSH deploy to production (Hostico)
```

### 8.3 Staging Environment

**Subdomain:** `staging.matchday.ro`
- Bază de date separată
- Date de test (nu producție)
- .htaccess cu IP whitelist

### 8.4 Rollback Plan

**Procedură:**
1. Identificare commit problematic
2. `git revert <commit>` sau `git reset --hard <previous>`
3. Push to main
4. Verificare health check
5. Post-mortem documentat

**Backup pre-deploy:**
- Snapshot DB înainte de fiecare deploy
- Arhivă fișiere modificate

---

## 🎨 Faza 9: Design System

### 9.1 Paletă Culori

```css
:root {
    /* Primary - Brand */
    --color-primary: #1a5f2a;        /* Verde MatchDay */
    --color-primary-light: #2d8a3e;
    --color-primary-dark: #0d3d16;
    
    /* Secondary - Accent */
    --color-accent: #f0b90b;         /* Galben/Auriu */
    --color-accent-light: #ffd43b;
    --color-accent-dark: #c49608;
    
    /* Neutrals */
    --color-bg: #ffffff;
    --color-bg-alt: #f8f9fa;
    --color-bg-dark: #1a1a2e;
    --color-text: #212529;
    --color-text-muted: #6c757d;
    --color-border: #dee2e6;
    
    /* Semantic */
    --color-success: #28a745;
    --color-warning: #ffc107;
    --color-danger: #dc3545;
    --color-info: #17a2b8;
    
    /* Dark Mode */
    --dark-bg: #121212;
    --dark-bg-alt: #1e1e1e;
    --dark-text: #e0e0e0;
    --dark-border: #333333;
}
```

### 9.2 Tipografie

```css
:root {
    /* Font Families */
    --font-heading: 'Montserrat', sans-serif;
    --font-body: 'Open Sans', sans-serif;
    --font-mono: 'Fira Code', monospace;
    
    /* Font Sizes */
    --text-xs: 0.75rem;    /* 12px */
    --text-sm: 0.875rem;   /* 14px */
    --text-base: 1rem;     /* 16px */
    --text-lg: 1.125rem;   /* 18px */
    --text-xl: 1.25rem;    /* 20px */
    --text-2xl: 1.5rem;    /* 24px */
    --text-3xl: 1.875rem;  /* 30px */
    --text-4xl: 2.25rem;   /* 36px */
    
    /* Line Heights */
    --leading-tight: 1.25;
    --leading-normal: 1.5;
    --leading-relaxed: 1.75;
    
    /* Font Weights */
    --font-normal: 400;
    --font-medium: 500;
    --font-semibold: 600;
    --font-bold: 700;
}
```

### 9.3 Spacing Scale

```css
:root {
    --space-1: 0.25rem;   /* 4px */
    --space-2: 0.5rem;    /* 8px */
    --space-3: 0.75rem;   /* 12px */
    --space-4: 1rem;      /* 16px */
    --space-5: 1.5rem;    /* 24px */
    --space-6: 2rem;      /* 32px */
    --space-8: 3rem;      /* 48px */
    --space-10: 4rem;     /* 64px */
    --space-12: 6rem;     /* 96px */
}
```

### 9.4 Componente UI Documentate

**Lista componente:**
| Componentă | Fișier | Variante |
|------------|--------|----------|
| Button | `_buttons.css` | primary, secondary, outline, ghost, sizes |
| Card | `_cards.css` | article, poll, live-score, sidebar |
| Badge | `_badges.css` | status, category, user-level |
| Form | `_forms.css` | input, textarea, select, checkbox, switch |
| Alert | `_alerts.css` | success, warning, error, info |
| Modal | `_modals.css` | small, medium, large |
| Table | `_tables.css` | striped, hover, responsive |
| Navigation | `_nav.css` | navbar, sidebar, breadcrumb, pagination |
| Comment | `_comments.css` | single, nested, reply-form |
| Poll | `_polls.css` | voting, results, widget |
| Live Score | `_livescores.css` | match-card, widget, full-list |

### 9.5 Guideline Imagini

| Tip | Dimensiuni | Format | Max Size |
|-----|------------|--------|----------|
| Featured | 1200x630 | JPEG/WebP | 200KB |
| Thumbnail | 400x300 | JPEG/WebP | 50KB |
| Avatar | 200x200 | PNG/WebP | 30KB |
| Logo | 200x60 | PNG/SVG | 20KB |
| Icon | 64x64 | SVG/PNG | 5KB |

**Convenții:**
- Aspect ratio articole: 16:9 sau 1.91:1 (OG)
- Compresie: 80% quality JPEG
- Alt text obligatoriu
- Lazy loading pentru toate imaginile below fold

---

## 📊 Faza 10: KPIs & Analytics

### 10.1 KPIs Conținut

| Metric | Target | Măsurare |
|--------|--------|----------|
| Articole noi/săptămână | 3-5 | COUNT posts WHERE created_at > 7 days |
| Timp pe pagină | > 2:00 | Google Analytics |
| Bounce rate | < 40% | Google Analytics |
| Pagini/sesiune | > 2.5 | Google Analytics |
| Return visitors | > 30% | Google Analytics |

### 10.2 KPIs Interactivitate

| Metric | Target | Măsurare |
|--------|--------|----------|
| Comentarii/articol | > 5 | AVG(comments per post) |
| Voturi sondaje/zi | > 50 | COUNT votes WHERE date = today |
| Share rate | > 5% | Clicks share / Views |
| Newsletter signup | > 2% | Signups / Unique visitors |

### 10.3 KPIs Tehnici

| Metric | Target | Măsurare |
|--------|--------|----------|
| Page load time | < 2s | Lighthouse / RUM |
| TTFB | < 500ms | Server logs |
| Cache hit rate | > 80% | Cache stats |
| Error rate | < 0.1% | Error logs / Requests |
| Uptime | > 99.5% | UptimeRobot |

### 10.4 KPIs Scoruri Live

| Metric | Target | Măsurare |
|--------|--------|----------|
| API latency | < 200ms | api.log |
| Data freshness | < 60s | Cache TTL check |
| Widget views/zi | > 500 | Event tracking |
| Click-through | > 10% | Clicks / Views |

### 10.5 Dashboard KPIs

**Pagină:** `/admin/kpis.php`
- Grafice cu trend (ultimele 30 zile)
- Comparație cu perioada anterioară
- Alertă când KPI sub target
- Export CSV/PDF

---

## 🧩 Faza 11: Strategie Editorială

### 11.1 Tipuri de Articole

| Tip | Frecvență | Lungime | Template |
|-----|-----------|---------|----------|
| **Preview Meci** | 2-3/săpt | 600-800 cuvinte | `template-preview.md` |
| **Analiză Meci** | 2-3/săpt | 800-1200 cuvinte | `template-analiza.md` |
| **Transfer News** | când e cazul | 400-600 cuvinte | `template-transfer.md` |
| **Interviu** | 1-2/lună | 1000-1500 cuvinte | `template-interviu.md` |
| **Opinie/Editorial** | 1/săpt | 800-1000 cuvinte | `template-opinie.md` |
| **Breaking News** | urgent | 200-400 cuvinte | `template-breaking.md` |
| **Istoric/Retrospectivă** | 1-2/lună | 1000-1500 cuvinte | `template-istoric.md` |

### 11.2 Ton & Stil

**Caracteristici:**
- Profesional dar accesibil
- Pasiune pentru fotbal, fără fanatism
- Obiectiv în analize, personal în opinii
- Evitare jargon excesiv
- Structuri clare (subtitluri, liste, bold pentru idei cheie)

**Evitări:**
- Clickbait exagerat
- Titluri ALL CAPS
- Insulte la adresa jucătorilor/cluburilor
- Informații neverificate
- Plagiat

### 11.3 Șabloane Articole

**Preview Meci:**
```markdown
# [Echipa A] vs [Echipa B]: Tot ce trebuie să știi

## Context
- Poziții clasament
- Forma recentă (ultimele 5 meciuri)
- Istoric direct

## Absenți și Incertitudini
- Echipa A: ...
- Echipa B: ...

## Analiză Tactică
- Stilul fiecărei echipe
- Puncte forte/slabe

## Pronostic
- Scor estimat
- Argumentare

## Unde vezi meciul
- TV / Streaming
```

### 11.4 Calendar Editorial (Model 3 luni)

| Săptămâna | Luni | Marți | Miercuri | Joi | Vineri | Weekend |
|-----------|------|-------|----------|-----|--------|---------|
| S1 | Preview Liga 1 | - | Analiză UCL | - | Opinie | 2x Report meciuri |
| S2 | Transfer News | Preview | - | Analiză | - | 2x Report |
| S3 | Preview Liga 1 | Interviu | Preview UCL | - | Retrospectivă | 2x Report |
| S4 | - | Preview | Analiză UCL | Opinie | - | 2x Report |

### 11.5 Checklist SEO pentru Autori

```markdown
## SEO Checklist (înainte de publicare)

### Titlu
- [ ] Include keyword principal
- [ ] Sub 60 caractere
- [ ] Captivant dar nu clickbait

### Meta Description
- [ ] 150-160 caractere
- [ ] Include keyword
- [ ] Call to action implicit

### Conținut
- [ ] Keyword în primul paragraf
- [ ] Subtitluri H2/H3 cu keywords
- [ ] Link-uri interne (2-3 articole relevante)
- [ ] Link extern authoritative (opțional)
- [ ] Imagini cu alt text descriptiv

### URL
- [ ] Slug curat, cu keyword
- [ ] Fără diacritice sau caractere speciale

### Categorie & Tags
- [ ] Categorie principală selectată
- [ ] 3-5 tags relevante
```

---

## 💰 Faza 12: Monetizare

### 12.1 Poziționare Reclame

```
┌─────────────────────────────────────────┐
│         HEADER BANNER (728x90)          │
├────────────────────────┬────────────────┤
│                        │  SIDEBAR AD    │
│                        │   (300x250)    │
│     ARTICOL            ├────────────────┤
│                        │  SIDEBAR AD 2  │
│  [IN-ARTICLE AD]       │   (300x250)    │
│    (336x280)           │                │
│                        │                │
├────────────────────────┴────────────────┤
│         FOOTER BANNER (728x90)          │
└─────────────────────────────────────────┘
```

### 12.2 Pachete Sponsori

| Pachet | Preț/lună | Include |
|--------|-----------|---------|
| **Basic** | 200 RON | 1 banner sidebar, 10K impresii |
| **Standard** | 500 RON | Header + sidebar, 30K impresii |
| **Premium** | 1000 RON | Toate pozițiile, 100K impresii, 1 articol sponsorizat |
| **Exclusiv** | 2000 RON | Exclusivitate categorii, branded content |

### 12.3 Articole Sponsorizate

**Politică:**
- Marcare clară: "Articol sponsorizat" / "Publicitate"
- Disclaimer vizibil
- Conținut relevant pentru audiență
- Review editorial înainte de publicare
- Maxim 20% din conținut = sponsorizat

**Template disclaimer:**
```html
<div class="sponsored-notice">
    <i class="fas fa-ad"></i>
    Acest articol este o colaborare cu [SPONSOR]. 
    Opiniile exprimate aparțin redacției.
</div>
```

### 12.4 Pagină "Advertise with Us"

**Secțiuni:**
1. Statistici audiență (vizitatori, demografice)
2. Pachete și prețuri
3. Specificații tehnice bannere
4. Politică editorială
5. Formular contact
6. Clienți anteriori (logo-uri)

### 12.5 Google Ad Manager

**Implementare:**
- Cont Google Ad Manager
- Ad units definite
- Header bidding (opțional)
- Lazy loading pentru ads below fold
- Blocuri native pentru experiență mai bună

### 12.6 Affiliate Marketing

**Opțiuni:**
- Link-uri afiliate către case de pariuri (cu disclaimer)
- Magazine echipamente sportive
- Bilete evenimente sportive
- Abonamente streaming

---

## 🧠 Faza 13: Documentație Arhitectură

### 13.1 Diagrama Entități (ERD)

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   USERS     │     │   POSTS     │     │ CATEGORIES  │
├─────────────┤     ├─────────────┤     ├─────────────┤
│ id (PK)     │──┐  │ id (PK)     │  ┌──│ id (PK)     │
│ username    │  │  │ title       │  │  │ name        │
│ email       │  │  │ content     │  │  │ slug        │
│ password    │  │  │ author_id(FK)│──┘  │ description │
│ role        │  │  │ category_id │     └─────────────┘
│ created_at  │  │  │ status      │
└─────────────┘  │  │ views       │
                 │  │ created_at  │
                 │  └─────────────┘
                 │         │
                 │         │ 1:N
                 │         ▼
                 │  ┌─────────────┐
                 │  │  COMMENTS   │
                 │  ├─────────────┤
                 │  │ id (PK)     │
                 │  │ post_id (FK)│
                 │  │ parent_id   │──┐ (self-ref)
                 └──│ user_id (FK)│  │
                    │ author_name │  │
                    │ content     │──┘
                    │ likes       │
                    │ status      │
                    └─────────────┘

┌─────────────┐     ┌─────────────┐
│   POLLS     │     │   VOTES     │
├─────────────┤     ├─────────────┤
│ id (PK)     │◄────│ poll_id(FK) │
│ question    │     │ option_idx  │
│ options JSON│     │ ip_address  │
│ active      │     │ created_at  │
│ created_at  │     └─────────────┘
└─────────────┘

┌─────────────┐     ┌─────────────┐
│SUBMISSIONS  │     │LIVE_MATCHES │
├─────────────┤     ├─────────────┤
│ id (PK)     │     │ id (PK)     │
│ title       │     │ competition │
│ content     │     │ home_team   │
│ author_name │     │ away_team   │
│ author_email│     │ home_score  │
│ status      │     │ away_score  │
│ token       │     │ status      │
│ reviewer_id │     │ kickoff     │
│ created_at  │     │ created_at  │
└─────────────┘     └─────────────┘
```

### 13.2 Fluxuri Vizuale

**Flux Publicare Articol:**
```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│  DRAFT   │───▶│ PREVIEW  │───▶│ PUBLISH  │───▶│   LIVE   │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
     │               │               │               │
     ▼               ▼               ▼               ▼
 Auto-save      Verificare      Cache clear     Indexare
 localStorage   conținut        Sitemap update  Social share
```

**Flux Comentariu + Moderare:**
```
┌────────────┐    ┌────────────┐    ┌────────────┐
│ User scrie │───▶│ Validare   │───▶│   SPAM?    │
└────────────┘    │ - CSRF     │    └─────┬──────┘
                  │ - Content  │          │
                  │ - Rate     │     NO   │   YES
                  └────────────┘          │    │
                        │                 ▼    ▼
                        │          ┌──────────┐ ┌──────────┐
                        │          │ PENDING  │ │ REJECTED │
                        │          └────┬─────┘ └──────────┘
                        │               │
                        │  (if moderation OFF)
                        ▼               ▼
                  ┌────────────┐  ┌──────────┐
                  │  APPROVED  │◀─│  REVIEW  │
                  └────────────┘  └──────────┘
```

**Flux Contribuții Externe:**
```
┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
│ SUBMIT  │──▶│ PENDING │──▶│REVIEWING│──▶│APPROVED │──▶│PUBLISHED│
└─────────┘   └─────────┘   └─────────┘   └─────────┘   └─────────┘
     │             │             │             │             │
     ▼             ▼             ▼             ▼             ▼
 Token gen    Email notif   Editor claim   Feedback      Convert to
 Rate check      Admin         Review         Email         Post
                 alert
                               │
                               ▼
                          ┌─────────┐
                          │REJECTED │
                          └─────────┘
```

### 13.3 Arhitectura Request Flow

```
┌─────────┐     ┌─────────────┐     ┌─────────────┐
│ Browser │────▶│   Apache    │────▶│  index.php  │
└─────────┘     │ .htaccess   │     └──────┬──────┘
                │ mod_rewrite │            │
                └─────────────┘            ▼
                                   ┌─────────────┐
                                   │   Router    │
                                   │ (implicit)  │
                                   └──────┬──────┘
                     ┌────────────────────┼────────────────────┐
                     ▼                    ▼                    ▼
              ┌─────────────┐      ┌─────────────┐      ┌─────────────┐
              │   config/   │      │  includes/  │      │   admin/    │
              │ database.php│      │  Post.php   │      │ dashboard   │
              │ security.php│      │ Comment.php │      │ posts.php   │
              │ cache.php   │      │ etc...      │      │ etc...      │
              └─────────────┘      └─────────────┘      └─────────────┘
                     │                    │                    │
                     └────────────────────┼────────────────────┘
                                          ▼
                                   ┌─────────────┐
                                   │  Database   │
                                   │ MySQL/SQLite│
                                   └─────────────┘
```

---

## 📈 Faza 14: Plan Scalare

### 14.1 Scenarii Load

| Nivel | Vizitatori/zi | Articole | DB Size | Soluție |
|-------|---------------|----------|---------|---------|
| Actual | 500-1000 | 200 | 50MB | Shared hosting OK |
| Mediu | 5000-10000 | 1000 | 500MB | VPS dedicat |
| Mare | 50000 | 5000 | 5GB | Cloud (DO/AWS) |
| Enterprise | 100000+ | 10000+ | 50GB+ | Cluster + CDN |

### 14.2 Optimizări per Nivel

**Nivel Mediu (5-10K vizitatori):**
- [ ] VPS cu 2-4GB RAM
- [ ] MySQL dedicat (nu shared)
- [ ] OPcache activat
- [ ] Redis/Memcached pentru sesiuni
- [ ] CDN pentru imagini (Cloudflare)

**Nivel Mare (50K vizitatori):**
- [ ] Load balancer
- [ ] Database replicas (read replicas)
- [ ] Full-page cache (Varnish/Nginx)
- [ ] Queue pentru email/notifications
- [ ] Elasticsearch pentru search

**Nivel Enterprise (100K+):**
- [ ] Kubernetes cluster
- [ ] Database sharding
- [ ] Microservices (separate API scoruri)
- [ ] Global CDN (CloudFront)
- [ ] Real-time (WebSockets pentru scoruri)

### 14.3 API Fallback Strategy

```php
// LiveScores fallback
try {
    $data = ApiFootball::getLiveMatches();
} catch (ApiException $e) {
    Logger::api('api-football', '/matches/live', 500, 0);
    
    // Try backup provider
    try {
        $data = FootballData::getLiveMatches();
    } catch (ApiException $e2) {
        // Use cached data (even if stale)
        $data = Cache::get('live_matches_backup');
        
        // Alert admin
        Notification::sendToAdmin('All live score APIs down!');
    }
}
```

### 14.4 Database Growth Plan

**Partitioning:**
```sql
-- Comentarii partiționate pe an
ALTER TABLE comments 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION future VALUES LESS THAN MAXVALUE
);
```

**Archiving:**
- Articole > 2 ani → arhivă separată
- Logs > 30 zile → compresie + S3
- Comentarii spam → delete permanent

### 14.5 Image Storage Strategy

| Volum | Strategie |
|-------|-----------|
| < 10GB | Local + backup |
| 10-100GB | CDN (Cloudflare R2, BunnyCDN) |
| > 100GB | Object storage (S3/DO Spaces) |

**Implementare CDN:**
```php
define('CDN_URL', 'https://cdn.matchday.ro');

function imageUrl($path) {
    if (USE_CDN) {
        return CDN_URL . $path;
    }
    return '/assets/uploads' . $path;
}
```

---

## ✅ Checklist Implementare

### Faza 6 - Testing
- [ ] Setup PHPUnit + config
- [ ] Unit tests pentru modele
- [ ] Integration tests pentru API
- [ ] Playwright E2E setup
- [ ] QA checklist document

### Faza 7 - Logging
- [ ] Logger class
- [ ] Error handler integration
- [ ] Audit log pentru admin
- [ ] Health check endpoint
- [ ] External monitoring setup

### Faza 8 - CI/CD
- [ ] GitHub Actions workflow
- [ ] Staging environment
- [ ] Deploy scripts
- [ ] Rollback procedure
- [ ] Documentation

### Faza 9 - Design System
- [ ] CSS variables (culori, tipografie, spacing)
- [ ] Component library documentation
- [ ] Image guidelines
- [ ] Style guide page

### Faza 10 - KPIs
- [ ] Define metrics
- [ ] Database queries pentru stats
- [ ] Admin dashboard KPIs
- [ ] Alerting pentru thresholds

### Faza 11 - Editorial
- [ ] Tipuri articole definite
- [ ] Șabloane create
- [ ] Ghid ton & stil
- [ ] Calendar editorial template
- [ ] SEO checklist

### Faza 12 - Monetizare
- [ ] Pachete sponsori definite
- [ ] Pagină "Advertise"
- [ ] Ad Manager integration
- [ ] Disclaimer articole sponsorizate

### Faza 13 - Documentație
- [ ] ERD diagram
- [ ] Flow diagrams
- [ ] Architecture diagram
- [ ] API documentation

### Faza 14 - Scalare
- [ ] Monitoring setup
- [ ] Cache optimization
- [ ] CDN integration
- [ ] Fallback strategies
- [ ] Growth planning

---

## 🎯 Priorități Recomandate

**IMEDIAT (săptămâna 1-2):**
1. Logging & Error handling (fără asta nu știi ce pică)
2. Health check endpoint
3. Basic unit tests pentru Security și Database

**SĂPTĂMÂNA 3-4:**
4. CI/CD pipeline cu GitHub Actions
5. Staging environment
6. PHPUnit tests complete

**LUNA 2:**
7. Design System documentat
8. KPIs dashboard
9. Strategie editorială

**LUNA 3:**
10. Monetizare setup
11. Documentație arhitectură
12. Plan scalare

---

*Plan creat: 8 Aprilie 2026*
*Status: În așteptare aprobare*
