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
$tipo = $_POST['tipo'];

// Controlla che il post non appartenga all'utente loggato
$res_post = mysqli_query($connessione, "SELECT * FROM post WHERE ID = '$id_post'");
$post = mysqli_fetch_assoc($res_post);

if ($post['ID_Utente'] == $id_utente) {
    echo json_encode(['upvote' => 0, 'downvote' => 0]);
    exit();
}

//Controlla se l'utente ha già messo un feedback su questo post
$res = mysqli_query($connessione, "SELECT * FROM feedback WHERE ID_Utente = '$id_utente' AND ID_Post = '$id_post'");

if (mysqli_num_rows($res) == 1) {
    $feedback = mysqli_fetch_assoc($res);

    //Se clicca lo stesso tipo, rimuove il feedback (toggle)
    if ($feedback['Tipo'] == $tipo) {
        mysqli_query($connessione,
            "DELETE FROM feedback 
             WHERE ID_Utente = '$id_utente' AND ID_Post = '$id_post'");
    } else {
        //Se clicca tipo diverso, aggiorna il feedback
        mysqli_query($connessione,
            "UPDATE feedback SET Tipo = '$tipo'
             WHERE ID_Utente = '$id_utente' AND ID_Post = '$id_post'");
    }
} else {
    //Nessun feedback ancora, lo inserisce
    mysqli_query($connessione,
        "INSERT INTO feedback (ID_Utente, ID_Post, Tipo)
         VALUES ('$id_utente', '$id_post', '$tipo')");
}

//Conta upvote e downvote aggiornati
$res_up = mysqli_query($connessione,
    "SELECT COUNT(*) AS tot FROM feedback 
     WHERE ID_Post = '$id_post' AND Tipo = 'upvote'");
$upvote = mysqli_fetch_assoc($res_up);

$res_down = mysqli_query($connessione,
    "SELECT COUNT(*) AS tot FROM feedback 
     WHERE ID_Post = '$id_post' AND Tipo = 'downvote'");
$downvote = mysqli_fetch_assoc($res_down);

//Risponde con i due contatori
echo json_encode([
    'upvote'   => $upvote['tot'],
    'downvote' => $downvote['tot']
]);
?>