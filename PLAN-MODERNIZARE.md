# 🚀 PLAN MODERNIZARE MATCHDAY.RO

**Data început:** 19 martie 2026  
**Status:** În progres

---

## 📊 Rezumat Situație Actuală

### ✅ Ce funcționează bine:
- SEO complet (Open Graph, Schema.org, Twitter Cards)
- Securitate solidă (CSRF, XSS, Argon2id, rate limiting)
- Design responsiv și curat
- Sondaje și comentarii interactive
- **Bază de date MySQL** pe Hostico (SQLite local)
- **Admin modern**: dashboard, CRUD articole, editor formatare
- Structură MVC cu modele (Post, Poll, Comment, User, Settings)
- **Multi-user** cu roluri Admin/Editor
- **Pagină setări** configurabilă din admin
- Media Library cu upload și browse

### ❌ Probleme rămase:
- Analytics/statistici vizitatori în timp real
- Căutare full-text avansată
- Newsletter integration

---

## 🎯 FAZA 1: Reparații Urgente
**Durată estimată:** 1 zi  
**Status:** ✅ COMPLETAT

| # | Task | Status | Notițe |
|---|------|--------|--------|
| 1.1 | Șterge blocul DEBUG din index.php | ✅ DONE | Linii 24-32 șterse |
| 1.2 | Adaugă .htaccess în /data/ pentru securitate | ✅ DONE | Blochează acces JSON |
| 1.3 | Fix CSRF pentru polls-actions.php | ✅ DONE | + polls-manager.php + header.php |
| 1.4 | Testare locală | ✅ DONE | PHP syntax OK |
| 1.5 | Deploy pe Hostico | ✅ DONE | Commit d12f4e2 |

---

## 🎯 FAZA 2: Migrare la MySQL/SQLite
**Durată estimată:** 2-3 zile  
**Status:** ✅ COMPLETAT

| # | Task | Status | Notițe |
|---|------|--------|--------|
| 2.1 | Creare structură bază de date | ✅ DONE | config/database.php |
| 2.2 | Clasa Database.php | ✅ DONE | Singleton PDO + helpers |
| 2.3 | Model Post.php | ✅ DONE | CRUD complet + search |
| 2.4 | Model Poll.php | ✅ DONE | CRUD + voting |
| 2.5 | Model Comment.php | ✅ DONE | CRUD + moderation |
| 2.6 | Model User.php | ⬜ TODO | Multi-user auth |
| 2.7 | Script migrare date existente | ✅ DONE | migrate.php - 7 posts, 4 polls |
| 2.8 | Refactor index.php | ✅ DONE | Folosește DB |
| 2.9 | Refactor category.php | ✅ DONE | Folosește DB |
| 2.10 | Refactor polls_api.php | ✅ DONE | Folosește Poll model |
| 2.11 | Refactor comments_api.php | ✅ DONE | Folosește Comment model |
| 2.12 | Creat post.php | ✅ DONE | Afișare articol individual |
| 2.13 | **Suport MySQL Hostico** | ✅ DONE | opnwyzqa_matchday |
| 2.14 | Migrare date pe Hostico | ✅ DONE | 10 posts, 4 polls, 7 categorii |
| 2.15 | Deploy și testare | ✅ DONE | Site live cu MySQL |

---

## 🎯 FAZA 3: Admin Panel Complet
**Durată estimată:** 3-4 zile  
**Status:** ✅ COMPLETAT

| # | Task | Status | Notițe |
|---|------|--------|--------|
| 3.1 | Dashboard refactorizat | ✅ DONE | Folosește DB, statistici live |
| 3.2 | Lista articole cu paginare | ✅ DONE | admin/posts.php - CRUD complet |
| 3.3 | Editor articole cu formatare | ✅ DONE | Toolbar HTML integrat |
| 3.4 | Editare articole existente | ✅ DONE | admin/edit-post.php |
| 3.5 | Save articole în DB | ✅ DONE | admin/save-post.php refactorizat |
| 3.6 | Media Library | ✅ DONE | Upload, browse, delete, copy URL |
| 3.7 | CRUD Categorii din admin | ✅ DONE | Category model + admin/categories.php |
| 3.8 | Sistem multi-user | ✅ DONE | User.php + admin/users.php |
| 3.9 | Dashboard comentarii | ✅ DONE | Bulk approve/delete, filtre |
| 3.10 | Editor sondaje îmbunătățit | ✅ DONE | Refactorizat cu Poll model DB |
| 3.11 | Pagină setări site | ✅ DONE | Settings.php + admin/settings.php |
| 3.12 | Deploy Faza 3 | ✅ DONE | Commit a7f2a31 |

