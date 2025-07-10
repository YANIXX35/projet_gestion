<?php
session_start();
include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);
$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;

if (!$utilisateur_id || !isset($data['id'], $data['after_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}

// ➤ Ici tu peux stocker une colonne "ordre" dans la table `dossiers` pour gérer l'affichage
// Exemple : mettre "id" juste après "after_id"

echo json_encode(['success' => true]);
