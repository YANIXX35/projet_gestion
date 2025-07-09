<?php
// fonctionalites.php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fonctionnalités - DocGestion</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .topbar {
            background: #f1f1f1;
            padding: 10px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #555;
        }

        .topbar .left, .topbar .right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar {
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 50px;
            border-bottom: 1px solid #ddd;
        }

        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007BFF;
        }

        .navbar .nav-links {
            display: flex;
            gap: 30px;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .navbar .nav-links a:hover {
            color: #007BFF;
        }

        .navbar .nav-buttons a {
            text-decoration: none;
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            margin-left: 10px;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .navbar .nav-buttons a:hover {
            background-color: #bd2130;
        }

        header {
            background-color: #007BFF;
            color: white;
            padding: 60px 20px;
            text-align: center;
            animation: fadeIn 1s ease;
        }

        main {
            padding: 40px 20px;
        }

        section {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: auto;
            animation: fadeIn 1.2s ease-in-out;
        }

        h2 {
            color: #007BFF;
            margin-top: 30px;
        }

        p {
            line-height: 1.6;
        }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 40px;
        }

        .back-home a {
            background: #007BFF;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }

        .back-home a:hover {
            background-color: #0056b3;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @media (max-width: 768px) {
            .navbar, .topbar {
                flex-direction: column;
                gap: 10px;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>

<!-- Bandeau haut -->
<div class="topbar">
    <div class="left">
        📍 Abidjan, Côte d'Ivoire &nbsp;&nbsp; 🕒 Lun - Ven : 08h00 - 18h00
    </div>
    <div class="right">
        📞 +225 01 23 45 67 89 &nbsp;&nbsp; ✉️ support@docsystem.com
    </div>
</div>

<!-- Barre de navigation -->
<div class="navbar">
    <div class="logo">DocGestion</div>
    <div class="nav-links">
        <a href="index.php">Accueil</a>
        <a href="fonctionnalites.php">Fonctionnalités</a>
        <a href="apropos.php">À propos</a>
        <a href="contact.php">Contact</a>
    </div>
    <div class="nav-buttons">
        <a href="login.php">🔐 Connexion</a>
        <a href="register.php">📝 S’inscrire</a>
    </div>
</div>

<!-- Contenu principal -->
<header>
    <h1>Nos Fonctionnalités Clés</h1>
</header>

<main>
    <section>
        <h2>📁 Centralisation des documents</h2>
        <p>Accédez à tous vos documents depuis un seul tableau de bord intuitif et sécurisé.</p>

        <h2>📂 Organisation intuitive</h2>
        <p>Classez vos fichiers par catégories, par projet ou par date pour une navigation rapide.</p>

        <h2>🔍 Recherche intelligente</h2>
        <p>Grâce à notre moteur de recherche intégré, trouvez en quelques secondes ce dont vous avez besoin.</p>

        <h2>🔐 Partage sécurisé</h2>
        <p>Partagez vos fichiers en toute sécurité avec des permissions personnalisées pour chaque utilisateur.</p>

        <h2>📊 Suivi des accès</h2>
        <p>Gardez un œil sur qui consulte vos documents et à quel moment grâce à notre historique d’activité.</p>

        <div class="back-home">
            <a href="index.php">⬅ Retour à l'accueil</a>
        </div>
    </section>
</main>

</body>
</html>
