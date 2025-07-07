<?php
session_start();
$step = isset($_POST['step']) ? $_POST['step'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Documents - Accueil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            text-align: center;
        }
        .btn, button {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: none;
            background: #007BFF;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if ($step == 0): ?>
        <h2>Bienvenue</h2>
        <form method="POST">
            <input type="hidden" name="step" value="1">
            <button class="btn" name="action" value="login">Se connecter</button>
            <button class="btn" name="action" value="register">Créer un compte</button>
        </form>

    <?php elseif ($step == 1 && $_POST['action'] == 'login'): ?>
        <h2>Connexion</h2>
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <label for="role">Vous êtes :</label>
            <select name="role" required>
                <option value="">-- Choisir un rôle --</option>
                <option value="admin">Admin</option>
                <option value="utilisateur">Utilisateur</option>
            </select>
            <button class="btn">Suivant</button>
        </form>

    <?php elseif ($step == 2): ?>
        <h2>Connexion <?php echo htmlspecialchars($_POST['role']); ?></h2>
        <form action="login.php" method="POST">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($_POST['role']); ?>">
            <input type="text" name="identifiant" placeholder="Nom ou adresse email" required>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <button class="btn">Connexion</button>
        </form>

    <?php elseif ($step == 1 && $_POST['action'] == 'register'): ?>
        <h2>Créer un compte</h2>
        <form action="register.php" method="POST">
            <input type="text" name="nom" placeholder="Nom du département" required>
            <input type="email" name="email" placeholder="Adresse Email" required>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <button class="btn">Créer un compte</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
