<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Gestion de Documents</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fa;
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
            align-items: center;
            gap: 30px;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
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

        .hero {
            background: url('images/bg-docs.jpg') center/cover no-repeat;
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 20px;
            position: relative;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }
            .navbar, .topbar {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<!-- Bandeau haut -->
<div class="topbar">
    <div class="left">
        📍 Abidjan, Côte d'Ivoire
        🕒 Lun - Ven : 08h00 - 18h00
    </div>
    <div class="right">
        📞 +225 01 23 45 67 89
        ✉️ support@docsystem.com
    </div>
</div>

<!-- Barre de navigation -->
<div class="navbar">
    <div class="logo">DocGestion</div>
    <div class="nav-links">
        <a href="#">Accueil</a>
        <a href="#">Fonctionnalités</a>
        <a href="#">À propos</a>
        <a href="#">Contact</a>
    </div>
    <div class="nav-buttons">
        <a href="login.php">🔐 Connexion</a>
        <a href="register.php">📝 S’inscrire</a>
    </div>
</div>

<!-- Section principale -->
<div class="hero">
    <div class="hero-content">
        <h1>Plateforme de Gestion de Documents</h1>
        <p>Centralisez, organisez et accédez à vos documents facilement et en toute sécurité.</p>
    </div>
</div>

</body>
</html>
