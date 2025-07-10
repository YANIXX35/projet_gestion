<?php
include 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = isset($_POST["titre"]) ? trim($_POST["titre"]) : '';
    $departement = isset($_POST["departement"]) ? trim($_POST["departement"]) : '';

    if ($titre && $departement) {
        // Ajoute dans la base de données
        $stmt = $conn->prepare("INSERT INTO documents (titre, departement, date_creation) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $titre, $departement);

        if ($stmt->execute()) {
            $message = "✅ Document ajouté avec succès !";
        } else {
            $message = "❌ Erreur lors de l'ajout du document.";
        }
    } else {
        $message = "❌ Veuillez remplir tous les champs.";
    }
}
?>

<?php if ($message): ?>
    <div class="success-message"><?php echo $message; ?></div>
<?php endif; ?>

<h2>📁 Ajouter un document</h2>
<form method="post" action="">
    <label for="titre">Titre du document :</label><br>
    <input type="text" name="titre" id="titre" required style="width:100%; padding:8px; margin-bottom:12px;"><br>

    <label for="departement">Département :</label><br>
    <select name="departement" id="departement" required style="width:100%; padding:8px; margin-bottom:12px;">
        <option value="">-- Sélectionner un département --</option>
        <option value="Finance">Finance</option>
        <option value="RH">Ressources Humaines</option>
        <option value="Informatique">Informatique</option>
    </select><br>

    <button type="submit" class="btn">Ajouter le document</button>
</form>
