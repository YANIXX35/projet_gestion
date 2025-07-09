<?php
include 'config.php';
session_start();

$message = '';
$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;

if (!$utilisateur_id) {
    echo "<div style='color:red;'>❌ Erreur : utilisateur non connecté.</div>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = $_POST["titre"] ?? '';
    $description = $_POST["description"] ?? '';
    $type_format = $_POST["type_format"] ?? '';
    $auteur_nom = trim($_POST["auteur"] ?? '');
    $categorie_nom = trim($_POST["categorie"] ?? '');
    $confidentialite = $_POST["confidentialite"] ?? '';
    $statut = $_POST["statut"] ?? '';
    $departement_id = intval($_POST["departement_id"] ?? 0);

    $fichier_nom = '';
    if (isset($_FILES["fichier"]) && $_FILES["fichier"]["error"] === UPLOAD_ERR_OK) {
        $fichier_nom = basename($_FILES["fichier"]["name"]);
        move_uploaded_file($_FILES["fichier"]["tmp_name"], "uploads/" . $fichier_nom);
    }

    if ($titre && $departement_id && $auteur_nom && $categorie_nom) {
        try {
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

            $stmt = $pdo->prepare("INSERT INTO documents (
                titre, description, type_format, auteur_id, categorie_id,
                confidentialite, statut, fichier, departement_id, utilisateur_id, date_creation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->execute([
                $titre, $description, $type_format, $auteur_id, $categorie_id,
                $confidentialite, $statut, $fichier_nom, $departement_id, $utilisateur_id
            ]);

            $message = "<div class='success'>✅ Document ajouté avec succès !</div>";
        } catch (PDOException $e) {
            $message = "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='error'>❌ Veuillez remplir tous les champs obligatoires.</div>";
    }
}

$departements = $pdo->query("SELECT id, nom FROM departements")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f2f3f5;
        padding: 20px;
    }

    form {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        max-width: 700px;
        margin: auto;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #007BFF;
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input[type="text"],
    input[type="file"],
    select,
    textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-top: 5px;
        margin-bottom: 15px;
        box-sizing: border-box;
    }

    .btn {
        background-color: #28a745;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        display: block;
        width: 100%;
        margin-top: 10px;
    }

    .btn:hover {
        background-color: #218838;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 6px;
        border: 1px solid #c3e6cb;
        max-width: 700px;
        margin: 20px auto;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 6px;
        border: 1px solid #f5c6cb;
        max-width: 700px;
        margin: 20px auto;
    }
</style>

<?php if ($message): ?>
    <?php echo $message; ?>
<?php endif; ?>

<h2>📁 Ajouter un document</h2>
<form method="post" enctype="multipart/form-data">
    <label for="titre">Titre :</label>
    <input type="text" name="titre" id="titre" required>

    <label for="description">Description :</label>
    <textarea name="description" id="description" rows="3"></textarea>

    <label for="type_format">Type/Format :</label>
    <input type="text" name="type_format" id="type_format">

    <label for="auteur">Auteur :</label>
    <input type="text" name="auteur" id="auteur" required placeholder="Nom complet">

    <label for="categorie">Catégorie :</label>
    <input type="text" name="categorie" id="categorie" required placeholder="Ex: Rapport, Note, Lettre...">

    <label for="confidentialite">Confidentialité :</label>
    <select name="confidentialite" id="confidentialite">
        <option value="public">Public</option>
        <option value="interne">Interne</option>
        <option value="confidentiel">Confidentiel</option>
        <option value="secret">Secret</option>
    </select>

    <label for="statut">Statut :</label>
    <select name="statut" id="statut">
        <option value="brouillon">Brouillon</option>
        <option value="valide">Validé</option>
        <option value="archive">Archivé</option>
        <option value="rejete">Rejeté</option>
    </select>

    <label for="fichier">Fichier :</label>
    <input type="file" name="fichier" id="fichier">

    <label for="departement_id">Département :</label>
    <select name="departement_id" id="departement_id" required>
        <option value="">-- Sélectionner un département --</option>
        <?php foreach ($departements as $dep): ?>
            <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">📤 Ajouter le document</button>
</form>
