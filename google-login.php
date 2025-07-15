<?php
session_start();
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('TON_CLIENT_ID'); // Remplace par ton Client ID de Google Cloud
$client->setClientSecret('TON_CLIENT_SECRET'); // Remplace par ton Client Secret
$client->setRedirectUri('http://localhost/formation/callback.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    $email = $google_account_info->email;
    $name = $google_account_info->name;

    // Vérification si l'email existe déjà
    include 'config.php';
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Connexion existante
        $_SESSION['user'] = ['id' => $existingUser['id'], 'nom' => $existingUser['nom'], 'email' => $email, 'role' => $existingUser['role']];
        header('Location: index.php'); // Redirige vers la page principale
    } else {
        // Création d'un nouveau compte
        $hash = password_hash('default_password', PASSWORD_DEFAULT); // À améliorer avec un mot de passe temporaire
        $sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (:nom, :email, :pass, :role)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nom' => $name, ':email' => $email, ':pass' => $hash, ':role' => 'utilisateur']);

        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $newUser = $stmt->fetch();
        $_SESSION['user'] = ['id' => $newUser['id'], 'nom' => $name, 'email' => $email, 'role' => 'utilisateur'];
        header('Location: callback.php');
    }
    exit;
} else {
    echo '<a href="' . htmlspecialchars($client->createAuthUrl()) . '">Se connecter avec Google</a>';
}
?>