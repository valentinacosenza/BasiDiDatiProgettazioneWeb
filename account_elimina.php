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

//Elimina i file fisici delle immagini dei post dell'utente
$res_immagini = mysqli_query($connessione,
    "SELECT Percorso FROM immagine WHERE ID_Post IN (
        SELECT ID FROM post WHERE ID_Utente = '$id_utente'
    )");
while ($immagine = mysqli_fetch_assoc($res_immagini)) {
    $percorso = 'uploads_post_immagine/' . $immagine['Percorso'];
    if (file_exists($percorso)) {
        unlink($percorso);
    }
}

//Elimina i file fisici dei banner delle bacheche dell'utente
$res_banner = mysqli_query($connessione,
    "SELECT Banner FROM bacheca WHERE ID_Creatore = '$id_utente' AND Banner IS NOT NULL");
while ($bacheca = mysqli_fetch_assoc($res_banner)) {
    $percorso = 'uploads_banner/' . $bacheca['Banner'];
    if (file_exists($percorso)) {
        unlink($percorso);
    }
}

//Elimina l'utente (CASCADE elimina automaticamente tutto il resto)
mysqli_query($connessione, "DELETE FROM utente WHERE ID = '$id_utente'");

//Distrugge la sessione
session_destroy();

header("Location: login.php");
exit();
?>