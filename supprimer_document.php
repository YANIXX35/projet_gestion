<?php
session_start();
include 'config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$utilisateur_id = $_SESSION['utilisateur_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['document_id']) && is_numeric($_POST['document_id'])) {
        $document_id = intval($_POST['document_id']);
        $action = $_POST['action'] ?? '';

        // Vérifier que le document appartient à l'utilisateur
        $stmt = $pdo->prepare("SELECT statut FROM documents WHERE id = ? AND utilisateur_id = ?");
        $stmt->execute([$document_id, $utilisateur_id]);
        $document = $stmt->fetch();

        if ($document) {
            if ($action === 'restaurer' && $document['statut'] === 'supprimé') {
                $stmt = $pdo->prepare("UPDATE documents SET statut = 'en_attente' WHERE id = ? AND utilisateur_id = ?");
                $stmt->execute([$document_id, $utilisateur_id]);
                $message = "✅ Document restauré avec succès !";
            } elseif ($action === 'supprimer_definitif' && $document['statut'] === 'supprimé') {
                $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ? AND utilisateur_id = ?");
                $stmt->execute([$document_id, $utilisateur_id]);
                $message = "✅ Document supprimé définitivement !";
            } else {
                $message = "❌ Action non autorisée ou document introuvable.";
            }
        } else {
            $message = "❌ Document introuvable ou non autorisé.";
        }
    } else {
        $message = "❌ ID de document invalide.";
    }
}

// Rediriger vers la corbeille avec un message
header("Location: dashboard.php?section=corbeille&message=" . urlencode($message));
exit;
?>