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
            $statut = $_POST['statut'] ?? 'en_attente';

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
                            $fichier_nom = '';
                        }
                    } else {
                        $fichier_nom = '';
                    }
                }
                if ($titre && $departement_id && $fichier_nom) {
                    $stmt = $pdo->prepare("INSERT INTO documents (titre, description, fichier, departement_id, utilisateur_id, dossier_id, date_creation, statut) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$titre, $description, $fichier_nom, $departement_id, $utilisateur_id, $dossier_id, $statut]);
                }
            } elseif ($type_contenu === 'texte') {
                $contenu_texte = $_POST['contenu_texte'] ?? '';
                if ($titre && $departement_id && $contenu_texte) {
                    $stmt = $pdo->prepare("INSERT INTO documents (titre, description, contenu_texte, departement_id, utilisateur_id, dossier_id, date_creation, statut) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$titre, $description, $contenu_texte, $departement_id, $utilisateur_id, $dossier_id, $statut]);
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
        if (isset($_POST['filtrer_documents'])) {
            $type = $_POST['type_filtre'] ?? '';
            $statut = $_POST['statut_filtre'] ?? '';
            $date = $_POST['date_filtre'] ?? '';
            $recherche = $_POST['recherche'] ?? '';

            $query = "SELECT d.id, d.titre, d.description, d.fichier, d.contenu_texte, d.date_creation, d.statut, u.nom AS nom_utilisateur, dos.nom AS nom_dossier
                      FROM documents d
                      LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
                      LEFT JOIN dossiers dos ON d.dossier_id = dos.id
                      WHERE 1=1";
            $params = [];

            if ($type) {
                $query .= " AND (d.fichier IS NOT NULL AND ? = 'fichier' OR d.contenu_texte IS NOT NULL AND ? = 'texte')";
                $params[] = $type;
                $params[] = $type;
            }
            if ($statut) {
                $query .= " AND d.statut = ?";
                $params[] = $statut;
            }
            if ($date) {
                $query .= " AND DATE(d.date_creation) = ?";
                $params[] = $date;
            }
            if ($recherche) {
                $query .= " AND (d.titre LIKE ? OR d.description LIKE ?)";
                $params[] = "%$recherche%";
                $params[] = "%$recherche%";
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("SELECT d.id, d.titre, d.description, d.fichier, d.contenu_texte, d.date_creation, d.statut, u.nom AS nom_utilisateur, dos.nom AS nom_dossier
                                  FROM documents d
                                  LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
                                  LEFT JOIN dossiers dos ON d.dossier_id = dos.id");
            $stmt->execute();
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $stmt = $pdo->prepare("SELECT d.id, d.titre, d.description, d.fichier, d.contenu_texte, d.date_creation, d.statut, u.nom AS nom_utilisateur, dos.nom AS nom_dossier
                              FROM documents d
                              LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
                              LEFT JOIN dossiers dos ON d.dossier_id = dos.id");
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare("SELECT id, nom, email, role, statut FROM utilisateurs");
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        body {
            transition: background-color 0.3s, color 0.3s;
        }
        body.dark-mode {
            background-color: #1a202c;
            color: #e2e8f0;
        }
        body.dark-mode .sidebar {
            background: #2d3748;
        }
        body.dark-mode .sidebar a:hover, body.dark-mode .sidebar a.active {
            background: #4a5568;
        }
        body.dark-mode .card {
            background: #2d3748;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        body.dark-mode .header {
            background: #2b6cb0;
        }
        body.dark-mode table th {
            background: #2b6cb0;
        }
        body.dark-mode tr:hover {
            background: #4a5568;
        }
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
            transition: transform 0.3s;
        }
        .action-btn:hover {
            transform: scale(1.2);
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 10;
            top: 100%;
            right: 0;
        }
        body.dark-mode .dropdown-menu {
            background: #2d3748;
        }
        .dropdown-menu a {
            display: block;
            padding: 10px;
            color: #2c3e50;
        }
        body.dark-mode .dropdown-menu a {
            color: #e2e8f0;
        }
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        .dossier-carte {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.3s;
        }
        .dossier-carte:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .dossier-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        .dossier-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        .dossier-nom {
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2 class="text-xl font-bold p-4">Menu Admin</h2>
            <a href="?section=utilisateurs" class="<?= $active_section === 'utilisateurs' ? 'active' : '' ?>">
                <span class="mr-2">👥</span> Utilisateurs
            </a>
            <a href="?section=documents" class="<?= $active_section === 'documents' ? 'active' : '' ?>">
                <span class="mr-2">📚</span> Documents
            </a>
            <a href="?section=stats" class="<?= $active_section === 'stats' ? 'active' : '' ?>">
                <span class="mr-2">📊</span> Statistiques
            </a>
            <a href="profil.php">
                <span class="mr-2">👤</span> Profil
            </a>
            <a href="logout.php" class="mt-4 block p-2 bg-red-600 hover:bg-red-700 rounded">
                <span class="mr-2">🚪</span> Déconnexion
            </a>
        </div>

        <!-- Content -->
        <div class="content">
            <header class="header bg-blue-900 text-white p-4 mb-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Bienvenue <?= htmlspecialchars($nom_utilisateur) ?> (<?= htmlspecialchars($role) ?>)</h2>
                    <div class="flex items-center gap-4">
                        <button id="theme-toggle" class="text-white hover:text-gray-300">
                            <span id="theme-icon">🌞</span>
                        </button>
                        <div class="dropdown relative">
                            <img src="<?= htmlspecialchars($avatar_path) ?>" alt="avatar" class="avatar">
                            <div class="dropdown-menu">
                                <a href="profil.php">Profil</a>
                                <a href="logout.php">Déconnexion</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <?php if ($role === 'admin'): ?>
                <!-- Section Utilisateurs -->
                <div class="card" id="utilisateurs" <?= $active_section === 'utilisateurs' ? '' : 'style="display:none;"' ?>>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Gestion des utilisateurs</h3>
                    <table>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['role']) ?></td>
                                <td><?= htmlspecialchars($utilisateur['statut']) ?></td>
                                <td>
                                    <div class="dropdown relative inline-block">
                                        <button class="action-btn text-blue-500 hover:text-blue-700">⚙️</button>
                                        <div class="dropdown-menu">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="utilisateur_id" value="<?= $utilisateur['id'] ?>" />
                                                <button type="submit" name="desactiver_compte" onclick="return confirm('Désactiver ce compte ?')">Désactiver</button>
                                                <button type="submit" name="reinitialiser_mot_de_passe" onclick="return confirm('Réinitialiser le mot de passe ?')">Réinitialiser mot de passe</button>
                                                <button type="submit" name="supprimer_compte" onclick="return confirm('Supprimer ce compte ?')">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Section Documents -->
                <div class="card" id="documents" <?= $active_section === 'documents' ? '' : 'style="display:none;"' ?>>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Tous les documents</h3>
                    <form method="POST" class="filter-container">
                        <select name="type_filtre" class="border rounded p-2">
                            <option value="">Type</option>
                            <option value="fichier">Fichier</option>
                            <option value="texte">Texte</option>
                        </select>
                        <select name="statut_filtre" class="border rounded p-2">
                            <option value="">Statut</option>
                            <option value="en_attente">En attente</option>
                            <option value="valide">Validé</option>
                            <option value="archive">Archivé</option>
                        </select>
                        <input type="date" name="date_filtre" class="border rounded p-2" />
                        <input type="text" name="recherche" placeholder="Rechercher..." class="border rounded p-2" />
                        <button type="submit" name="filtrer_documents" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Filtrer</button>
                    </form>
                    <table>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Utilisateur</th>
                            <th>Dossier</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($documents as $document): ?>
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td><?= htmlspecialchars($document['titre']) ?></td>
                                <td><?= htmlspecialchars($document['description'] ?: 'Aucune description') ?></td>
                                <td><?= htmlspecialchars($document['nom_utilisateur'] ?: 'Inconnu') ?></td>
                                <td><?= htmlspecialchars($document['nom_dossier'] ?: 'Aucun dossier') ?></td>
                                <td><?= htmlspecialchars($document['date_creation']) ?></td>
                                <td><?= htmlspecialchars($document['statut'] ?: 'En attente') ?></td>
                                <td>
                                    <div class="dropdown relative inline-block">
                                        <button class="action-btn text-blue-500 hover:text-blue-700">⚙️</button>
                                        <div class="dropdown-menu">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="document_id" value="<?= $document['id'] ?>" />
                                                <button type="submit" name="supprimer_document" onclick="return confirm('Supprimer ce document ?')">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Section Statistiques -->
                <div class="card" id="stats" <?= $active_section === 'stats' ? '' : 'style="display:none;"' ?>>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Statistiques</h3>
                    <div class="chart-container">
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="container">
                    <form method="POST" style="margin: 20px 0; display: flex; gap: 10px;">
                        <input type="text" name="nom" placeholder="Nom du dossier" required class="border rounded p-2" />
                        <button type="submit" name="nouveau_dossier" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">📁 Créer</button>
                    </form>

                    <div class="galerie" id="galerie" style="display: flex; flex-direction: column; gap: 20px;">
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
                                <div class="dossier-actions flex justify-end gap-2 p-2">
                                    <div class="dropdown relative inline-block">
                                        <button class="action-btn text-blue-500 hover:text-blue-700">⚙️</button>
                                        <div class="dropdown-menu">
                                            <form method="POST" style="display:flex; flex-direction: column;">
                                                <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>" />
                                                <input type="text" name="nouveau_nom" placeholder="Renommer" class="border rounded p-2 mb-2" />
                                                <button type="submit" name="renommer_dossier" class="p-2 hover:bg-gray-200">Renommer</button>
                                                <button type="submit" name="supprimer_dossier" class="p-2 hover:bg-red-100 text-red-600" onclick="return confirm('Supprimer ce dossier ?')">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <a href="documents_dossier.php?id=<?= $dossier['id'] ?>" style="text-decoration:none; color:inherit;">
                                    <img src="<?= htmlspecialchars($vignette) ?>" class="dossier-img" alt="vignette" />
                                    <div class="dossier-nom">📁 <?= htmlspecialchars($dossier['nom']) ?></div>
                                </a>
                                <form method="POST" enctype="multipart/form-data" class="doc-form mt-2">
                                    <input type="hidden" name="dossier_id" value="<?= $dossier['id'] ?>" />
                                    <select name="type_contenu" required class="border rounded p-2 mb-2 w-full">
                                        <option value="fichier">Fichier</option>
                                        <option value="texte">Texte</option>
                                    </select>
                                    <input type="text" name="titre" placeholder="Titre" required class="border rounded p-2 mb-2 w-full" />
                                    <textarea name="description" placeholder="Description" class="border rounded p-2 mb-2 w-full"></textarea>
                                    <select name="departement_id" required class="border rounded p-2 mb-2 w-full">
                                        <option value="">-- Département --</option>
                                        <?php foreach ($departements as $dep): ?>
                                            <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="statut" required class="border rounded p-2 mb-2 w-full">
                                        <option value="en_attente">En attente</option>
                                        <option value="valide">Validé</option>
                                        <option value="archive">Archivé</option>
                                    </select>
                                    <div id="fichier_fields_<?= $dossier['id'] ?>" style="display: block;">
                                        <input type="file" name="fichier" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" class="border rounded p-2 mb-2 w-full" />
                                    </div>
                                    <div id="texte_fields_<?= $dossier['id'] ?>" style="display: none;">
                                        <textarea name="contenu_texte" placeholder="Contenu du texte" rows="4" class="border rounded p-2 mb-2 w-full"></textarea>
                                    </div>
                                    <button type="submit" name="ajouter_contenu" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 w-full">📥 Ajouter</button>
                                </form>
                                <script>
                                    document.querySelector('select[name="type_contenu"]').addEventListener('change', function() {
                                        const fichierFields = document.getElementById('fichier_fields_<?= $dossier['id'] ?>');
                                        const texteFields = document.getElementById('texte_fields_<?= $dossier['id'] ?>');
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
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            themeIcon.textContent = isDark ? '🌜' : '🌞';
            document.cookie = `theme=${isDark ? 'dark' : 'light'}; path=/; max-age=31536000`;
        });

        // Chart.js
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