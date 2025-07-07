<?php
try {
    // Connexion au serveur MySQL (sans base sélectionnée)
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Créer la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gestion_documents");
    echo "✅ Base de données 'gestion_documents' créée avec succès.<br>";

    // 2. Sélectionner la base
    $pdo->exec("USE gestion_documents");

    // 3. Supprimer les tables existantes (ordre inverse des dépendances)
    $pdo->exec("DROP TABLE IF EXISTS historique");
    $pdo->exec("DROP TABLE IF EXISTS documents");
    $pdo->exec("DROP TABLE IF EXISTS departements");
    echo "🗑️ Tables existantes supprimées.<br>";

    // 4. Recréer la table 'departements'
    $pdo->exec("
        CREATE TABLE departements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL
        )
    ");
    echo "✅ Table 'departements' créée.<br>";

    // 5. Recréer la table 'documents'
    $pdo->exec("
        CREATE TABLE documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titre VARCHAR(255) NOT NULL,
            chemin_fichier VARCHAR(255) NOT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            departement_id INT NOT NULL,
            FOREIGN KEY (departement_id) REFERENCES departements(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Table 'documents' créée.<br>";

    // 6. Recréer la table 'historique' (facultatif)
    $pdo->exec("
        CREATE TABLE historique (
            id INT AUTO_INCREMENT PRIMARY KEY,
            departement_id INT NOT NULL,
            action TEXT NOT NULL,
            date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (departement_id) REFERENCES departements(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Table 'historique' créée.<br>";

    // 7. Ajouter un département de test
    $nom = "Département Informatique";
    $email = "info@entreprise.com";
    $mot_de_passe = password_hash("motdepasse", PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT COUNT(*) FROM departements WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO departements (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $email, $mot_de_passe]);
        echo "✅ Département de test ajouté.<br>";
    } else {
        echo "ℹ️ Département de test déjà existant.<br>";
    }

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
