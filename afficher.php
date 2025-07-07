<?php
include 'config.php';

try {
    $stmt = $pdo->query("
        SELECT d.titre, d.date_creation, d.chemin_fichier, dep.nom AS departement
        FROM documents d
        JOIN departements dep ON d.departement_id = dep.id
        ORDER BY d.date_creation DESC
    ");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$documents) {
        echo "<tr><td colspan='4'>⚠️ Aucun document trouvé.</td></tr>";
    } else {
        foreach ($documents as $doc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($doc['titre']) . "</td>";
            echo "<td>" . $doc['date_creation'] . "</td>";
            echo "<td>" . ($doc['chemin_fichier'] ? "<a href='" . htmlspecialchars($doc['chemin_fichier']) . "' target='_blank'>" . basename($doc['chemin_fichier']) . "</a>" : "Aucun fichier") . "</td>";
            echo "<td class='actions'>
                    <button class='btn' onclick=\"openModal('editModal')\">✏️ Modifier</button>
                    <button class='btn' onclick='confirmDelete()'>🗑️ Supprimer</button>
                  </td>";
            echo "</tr>";
        }
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='4'>❌ Erreur : " . $e->getMessage() . "</td></tr>";
}
?>
