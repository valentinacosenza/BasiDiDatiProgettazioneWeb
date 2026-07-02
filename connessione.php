<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "valentina_cosenza";

$connessione = mysqli_connect($servername, $username, $password, $dbname);

if (!$connessione) {
    die("Connessione fallita: " . mysqli_connect_error());
}
?>