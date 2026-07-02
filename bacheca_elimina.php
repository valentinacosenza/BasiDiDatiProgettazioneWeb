<?php
session_start();

//Includo il file per la connessione al database
include 'connessione.php';

//Se non è loggato, rimanda al login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$id_utente  = $_SESSION['id'];
$id_bacheca = $_GET['id'];

//Controlla che esista e che sia il creatore
$res = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$id_bacheca'");
$bacheca = mysqli_fetch_assoc($res);

if (!$bacheca || $bacheca['ID_Creatore'] != $id_utente) {
    header("Location: bacheche.php");
    exit();
}

//Elimina il file banner fisico se presente
if ($bacheca['Banner']) {
    $percorso = 'uploads_banner/' . $bacheca['Banner'];
    if (file_exists($percorso)) {
        unlink($percorso);
    }
}

//Elimina la bacheca (CASCADE elimina automaticamente post, commenti ecc.)
mysqli_query($connessione, "DELETE FROM bacheca WHERE ID = '$id_bacheca'");

header("Location: bacheche.php");
exit();
?>