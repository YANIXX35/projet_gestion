<?php
session_start();
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email invalide.";
    } else {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Pour sécurité, ne pas dire que l'email n'existe pas
            $message = "Un email de réinitialisation a été envoyé si ce compte existe.";
        } else {
            // Générer token et expiration
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

            // Supprimer anciens tokens pour cet email
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);

            // Insérer nouveau token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires_at]);

            // Envoyer email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'binksyao95@gmail.com';  // ton email
                $mail->Password   = 'phkhvhvljtdvroaj';      // mot de passe app
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('binksyao95@gmail.com', 'Ton Site');
                $mail->addAddress($email, $user['nom']);
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $url = "http://localhost/formation/change_password.php?token=$token";

                $mail->Body = "
                    <p>Bonjour <b>" . htmlspecialchars($user['nom']) . "</b>,</p>
                    <p>Vous avez demandé une réinitialisation de mot de passe.</p>
                    <p>Cliquez sur ce lien pour changer votre mot de passe (valide 10 minutes) :</p>
                    <p><a href='$url'>$url</a></p>
                    <p>Si ce n'était pas vous, ignorez ce message.</p>
                ";

                $mail->send();
                $message = "Un email de réinitialisation a été envoyé si ce compte existe.";
            } catch (Exception $e) {
                error_log("Erreur mail reset: " . $mail->ErrorInfo);
                $message = "Erreur lors de l'envoi de l'email.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Demander réinitialisation</title>
</head>
<body>
    <h2>Réinitialisation du mot de passe</h2>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Email :</label>
        <input type="email" name="email" required />
        <button type="submit">Envoyer le lien</button>
    </form>
</body>
</html>