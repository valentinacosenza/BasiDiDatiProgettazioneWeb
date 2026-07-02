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
$id_post = $_POST['id_post'];

//Prende i dati del post
$res_post = mysqli_query($connessione, "SELECT * FROM post WHERE ID = '$id_post'");
$post = mysqli_fetch_assoc($res_post);

//Prende i dati della bacheca
$res_bacheca = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
$bacheca = mysqli_fetch_assoc($res_bacheca);

//Controlla se l'utente è il creatore della bacheca
$is_creatore = ($bacheca['ID_Creatore'] == $id_utente);

//Controlla se l'utente è moderatore della bacheca
$res_mod = mysqli_query($connessione, "SELECT * FROM modera WHERE ID_Utente = '$id_utente' AND ID_Bacheca = '$bacheca[ID]'");
$is_moderatore = (mysqli_num_rows($res_mod) > 0);

//Solo creatore o moderatore possono rifiutare (elimina il post)
if ($is_creatore || $is_moderatore) {
    mysqli_query($connessione,
        "DELETE FROM post WHERE ID = '$id_post'");
    echo 'ok';
} else {
    echo 'errore';
}
?>