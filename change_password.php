<?php
session_start();
include 'config.php';

// Vérifier si un token est fourni
$token = $_GET['token'] ?? '';
$message = '';

if ($token) {
    // Vérifier la validité du token dans la table password_resets
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $email = $reset['email'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (strlen($new_password) < 6) {
                $message = "Le mot de passe doit contenir au moins 6 caractères.";
            } elseif ($new_password !== $confirm_password) {
                $message = "Les mots de passe ne correspondent pas.";
            } else {
                // Mettre à jour le mot de passe dans la table utilisateurs
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $email]);

                // Supprimer le token utilisé
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);

                $message = "Mot de passe mis à jour avec succès. <a href='login.php'>Se connecter</a>.";
                // Redirection après 3 secondes (optionnel, pour UX)
                header("Refresh: 3; URL=login.php");
            }
        }
    } else {
        $message = "Le lien de réinitialisation est invalide ou a expiré (valide 10 minutes).";
    }
} else {
    $message = "Aucun token fourni.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer le mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .btn {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin-bottom: 15px;
            color: #d93025;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Changer le mot de passe</h2>

    <?php if ($message): ?>
        <div class="message <?= strpos($message, 'succès') !== false ? 'success' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($reset && strpos($message, 'succès') === false): ?>
        <form method="POST">
            <label for="new_password">Nouveau mot de passe :</label>
            <input type="password" name="new_password" id="new_password" required>

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit" class="btn">Mettre à jour</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>