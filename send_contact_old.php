<?php
require_once(__DIR__ . '/config/config.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){ header('Location: contact.php'); exit; }
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
if ($name === '' || $email === '' || $message === ''){ header('Location: contact.php'); exit; }
$to = CONTACT_TO_EMAIL;
$subject = 'Mesaj de pe ' . SITE_NAME;
$body = "Nume: $name\nEmail: $email\n\n$message";
$ok = false;
if (function_exists('mail') && $to !== 'contact@example.com'){
  $ok = @mail($to, $subject, $body, 'From: '.$email);
}
header('Location: contact.php' . ($ok ? '?sent=1' : '?sent=0'));
