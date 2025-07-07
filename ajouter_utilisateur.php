<?php
// Connexion à la base
include 'config.php'; // Ce fichier doit contenir la connexion PDO dans la variable $pdo

// Données de l'utilisateur à insérer
$nom = 'yao';
$email = 'yao@example.com';
$mot_de_passe_en_clair = 'motdepasse123';
$role = 'utilisateur';

// Hachage du mot de passe
$mot_de_passe_hash = password_hash($mot_de_passe_en_clair, PASSWORD_DEFAULT);

// Requête d'insertion
$sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([$nom, $email, $mot_de_passe_hash, $role]);
    echo "✅ Utilisateur inséré avec succès.";
} catch (PDOException $e) {
    echo "❌ Erreur lors de l'insertion : " . $e->getMessage();
}
?>
