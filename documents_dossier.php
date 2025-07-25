<?php
session_start();
include 'config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$utilisateur_id = $_SESSION['utilisateur_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>❌ Dossier invalide.</p>";
    exit;
}

$dossier_id = intval($_GET['id']);

// Vérifier que le dossier appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM dossiers WHERE id = ? AND utilisateur_id = ?");
$stmt->execute([$dossier_id, $utilisateur_id]);
$dossier = $stmt->fetch();

if (!$dossier) {
    echo "<p style='color:red;'>❌ Dossier introuvable ou non autorisé.</p>";
    exit;
}

// Récupérer tous les documents du dossier
$stmtDocs = $pdo->prepare("
    SELECT d.*, a.nom AS auteur, c.nom AS categorie, dep.nom AS departement
    FROM documents d
    LEFT JOIN auteurs a ON d.auteur_id = a.id
    LEFT JOIN categories c ON d.categorie_id = c.id
    LEFT JOIN departements dep ON d.departement_id = dep.id
    WHERE d.dossier_id = ? AND d.utilisateur_id = ?
    ORDER BY d.date_creation DESC
");
$stmtDocs->execute([$dossier_id, $utilisateur_id]);
$documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

// Débogage : Vérifier si les fichiers existent
foreach ($documents as &$doc) {
    if ($doc['fichier'] && !file_exists("uploads/" . $doc['fichier'])) {
        $doc['fichier'] = null; // Marquer comme inexistant si le fichier n'est pas trouvé
    }
}
unset($doc);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Documents du dossier <?= htmlspecialchars($dossier['nom']) ?></title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f6fa; }
        .doc-liste { max-width: 900px; margin: auto; }
        .doc-item {
            background: white; margin-bottom: 15px; padding: 15px; border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        .doc-item h3 { margin: 0 0 8px; }
        .doc-item p { margin: 3px 0; }
        .preview { margin-top: 10px; }
        .preview iframe { width: 100%; height: 400px; border: none; }
        a.btn {
            display: inline-block; margin-top: 10px; padding: 8px 12px;
            background: #007BFF; color: white; text-decoration: none; border-radius: 6px;
        }
        a.btn:hover { background: #0056b3; }
        a.btn-back {
            margin-bottom: 20px; display: inline-block; color: #007BFF; text-decoration: none;
        }
        a.btn-back:hover { text-decoration: underline; }
    </style>
</head>
<body>

<a href="dashboard.php" class="btn-back">⬅ Retour au dashboard</a>

<div class="doc-liste">
    <h2>Documents dans le dossier : <?= htmlspecialchars($dossier['nom']) ?></h2>

    <?php if (count($documents) === 0): ?>
        <p>Aucun document dans ce dossier.</p>
    <?php else: ?>
        <?php foreach ($documents as $doc): ?>
            <div class="doc-item">
                <h3><?= htmlspecialchars($doc['titre']) ?></h3>
                <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($doc['description'])) ?></p>
                <p><strong>Auteur :</strong> <?= htmlspecialchars($doc['auteur']) ?></p>
                <p><strong>Catégorie :</strong> <?= htmlspecialchars($doc['categorie']) ?></p>
                <p><strong>Département :</strong> <?= htmlspecialchars($doc['departement']) ?></p>

                <div class="preview">
                    <?php if ($doc['fichier'] && file_exists("uploads/" . $doc['fichier'])): ?>
                        <?php
                        $file_extension = strtolower(pathinfo($doc['fichier'], PATHINFO_EXTENSION));
                        if (in_array($file_extension, ['doc', 'docx'])) {
                            echo '<iframe src="https://docs.google.com/gview?url=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $doc['fichier']) . '&embedded=true"></iframe>';
                            echo '<br><a href="uploads/' . htmlspecialchars($doc['fichier']) . '" class="btn" download>📥 Télécharger le fichier</a>';
                        } elseif (in_array($file_extension, ['pdf'])) {
                            echo '<iframe src="uploads/' . htmlspecialchars($doc['fichier']) . '"></iframe>';
                            echo '<br><a href="uploads/' . htmlspecialchars($doc['fichier']) . '" class="btn" download>📥 Télécharger le fichier</a>';
                        } else {
                            echo '<p>Prévisualisation non disponible pour ce type de fichier.</p>';
                            echo '<a href="uploads/' . htmlspecialchars($doc['fichier']) . '" class="btn" download>📥 Télécharger le fichier</a>';
                        }
                        ?>
                    <?php else: ?>
                        <p>❌ Fichier introuvable ou non associé.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>