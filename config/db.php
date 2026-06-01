<?php
$host = 'localhost';
$dbname = 'connecthub';
$user = 'root';
$pass = 'root';
$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) die("Erreur DB : " . mysqli_connect_error());
?>