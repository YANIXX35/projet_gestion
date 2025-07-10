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
            overflow-x: hidden;
        }

        .topbar {
            background: #f1f1f1;
            padding: 10px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #555;
            position: relative;
            transition: background 0.5s;
        }

        .topbar .left, .topbar .right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .topbar .clock {
            font-weight: bold;
            color: #007BFF;
            animation: pulse 2s infinite;
        }

        .navbar {
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 50px;
            border-bottom: 1px solid #ddd;
            transition: background 0.5s;
        }

        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007BFF;
            animation: rotate 4s linear infinite, fadeIn 1s ease-in-out;
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
            position: relative;
        }

        .navbar .nav-links a:hover {
            color: #007BFF;
        }

        .navbar .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background: #007BFF;
            transition: width 0.3s ease;
        }

        .navbar .nav-links a:hover::after {
            width: 100%;
        }

        .navbar .nav-buttons a {
            text-decoration: none;
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            margin-left: 10px;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s, transform 0.3s;
        }

        .navbar .nav-buttons a:hover {
            background-color: #bd2130;
            transform: scale(1.05);
        }

        .hero {
            background: url('images/bg-docs.jpg') center/cover no-repeat;
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            color: white;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            transition: opacity 0.5s;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            animation: fadeIn 1.5s ease;
        }

        .hero h1 {
            font-size: 48px;
            background: linear-gradient(to right, #00c6ff, #0072ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            animation: slideUp 1s ease-out;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            animation: fadeIn 2s ease;
        }

        .hero a.button {
            background-color: #007BFF;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s, transform 0.3s;
            display: inline-block;
            animation: bounce 2s infinite;
        }

        .hero a.button:hover {
            background-color: #0056b3;
            transform: scale(1.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .navbar, .topbar {
                flex-direction: column;
                gap: 10px;
                padding: 10px 20px;
            }

            .hero a.button {
                padding: 10px 20px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<!-- Bandeau haut -->
<div class="topbar">
    <div class="left">
        📍 Abidjan, Côte d'Ivoire   🕒 <span class="clock" id="clock">10:28 AM</span>
    </div>
    <div class="right">
        📞 +225 01 23 45 67 89   ✉️ support@docsystem.com
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

<!-- Section principale -->
<div class="hero">
    <div class="hero-content">
        <h1>Plateforme de Gestion de Documents</h1>
        <p>Centralisez, organisez et accédez à vos documents facilement et en toute sécurité.</p>
        <a class="button" href="register.php">Commencer maintenant</a>
    </div>
</div>

<script>
    // Horloge en temps réel
    function updateClock() {
        const now = new Date();
        const options = { hour: 'numeric', minute: 'numeric', hour12: true };
        const timeString = now.toLocaleTimeString('en-US', options);
        document.getElementById('clock').textContent = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Changer la couleur du bouton en fonction de l'heure
    function updateButtonColor() {
        const hour = new Date().getHours();
        const button = document.querySelector('.button');
        if (hour < 12) {
            button.style.backgroundColor = '#00c6ff'; // Matin
        } else if (hour < 18) {
            button.style.backgroundColor = '#0072ff'; // Après-midi
        } else {
            button.style.backgroundColor = '#0056b3'; // Soir
        }
    }
    setInterval(updateButtonColor, 60000); // Mise à jour toutes les minutes
    updateButtonColor();
</script>

</body>
</html>