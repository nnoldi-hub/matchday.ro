# ⚽ MatchDay.ro

**Jurnalul online dedicat fotbalului românesc și internațional**

## 📖 Despre Proiect

MatchDay.ro este o platformă de jurnalism sportiv creată de **David Nyikora**, dedicată pasionaților de fotbal care doresc analize de calitate, comentarii la meciuri și perspective unice asupra lumii fotbalului.

### ✨ Caracteristici

- 📝 **Sistem de articole** cu editor vizual
- 📊 **Sondaje interactive** pentru cititori
- 💬 **Sistem de comentarii** cu moderare
- 📅 **Calendar editorial** pentru planificare
- 🔍 **SEO complet** optimizat pentru motoarele de căutare
- 📱 **Design responsive** pentru toate dispozitivele
- ⚡ **Performanță optimizată** cu cache
- 🔐 **Securitate avansată** cu protecții CSRF

### 🛠️ Stack Tehnic

- **Backend:** PHP 8.3+
- **Frontend:** HTML5, CSS3, JavaScript ES6+
- **Framework CSS:** Bootstrap 5
- **Database:** JSON files (fără bază de date)
- **Cache:** File-based caching system
- **SEO:** Meta tags, Open Graph, JSON-LD

### 🚀 Instalare și Configurare

1. **Clone repository:**
```bash
git clone https://github.com/username/matchday.ro.git
cd matchday.ro
```

2. **Configurare:**
```bash
cp config/config.example.php config/config.php
# Editează config.php cu setările tale
```

3. **Permisiuni:**
```bash
chmod 755 data/
chmod 755 assets/uploads/
```

4. **Configurare server web:**
   - Apache cu mod_rewrite activat
   - PHP 8.3+ cu extensiile: json, fileinfo, filter

### 📁 Structura Proiectului

```
matchday.ro/
├── admin/              # Panoul de administrare
├── assets/             # CSS, JS, imagini
├── config/             # Configurări
├── data/               # Date JSON, cache
├── includes/           # Fișiere PHP comune
├── posts/              # Articolele publicate
├── vendor/             # Dependențe externe (PHPMailer)
└── *.php               # Paginile principale
```

### 🎯 Funcționalități Admin

- ✅ Dashboard cu statistici
- ✅ Gestionare articole (CRUD complet)
- ✅ Sistem de sondaje cu statistici
- ✅ Management comentarii cu moderare
- ✅ Calendar editorial cu tracking
- ✅ Optimizări SEO integrate

### 📞 Contact

**David Nyikora** - Jurnalist sportiv
- 📧 Email: contact@matchday.ro
- 📱 Telefon: 0740 173 581
- 🌐 Website: [matchday.ro](https://matchday.ro)

### 📄 Licență

© 2025 MatchDay.ro - Toate drepturile rezervate.

---

**MatchDay.ro** - *Fiecare meci are o poveste. Noi o scriem.*
