<?php
session_start();
include 'config.php';

$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;

if (!$utilisateur_id) {
    header("Location: index.php");
    exit;
}

$message = '';
$avatar_path = 'images/avatar_defaut.png';

$stmt = $pdo->prepare("SELECT avatar FROM utilisateurs WHERE id = ?");
$stmt->execute([$utilisateur_id]);
$avatar = $stmt->fetchColumn();

if ($avatar && file_exists("uploads/avatars/$avatar")) {
    $avatar_path = "uploads/avatars/$avatar";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === 0 && $file['size'] <= 2 * 1024 * 1024) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $ext;
        $target = "uploads/avatars/" . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET avatar = ? WHERE id = ?");
            $stmt->execute([$filename, $utilisateur_id]);
            $message = "✅ Avatar mis à jour.";
            $avatar_path = $target;
        } else {
            $message = "❌ Échec du téléchargement.";
        }
    } else {
        $message = "❌ Fichier trop grand ou invalide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .profile-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            text-align: center;
            width: 400px;
        }

        img.avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
        }

        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 6px;
        }

        .btn:hover {
            background: #0056b3;
        }

        .message {
            margin-top: 10px;
            color: green;
        }
    </style>
</head>
<body>
<div class="profile-box">
    <h2>Mon Profil</h2>
    <img src="<?= htmlspecialchars($avatar_path) ?>" class="avatar" alt="Avatar"><br>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="avatar" accept="image/*" required><br><br>
        <button type="submit" class="btn">Mettre à jour l'avatar</button>
    </form>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <br>
    <a href="dashboard.php" class="btn">⬅️ Retour au Dashboard</a>
</div>
</body>
</html>
