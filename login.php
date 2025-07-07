<?php
session_start();
include 'config.php'; // Fichier de connexion PDO à la base

// Récupération des champs du formulaire
$identifiant = $_POST['identifiant'] ?? '';
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$role = $_POST['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($identifiant && $mot_de_passe && $role) {
        // ✅ Requête insensible à la casse sur le rôle
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE (nom = ? OR email = ?) AND LOWER(role) = LOWER(?)");
        $stmt->execute([$identifiant, $identifiant, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 👉 Débogage temporaire (supprime après test)
        // echo '<pre>'; print_r($user); echo '</pre>';

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            // Authentification réussie
            $_SESSION['utilisateur_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];

            // Redirection vers dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Erreur d'identifiants
            $erreur = "❌ Identifiants ou mot de passe incorrects.";
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
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
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
    <h2>Connexion</h2>

    <?php if (!empty($erreur)): ?>
        <div class="erreur"><?= $erreur ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="identifiant">Nom ou Email :</label>
        <input type="text" name="identifiant" id="identifiant" required>

        <label for="mot_de_passe">Mot de passe :</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" required>

        <label for="role">Rôle :</label>
        <select name="role" id="role" required>
            <option value="utilisateur">Utilisateur</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit" class="btn">Se connecter</button>
    </form>
</div>
</body>
</html>