---

## 🎯 FAZA 4: Features Avansate
**Durată estimată:** 2-3 zile  
**Status:** 🔄 În progres

| # | Task | Status | Notițe |
|---|------|--------|--------|
| 4.1 | **Analytics Dashboard** | ✅ DONE | Stats model + visitor tracking |
| 4.2 | **Căutare full-text** | ✅ DONE | FULLTEXT MySQL + relevance scoring |
| 4.3 | **Articole similare** | ✅ DONE | Algoritm tags + categorie |
| 4.4 | **Newsletter** | ✅ DONE | Subscriberi DB + admin send |
| 4.5 | **Social share manual** | ✅ DONE | Butoane share pe articole |
| 4.6 | **PWA (Service Worker)** | ✅ DONE | manifest.json + offline support |
| 4.7 | **Backup automat** | ✅ DONE | JSON/SQL/ZIP export + restore |
| 4.8 | Testare completă | ✅ DONE | Fix SITE_URL + Post::getSimilar |
| 4.9 | Deploy final | ✅ DONE | Toate feature-urile funcționale |

---

## 🎯 FAZA 5: Admin Modern + Reclame
**Durată estimată:** 2-3 zile  
**Status:** ✅ COMPLETAT

### 🧱 5.1 Reorganizarea Dashboard-ului Admin
| # | Task | Status | Notițe |
|---|------|--------|--------|
| 5.1.1 | Creare layout cu sidebar fix stânga | ✅ DONE | 260px, admin-header.php |
| 5.1.2 | Design sidebar modern cu iconițe | ✅ DONE | FontAwesome + dark theme #1a1d21 |
| 5.1.3 | Zona principală scrollabilă dreapta | ✅ DONE | margin-left responsive |
| 5.1.4 | Responsive pentru mobil | ✅ DONE | Hamburger menu @991px |
| 5.1.5 | Refactor toate paginile admin | ✅ DONE | 15+ pagini actualizate |

**Meniu Sidebar propus:**
```
📊 Dashboard
➕ Articol nou
📝 Articole
📂 Categorii
🖼️ Media
📊 Sondaje
👤 Utilizatori
📈 Statistici
📧 Newsletter
📢 Reclame / Sponsori  ← NOU
💾 Backup
⚙️ Setări
📅 Plan Editorial
🚪 Delogare
```

### 📢 5.2 Modul Gestionare Reclame / Sponsori
| # | Task | Status | Notițe |
|---|------|--------|--------|
| 5.2.1 | Creare tabel `ads` în DB | ✅ DONE | MySQL + SQLite schema |
| 5.2.2 | Model Ad.php | ✅ DONE | CRUD + getActive + stats |
| 5.2.3 | Admin: admin/ads.php | ✅ DONE | Lista, add, edit, delete, toggle |
| 5.2.4 | Upload imagine banner | ✅ DONE | /assets/uploads/ads/ |
| 5.2.5 | Poziții disponibile | ✅ DONE | 5 poziții (sidebar, header, footer, article-inline, article-content) |
| 5.2.6 | Perioadă activare (start/end) | ✅ DONE | Date picker în modal |
| 5.2.7 | Status activ/inactiv | ✅ DONE | Toggle instant |
| 5.2.8 | Tracking clicks + impressions | ✅ DONE | ad-click.php + CTR stats |

**Schema MySQL propusă:**
```sql
CREATE TABLE ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    link VARCHAR(500),
    position ENUM('sidebar','header','footer','article-inline') DEFAULT 'sidebar',
    start_date DATE,
    end_date DATE,
    active TINYINT(1) DEFAULT 1,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME
);
```

