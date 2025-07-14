<?php
// Inclusion des classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$mail = new PHPMailer(true);

try {
    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'binksyao95@gmail.com';             // Ton adresse Gmail
    $mail->Password   = 'phkhvhvljtdvroaj';                 // Mot de passe d'application sans espace
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Expéditeur
    $mail->setFrom('binksyao95@gmail.com', 'Binks Yao');

    // Destinataire
    $mail->addAddress('brandonparrain98@gmail.com', 'Brandon');

    // Contenu du message
    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP Gmail avec PHPMailer';
    $mail->Body    = '<b>Ceci est un test d\'envoi d\'email avec PHPMailer et Gmail SMTP.</b>';
    $mail->AltBody = 'Ceci est un test d\'envoi d\'email avec PHPMailer et Gmail SMTP.';

    $mail->send();
    echo '✅ Email envoyé avec succès à Brandon !';
} catch (Exception $e) {
    echo "❌ Erreur : {$mail->ErrorInfo}";
}
