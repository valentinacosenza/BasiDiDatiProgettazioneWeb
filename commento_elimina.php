<?php
session_start();

//Includo il file per la connessione al database
include 'connessione.php';

//Se non è loggato, rimanda al login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$id_utente = $_SESSION['id'];
$id_commento = $_POST['id_commento'];

//Controlla che il commento appartenga all'utente loggato
$res = mysqli_query($connessione, "SELECT * FROM commento WHERE ID = '$id_commento' AND ID_Utente = '$id_utente'");

if (mysqli_num_rows($res) == 1) {
    mysqli_query($connessione, "DELETE FROM commento WHERE ID = '$id_commento'");
    echo 'ok';
} else {
    echo 'errore';
}
?>