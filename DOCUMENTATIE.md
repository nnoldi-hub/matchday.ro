# MatchDay.ro - Documentație Completă

## 📋 Despre Aplicație

**MatchDay.ro** este o platformă profesională de jurnalism sportiv, construită în PHP nativ, specializată pe fotbal românesc și internațional. Aplicația oferă un sistem complet de management al conținutului (CMS) cu funcționalități avansate de interactivitate, securitate și performanță.

---

## 🏗️ Arhitectură Tehnică

### Stack Tehnologic
| Componenta | Tehnologie |
|------------|------------|
| Backend | PHP 8.1+ |
| Bază de date | MySQL/MariaDB (producție), SQLite (dezvoltare) |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript vanilla |
| Server | Apache cu mod_rewrite |
| Hosting | Hostico.ro |

### Structura Directoare
```
matchday/
├── admin/              # Panou de administrare
├── assets/
│   ├── css/           # Stiluri (admin.css, bootstrap)
│   ├── js/            # JavaScript (live-scores.js, etc.)
│   ├── images/        # Imagini statice
│   └── uploads/       # Imagini încărcate de utilizatori
├── config/            # Configurări (database, security, cache)
├── data/              # Date JSON, cache, backup-uri
├── includes/          # Clase PHP (Post, User, Comment, etc.)
├── posts/             # Articole (fișiere HTML)
└── index.php          # Entry point
```

---

## 🔐 Securitate

### Autentificare și Autorizare
- **Sesiuni securizate** cu regenerare ID la login
- **Parole hash-uite** cu Argon2ID
- **Rate limiting** pentru prevenirea atacurilor brute-force
- **Roluri utilizatori**: Admin, Editor

### Protecții Implementate
- **CSRF Tokens** pe toate formularele
- **XSS Prevention** - sanitizare input/output
- **SQL Injection** - prepared statements
- **Security Headers** - X-Frame-Options, X-Content-Type-Options, CSP
- **File Upload Security** - verificare MIME type, dimensiune maximă

### Validare Date
- Validare email, URL, telefon
- Filtrare cuvinte interzise în comentarii
- Honeypot anti-spam
- Captcha opțional

---

## 📰 Sistem Articole

### Funcționalități
- **CRUD complet** - Creare, Citire, Actualizare, Ștergere
- **Status articole** - Draft, Publicat, Programat
- **Categorii** - Organizare tematică (Liga 1, UCL, Națională, etc.)
- **Tags** - Etichetare flexibilă
- **Imagini featured** - Cu upload și redimensionare
- **SEO** - Meta title, description, keywords per articol

### Editor
- **Editor vizual** cu formatare (bold, italic, liste, link-uri)
- **Preview live** al articolului
- **Auto-save** draft-uri în localStorage
- **Contor caractere/cuvinte**
- **Generare automată URL slug**

### Afișare
- **Paginare** inteligentă
- **Articole recomandate** bazate pe categorie
- **Social sharing** - Facebook, Twitter, LinkedIn, WhatsApp
- **Timp estimat citire**
- **Data ultimei actualizări**

---

## 💬 Sistem Comentarii

### Funcționalități
- **Comentarii nested** (răspunsuri la comentarii)
- **Like-uri** pe comentarii
- **Paginare** pentru performanță
- **Moderare** - aprobare manuală opțională
- **Sortare** - cele mai noi/cele mai vechi

### Anti-Spam
- **Rate limiting** - maxim 5 comentarii/minut
- **Honeypot** - câmp ascuns pentru roboți
- **Filtre cuvinte** - blocarea limbajului vulgar
- **IP tracking** - pentru identificare abuz

---

## 📊 Sondaje Interactive

### Funcționalități
- **Creare sondaje** cu opțiuni multiple
- **Vot unic** per utilizator (cookie/IP based)
- **Rezultate în timp real** cu bare de progres
- **Perioadă activă** - start/end date
- **Afișare în sidebar** sau în articol

### Administrare
- **Statistici complete** - total voturi, distribuție
- **Export rezultate**
- **Activare/dezactivare** rapidă

---

## 📈 Statistici și Analytics

### Dashboard Admin
- **Total articole** (publicate/draft)
- **Total comentarii** (aprobate/în așteptare)
- **Vizualizări** per articol
- **Utilizatori activi**
- **Spațiu disk utilizat**

