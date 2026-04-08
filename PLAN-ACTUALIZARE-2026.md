# 📋 Plan de Actualizare MatchDay.ro

**Versiune:** 1.0  
**Data:** 8 Aprilie 2026  
**Status:** În lucru

---

## 📊 Prioritizare

| Prioritate | Descriere |
|------------|-----------|
| 🔴 P1 | Critice - Impact major, implementare rapidă |
| 🟠 P2 | Importante - Îmbunătățiri vizibile |
| 🟡 P3 | Nice-to-have - Funcții avansate |
| 🟢 P4 | Viitor - Funcții premium |

---

## 🔴 FAZA 1: Quick Wins (1-2 săptămâni)

### 1.1 Titluri articole tăiate
- [x] **P1** Ajustare CSS carduri pentru 2-3 linii de titlu ✅
- [x] Testare pe toate dispozitivele (mobile, tablet, desktop) ✅
- **Fișiere:** `assets/css/style.css`

### 1.2 Contrast și lizibilitate
- [x] **P1** Îmbunătățire contrast fundal verde vs text alb/galben ✅
- [x] Verificare WCAG 2.1 pentru accesibilitate ✅
- **Fișiere:** `assets/css/style.css`

### 1.3 Sondaje - Feedback vizual
- [x] **P1** Adăugare bare de progres la sondaje ✅
- [x] Afișare număr total voturi ✅
- [x] Afișare procente pentru fiecare opțiune ✅
- [x] Link "Vezi rezultate" înainte de vot ✅
- **Fișiere:** `includes/Poll.php`, `assets/js/polls.js`, `assets/css/style.css`

### 1.4 Meta descrieri SEO
- [x] **P1** Meta descrieri unice pentru fiecare articol ✅ (exista deja)
- [x] Verificare titluri SEO (max 60 caractere) ✅ (exista deja)
- **Fișiere:** `includes/seo.php`, `post.php`

---

## 🟠 FAZA 2: Îmbunătățiri Core (2-4 săptămâni)

### 2.1 Imagini consistente
- [x] **P2** Placeholder default pentru articole fără imagine ✅
- [x] Optimizare imagini existente (compresie, lazy loading) ✅
- [x] Dimensiuni uniforme pentru thumbnail-uri ✅
- **Fișiere:** `includes/Post.php`, `assets/css/style.css`

### 2.2 Sidebar echilibrat
- [x] **P2** Redesign sidebar stâng ✅
- [x] Adăugare widget-uri utile (autori, taguri populare) ✅
- [x] Echilibrare vizuală cu sidebar-ul drept ✅
- **Fișiere:** `includes/sidebars.php`, `index.php`

### 2.3 Secțiunea "Despre"
- [x] **P2** Pagină completă: misiune, echipă, valori ✅
- [x] Prezentare autori (inclusiv profiluri) ✅
- [x] Povestea site-ului ✅
- **Fișiere:** `despre.php`

### 2.4 Formular Contact îmbunătățit
- [x] **P2** Formular complet cu validare ✅
- [x] Dropdown subiect și FAQ ✅
- [x] Confirmare vizuală la trimitere ✅
- **Fișiere:** `contact.php`, `send_contact.php`

### 2.5 Disclaimer și politici
- [x] **P2** Adăugare disclaimer pentru știri/surse ✅
- [x] Politică de confidențialitate (GDPR) ✅
- [x] Termeni și condiții ✅
- [x] Link-uri în footer către pagini legale ✅
- **Fișiere:** `disclaimer.php`, `privacy.php`, `termeni.php`, `includes/footer.php`

---

## 🟡 FAZA 3: Funcționalități Avansate (1-2 luni)

### 3.1 Sistem de filtrare avansată
- [x] **P3** Filtre: competiție, echipă, dată ✅
- [x] Filtre combinate ✅
- [x] Salvare preferințe utilizator (localStorage) ✅
- **Fișiere:** `search.php`, `assets/js/filters.js`

### 3.2 Căutare full-text îmbunătățită
- [x] **P3** Implementare căutare full-text în conținut ✅
- [x] Sugestii de căutare (autocomplete cu imagini) ✅
- [x] Highlight rezultate ✅
- [x] Navigare cu tastatura (săgeți + Enter) ✅
- **Fișiere:** `search.php`, `search-suggestions.php`, `assets/js/filters.js`

### 3.3 Profiluri autori
- [x] **P3** Pagină de profil pentru fiecare autor ✅
- [x] Bio, avatar, articole scrise ✅
- [x] Link-uri social media ✅
- [x] Statistici (articole, vizualizări, experiență) ✅
- **Fișiere:** `author.php`, `assets/css/style.css`

### 3.4 Schema.org pentru SEO
- [x] **P3** Markup NewsArticle pentru articole sportive ✅
- [x] Markup Organization cu sameAs ✅
- [x] Markup WebSite cu SearchAction ✅
- [x] BreadcrumbList pentru navigare ✅
- **Fișiere:** `includes/seo.php`

### 3.5 Taguri consistente
- [ ] **P3** Sistem de taguri standardizat
- [ ] Pagină de tag cu articole aferente
- [ ] Tag cloud în sidebar
- **Fișiere:** Nou: `tag.php`, update `admin/new-post.php`

