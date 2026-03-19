# Configurare Email pentru Producție

## 📧 Problema actuală
Sistemul curent folosește funcția PHP `mail()` care **NU funcționează** pe majoritatea hostingurilor online.

## ✅ Soluția recomandată

### 1. Instalează PHPMailer
```bash
composer require phpmailer/phpmailer
```

### 2. Configurează SMTP în `send_contact.php`

Găsește această secțiune în `send_contact.php`:
```php
// Configurare SMTP - ACTUALIZEAZĂ ACESTE SETĂRI PENTRU HOSTINGUL TĂU
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com'; // sau smtp-ul hostingului tău
$mail->SMTPAuth   = true;
$mail->Username   = 'your-email@gmail.com'; // SCHIMBĂ
$mail->Password   = 'your-app-password';    // SCHIMBĂ
```

### 3. Opțiuni pentru SMTP:

#### A) Gmail SMTP:
- Host: `smtp.gmail.com`
- Port: `587`
- Username: adresa ta Gmail
- Password: [App Password](https://support.google.com/accounts/answer/185833)

#### B) Hostingul tău:
- Host: `mail.domeniul-tau.ro` (întreabă hostingul)
- Port: `587` sau `465`
- Username: adresa ta email
- Password: parola email-ului

#### C) SendGrid (recomandat pentru volume mari):
- Host: `smtp.sendgrid.net`
- Port: `587`
- Username: `apikey`
- Password: API Key-ul tău SendGrid

### 4. Testare locală
Pentru testare locală, sistemul va folosi funcția `mail()` nativă PHP.

### 5. Pentru producție
Actualizează variabilele din secțiunea SMTP cu datele reale și asigură-te că PHPMailer este instalat.

## 🔒 Securitate
Codul include:
- ✅ CSRF protection
- ✅ Rate limiting
- ✅ Input validation
- ✅ Honeypot pentru spam
- ✅ Headers corecte pentru deliverability

## 📝 Status actual
- ✅ Sistem securizat implementat
- ✅ PHPMailer instalat și funcțional (v6.10.0)
- ❌ SMTP nu este configurat pentru producție

## 🎯 Următorii pași pentru producție:
1. Actualizează credențialele SMTP în `send_contact.php`
2. Testează trimiterea pe server de producție
3. Configurează email-ul de destinație în `config.php`
