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
- [ ] **P2** Placeholder default pentru articole fără imagine
- [ ] Optimizare imagini existente (compresie, lazy loading)
- [ ] Dimensiuni uniforme pentru thumbnail-uri
- **Fișiere:** `includes/Post.php`, `assets/css/style.css`

### 2.2 Sidebar echilibrat
- [ ] **P2** Redesign sidebar stâng
- [ ] Adăugare widget-uri utile (autori, taguri populare)
- [ ] Echilibrare vizuală cu sidebar-ul drept
- **Fișiere:** `includes/sidebars.php`, `index.php`

### 2.3 Secțiunea "Despre"
- [ ] **P2** Pagină completă: misiune, echipă, valori
- [ ] Prezentare autori (inclusiv profiluri)
- [ ] Povestea site-ului
- **Fișiere:** `despre.php`

### 2.4 Formular Contact îmbunătățit
- [ ] **P2** Formular complet cu validare
- [ ] Integrare reCAPTCHA (dacă nu există)
- [ ] Confirmare vizuală la trimitere
- **Fișiere:** `contact.php`, `send_contact.php`

### 2.5 Disclaimer și politici
- [ ] **P2** Adăugare disclaimer pentru știri/surse
- [ ] Politică de confidențialitate
- [ ] Termeni și condiții
- **Fișiere:** Pagini noi: `disclaimer.php`, `privacy.php`, `terms.php`

---

## 🟡 FAZA 3: Funcționalități Avansate (1-2 luni)

### 3.1 Sistem de filtrare avansată
- [ ] **P3** Filtre: competiție, echipă, dată
- [ ] Filtre combinate
- [ ] Salvare preferințe utilizator
- **Fișiere:** `search.php`, `category.php`, nou: `assets/js/filters.js`

### 3.2 Căutare full-text îmbunătățită
- [ ] **P3** Implementare căutare full-text în conținut
- [ ] Sugestii de căutare (autocomplete)
- [ ] Highlight rezultate
- **Fișiere:** `search.php`, `search-suggestions.php`

### 3.3 Profiluri autori
- [ ] **P3** Pagină de profil pentru fiecare autor
- [ ] Bio, avatar, articole scrise
- [ ] Link-uri social media
- **Fișiere:** Nou: `author.php`, `includes/User.php`

### 3.4 Schema.org pentru SEO
- [ ] **P3** Markup pentru articole sportive
- [ ] Markup pentru evenimente (meciuri)
- [ ] Markup pentru organizație
- **Fișiere:** `includes/seo.php`, `post.php`

### 3.5 Taguri consistente
- [ ] **P3** Sistem de taguri standardizat
- [ ] Pagină de tag cu articole aferente
- [ ] Tag cloud în sidebar
- **Fișiere:** Nou: `tag.php`, update `admin/new-post.php`

---

## 🟢 FAZA 4: Funcții Premium (2-3 luni)

### 4.1 Sistem de comentarii
- [ ] **P4** Comentarii pe articole
- [ ] Moderare automată (cuvinte interzise)
- [ ] Aprobare manuală pentru primii comentatori
- [ ] Notificări pentru răspunsuri
- **Fișiere:** Există deja: `includes/Comment.php`, `comments_api.php`

### 4.2 Newsletter săptămânal
- [ ] **P4** Template email profesional
- [ ] Cele mai citite articole automat
- [ ] Rezultate importante
- [ ] Programare automată (cron)
- **Fișiere:** `includes/Newsletter.php`, nou: `cron/weekly-newsletter.php`

### 4.3 Notificări push
- [ ] **P4** Service Worker pentru push notifications
- [ ] Notificări pentru rezultate live
- [ ] Notificări pentru articole noi
- **Fișiere:** `service-worker.js`, nou: `assets/js/push-notifications.js`

### 4.4 Badge-uri articole populare
- [ ] **P4** Sistem de badge-uri: "Popular", "Trending", "Editor's Pick"
- [ ] Afișare vizuală pe carduri
- [ ] Algoritm pentru "Trending"
- **Fișiere:** `includes/Post.php`, `assets/css/style.css`

### 4.5 Gamificare cititori
- [ ] **P4** Puncte pentru citire, comentarii, share
- [ ] Top cititori
- [ ] Achievements/trofee
- **Fișiere:** Nou: `includes/Gamification.php`, `assets/js/gamification.js`

---

## 🎯 FAZA 5: Integrări Externe (3+ luni)

### 5.1 API Scoruri Live
- [ ] **P4** Integrare API-Football sau LiveScore
- [ ] Widget scoruri live pe homepage
- [ ] Update automat rezultate
- **Estimare cost:** ~$10-50/lună pentru API

### 5.2 "Scrie un articol" - Onboarding
- [ ] **P4** Formular pentru contribuții externe
- [ ] Workflow de aprobare
- [ ] Ghid pentru tineri jurnaliști
- **Fișiere:** Nou: `contribute.php`, `admin/submissions.php`

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
| Faza 2 | ⏳ Planificat | 0% |
| Faza 3 | ⏳ Planificat | 0% |
| Faza 4 | ⏳ Planificat | 0% |
| Faza 5 | ⏳ Planificat | 0% |

---

*Actualizat: 8 Aprilie 2026*
