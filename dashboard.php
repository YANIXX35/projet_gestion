<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestion de Documents</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f6fa;
        }

        header {
            background-color: #007BFF;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            background-color: #007BFF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        .form-container {
            margin-top: 30px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<header>
    <h1>Tableau de Bord - Gestion des Documents</h1>
</header>

<div class="container">
    <div class="top-actions">
        <h2>Mes Documents</h2>
        <a href="?ajouter=1" class="btn">➕ Ajouter un document</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Date</th>
                <th>Département</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Charger tous les documents avec le nom du département
            $sql = "SELECT d.id, d.titre, d.date_creation, dep.nom AS departement 
                    FROM documents d
                    JOIN departements dep ON d.departement_id = dep.id
                    ORDER BY d.date_creation DESC";

            $documents = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if (count($documents) > 0) {
                foreach ($documents as $doc) {
                    echo "<tr>
                            <td>" . htmlspecialchars($doc['titre']) . "</td>
                            <td>" . $doc['date_creation'] . "</td>
                            <td>" . htmlspecialchars($doc['departement']) . "</td>
                            <td>
                                <a class='btn' href='#'>✏️ Modifier</a>
                                <a class='btn' href='#'>🗑️ Supprimer</a>
                            </td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>Aucun document trouvé.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php
    // Affiche le formulaire si on a cliqué sur "Ajouter un document"
    if (isset($_GET['ajouter'])) {
        echo '<div class="form-container">';
        include 'ajout_document.php';
        echo '</div>';
    }
    ?>
</div>

</body>
</html>