### Tracking
- **Vizualizări articole** - incrementare la fiecare vizită
- **Surse trafic** - referrer tracking
- **Dispozitive** - desktop/mobile breakdown
- **Integrare Google Analytics** (opțional)

---

## ⚽ Scoruri Live (Faza 5)

### Funcționalități
- **Widget scoruri live** pentru sidebar/homepage
- **Auto-refresh** la intervale configurabile
- **Filtrare competiții** - Liga 1, UCL, Europa League
- **Status meciuri** - Live, Pauză, Terminat, Programat

### Surse Date
| Provider | Descriere |
|----------|-----------|
| Manual | Introdu scoruri din admin |
| API-Football | API comercial ($10-50/lună) |
| Football-Data.org | API cu tier gratuit |

### Admin Panel (`/admin/livescores.php`)
- Adăugare/editare meciuri manual
- Butoane rapide pentru actualizare scor
- Setare status meci

---

## ✍️ Contribuții Externe (Faza 5)

### Flux de Lucru
```
Cititor trimite articol → Pending → Editor revizuiește 
    → Aprobat/Respins → Publicare (opțional)
```

### Formular Contribuții (`/contribute.php`)
- **Informații autor** - nume, email, bio
- **Conținut articol** - titlu, excerpt, conținut complet
- **Imagine featured** - upload opțional
- **Categorie** - selecție din categoriile existente
- **Draft auto-save** - salvare în localStorage

### Status Tracking (`/submission-status.php`)
- **Token unic** per contribuție
- **Timeline vizuală** a statusului
- **Notificări email** la schimbarea statusului

### Admin Panel (`/admin/submissions.php`)
- **Listă contribuții** cu filtre (status, search)
- **Preview complet** al articolului
- **Aprobare/Respingere** cu feedback
- **Publicare directă** ca articol nou

---

## 📧 Newsletter

### Funcționalități
- **Abonare** cu double opt-in
- **Dezabonare** cu un click
- **Segmentare** pe categorii de interes
- **Template-uri email** responsive

### Automatizare
- **Email bun venit** la abonare
- **Digest săptămânal** cu cele mai citite articole
- **Notificare articol nou** pentru categorii favorite

---

## 🎯 Reclame (Ads)

### Poziții Disponibile
| Poziție | Dimensiune | Locație |
|---------|------------|---------|
| Header Banner | 728x90 | Sus pe toate paginile |
| Sidebar | 300x250 | Coloana dreaptă |
| In-Article | 336x280 | În mijlocul articolului |
| Footer | 728x90 | Footer, toate paginile |

### Funcționalități
- **Rotație** - multiple reclame pe aceeași poziție
- **Scheduling** - perioadă activă
- **Click tracking** - statistici clickuri
- **Ad blocker friendly** - fallback content

---

## 🗂️ Plan Editorial

### Calendar Editorial (`/admin/editorial-management.php`)
- **Planificare articole** pe zile/săptămâni
- **Categorii tip conținut** - Preview, Analiză, Transfer, etc.
- **Status** - Planificat, În Lucru, Finalizat
- **Drag & drop** pentru reorganizare

### Notificări
- **Reminder articole** programate pentru azi
- **Deadline approaching** - avertisment

---

## 🔧 Setări Admin

### Tab General
- Numele site-ului
- Descriere SEO
- Cuvinte cheie
- Email contact
- Text footer

### Tab Conținut
- Articole per pagină
- Comentarii activate/moderare
- Sondaje activate

### Tab Social Media
- Link-uri Facebook, Twitter, Instagram, YouTube

### Tab Avansat
- Cod Google Analytics
- Mod mentenanță (on/off cu mesaj)

### Tab Integrări (NOU)
- **Scoruri Live** - provider, API key, cache
- **Contribuții** - activare, moderare, email notificări

---

## 💾 Backup & Restore

### Backup Automat
- **Articole** - export HTML/JSON
- **Bază de date** - dump SQL
- **Media** - arhivă imagini
- **Configurări** - export setări

### Restore
- Import din backup anterior
- Verificare integritate date

---

## 🌐 SEO & Social

### Meta Tags Auto-generate
- **Title** - optimizat cu keywords
- **Description** - din excerpt sau primele 160 caractere
- **Keywords** - din tags + categorie
- **Canonical URL**

### Open Graph (Facebook)
```html
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="...">
<meta property="og:url" content="...">
```

