<?php
// download.php
session_start();
include 'config.php';

if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("Fichier non spécifié.");
}

$filename = basename($_GET['file']); // éviter path traversal
$filepath = __DIR__ . "/uploads/" . $filename;

if (!file_exists($filepath)) {
    die("Fichier introuvable.");
}

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
switch ($ext) {
    case 'pdf':
        $mime = 'application/pdf';
        break;
    case 'doc':
    case 'docx':
        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'ppt':
    case 'pptx':
        $mime = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        break;
    default:
        $mime = 'application/octet-stream';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
