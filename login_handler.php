<?php
session_start();
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['mot_de_passe'])) {
                // ✅ Connexion réussie
                $_SESSION['utilisateur_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['role'] = $user['role'];
                header("Location: dashboard.php");
                exit;
            } else {
                // ❌ Mauvais mot de passe → notifier l’utilisateur
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'binksyao95@gmail.com';
                    $mail->Password = 'phkhvhvljtdvroaj'; // mot de passe application Gmail
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('binksyao95@gmail.com', 'Sécurité YANISSE');
                    $mail->addAddress($user['email'], $user['nom']);

                    $mail->isHTML(true);
                    $mail->Subject = "Tentative de connexion suspecte détectée !";
                    $mail->Body = "
                        <p>Bonjour <b>{$user['nom']}</b>,</p>
                        <p>Quelqu'un a tenté de se connecter à votre compte avec votre email <b>{$user['email']}</b> mais avec un mot de passe incorrect.</p>
                        <p>Si c'était bien vous, vous pouvez ignorer ce message. Sinon, nous vous conseillons de <b>changer votre mot de passe</b> immédiatement.</p>
                        <p>👉 <a href='http://localhost/formation/change_password.php'>Changer mon mot de passe</a></p>
                        <p style='color:red;'>Date tentative : " . date("d/m/Y H:i:s") . "</p>
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Erreur email de sécurité : " . $mail->ErrorInfo);
                }

                $erreur = "Email ou mot de passe incorrect.";
            }
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    } else {
        $erreur = "Tous les champs sont requis.";
    }
}
?>
