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
$id_post   = $_POST['id_post'];

//Prende i dati del post
$res_post = mysqli_query($connessione, "SELECT * FROM post WHERE ID = '$id_post'");
$post = mysqli_fetch_assoc($res_post);

//Prende i dati della bacheca
$res_bacheca = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
$bacheca = mysqli_fetch_assoc($res_bacheca);

//Controlla se è il creatore della bacheca
$is_creatore = ($bacheca['ID_Creatore'] == $id_utente);

//Controlla se è moderatore
$res_mod = mysqli_query($connessione,
    "SELECT * FROM modera 
    WHERE ID_Utente = '$id_utente' AND ID_Bacheca = '$bacheca[ID]'");
$is_moderatore = (mysqli_num_rows($res_mod) > 0);

//Può eliminare solo l'autore, il creatore della bacheca o un moderatore
if ($post['ID_Utente'] == $id_utente || $is_creatore || $is_moderatore) {
    mysqli_query($connessione, "DELETE FROM post WHERE ID = '$id_post'");
    echo 'ok';
} else {
    echo 'errore';
}
?>