### 🧩 5.3 Integrare Front-end
| # | Task | Status | Notițe |
|---|------|--------|--------|
| 5.3.1 | Helper Ad::getActive($position) | ✅ DONE | Filtrare poziție + date |
| 5.3.2 | Widget sidebar reclame | ✅ DONE | AdWidget::sidebar() |
| 5.3.3 | Banner header (opțional) | ✅ DONE | AdWidget::header() |
| 5.3.4 | Banner între articole | ✅ DONE | AdWidget::articleInline() |
| 5.3.5 | Tracking click redirect | ✅ DONE | /ad-click.php?id=X |

### 🎨 5.4 Template-uri Banner
| # | Task | Status | Notițe |
|---|------|--------|--------|
| 5.4.1 | Banner 300x250 (sidebar) | ✅ DONE | .ad-sidebar class |
| 5.4.2 | Banner 728x90 (header) | ✅ DONE | .ad-header-banner class |
| 5.4.3 | Banner 320x100 (mobile) | ✅ DONE | Responsive img-fluid |
| 5.4.4 | Card sponsor cu logo+text | ✅ DONE | AdWidget::sidebar() |

---

## 📁 Structura Nouă Propusă

```
matchday/
├── admin/
│   ├── index.php          # Dashboard
│   ├── posts.php          # Lista articole
│   ├── post-editor.php    # Creare/Editare articol
│   ├── categories.php     # Gestiune categorii
│   ├── media.php          # Media library
│   ├── comments.php       # Moderare comentarii
│   ├── polls.php          # Gestiune sondaje
│   ├── users.php          # Gestiune utilizatori
│   ├── settings.php       # Setări site
│   └── api/
│       ├── posts.php
│       ├── upload.php
│       └── stats.php
├── api/
│   ├── posts.php          # API public articole
│   ├── polls.php          # API sondaje
│   └── comments.php       # API comentarii
├── assets/
│   ├── css/
│   ├── js/
│   ├── uploads/
│   └── tinymce/
├── config/
│   ├── config.php
│   ├── database.php
│   └── routes.php
├── includes/
│   ├── Database.php
│   ├── Post.php
│   ├── Poll.php
│   ├── User.php
│   ├── Auth.php
│   └── helpers.php
├── data/
│   └── matchday.db        # SQLite database
├── index.php
├── post.php
├── category.php
└── search.php
```

---

## 📝 Structura Bază de Date SQLite

```sql
-- Articole
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT,
    excerpt TEXT,
    category TEXT,
    cover_image TEXT,
    tags TEXT,
    status TEXT DEFAULT 'draft',
    views INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    published_at DATETIME
);

-- Sondaje
CREATE TABLE polls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    question TEXT NOT NULL,
    description TEXT,
    options TEXT,
    active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Voturi sondaje
CREATE TABLE poll_votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    poll_id INTEGER,
    option_id TEXT,
    ip_hash TEXT,
    voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id)
);

-- Comentarii
CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_slug TEXT,
    author_name TEXT NOT NULL,
    content TEXT NOT NULL,
    ip_hash TEXT,
    approved INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Utilizatori
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'editor',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- Statistici vizualizări
CREATE TABLE stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER,
    date DATE,
    views INTEGER DEFAULT 0,
    unique_visitors INTEGER DEFAULT 0,
    FOREIGN KEY (post_id) REFERENCES posts(id)
);

-- Categorii (pentru CRUD din admin)
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    color TEXT DEFAULT '#007bff',
    icon TEXT DEFAULT 'fas fa-folder',
    sort_order INTEGER DEFAULT 0
);
```

---

## 📅 Timeline

```
Săptămâna 1:
├── Ziua 1: FAZA 1 (Fix-uri urgente) ← ACUM
├── Ziua 2-3: FAZA 2 (SQLite setup + migrare)
└── Ziua 4-5: FAZA 2 (Refactor APIs)

Săptămâna 2:
├── Ziua 6-8: FAZA 3 (Admin panel)
└── Ziua 9-10: FAZA 3 (Editor, Media, Users)

Săptămâna 3:
├── Ziua 11-12: FAZA 4 (Search, Related posts)
└── Ziua 13-14: FAZA 4 (Analytics, PWA, Polish)
```

---

## 🔄 Log Modificări

| Data | Faza | Modificare |
|------|------|------------|
| 19.03.2026 | - | Plan creat |
| | | |