### Twitter Cards
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="...">
<meta name="twitter:description" content="...">
```

### Structured Data
- Schema.org Article markup
- BreadcrumbList
- Organization

### Sitemap & RSS
- `/sitemap.php` - XML Sitemap dinamic
- `/rss.php` - Feed RSS pentru abonați

---

## 🚀 Performanță

### Caching
- **Page cache** - articole cu trafic mare
- **Query cache** - rezultate DB frecvente
- **Object cache** - configurări, categorii

### Optimizări
- **Lazy loading** imagini
- **Minificare** CSS/JS (producție)
- **Gzip compression**
- **Browser caching** - expires headers
- **CDN ready** - imagini pot fi servite de CDN

---

## 📱 Mobile & PWA

### Responsive Design
- Mobile-first approach
- Breakpoints: 576px, 768px, 992px, 1200px
- Touch-friendly navigation

### Progressive Web App
- `manifest.json` pentru instalare
- `service-worker.js` pentru offline
- Push notifications (opțional)

---

## 👥 Roluri Utilizatori

| Rol | Permisiuni |
|-----|------------|
| **Admin** | Acces complet: articole, utilizatori, setări, backup |
| **Editor** | Articole proprii, comentarii, sondaje, contribuții |

---

## 📂 Fișiere Cheie

### Configurare
| Fișier | Scop |
|--------|------|
| `config/config.php` | Constante globale (SITE_NAME, BASE_URL) |
| `config/database.php` | Clasa Database (MySQL/SQLite) |
| `config/security.php` | Clasa Security (CSRF, sanitizare) |
| `config/cache.php` | Sistem de caching |

### Modele (Includes)
| Fișier | Scop |
|--------|------|
| `includes/Post.php` | CRUD articole |
| `includes/Comment.php` | Sistem comentarii |
| `includes/User.php` | Autentificare, roluri |
| `includes/Poll.php` | Sondaje |
| `includes/Category.php` | Categorii |
| `includes/LiveScores.php` | API scoruri live |
| `includes/Submission.php` | Contribuții externe |
| `includes/Newsletter.php` | Abonamente email |
| `includes/Ad.php` | Reclame |
| `includes/Settings.php` | Setări dinamice |

### Admin Panel
| Fișier | Scop |
|--------|------|
| `admin/dashboard.php` | Pagina principală admin |
| `admin/posts.php` | Management articole |
| `admin/comments.php` | Moderare comentarii |
| `admin/polls.php` | Gestiune sondaje |
| `admin/livescores.php` | Scoruri live manual |
| `admin/submissions.php` | Review contribuții |
| `admin/settings.php` | Setări site |
| `admin/users.php` | Management utilizatori |

---

## 🔄 Migrări Bază de Date

### Fișiere de Migrare
| Fișier | Tabele Create |
|--------|---------------|
| `migrate.php` | posts, users, categories, comments |
| `migrate-phase4.php` | user_badges, comment_likes, push_subscriptions |
| `migrate-phase5.php` | live_matches, submissions |

### Rulare Migrări (producție)
```
https://matchday.ro/migrate-phase4.php
https://matchday.ro/migrate-phase5.php
```

⚠️ **Șterge fișierele de migrare după rulare!**

---

## 📞 Contact & Support

- **Email**: contact@matchday.ro
- **Website**: https://matchday.ro
- **Repository**: GitHub (privat)

---

## 📅 Istoric Versiuni

| Versiune | Data | Descriere |
|----------|------|-----------|
| 1.0 | Ianuarie 2026 | Lansare inițială |
| 2.0 | Aprilie 2026 | Actualizare completă (5 faze) |

### Faze Implementate

**Faza 1 - Quick Wins** ✅
- Search suggestions
- Lazy loading imagini
- Share buttons
- Paginare îmbunătățită

**Faza 2 - Core Improvements** ✅
- Comentarii nested cu like-uri
- Sistem cache avansat
- Dark mode

**Faza 3 - Advanced Features** ✅
- Sondaje interactive
- Gamification (badges)
- Plan editorial

**Faza 4 - Premium Features** ✅
- Sistem badge-uri utilizatori
- Push notifications
- Comment likes

**Faza 5 - External Integrations** ✅
- Scoruri live (manual/API)
- Contribuții externe cu review workflow
- Tab setări integrări

---

*Documentație actualizată: 8 Aprilie 2026*
