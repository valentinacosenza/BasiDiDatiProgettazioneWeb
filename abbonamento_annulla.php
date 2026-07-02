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

//Elimina abbonamento
mysqli_query($connessione, "DELETE FROM abbonamento WHERE ID_Utente = '$id_utente'");

//Torna a regular
mysqli_query($connessione, "UPDATE utente SET Tipologia = 'regular' WHERE ID = '$id_utente'");

//Aggiorna la sessione
$_SESSION['tipologia'] = 'regular';

header("Location: account.php");
exit();
?>