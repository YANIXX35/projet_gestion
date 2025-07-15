<?php
// contact.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$message_envoye = false;
$erreur_envoi = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $contenu = trim($_POST['message'] ?? '');

    if ($nom && filter_var($email, FILTER_VALIDATE_EMAIL) && $contenu) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'binksyao95@gmail.com';      // Email expéditeur
            $mail->Password   = 'phkhvhvljtdvroaj';           // Mot de passe d'application
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom($email, $nom);                     // Expéditeur (visiteur)
            $mail->addAddress('binksyao95@gmail.com', 'Admin DocGestion'); // Destinataire

            $mail->isHTML(true);
            $mail->Subject = "📩 Nouveau message de contact - DocGestion";
            $mail->Body = "
                <p><b>Nom :</b> " . htmlspecialchars($nom) . "</p>
                <p><b>Email :</b> " . htmlspecialchars($email) . "</p>
                <p><b>Message :</b><br>" . nl2br(htmlspecialchars($contenu)) . "</p>
            ";
            $mail->AltBody = "Nom: $nom\nEmail: $email\nMessage:\n$contenu";

            $mail->send();
            $message_envoye = true;
        } catch (Exception $e) {
            $erreur_envoi = "Erreur d'envoi : " . $mail->ErrorInfo;
        }
    } else {
        $erreur_envoi = "Veuillez remplir tous les champs correctement.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contact - DocGestion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #007BFF;
            color: white;
            padding: 20px;
            text-align: center;
        }
        main {
            padding: 30px;
        }
        section {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            max-width: 800px;
            margin: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        form {
            margin-top: 20px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007BFF;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .success { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
<header>
    <h1>Contactez-nous</h1>
</header>
<main>
    <section>
        <p>📍 Abidjan, Côte d'Ivoire</p>
        <p>📞 Téléphone : +225 01 23 45 67 89</p>
        <p>✉️ Email : support@docsystem.com</p>

        <?php if ($message_envoye): ?>
            <div class="success">✅ Votre message a bien été envoyé. Merci !</div>
        <?php elseif ($erreur_envoi): ?>
            <div class="error">❌ <?= htmlspecialchars($erreur_envoi) ?></div>
        <?php endif; ?>

        <form action="contact.php" method="post">
            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">

            <label for="email">Email :</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="message">Message :</label>
            <textarea id="message" name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

            <button type="submit">Envoyer</button>
        </form>
    </section>
</main>
</body>
</html>
