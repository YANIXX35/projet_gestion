<?php
session_start();
include 'config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$utilisateur_id = $_SESSION['utilisateur_id'];
$role = $_SESSION['role'];
$nom_utilisateur = $_SESSION['nom'];
$avatar_path = 'images/avatar_defaut.png';

// Charger avatar
$stmt = $pdo->prepare("SELECT avatar FROM utilisateurs WHERE id = ?");
$stmt->execute([$utilisateur_id]);
$avatar = $stmt->fetchColumn();
if ($avatar && file_exists("uploads/avatars/$avatar")) {
    $avatar_path = "uploads/avatars/$avatar";
}

// Si rôle = utilisateur : charger ses dossiers
if ($role === 'utilisateur') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['nouveau_dossier'])) {
            $nom = trim($_POST['nom']);
            if ($nom) {
                $stmt = $pdo->prepare("INSERT INTO dossiers (nom, utilisateur_id) VALUES (?, ?)");
                $stmt->execute([$nom, $utilisateur_id]);
            }
        }

        if (isset($_POST['renommer_dossier'])) {
            $id = $_POST['dossier_id'];
            $nouveau_nom = trim($_POST['nouveau_nom']);
            $stmt = $pdo->prepare("UPDATE dossiers SET nom = ? WHERE id = ? AND utilisateur_id = ?");
            $stmt->execute([$nouveau_nom, $id, $utilisateur_id]);
        }

        if (isset($_POST['supprimer_dossier'])) {
            $id = $_POST['dossier_id'];
            $stmt = $pdo->prepare("DELETE FROM dossiers WHERE id = ? AND utilisateur_id = ?");
            $stmt->execute([$id, $utilisateur_id]);
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM dossiers WHERE utilisateur_id = ?");
    $stmt->execute([$utilisateur_id]);
    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Si rôle = admin : charger tous les documents
if ($role === 'admin') {
    $sql = "SELECT d.titre, d.date_creation, u.nom AS utilisateur, dep.nom AS departement
            FROM documents d
            JOIN utilisateurs u ON d.utilisateur_id = u.id
            JOIN departements dep ON d.departement_id = dep.id
            ORDER BY d.date_creation DESC";
    $documents_admin = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= ucfirst($role) ?> - Tableau de Bord</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 0; }
        header { background: #007BFF; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        header img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .avatar-profil { display: flex; align-items: center; gap: 10px; }
        .container { max-width: 900px; margin: 30px auto; background: white; padding: 20px; border-radius: 10px; }
        .btn { background: #007BFF; color: white; padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; margin: 4px 0; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #007BFF; color: white; }
        .dossier { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 6px; }
    </style>
</head>
<body>

<header>
    <h2>👋 Bonjour, <?= htmlspecialchars($nom_utilisateur) ?> (<?= htmlspecialchars($role) ?>)</h2>
    <div class="avatar-profil">
        <img src="<?= htmlspecialchars($avatar_path) ?>" alt="Avatar">
        <a href="profil.php" class="btn">Mon Profil</a>
    </div>
</header>

<div class="container">
    <?php if ($role === 'utilisateur'): ?>
        <h3>📁 Mes Dossiers</h3>

        <!-- Création de dossier -->
        <form method="POST">
            <input type="text" name="nom" placeholder="Nom du dossier" required>
            <button type="submit" name="nouveau_dossier" class="btn">Créer un dossier</button>
        </form>

        <!-- Liste des dossiers -->
        <?php foreach ($dossiers as $dossier): ?>
            <div class="dossier">
                <strong><?= htmlspecialchars($dossier['nom']) ?></strong>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>">
                    <input type="text" name="nouveau_nom" placeholder="Nouveau nom">
                    <button type="submit" name="renommer_dossier" class="btn">Renommer</button>
                    <button type="submit" name="supprimer_dossier" class="btn" onclick="return confirm('Supprimer ce dossier ?')">Supprimer</button>
                </form>

                <br><br>

                <!-- Ajouter un document -->
                <form action="ajout_document.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>">
                    <input type="text" name="titre" placeholder="Titre du document" required>
                    <input type="file" name="fichier" required>
                    <button type="submit" class="btn">Ajouter Document</button>
                </form>
            </div>
        <?php endforeach; ?>

    <?php elseif ($role === 'admin'): ?>
        <h3>📂 Tous les Documents</h3>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Département</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents_admin as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['titre']) ?></td>
                        <td><?= $doc['date_creation'] ?></td>
                        <td><?= htmlspecialchars($doc['utilisateur']) ?></td>
                        <td><?= htmlspecialchars($doc['departement']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
