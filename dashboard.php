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
$message = '';

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

        if (isset($_POST['ajouter_fichier'])) {
            $dossier_id = intval($_POST['dossier_id'] ?? 0);
            $titre = trim($_POST['titre'] ?? '');
            $departement_id = intval($_POST['departement_id'] ?? 0);

            if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $fichier_nom = basename($_FILES['fichier']['name']);
                $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                $file_extension = strtolower(pathinfo($fichier_nom, PATHINFO_EXTENSION));
                if (in_array($file_extension, $allowed_types)) {
                    $upload_dir = "uploads/";
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $unique_fichier_nom = time() . '_' . $fichier_nom;
                    $destination = $upload_dir . $unique_fichier_nom;
                    if (move_uploaded_file($_FILES['fichier']['tmp_name'], $destination)) {
                        $stmt = $pdo->prepare("INSERT INTO documents (titre, fichier, departement_id, utilisateur_id, dossier_id, date_creation, statut) VALUES (?, ?, ?, ?, ?, NOW(), 'en_attente')");
                        $stmt->execute([$titre, $unique_fichier_nom, $departement_id, $utilisateur_id, $dossier_id]);
                        $message = "✅ Fichier ajouté avec succès !";
                    } else {
                        $message = "❌ Erreur lors du téléchargement du fichier.";
                    }
                } else {
                    $message = "❌ Type de fichier non autorisé. Utilisez : " . implode(', ', $allowed_types);
                }
            } elseif ($titre && $departement_id) {
                $message = "❌ Veuillez sélectionner un fichier.";
            } else {
                $message = "❌ Veuillez remplir tous les champs.";
            }
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM dossiers WHERE utilisateur_id = ? ORDER BY ordre ASC, id ASC");
    $stmt->execute([$utilisateur_id]);
    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $departements = $pdo->query("SELECT id, nom FROM departements")->fetchAll(PDO::FETCH_ASSOC);

    // Prepare room-like data with document links
    $rooms = [];
    foreach ($dossiers as $dossier) {
        $docs = $pdo->prepare("SELECT COUNT(*) as total, GROUP_CONCAT(statut) as statuts, MAX(date_creation) as last_modified FROM documents WHERE dossier_id = ? AND utilisateur_id = ?");
        $docs->execute([$dossier['id'], $utilisateur_id]);
        $doc_info = $docs->fetch();
        $tags = $doc_info['statuts'] ? array_unique(array_filter(explode(',', $doc_info['statuts']))) : ['aucun'];
        $rooms[] = [
            'id' => $dossier['id'],
            'title' => $dossier['nom'],
            'tags' => $tags,
            'icon' => '📁',
            'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
            'total_docs' => $doc_info['total'],
            'last_modified' => $doc_info['last_modified'] ?: 'N/A'
        ];
    }
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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
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
        body.dark-mode .room-list .room {
            background: #2d3748;
            border-color: #4a5568;
        }
        body.dark-mode .details-panel {
            background: #2d3748;
            border-color: #4a5568;
        }
        body.dark-mode .tag {
            background: #4a5568;
        }
        body.dark-mode .drop-zone {
            background: #2d3748;
            border-color: #4a5568;
        }
        body.dark-mode .drop-zone.dragover {
            background: #4a5568;
        }
        body.dark-mode .file-item {
            background: #2d3748;
            border-color: #4a5568;
        }
        .sidebar {
            width: 220px;
            background: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #ddd;
            color: #333;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            transition: all 0.3s;
        }
        .sidebar h2 {
            font-size: 18px;
        }
        .sidebar a, .sidebar button {
            display: block;
            width: 100%;
            margin: 15px 0;
            padding: 10px;
            background: #2979ff;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: left;
            text-decoration: none;
        }
        .sidebar a:hover, .sidebar button:hover {
            background: #1a60d8;
        }
        .sidebar a.active {
            background: #1a60d8;
        }
        .menu-item {
            margin: 15px 0;
            color: #333;
            cursor: pointer;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .main {
            flex: 1;
            display: flex;
            flex-direction: row;
        }
        .room-list {
            flex: 2;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .room {
            width: 180px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            position: relative;
            cursor: pointer;
            transition: box-shadow 0.3s;
        }
        .room:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .room .icon {
            font-size: 30px;
        }
        .room .title {
            font-weight: bold;
            margin-top: 10px;
        }
        .tags {
            margin-top: 10px;
        }
        .tag {
            display: inline-block;
            background: #eee;
            border-radius: 12px;
            padding: 3px 8px;
            font-size: 12px;
            margin: 2px;
        }
        .details-panel {
            flex: 1;
            padding: 20px;
            border-left: 1px solid #ddd;
            background: #fafafa;
        }
        .details-panel h3 {
            margin-top: 0;
        }
        .property {
            margin-bottom: 10px;
        }
        .property span {
            font-weight: bold;
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
        .dropdown-menu a, .dropdown-menu button {
            display: block;
            padding: 10px;
            color: #2c3e50;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
        }
        body.dark-mode .dropdown-menu a, body.dark-mode .dropdown-menu button {
            color: #e2e8f0;
        }
        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        .drop-zone {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            background: #f9f9f9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone.dragover {
            background: #e0e0e0;
            border-color: #3498db;
        }
        .file-preview {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .file-item {
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
            background: #fff;
            display: flex;
            align-items: center;
        }
        body.dark-mode .file-item {
            background: #2d3748;
            border-color: #4a5568;
        }
        .file-item img {
            max-width: 40px;
            max-height: 40px;
            margin-right: 5px;
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2 class="text-xl font-bold">Menu</h2>
            <?php if ($role === 'admin'): ?>
                <a href="?section=utilisateurs" class="<?= $active_section === 'utilisateurs' ? 'active' : '' ?>">
                    <span class="mr-2">👥</span> Utilisateurs
                </a>
                <a href="?section=documents" class="<?= $active_section === 'documents' ? 'active' : '' ?>">
                    <span class="mr-2">📚</span> Documents
                </a>
                <a href="?section=stats" class="<?= $active_section === 'stats' ? 'active' : '' ?>">
                    <span class="mr-2">📊</span> Statistiques
                </a>
            <?php else: ?>
                <button onclick="document.getElementById('new-room-form').style.display='block'">Nouveau dossier</button>
                <a href="documents_dossier.php" class="menu-item">📄 Mes documents</a>
                <div class="menu-item">🗃️ Archive</div>
                <div class="menu-item">⚙️ Paramètres</div>
                <div class="menu-item">🗑️ Corbeille</div>
            <?php endif; ?>
            <a href="profil.php">
                <span class="mr-2">👤</span> Profil
            </a>
            <a href="logout.php" class="bg-red-600 hover:bg-red-700">
                <span class="mr-2">🚪</span> Déconnexion
            </a>
            <div style="font-size: 13px; background: #e3f2fd; padding: 10px; border-radius: 5px; margin-top: 20px;">
                Utilisez notre plateforme comme un pro<br>
                <a href="#">En savoir plus</a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <header class="header bg-blue-900 text-white p-4 mb-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Bienvenue <?= htmlspecialchars($nom_utilisateur) ?> (<?= htmlspecialchars($role) ?>)</h2>
                    <div class="flex items-center gap-4">
                        <button id="theme-toggle" class="text-white hover:text-gray-300">
                            <span id="theme-icon"><?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? '🌜' : '🌞' ?></span>
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
                    <form method="POST" class="flex gap-2 mb-4">
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
                <div class="main">
                    <!-- Room list -->
                    <div class="room-list">
                        <form id="new-room-form" method="POST" style="display:none; margin-bottom: 20px; width: 100%;">
                            <input type="text" name="nom" placeholder="Nom du dossier" required class="border rounded p-2 mr-2" />
                            <button type="submit" name="nouveau_dossier" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Créer</button>
                            <button type="button" onclick="this.parentElement.style.display='none'" class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600">Annuler</button>
                        </form>
                        <?php
                        $stmt = $pdo->prepare("SELECT d.id, d.titre, d.fichier, dos.nom AS nom_dossier FROM documents d LEFT JOIN dossiers dos ON d.dossier_id = dos.id WHERE d.utilisateur_id = ?");
                        $stmt->execute([$utilisateur_id]);
                        $user_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rooms as $room): ?>
                            <div class="room" style="border-color: <?= htmlspecialchars($room['color']) ?>">
                                <div class="dropdown relative inline-block" style="position: absolute; top: 10px; right: 10px;">
                                    <button class="action-btn text-blue-500 hover:text-blue-700">⚙️</button>
                                    <div class="dropdown-menu">
                                        <form method="POST" style="display:flex; flex-direction: column;">
                                            <input type="hidden" name="dossier_id" value="<?= $room['id'] ?>" />
                                            <input type="text" name="nouveau_nom" placeholder="Renommer" class="border rounded p-2 mb-2" />
                                            <button type="submit" name="renommer_dossier" class="p-2 hover:bg-gray-200">Renommer</button>
                                            <button type="submit" name="supprimer_dossier" class="p-2 hover:bg-red-100 text-red-600" onclick="return confirm('Supprimer ce dossier ?')">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                                <a href="documents_dossier.php?id=<?= $room['id'] ?>" style="text-decoration:none; color:inherit;">
                                    <div class="icon"><?= $room['icon'] ?></div>
                                    <div class="title"><?= htmlspecialchars($room['title']) ?></div>
                                    <div class="tags">
                                        <?php foreach ($room['tags'] as $tag): ?>
                                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php foreach ($user_documents as $doc): ?>
                                        <?php if ($doc['nom_dossier'] === $room['title'] && $doc['fichier']): ?>
                                            <a href="uploads/<?= htmlspecialchars($doc['fichier']) ?>" target="_blank" class="block mt-2 text-blue-500 hover:underline">
                                                <?= htmlspecialchars($doc['titre'] ?: $doc['fichier']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Details panel -->
                    <div class="details-panel">
                        <h3>Ajouter un fichier</h3>
                        <?php if ($message): ?>
                            <div class="text-green-500 mb-4" id="message"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        <form method="post" action="" enctype="multipart/form-data" class="space-y-4">
                            <div class="drop-zone" id="dropZone">
                                <p>Glissez et déposez un fichier ici ou <span class="text-blue-500 cursor-pointer">parcourez</span></p>
                                <input type="file" id="fileInput" name="fichier" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png" class="hidden" required>
                                <div class="file-preview" id="filePreview"></div>
                            </div>
                            <input type="text" name="titre" placeholder="Nom du fichier" required class="w-full p-2 border rounded">
                            <select name="dossier_id" required class="w-full p-2 border rounded">
                                <option value="">-- Sélectionner un dossier --</option>
                                <?php foreach ($dossiers as $dossier): ?>
                                    <option value="<?= $dossier['id'] ?>"><?= htmlspecialchars($dossier['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="departement_id" required class="w-full p-2 border rounded">
                                <option value="">-- Sélectionner un département --</option>
                                <?php foreach ($departements as $dep): ?>
                                    <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="ajouter_fichier" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Ajouter</button>
                        </form>
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

        // Chart.js for admin
        <?php if ($role === 'admin'): ?>
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
        <?php endif; ?>

        // Drag and Drop functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updatePreview();
        });
        fileInput.addEventListener('change', updatePreview);

        function updatePreview() {
            filePreview.innerHTML = '';
            if (fileInput.files.length > 0) {
                for (const file of fileInput.files) {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    const icon = getFileIcon(file.name);
                    fileItem.innerHTML = `${icon} ${file.name}`;
                    filePreview.appendChild(fileItem);
                }
            }
        }

        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            switch (ext) {
                case 'pdf': return '<img src="https://img.icons8.com/color/48/000000/pdf.png" alt="PDF">';
                case 'doc':
                case 'docx': return '<img src="https://img.icons8.com/color/48/000000/microsoft-word.png" alt="Word">';
                case 'ppt':
                case 'pptx': return '<img src="https://img.icons8.com/color/48/000000/microsoft-powerpoint.png" alt="PowerPoint">';
                case 'jpg':
                case 'jpeg':
                case 'png': return '<img src="https://img.icons8.com/color/48/000000/image.png" alt="Image">';
                default: return '<span>📄</span>';
            }
        }

        // Auto-clear message after 3 seconds
        const messageDiv = document.getElementById('message');
        if (messageDiv) {
            setTimeout(() => messageDiv.style.display = 'none', 3000);
        }
    </script>
</body>
</html>