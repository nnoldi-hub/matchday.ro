# 🚀 MatchDay.ro - Deployment Configuration

## 📋 Informații Hostico necesare

Pentru ca GitHub Actions să funcționeze, ai nevoie de următoarele informații de la Hostico:

### 🔐 Secrets de configurat în GitHub:

1. **HOSTICO_FTP_SERVER** - Serverul FTP (ex: `ftp.hostico.ro` sau IP-ul serverului)
2. **HOSTICO_FTP_USERNAME** - Username-ul tău FTP de la Hostico  
3. **HOSTICO_FTP_PASSWORD** - Parola FTP de la Hostico
4. **HOSTICO_SSH_HOST** - Host SSH (opțional, pentru comenzi post-deployment)
5. **HOSTICO_SSH_USERNAME** - Username SSH (opțional)
6. **HOSTICO_SSH_PASSWORD** - Parola SSH (opțional)
7. **HOSTICO_SSH_PORT** - Portul SSH (default: 22, opțional)

### 📝 Cum găsești aceste informații:

#### FTP Settings:
1. Loghează-te în cPanel Hostico
2. Mergi la **File Manager** sau **FTP Accounts**
3. Notează:
   - Server: `ftp.hostico.ro` (sau domeniul tău)
   - Username: username-ul tău de hosting
   - Password: parola de hosting

#### SSH Settings (opțional):
1. În cPanel → **SSH Access**
2. Activează SSH dacă nu e deja activ
3. Notează detaliile de conexiune

### 🛠️ Configurare în GitHub:

1. Mergi la repository-ul tău pe GitHub
2. **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Adaugă fiecare secret cu valorile de la Hostico

### 🔄 Testare:

După configurare, fă un commit și push:
```bash
git add .
git commit -m "Configure GitHub Actions deployment"
git push origin main
```

GitHub Actions va rula automat și va deploya site-ul pe Hostico!

### 📞 Support:

Dacă ai nevoie de ajutor cu configurarea:
- **Email:** contact@matchday.ro
- **Telefon:** 0740 173 581
