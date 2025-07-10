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
$active_section = isset($_GET['section']) ? $_GET['section'] : 'utilisateurs';

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
                $stmt = $pdo->prepare("INSERT INTO dossiers (nom, utilisateur_id, ordre) VALUES (?, ?, 0)");
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

        if (isset($_POST['ajouter_contenu'])) {
            $dossier_id = intval($_POST['dossier_id'] ?? 0);
            $type_contenu = $_POST['type_contenu'] ?? 'fichier';
            $titre = $_POST['titre'] ?? '';
            $description = $_POST['description'] ?? '';
            $departement_id = intval($_POST['departement_id'] ?? 0);

            if ($type_contenu === 'fichier') {
                $fichier_nom = '';
                if (isset($_FILES["fichier"]) && $_FILES["fichier"]["error"] === UPLOAD_ERR_OK) {
                    $fichier_nom = basename($_FILES["fichier"]["name"]);
                    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                    $file_extension = strtolower(pathinfo($fichier_nom, PATHINFO_EXTENSION));
                    if (in_array($file_extension, $allowed_types)) {
                        $upload_dir = "uploads/";
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        if (move_uploaded_file($_FILES["fichier"]["tmp_name"], $upload_dir . $fichier_nom)) {
                            // Fichier téléchargé avec succès
                        } else {
                            $fichier_nom = ''; // Échec du téléchargement
                        }
                    } else {
                        $fichier_nom = ''; // Type de fichier non autorisé
                    }
                }
                if ($titre && $departement_id && $fichier_nom) {
                    $stmt = $pdo->prepare("INSERT INTO documents (titre, description, fichier, departement_id, utilisateur_id, dossier_id, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$titre, $description, $fichier_nom, $departement_id, $utilisateur_id, $dossier_id]);
                }
            } elseif ($type_contenu === 'texte') {
                $contenu_texte = $_POST['contenu_texte'] ?? '';
                if ($titre && $departement_id && $contenu_texte) {
                    $stmt = $pdo->prepare("INSERT INTO documents (titre, description, contenu_texte, departement_id, utilisateur_id, dossier_id, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$titre, $description, $contenu_texte, $departement_id, $utilisateur_id, $dossier_id]);
                }
            }
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM dossiers WHERE utilisateur_id = ? ORDER BY ordre ASC, id ASC");
    $stmt->execute([$utilisateur_id]);
    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $departements = $pdo->query("SELECT id, nom FROM departements")->fetchAll(PDO::FETCH_ASSOC);
}

if ($role === 'admin') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['supprimer_compte'])) {
            $id = $_POST['utilisateur_id'];
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND id != ?");
            $stmt->execute([$id, $utilisateur_id]);
        }
        if (isset($_POST['desactiver_compte'])) {
            $id = $_POST['utilisateur_id'];
            $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'inactif' WHERE id = ? AND id != ?");
            $stmt->execute([$id, $utilisateur_id]);
        }
        if (isset($_POST['reinitialiser_mot_de_passe'])) {
            $id = $_POST['utilisateur_id'];
            $nouveau_mot_de_passe = bin2hex(random_bytes(8));
            $mot_de_passe_hash = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ? AND id != ?");
            $stmt->execute([$mot_de_passe_hash, $id, $utilisateur_id]);
        }
        if (isset($_POST['supprimer_document'])) {
            $id = $_POST['document_id'];
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$id]);
        }
    }

    $stmt = $pdo->prepare("SELECT d.id, d.titre, d.description, d.fichier, d.contenu_texte, d.date_creation, u.nom AS nom_utilisateur, dos.nom AS nom_dossier
                          FROM documents d
                          LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
                          LEFT JOIN dossiers dos ON d.dossier_id = dos.id");
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, nom, email, role, statut FROM utilisateurs");
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Données pour les statistiques
    $stats_users = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs")->fetch()['total'];
    $stats_docs = $pdo->query("SELECT COUNT(*) as total FROM documents")->fetch()['total'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            transition: all 0.3s;
        }
        .sidebar a {
            color: white;
            padding: 15px 20px;
            display: block;
            text-decoration: none;
            transition: background 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #34495e;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: <?= $active_section === 'utilisateurs' || $active_section === 'documents' || $active_section === 'stats' ? 'block' : 'none' ?>;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #3498db;
            color: white;
            padding: 10px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .action-btn {
            margin-right: 5px;
            transition: color 0.3s;
        }
        .action-btn:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2 class="text-xl font-bold p-4">Menu Admin</h2>
            <a href="?section=utilisateurs" class="<?= $active_section === 'utilisateurs' ? 'active' : '' ?>">Utilisateurs</a>
            <a href="?section=documents" class="<?= $active_section === 'documents' ? 'active' : '' ?>">Documents</a>
            <a href="?section=stats" class="<?= $active_section === 'stats' ? 'active' : '' ?>">Statistiques</a>
            <a href="profil.php">Profil</a>
            <a href="logout.php" class="mt-4 block p-2 bg-red-600 hover:bg-red-700 rounded">Déconnexion</a>
        </div>

        <!-- Content -->
        <div class="content">
            <header class="bg-blue-900 text-white p-4 mb-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Bienvenue <?= htmlspecialchars($nom_utilisateur) ?> (<?= htmlspecialchars($role) ?>)</h2>
                    <div class="flex items-center gap-4">
                        <img src="<?= htmlspecialchars($avatar_path) ?>" alt="avatar" class="w-12 h-12 rounded-full border-2 border-white">
                    </div>
                </div>
            </header>

            <?php if ($role === 'admin'): ?>
                <!-- Section Utilisateurs -->
                <div class="card" id="utilisateurs" <?= $active_section === 'utilisateurs' ? '' : 'style="display:none;"' ?>>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Gestion des utilisateurs</h3>
                    <table>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr class="hover:bg-gray-100">
                                <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['role']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['statut']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="utilisateur_id" value="<?= $utilisateur['id'] ?>" />
                                        <button type="submit" name="desactiver_compte" class="action-btn text-red-500 hover:text-red-700" onclick="return confirm('Désactiver ce compte ?')">✖</button>
                                        <button type="submit" name="reinitialiser_mot_de_passe" class="action-btn text-yellow-500 hover:text-yellow-700" onclick="return confirm('Réinitialiser le mot de passe ?')">🔑</button>
                                        <button type="submit" name="supprimer_compte" class="action-btn text-red-700 hover:text-red-900" onclick="return confirm('Supprimer ce compte ?')">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Section Documents -->
                <div class="card" id="documents" <?= $active_section === 'documents' ? '' : 'style="display:none;"' ?>>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Tous les documents</h3>
                    <table>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Utilisateur</th>
                            <th>Dossier</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($documents as $document): ?>
                            <tr class="hover:bg-gray-100">
                                <td><?= htmlspecialchars($document['titre']) ?></td>
                                <td><?= htmlspecialchars($document['description'] ?: 'Aucune description') ?></td>
                                <td><?= htmlspecialchars($document['nom_utilisateur'] ?: 'Inconnu') ?></td>
                                <td><?= htmlspecialchars($document['nom_dossier'] ?: 'Aucun dossier') ?></td>
                                <td><?= htmlspecialchars($document['date_creation']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="document_id" value="<?= $document['id'] ?>" />
                                        <button type="submit" name="supprimer_document" class="action-btn text-red-700 hover:text-red-900" onclick="return confirm('Supprimer ce document ?')">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Section Statistiques -->
                <div class="card" id="stats" <?= $active_section === 'stats' ? '' : 'style="display:none;"' ?>>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Statistiques</h3>
                    <div class="chart-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="container">
                    <form method="POST" style="margin: 20px 0;">
                        <input type="text" name="nom" placeholder="Nom du dossier" required />
                        <button type="submit" name="nouveau_dossier" class="btn">Créer un dossier</button>
                    </form>

                    <div class="galerie" id="galerie">
                        <?php foreach ($dossiers as $dossier): ?>
                            <?php
                            $docs = $pdo->prepare("SELECT * FROM documents WHERE dossier_id = ? AND utilisateur_id = ? LIMIT 1");
                            $docs->execute([$dossier['id'], $utilisateur_id]);
                            $doc = $docs->fetch();
                            $vignette = "images/folder.png";
                            if ($doc && $doc['fichier'] && file_exists("uploads/" . $doc['fichier']) && in_array(strtolower(pathinfo($doc['fichier'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])) {
                                $vignette = "uploads/" . $doc['fichier'];
                            }
                            ?>
                            <div class="dossier-carte" draggable="true" data-id="<?= $dossier['id'] ?>">
                                <a href="documents_dossier.php?id=<?= $dossier['id'] ?>" style="text-decoration:none; color:inherit;">
                                    <img src="<?= htmlspecialchars($vignette) ?>" class="dossier-img" alt="vignette" />
                                    <div class="dossier-nom">📁 <?= htmlspecialchars($dossier['nom']) ?></div>
                                </a>
                                <div class="dossier-actions">
                                    <form method="POST" style="display:flex; gap: 5px;">
                                        <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>" />
                                        <input type="text" name="nouveau_nom" placeholder="Renommer" />
                                        <button type="submit" name="renommer_dossier" class="btn">✏️</button>
                                        <button type="submit" name="supprimer_dossier" class="btn btn-red" onclick="return confirm('Supprimer ce dossier ?')">🗑️</button>
                                    </form>
                                </div>
                                <form method="POST" enctype="multipart/form-data" class="doc-form">
                                    <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>" />
                                    <select name="type_contenu" required>
                                        <option value="fichier">Fichier</option>
                                        <option value="texte">Texte</option>
                                    </select>
                                    <input type="text" name="titre" placeholder="Titre" required />
                                    <textarea name="description" placeholder="Description"></textarea>
                                    <select name="departement_id" required>
                                        <option value="">-- Département --</option>
                                        <?php foreach ($departements as $dep): ?>
                                            <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="fichier_fields" style="display: block;">
                                        <input type="file" name="fichier" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" />
                                    </div>
                                    <div id="texte_fields" style="display: none;">
                                        <textarea name="contenu_texte" placeholder="Contenu du texte" rows="4"></textarea>
                                    </div>
                                    <button type="submit" name="ajouter_contenu" class="btn">📥 Ajouter</button>
                                </form>
                                <script>
                                    document.querySelector('select[name="type_contenu"]').addEventListener('change', function() {
                                        const fichierFields = document.getElementById('fichier_fields');
                                        const texteFields = document.getElementById('texte_fields');
                                        if (this.value === 'fichier') {
                                            fichierFields.style.display = 'block';
                                            texteFields.style.display = 'none';
                                        } else {
                                            fichierFields.style.display = 'none';
                                            texteFields.style.display = 'block';
                                        }
                                    });
                                </script>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Graphique avec Chart.js
            const ctx = document.getElementById('myChart').getContext('2d');
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Utilisateurs', 'Documents'],
                    datasets: [{
                        label: 'Nombre total',
                        data: [<?= $stats_users ?>, <?= $stats_docs ?>],
                        backgroundColor: ['#3498db', '#e74c3c'],
                        borderColor: ['#2980b9', '#c0392b'],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </body>
</html>