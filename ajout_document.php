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
            // Vérifier ou insérer l'auteur
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

            // Vérifier ou insérer la catégorie
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

            // Insertion du document avec utilisateur_id
            $stmt = $pdo->prepare("INSERT INTO documents (
                titre, description, type_format, auteur_id, categorie_id,
                confidentialite, statut, fichier, departement_id, utilisateur_id, date_creation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->execute([
                $titre, $description, $type_format, $auteur_id, $categorie_id,
                $confidentialite, $statut, $fichier_nom, $departement_id, $utilisateur_id
            ]);

            $message = "<div style='color: green;'>✅ Document ajouté avec succès !</div>";
        } catch (PDOException $e) {
            $message = "<div style='color: red;'>❌ Erreur : " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div style='color: red;'>❌ Veuillez remplir tous les champs obligatoires.</div>";
    }
}

$departements = $pdo->query("SELECT id, nom FROM departements")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($message): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<h2>📁 Ajouter un document</h2>
<form method="post" enctype="multipart/form-data">
    <label>Titre :</label>
    <input type="text" name="titre" required style="width:100%; padding:8px; margin-bottom:10px;"><br>

    <label>Description :</label>
    <textarea name="description" style="width:100%; padding:8px; margin-bottom:10px;"></textarea><br>

    <label>Type/Format :</label>
    <input type="text" name="type_format" style="width:100%; padding:8px; margin-bottom:10px;"><br>

    <label>Auteur :</label>
    <input type="text" name="auteur" required placeholder="Nom complet" style="width:100%; padding:8px; margin-bottom:10px;"><br>

    <label>Catégorie :</label>
    <input type="text" name="categorie" required placeholder="Ex: Rapport, Note, Lettre..." style="width:100%; padding:8px; margin-bottom:10px;"><br>

    <label>Confidentialité :</label>
    <select name="confidentialite" style="width:100%; padding:8px; margin-bottom:10px;">
        <option value="public">Public</option>
        <option value="interne">Interne</option>
        <option value="confidentiel">Confidentiel</option>
        <option value="secret">Secret</option>
    </select><br>

    <label>Statut :</label>
    <select name="statut" style="width:100%; padding:8px; margin-bottom:10px;">
        <option value="brouillon">Brouillon</option>
        <option value="valide">Validé</option>
        <option value="archive">Archivé</option>
        <option value="rejete">Rejeté</option>
    </select><br>

    <label>Fichier :</label>
    <input type="file" name="fichier" style="margin-bottom:10px;"><br>

    <label>Département :</label>
    <select name="departement_id" required style="width:100%; padding:8px; margin-bottom:12px;">
        <option value="">-- Sélectionner un département --</option>
        <?php foreach ($departements as $dep): ?>
            <option value="<?php echo $dep['id']; ?>"><?php echo htmlspecialchars($dep['nom']); ?></option>
        <?php endforeach; ?>
    </select><br>

    <button type="submit" class="btn">Ajouter le document</button>
</form>
