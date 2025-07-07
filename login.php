<?php
session_start();
include 'config.php';

$identifiant = $_POST['identifiant'] ?? '';
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$role = $_POST['role'] ?? '';
$avatarPath = 'images/avatar_defaut.png'; // Par défaut

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($identifiant && $mot_de_passe && $role) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE (nom = ? OR email = ?) AND LOWER(role) = LOWER(?)");
        $stmt->execute([$identifiant, $identifiant, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            $_SESSION['utilisateur_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'] ?? $avatarPath;

            header("Location: dashboard.php");
            exit;
        } else {
            $erreur = "❌ Identifiants ou mot de passe incorrects.";
            if ($user && !empty($user['avatar'])) {
                $avatarPath = $user['avatar'];
            }
        }
    } else {
        $erreur = "❌ Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            display: flex;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 750px;
        }

        .avatar-section {
            background-color: #007BFF;
            color: white;
            padding: 40px;
            text-align: center;
            width: 40%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .avatar-section img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 20px;
        }

        .form-section {
            padding: 30px;
            width: 60%;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input, select {
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

        .erreur {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <!-- Section Avatar -->
    <div class="avatar-section">
        <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar">
        <h3>Bienvenue !</h3>
        <p>Connectez-vous pour accéder au système</p>
    </div>

    <!-- Section Formulaire -->
    <div class="form-section">
        <h2>Connexion</h2>

        <?php if (!empty($erreur)): ?>
            <div class="erreur"><?= $erreur ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="identifiant">Nom ou Email :</label>
            <input type="text" name="identifiant" id="identifiant" required value="<?= htmlspecialchars($identifiant) ?>">

            <label for="mot_de_passe">Mot de passe :</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" required>

            <label for="role">Rôle :</label>
            <select name="role" id="role" required>
                <option value="">-- Sélectionnez un rôle --</option>
                <option value="utilisateur" <?= ($role === 'utilisateur') ? 'selected' : '' ?>>Utilisateur</option>
                <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>

            <button type="submit" class="btn">Se connecter</button>
        </form>
    </div>
</div>
</body>
</html>
