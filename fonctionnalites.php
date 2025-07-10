<?php
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
            animation: fadeIn 1s ease-in-out;
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

        header {
            background: linear-gradient(135deg, #007BFF, #00c6ff);
            color: white;
            padding: 80px 20px;
            text-align: center;
            animation: slideDown 1s ease;
        }

        header h1 {
            font-size: 48px;
            margin: 0;
            animation: fadeIn 1.5s ease;
        }

        main {
            padding: 40px 20px;
            max-width: 1200px;
            margin: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            opacity: 0;
            transform: translateY(20px);
        }

        .feature-card.reveal {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .feature-card h2 {
            color: #007BFF;
            font-size: 20px;
            margin: 10px 0;
        }

        .feature-card p {
            line-height: 1.6;
            color: #555;
        }

        .feature-icon {
            font-size: 40px;
            margin-bottom: 10px;
            color: #007BFF;
            transition: transform 0.3s;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.2);
        }

        .back-home {
            text-align: center;
            margin-top: 40px;
        }

        .back-home a {
            background: linear-gradient(135deg, #007BFF, #00c6ff);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s, transform 0.3s;
            display: inline-block;
        }

        .back-home a:hover {
            background: linear-gradient(135deg, #0056b3, #007BFF);
            transform: scale(1.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            header h1 {
                font-size: 32px;
            }

            .navbar, .topbar {
                flex-direction: column;
                gap: 10px;
                padding: 10px 20px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Bandeau haut -->
<div class="topbar">
    <div class="left">
        📍 Abidjan, Côte d'Ivoire &nbsp; 🕒 <span class="clock" id="clock">10:28 AM</span>
    </div>
    <div class="right">
        📞 +225 01 23 45 67 89 &nbsp; ✉️ support@docsystem.com
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
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📁</div>
            <h2>Centralisation des documents</h2>
            <p>Accédez à tous vos documents depuis un seul tableau de bord intuitif et sécurisé.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📂</div>
            <h2>Organisation intuitive</h2>
            <p>Classez vos fichiers par catégories, par projet ou par date pour une navigation rapide.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔍</div>
            <h2>Recherche intelligente</h2>
            <p>Grâce à notre moteur de recherche intégré, trouvez en quelques secondes ce dont vous avez besoin.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔐</div>
            <h2>Partage sécurisé</h2>
            <p>Partagez vos fichiers en toute sécurité avec des permissions personnalisées pour chaque utilisateur.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h2>Suivi des accès</h2>
            <p>Gardez un œil sur qui consulte vos documents et à quel moment grâce à notre historique d’activité.</p>
        </div>
    </div>
    <div class="back-home">
        <a href="index.php">⬅ Retour à l'accueil</a>
    </div>
</main>

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

    // Animation de révélation au défilement
    function revealOnScroll() {
        const cards = document.querySelectorAll('.feature-card');
        cards.forEach((card, index) => {
            const cardTop = card.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            if (cardTop < windowHeight * 0.85) {
                setTimeout(() => {
                    card.classList.add('reveal');
                }, index * 150); // Délai progressif pour chaque carte
            }
        });
    }

    window.addEventListener('scroll', revealOnScroll);
    window.addEventListener('load', revealOnScroll);
</script>

</body>
</html>