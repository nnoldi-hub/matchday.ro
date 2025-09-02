# 🚀 Ghid pentru Încărcarea Site-ului MatchDay.ro Online

## 📋 **Pași pentru deployment:**

### 1. **Alegerea Hosting-ului**
- **✅ FOLOSEȘTI**: Hostico cu cPanel 
- **Cerințe îndeplinite**:
  - ✅ PHP 8.1+
  - ✅ Support pentru Composer
  - ✅ SSL certificate (Let's Encrypt)
  - ✅ cPanel pentru management email

### 2. **Pregătirea Fișierelor**

#### A. **Configurarea Email-ului**
Editează `config/config.php`:
```php
// Schimbă email-ul pentru contact
define('CONTACT_TO_EMAIL', 'contact@matchday.ro'); // OBLIGATORIU
```

#### B. **Configurarea SMTP în `send_contact.php`**
```php
$mail->Host       = 'mail.matchday.ro'; // SMTP-ul hostingului
$mail->Username   = 'contact@matchday.ro'; // Email-ul tău
$mail->Password   = 'ParolaEmailTau';      // Parola email-ului
```

**Pentru Gmail/Google Workspace:**
```php
$mail->Host       = 'smtp.gmail.com';
$mail->Username   = 'contact@matchday.ro';
$mail->Password   = 'app-password';  // App Password, nu parola normală
```

### 3. **Upload-ul Fișierelor**

#### Fișiere de încărcat:
```
📁 public_html/
├── 📁 admin/
├── 📁 assets/
├── 📁 config/
├── 📁 includes/
├── 📁 posts/
├── 📁 vendor/      ← PHPMailer
├── 📄 *.php
└── 📄 composer.json
```

#### Fișiere de NU încărcat:
- `📁 data/` (va fi creat automat)
- `📄 DEPLOYMENT_GUIDE.md`
- `📄 README.md`

### 4. **Configurarea pe Server**

#### A. **Permisiuni folder**
```bash
chmod 755 data/
chmod 755 data/comments/
chmod 755 data/contact_messages/
chmod 755 assets/uploads/
```

#### B. **Instalarea PHPMailer**
Dacă hostingul permite Composer:
```bash
composer install --no-dev
```

Sau upload manual folder `vendor/` cu PHPMailer.

#### C. **Configurarea .htaccess** (dacă nu există)
```apache
# Securitate
<Files "config.php">
    deny from all
</Files>

<Files "*.json">
    deny from all
</Files>

# Pretty URLs (opțional)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ posts/$1.html [L]
```

### 5. **Testarea Email-ului**

#### Pentru Gmail:
1. Activează **2-Factor Authentication**
2. Generează **App Password** în setările Google
3. Folosește App Password în loc de parola normală

#### Pentru Hostico cPanel:
1. **Creează email** în cPanel → Email Accounts
2. **Adaugă** `contact@matchday.ro` cu parolă sigură
3. **Setările SMTP** sunt automat:
   - **Host**: `mail.matchday.ro`
   - **Port**: `465` (SSL) sau `587` (TLS)
   - **Auth**: Da
4. **Configurația din cod**:
   ```php
   $mail->Host = 'mail.matchday.ro';
   $mail->Username = 'contact@matchday.ro';
   $mail->Password = 'parola-din-cpanel';
   $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
   $mail->Port = 465;
   ```

### 6. **Verificări Post-Deploy**

#### 🔧 **Script automat de verificare:**
1. Accesează `https://site-ul-tau.ro/check_deployment.php`
2. Verifică că toate punctele sunt ✅
3. **IMPORTANT**: Șterge fișierul după verificare!

#### ✅ **Checklist manual:**
- [ ] Site-ul se încarcă corect
- [ ] Admin login funcționează (`/admin/login.php`)
- [ ] Comentariile se salvează și apar după aprobare
- [ ] Sistemul de contact trimite email-uri
- [ ] SSL certificatul este activ (HTTPS)
- [ ] Permisiunile folder-elor sunt corecte (755)
- [ ] Fișierul `check_deployment.php` a fost șters

### 7. **Configurări de Securitate**

#### A. **Schimbarea parolei admin**
Rulează în terminal sau pe server:
```php
<?php
echo password_hash('NovaParolaSecreta', PASSWORD_ARGON2ID);
?>
```

#### B. **Activarea HTTPS**
În `config/config.php`:
```php
define('BASE_URL', 'https://matchday.ro');
```

### 8. **Specific pentru Hostico**

#### A. **Upload prin File Manager**
1. Accesează cPanel → File Manager
2. Navighează la `public_html/`
3. Upload toate fișierele (exclude `data/`, `README.md`)
4. Setează permisiuni 755 pentru directoare

#### B. **Configurare Email în cPanel**
1. **Email Accounts** → Create new account
2. **Email**: `contact@matchday.ro`
3. **Parolă**: Folosește o parolă sigură
4. **Quota**: Unlimited sau 1GB+

#### C. **Instalare PHPMailer**
Dacă Composer nu e disponibil:
1. Upload manual folder `vendor/`
2. Sau contactează suportul Hostico pentru activare Composer

#### D. **Testare conexiune SMTP**
În cPanel → Email → Email Deliverability
- Verifică că toate sunt ✅ verde

## 🔧 **Troubleshooting pentru Hostico**

### Email nu funcționează:
1. **Verifică în cPanel** → Email Accounts că email-ul există
2. **Testează** cu un client email (Outlook/Thunderbird)
3. **Verifică logs** în cPanel → Error Logs
4. **Contactează** suportul Hostico dacă persistă

### Site nu se încarcă:
1. **Verifică** că fișierele sunt în `public_html/`
2. **Verifică** versiunea PHP în cPanel (minim 8.1)
3. **Verifică** Error Logs pentru detalii

### Permisiuni:
```bash
# În File Manager sau prin SSH
chmod 755 data/
chmod 755 data/comments/
chmod 755 data/contact_messages/
chmod 755 assets/uploads/
```

#### A. **Log-uri**
- Verifică `error_log` pentru erori
- Monitorizează folder-ul `data/contact_messages/`

#### B. **Backup**
- Backup zilnic al folder-ului `data/`
- Backup săptămânal complet

## 🔧 **Troubleshooting Comun**

### Email nu se trimite:
1. Verifică credențialele SMTP
2. Testează cu `send_contact.php` direct
3. Verifică log-urile server-ului

### Comentariile nu se salvează:
1. Verifică permisiunile folder-ului `data/`
2. Verifică că PHP poate scrie în `data/comments/`

### Erori de încărcare:
1. Verifică versiunea PHP (minim 8.1)
2. Verifică că toate fișierele sunt încărcate
3. Verifică extensiile PHP necesare

## 📧 **Exemplu Configurare Email Completă**

### Gmail/Google Workspace:
```php
$mail->Host       = 'smtp.gmail.com';
$mail->Username   = 'contact@matchday.ro';
$mail->Password   = 'abcd efgh ijkl mnop'; // App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;
```

### Hostico cPanel:
```php
$mail->Host       = 'mail.matchday.ro';
$mail->Username   = 'contact@matchday.ro';
$mail->Password   = 'ParolaEmailTau';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL pentru port 465
$mail->Port       = 465;
```

---

**💡 Tip:** După deploy, testează întotdeauna funcționalitatea de contact și comentarii pentru a te asigura că totul funcționează corect!
