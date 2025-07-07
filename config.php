<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_documents", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}
?>