---

## 🟢 FAZA 4: Funcții Premium (2-3 luni)

### 4.1 Sistem de comentarii
- [x] **P4** Comentarii pe articole cu răspunsuri ✅
- [x] Moderare automată (cuvinte interzise) ✅
- [x] Aprobare automată pentru trusted commenters ✅
- [x] Like-uri pe comentarii ✅
- **Fișiere:** `includes/Comment.php`, `comments_api.php`

### 4.2 Newsletter săptămânal
- [x] **P4** Template email profesional (HTML responsive) ✅
- [x] Cele mai citite articole automat ✅
- [x] Cele mai noi articole ✅
- [x] Statistici săptămânale ✅
- [x] Script cron pentru trimitere automată ✅
- **Fișiere:** `includes/Newsletter.php`, `cron/weekly-newsletter.php`

### 4.3 Notificări push
- [x] **P4** Service Worker pentru push notifications ✅
- [x] Handler-e pentru notificări push ✅
- [x] Click-to-open pe notificări ✅
- **Fișiere:** `service-worker.js`

### 4.4 Badge-uri și gamificare
- [x] **P4** Sistem complet de badge-uri (14 badge-uri) ✅
- [x] Puncte pentru activități ✅
- [x] Notificări pentru badge-uri noi câștigate ✅
- [x] Tracking local activitate utilizatori ✅
- [x] API pentru verificare și acordare badge-uri ✅
- [x] Leaderboard utilizatori ✅
- **Fișiere:** `includes/Badge.php`, `assets/js/gamification.js`, `badges_api.php`

### 4.5 Migrație bază de date
- [x] **P4** Tabel user_badges ✅
- [x] Tabel comment_likes ✅
- [x] Tabel newsletter_logs ✅
- [x] Tabel push_subscriptions ✅
- [x] Coloane noi: parent_id, likes pe comments ✅
- **Fișiere:** `migrate-phase4.php`

---

## 🎯 FAZA 5: Integrări Externe (3+ luni)

### 5.1 API Scoruri Live
- [x] **P4** Widget scoruri live pe homepage ✅
- [x] Suport API-Football sau Football-Data.org ✅
- [x] Mod manual pentru introducere scoruri ✅
- [x] Update automat rezultate cu cache ✅
- [x] Admin panel pentru scoruri manuale ✅
- **Fișiere:** `includes/LiveScores.php`, `assets/js/live-scores.js`, `livescores_api.php`, `admin/livescores.php`

### 5.2 "Scrie un articol" - Onboarding
- [x] **P4** Formular pentru contribuții externe ✅
- [x] Model complet Submission cu status workflow ✅
- [x] Pagină de verificare status cu token ✅
- [x] Notificări email pentru contributori ✅
- [x] Admin panel pentru review articole ✅
- [x] Workflow de aprobare/respingere/publicare ✅
- [x] Ghid pentru tineri jurnaliști (pe pagina contribute) ✅
- [x] Rate limiting pentru spam protection ✅
- **Fișiere:** `contribute.php`, `submission-status.php`, `includes/Submission.php`, `admin/submissions.php`

### 5.3 Migrație bază de date Faza 5
- [x] **P4** Tabel live_matches ✅
- [x] Tabel submissions ✅
- [x] Indexuri pentru performanță ✅
- **Fișiere:** `migrate-phase5.php`

---

## 📅 Timeline Estimată

```
Săptămâna 1-2:   Faza 1 (Quick Wins)
Săptămâna 3-6:   Faza 2 (Îmbunătățiri Core)
Luna 2-3:        Faza 3 (Funcționalități Avansate)
Luna 3-4:        Faza 4 (Funcții Premium)
Luna 5+:         Faza 5 (Integrări Externe)
```

---

## 📝 Checklist Consistență Conținut

### Categorii de populat (conținut minim 3 articole):
- [ ] Liga 1
- [ ] Champions League
- [ ] Transferuri
- [ ] Analize
- [ ] Interviuri

### Imagini:
- [ ] Toate articolele au imagine featured
- [ ] Dimensiuni uniforme (16:9 recomandat)
- [ ] Alt text pentru SEO

### Publicare regulată:
- [ ] Minim 2-3 articole/săptămână
- [ ] Calendar editorial activ
- [ ] Acoperire meciuri importante

---

## 🔧 Note Tehnice

### Fișiere principale de modificat:
- `assets/css/style.css` - Design și layout
- `includes/seo.php` - SEO și meta tags
- `includes/Poll.php` - Funcționalitate sondaje
- `index.php` - Homepage layout
- `post.php` - Pagină articol

### Dependențe externe de verificat:
- [ ] Versiune PHP (recomandată 8.1+)
- [ ] Versiune MySQL/SQLite
- [ ] Cache sistem funcțional

---

## ✅ Progres

| Fază | Status | Completat |
|------|--------|-----------|
| Faza 1 | ✅ Finalizat | 100% |
| Faza 2 | ✅ Finalizat | 100% |
| Faza 3 | ✅ Finalizat | 100% |
| Faza 4 | ✅ Finalizat | 100% |
| Faza 5 | ✅ Finalizat | 100% |

---

*Actualizat: 8 Aprilie 2026 - Plan complet finalizat!*
