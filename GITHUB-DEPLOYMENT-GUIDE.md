# 🚀 GHID COMPLET: GitHub → Hostico Deployment

## 📋 **PASUL 1: PREGĂTEȘTE PROIECTUL LOCAL**

### În Windows (cmd/PowerShell):
```cmd
cd C:\wamp64\www\david-fc-journal
setup-git.bat
```

### În Linux/Mac:
```bash
cd /path/to/david-fc-journal
chmod +x setup-git.sh
./setup-git.sh
```

---

## 🌐 **PASUL 2: CREEAZĂ REPOSITORY PE GITHUB**

1. **Mergi pe GitHub.com** și loghează-te
2. **Click pe "+" → "New repository"**
3. **Setează:**
   - Repository name: `matchday.ro` (sau alt nume)
   - Description: `⚽ MatchDay.ro - Jurnalul fotbalului românesc`
   - Visibility: **Public** (recomandat) sau Private
   - ❌ **NU** bifa "Add README" (avem deja fișiere)

4. **Click "Create repository"**

---

## 🔗 **PASUL 3: CONECTEAZĂ LOCAL CU GITHUB**

Copiază comenzile din GitHub și rulează-le local:

```bash
# Setează remote origin
git remote add origin https://github.com/USERNAME/matchday.ro.git

# Setează branch-ul principal
git branch -M main

# Prima încărcare pe GitHub
git push -u origin main
```

---

## 🔐 **PASUL 4: CONFIGUREAZĂ SECRETS PENTRU HOSTICO**

### 4.1 Obține informațiile de la Hostico:

**Loghează-te în cPanel Hostico și găsește:**
- 📧 **FTP Server:** `ftp.hostico.ro` (sau IP-ul tău)
- 👤 **FTP Username:** username-ul tău de hosting
- 🔑 **FTP Password:** parola de hosting
- 📁 **FTP Directory:** `public_html/` (de obicei)

### 4.2 Adaugă Secrets în GitHub:

1. **În repository GitHub** → **Settings** → **Secrets and variables** → **Actions**
2. **Click "New repository secret"** și adaugă:

| Secret Name | Value Example | Descriere |
|-------------|---------------|-----------|
| `HOSTICO_FTP_SERVER` | `ftp.hostico.ro` | Server FTP Hostico |
| `HOSTICO_FTP_USERNAME` | `username123` | Username FTP |
| `HOSTICO_FTP_PASSWORD` | `parola_ta_sigura` | Parola FTP |

### 4.3 Opțional - SSH pentru comenzi avansate:
| Secret Name | Value | Când e necesar |
|-------------|-------|----------------|
| `HOSTICO_SSH_HOST` | IP server | Pentru clear cache automat |
| `HOSTICO_SSH_USERNAME` | username SSH | Pentru comenzi post-deploy |
| `HOSTICO_SSH_PASSWORD` | parola SSH | Pentru setare permisiuni |

---

## 🚀 **PASUL 5: TESTEAZĂ DEPLOYMENT-UL**

### 5.1 Trigger manual:
1. **GitHub repository** → **Actions** tab
2. **Click pe "🚀 Deploy MatchDay.ro to Hostico"**
3. **Click "Run workflow"** → **"Run workflow"**

### 5.2 Urmărește progresul:
- ✅ **Verde** = Success!
- ❌ **Roșu** = Eroare (verifică logs)
- 🟡 **Galben** = În progres

---

## 🔄 **WORKFLOW ZILNIC (DUPĂ CONFIGURARE):**

```bash
# 1. Modifici fișierele local
# 2. Commit și push:
git add .
git commit -m "Articol nou: Analiza meciului România vs Germania"
git push

# 3. GitHub Actions va face deployment automat pe Hostico! 🎉
```

---

## 🆘 **TROUBLESHOOTING:**

### ❌ **FTP Connection Failed**
- Verifică dacă HOSTICO_FTP_* secrets sunt corecte
- Testează FTP manual cu FileZilla

### ❌ **Permission Denied**
- Verifică dacă directorul `public_html` există pe Hostico
- Contactează support Hostico pentru verificarea permisiunilor

### ❌ **Site nu funcționează după deploy**
- Verifică că `config/config.php` are setările corecte pentru producție
- Verifică că directorul `data/` are permisiuni de scriere (755)

---

## 🎉 **REZULTAT FINAL:**

După configurare, **fiecare modificare** pe GitHub va actualiza **automat** site-ul pe Hostico în ~2-3 minute!

**Site-ul va fi disponibil la:** `https://matchday.ro` (sau domeniul tău)

---

## 📞 **SUPPORT:**

**David Nyikora**  
📧 contact@matchday.ro  
📱 0740 173 581
