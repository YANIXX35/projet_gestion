<?php
session_start();
include 'config.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$utilisateur_id = intval($_GET['id'] ?? 0);

// Vérifier si l'utilisateur existe
$stmt = $pdo->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
$stmt->execute([$utilisateur_id]);
$utilisateur_nom = $stmt->fetchColumn();

if (!$utilisateur_nom) {
    echo "<p>❌ Utilisateur non trouvé.</p>";
    exit;
}

// Récupérer les documents
$stmt = $pdo->prepare("
    SELECT d.*, a.nom AS auteur, c.nom AS categorie, dep.nom AS departement 
    FROM documents d
    LEFT JOIN auteurs a ON d.auteur_id = a.id
    LEFT JOIN categories c ON d.categorie_id = c.id
    LEFT JOIN departements dep ON d.departement_id = dep.id
    WHERE d.utilisateur_id = ?
    ORDER BY d.date_creation DESC
");
$stmt->execute([$utilisateur_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Documents de <?= htmlspecialchars($utilisateur_nom) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #007BFF; color: white; }
        a { text-decoration: none; color: #007BFF; }
    </style>
</head>
<body>

<h2>📄 Documents ajoutés par <strong><?= htmlspecialchars($utilisateur_nom) ?></strong></h2>

<?php if (count($documents) > 0): ?>
    <table>
        <tr>
            <th>Titre</th>
            <th>Description</th>
            <th>Auteur</th>
            <th>Catégorie</th>
            <th>Département</th>
            <th>Fichier</th>
            <th>Date</th>
        </tr>
        <?php foreach ($documents as $doc): ?>
            <tr>
                <td><?= htmlspecialchars($doc['titre']) ?></td>
                <td><?= htmlspecialchars($doc['description']) ?></td>
                <td><?= htmlspecialchars($doc['auteur']) ?></td>
                <td><?= htmlspecialchars($doc['categorie']) ?></td>
                <td><?= htmlspecialchars($doc['departement']) ?></td>
                <td>
                    <?php if (!empty($doc['fichier'])): ?>
                        <a href="uploads/<?= htmlspecialchars($doc['fichier']) ?>" download>📥 Télécharger</a>
                    <?php else: ?>
                        Aucun fichier
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($doc['date_creation']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>📂 Aucun document trouvé pour cet utilisateur.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="dashboard.php">⬅️ Retour au dashboard</a></p>

</body>
</html>
