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

//Controlla che sia premium
if ($_SESSION['tipologia'] != 'premium') {
    header("Location: account.php");
    exit();
}

//Prende la data di scadenza attuale
$res = mysqli_query($connessione, "SELECT * FROM abbonamento WHERE ID_Utente = '$id_utente'");
$abbonamento = mysqli_fetch_assoc($res);

if ($abbonamento) {
    //Calcola la nuova scadenza: 1 anno dalla scadenza attuale
    $nuova_scadenza = date('Y-m-d', strtotime($abbonamento['DataScadenza'] . ' +1 year'));

    mysqli_query($connessione,
        "UPDATE abbonamento SET DataScadenza = '$nuova_scadenza'
         WHERE ID_Utente = '$id_utente'");
}

header("Location: abbonamento.php");
exit();
?>