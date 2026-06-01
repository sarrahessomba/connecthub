<?php

header('Content-Type: application/json');
require_once '../../config.php'; // On teste juste cet appel
echo json_encode(["status" => "Config chargé sans erreur"]);
exit;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root"; // Identifiant par défaut de MAMP
$pass = "root"; // Mot de passe par défaut de MAMP
$dbname = "connecthub";
$port = "3306"; // Ton port MySQL

// Connexion à la base de données
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    die("Échec de connexion : " . mysqli_connect_error());
}
?>