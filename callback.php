<?php
session_start();

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    echo "Bienvenue, " . htmlspecialchars($user['nom']) . "! (Email : " . htmlspecialchars($user['email']) . ")";
    // Redirection vers la page principale après un court délai
    header("Refresh:3; url=index.php");
} else {
    echo "Erreur : Connexion non valide.";
    header("Refresh:3; url=register.php");
}
?>