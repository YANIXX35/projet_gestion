<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "gestion_documents"; // <-- corriger ici

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Échec de connexion : " . $conn->connect_error);
} else {
    echo "✅ Connexion réussie à la base de données.";
}
?>
