<?php
session_start();
include 'config.php';

// Inclure PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── 1. Nettoyage / récupération des données ───────────────────────────────
    $nom      = trim($_POST['nom']   ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass_raw = $_POST['mot_de_passe'] ?? '';
    $role     = 'utilisateur'; // Rôle fixé à "utilisateur"

    // ── 2. Contrôle minimal des champs texte ──────────────────────────────────
    $erreurs = [];
    if (!$nom)     $erreurs[] = "Le nom est requis.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide.";
    if (strlen($pass_raw) < 6) $erreurs[] = "Mot de passe trop court (6 car. min.).";

    // ── 3. Gestion de l’avatar (facultatif) ───────────────────────────────────
    $avatar_path = null;
    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $erreurs[] = "Format d’image non autorisé.";
        } elseif ($_FILES['avatar']['size'] > 2*1024*1024) {
            $erreurs[] = "Image trop lourde (max 2 Mo).";
        } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $erreurs[] = "Erreur d’upload (" . $_FILES['avatar']['error'] . ").";
        } else {
            $dir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $filename = uniqid('ava_') . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir.$filename)) {
                $avatar_path = 'uploads/avatars/' . $filename;
            } else {
                $erreurs[] = "Impossible de déplacer le fichier.";
            }
        }
    }

    // ── 4. Vérifier si l’email est déjà utilisé ───────────────────────────────
    if (!$erreurs) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // ⚠️ Email déjà utilisé → tentative suspecte → envoi alerte
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$existingUser['email']]);

            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$existingUser['email'], $token, $expires_at]);

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'binksyao95@gmail.com';
                $mail->Password   = 'phkhvhvljtdvroaj';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('binksyao95@gmail.com', 'Sécurité YANISSE');
                $mail->addAddress($existingUser['email'], $existingUser['nom']);

                $mail->isHTML(true);
                $mail->Subject = '🔒 Tentative d’inscription avec votre email !';
                $url = "http://localhost/formation/change_password.php?token=$token";
                $mail->Body = "
                    <p>Bonjour <b>{$existingUser['nom']}</b>,</p>
                    <p>Quelqu’un a tenté de créer un compte avec votre adresse email <b>{$existingUser['email']}</b>.</p>
                    <p><b>Date :</b> " . date("d/m/Y H:i:s") . "<br>
                    <b>IP :</b> " . $_SERVER['REMOTE_ADDR'] . "</p>
                    <p>👉 Si ce n'était pas vous, nous vous recommandons de vérifier l’activité de votre compte.</p>
                    <p>🔑 <a href='$url'>Changer mon mot de passe</a> (lien valide 10 minutes)</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Erreur email tentative inscription : " . $mail->ErrorInfo);
            }

            $erreurs[] = "⚠️ Cette adresse email est déjà utilisée.";
        } else {
            // 🔐 Aucun doublon → on peut créer le compte
            $hash = password_hash($pass_raw, PASSWORD_DEFAULT);

            $sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role, avatar)
                    VALUES (:nom, :email, :pass, :role, :avatar)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom'    => $nom,
                ':email'  => $email,
                ':pass'   => $hash,
                ':role'   => $role,
                ':avatar' => $avatar_path
            ]);

            // Envoi email de confirmation
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'binksyao95@gmail.com';
                $mail->Password   = 'phkhvhvljtdvroaj';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('binksyao95@gmail.com', 'Ton Site');
                $mail->addAddress($email, $nom);

                $mail->isHTML(true);
                $mail->Subject = 'Bienvenue sur le site de YANISSE !';
                $mail->Body = "
                    <p>Bonjour <b>" . htmlspecialchars($nom) . "</b>,</p>
                    <p>Votre inscription a bien été prise en compte.</p>
                    <p>👉 <a href='http://localhost/formation/login.php'>Se connecter</a></p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Erreur email de confirmation : " . $mail->ErrorInfo);
            }

            $_SESSION['flash'] = "✅ Compte créé avec succès.";
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f6f8fb;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
        .card{background:#fff;padding:30px 40px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.07);width:400px}
        h2{text-align:center;margin-top:0}
        label{font-weight:bold;margin-top:10px;display:block}
        input,select{width:100%;padding:10px;margin-top:6px;border-radius:6px;border:1px solid #ccc}
        .btn{width:100%;padding:12px;margin-top:18px;border:none;border-radius:6px;background:#007BFF;color:#fff;font-weight:bold;cursor:pointer}
        .btn:hover{background:#0056b3}
        .erreur{color:#d93025;margin-top:10px}
    </style>
</head>
<body>
<div class="card">
    <h2>Créer un compte</h2>

    <?php if (!empty($erreurs)): ?>
        <?php foreach ($erreurs as $e): ?>
            <div class="erreur"><?= htmlspecialchars($e) ?></div>
        <?php endforeach ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Nom :</label>
        <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>

        <label>Email :</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label>Mot de passe :</label>
        <input type="password" name="mot_de_passe" required>

        <label>Avatar (facultatif) :</label>
        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif,.webp">

        <button class="btn">Créer le compte</button>
    </form>
    <p>Ou</p>
    <a href="google-login.php" class="btn" style="background:#4285f4;">Se connecter avec Google</a>
</div>
</body>
</html>