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
$id_immagine = $_POST['id_immagine'];

//Controlla che l'immagine appartenga a un post dell'utente
$res = mysqli_query($connessione,
    "SELECT * FROM immagine WHERE ID = '$id_immagine'
    AND ID_Post IN (
        SELECT ID FROM post WHERE ID_Utente = '$id_utente'
    )");

if (mysqli_num_rows($res) == 1) {
    $immagine = mysqli_fetch_assoc($res);
    //Elimina il file fisico dalla cartella
    $percorso = 'uploads_post_immagine/' . $immagine['Percorso'];
    if (file_exists($percorso)) {
        unlink($percorso);
    }
    mysqli_query($connessione, "DELETE FROM immagine WHERE ID = '$id_immagine'");
    echo 'ok';
} else {
    echo 'errore';
}
?>