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

        if (isset($_POST['ajouter_document'])) {
            $titre = $_POST["titre"] ?? '';
            $description = $_POST["description"] ?? '';
            $type_format = $_POST["type_format"] ?? '';
            $auteur_nom = trim($_POST["auteur"] ?? '');
            $categorie_nom = trim($_POST["categorie"] ?? '');
            $confidentialite = $_POST["confidentialite"] ?? '';
            $statut = $_POST["statut"] ?? '';
            $departement_id = intval($_POST["departement_id"] ?? 0);
            $dossier_id = intval($_POST['dossier_id'] ?? 0);

            $fichier_nom = '';
            if (isset($_FILES["fichier"]) && $_FILES["fichier"]["error"] === UPLOAD_ERR_OK) {
                $fichier_nom = basename($_FILES["fichier"]["name"]);
                move_uploaded_file($_FILES["fichier"]["tmp_name"], "uploads/" . $fichier_nom);
            }

            if ($titre && $departement_id && $auteur_nom && $categorie_nom) {
                // Gérer auteur
                $stmtAuteur = $pdo->prepare("SELECT id FROM auteurs WHERE nom = ?");
                $stmtAuteur->execute([$auteur_nom]);
                $auteur = $stmtAuteur->fetch();
                if (!$auteur) {
                    $insertAuteur = $pdo->prepare("INSERT INTO auteurs (nom) VALUES (?)");
                    $insertAuteur->execute([$auteur_nom]);
                    $auteur_id = $pdo->lastInsertId();
                } else {
                    $auteur_id = $auteur['id'];
                }

                // Gérer catégorie
                $stmtCat = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
                $stmtCat->execute([$categorie_nom]);
                $categorie = $stmtCat->fetch();
                if (!$categorie) {
                    $insertCat = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                    $insertCat->execute([$categorie_nom]);
                    $categorie_id = $pdo->lastInsertId();
                } else {
                    $categorie_id = $categorie['id'];
                }

                // Vérifier que le dossier appartient bien à l'utilisateur sinon mettre NULL
                $stmtDossier = $pdo->prepare("SELECT COUNT(*) FROM dossiers WHERE id = ? AND utilisateur_id = ?");
                $stmtDossier->execute([$dossier_id, $utilisateur_id]);
                $existe_dossier = $stmtDossier->fetchColumn();
                if (!$existe_dossier) {
                    $dossier_id = null;
                }

                $stmt = $pdo->prepare("INSERT INTO documents (
                    titre, description, type_format, auteur_id, categorie_id,
                    confidentialite, statut, fichier, departement_id, utilisateur_id, dossier_id, date_creation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                $stmt->execute([
                    $titre, $description, $type_format, $auteur_id, $categorie_id,
                    $confidentialite, $statut, $fichier_nom, $departement_id, $utilisateur_id, $dossier_id
                ]);
            }
        }
    }

    // Récupérer dossiers utilisateur
    $stmt = $pdo->prepare("SELECT * FROM dossiers WHERE utilisateur_id = ?");
    $stmt->execute([$utilisateur_id]);
    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer départements
    $departements = $pdo->query("SELECT id, nom FROM departements")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Utilisateur</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 0; }
        header { background: #007BFF; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        header img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        header div { display: flex; align-items: center; gap: 15px; }
        .btn-profil {
            background:#28a745; 
            padding: 8px 12px; 
            border-radius: 6px; 
            color: white; 
            text-decoration: none;
            font-weight: bold;
        }
        .container { max-width: 1100px; margin: 20px auto; }
        .galerie { display: flex; flex-wrap: wrap; gap: 20px; padding: 20px; }
        .dossier-carte {
            width: 250px; background-color: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden; transition: transform 0.2s; position: relative; display: flex; flex-direction: column;
        }
        .dossier-carte:hover { transform: scale(1.02); }
        .dossier-img { width: 100%; height: 150px; object-fit: cover; cursor: pointer; }
        .dossier-nom { padding: 10px; font-weight: bold; text-align: center; background: #007BFF; color: white; cursor: pointer; }
        .dossier-actions, .doc-form { padding: 10px; display: flex; flex-direction: column; gap: 8px; }
        .btn { background: #007BFF; color: white; padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-red { background-color: red; }
        input, select, textarea { width: 100%; padding: 6px; border-radius: 6px; border: 1px solid #ccc; }
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body>
<header>
    <h2>Bienvenue <?= htmlspecialchars($nom_utilisateur) ?></h2>
    <div>
        <img src="<?= htmlspecialchars($avatar_path) ?>" alt="avatar" />
        <a href="profil.php" class="btn-profil">Modifier Profil</a>
    </div>
</header>

<div class="container">
    <form method="POST" style="margin: 20px 0;">
        <input type="text" name="nom" placeholder="Nom du dossier" required />
        <button type="submit" name="nouveau_dossier" class="btn">Créer un dossier</button>
    </form>

    <div class="galerie">
        <?php foreach ($dossiers as $dossier): ?>
            <?php
            // Récupérer 1 document pour vignette (image)
            $docs = $pdo->prepare("SELECT * FROM documents WHERE dossier_id = ? AND utilisateur_id = ? LIMIT 1");
            $docs->execute([$dossier['id'], $utilisateur_id]);
            $doc = $docs->fetch();

            $vignette = "images/folder.png";
            if ($doc && in_array(strtolower(pathinfo($doc['fichier'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])) {
                $vignette = "uploads/" . $doc['fichier'];
            }
            ?>
            <div class="dossier-carte">
                <a href="documents_dossier.php?id=<?= $dossier['id'] ?>" style="text-decoration:none; color:inherit;">
                    <img src="<?= htmlspecialchars($vignette) ?>" class="dossier-img" alt="vignette" />
                    <div class="dossier-nom">📁 <?= htmlspecialchars($dossier['nom']) ?></div>
                </a>

                <div class="dossier-actions">
                    <form method="POST" style="display:flex; gap: 5px;">
                        <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>" />
                        <input type="text" name="nouveau_nom" placeholder="Renommer" />
                        <button type="submit" name="renommer_dossier" class="btn" title="Renommer">✏️</button>
                        <button type="submit" name="supprimer_dossier" class="btn btn-red" onclick="return confirm('Supprimer ce dossier ?')" title="Supprimer">🗑️</button>
                    </form>
                </div>

                <form method="POST" enctype="multipart/form-data" class="doc-form">
                    <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>" />
                    <input type="text" name="titre" placeholder="Titre du document" required />
                    <textarea name="description" placeholder="Description"></textarea>
                    <input type="text" name="type_format" placeholder="Type/Format" />
                    <input type="text" name="auteur" placeholder="Auteur" required />
                    <input type="text" name="categorie" placeholder="Catégorie" required />
                    <select name="confidentialite">
                        <option value="public">Public</option>
                        <option value="interne">Interne</option>
                        <option value="confidentiel">Confidentiel</option>
                        <option value="secret">Secret</option>
                    </select>
                    <select name="statut">
                        <option value="brouillon">Brouillon</option>
                        <option value="valide">Validé</option>
                        <option value="archive">Archivé</option>
                        <option value="rejete">Rejeté</option>
                    </select>
                    <select name="departement_id" required>
                        <option value="">-- Département --</option>
                        <?php foreach ($departements as $dep): ?>
                            <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="file" name="fichier" />
                    <button type="submit" name="ajouter_document" class="btn">📥 Ajouter</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
