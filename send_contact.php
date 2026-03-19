<?php
require_once(__DIR__ . '/config/config.php');

// Încarcă PHPMailer dacă este disponibil
$phpmailerAvailable = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
    $phpmailerAvailable = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: contact.php'); 
    exit; 
}

try {
    // CSRF protection
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        throw new Exception('Token de securitate invalid.');
    }

    // Rate limiting pentru contact
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!Security::rateLimitCheck("contact_$clientIP", 3, 300)) {
        throw new Exception('Prea multe mesaje. Încearcă din nou în 5 minute.');
    }

    // Validare input
    $name = Validator::required($_POST['name'] ?? '', 'Numele');
    $name = Validator::maxLength(trim($name), 50, 'Numele');
    $name = Security::sanitizeInput($name);

    $email = Validator::required($_POST['email'] ?? '', 'Email-ul');
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Email invalid.');
    }

    $message = Validator::required($_POST['message'] ?? '', 'Mesajul');
    $message = Validator::maxLength(trim($message), 1000, 'Mesajul');
    $message = Security::sanitizeInput($message);

    // Honeypot check
    $honeypot = trim($_POST['website'] ?? '');
    if ($honeypot !== '') {
        // Silent fail for bots
        header('Location: contact.php?sent=1');
        exit;
    }

    $sent = false;

    // Pentru testare locală, salvează mesajul în fișier
    if (!isProductionEnvironment()) {
        $sent = saveMessageToFile($name, $email, $message);
    } else {
        // Pentru producție, încearcă PHPMailer apoi mail() nativ
        if ($phpmailerAvailable) {
            $sent = sendWithPHPMailer($name, $email, $message);
        }
        
        if (!$sent && function_exists('mail')) {
            $sent = sendWithNativeMail($name, $email, $message);
        }
    }

    header('Location: contact.php' . ($sent ? '?sent=1' : '?sent=0'));

} catch (Exception $e) {
    header('Location: contact.php?error=' . urlencode($e->getMessage()));
}

function isProductionEnvironment() {
    // Detectează dacă suntem în producție (nu pe localhost)
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return !in_array($host, ['localhost', '127.0.0.1', '::1']) && 
           !str_contains($host, 'localhost');
}

function saveMessageToFile($name, $email, $message) {
    try {
        $dir = __DIR__ . '/data/contact_messages';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $filename = $dir . '/messages.txt';
        
        $content = "\n" . str_repeat('=', 50) . "\n";
        $content .= "DATA: $timestamp\n";
        $content .= "NUME: $name\n";
        $content .= "EMAIL: $email\n";
        $content .= "MESAJ:\n$message\n";
        $content .= str_repeat('=', 50) . "\n";
        
        $result = file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
        
        // Log pentru debugging
        error_log("Contact message saved to file: $filename");
        
        return $result !== false;
        
    } catch (Exception $e) {
        error_log("Error saving contact message: " . $e->getMessage());
        return false;
    }
}

function sendWithPHPMailer($name, $email, $message) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configurare SMTP pentru Hostico cPanel
        $mail->isSMTP();
        $mail->Host       = 'mail.matchday.ro'; // Server SMTP Hostico
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contact@matchday.ro';
        $mail->Password   = 'PetreIonel205!';    // Parola email-ului din cPanel
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL pentru port 465
        $mail->Port       = 465; // Port SSL pentru Hostico
        $mail->CharSet    = 'UTF-8';

        // Expeditor și destinatar
        $mail->setFrom('noreply@matchday.ro', 'MatchDay.ro Contact');
        $mail->addAddress(CONTACT_TO_EMAIL);
        $mail->addReplyTo($email, $name);

        // Conținut
        $mail->isHTML(false);
        $mail->Subject = 'Mesaj de pe MatchDay.ro de la ' . $name;
        $mail->Body    = "Nume: $name\nEmail: $email\n\nMesaj:\n$message";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

function sendWithNativeMail($name, $email, $message) {
    $to = CONTACT_TO_EMAIL;
    $subject = 'Mesaj de pe MatchDay.ro de la ' . $name;
    $body = "Nume: $name\nEmail: $email\n\nMesaj:\n$message";
    
    $headers = [
        'From: noreply@matchday.ro',
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}
?>
