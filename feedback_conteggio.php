<?php
session_start();

//Includo il file per la connessione al database
include 'connessione.php';

//Se non è loggato, rimanda al login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$id_post = $_POST['id_post'];

//Conta upvote
$res_up = mysqli_query($connessione,
    "SELECT COUNT(*) AS tot FROM feedback 
     WHERE ID_Post = '$id_post' AND Tipo = 'upvote'");
$upvote = mysqli_fetch_assoc($res_up);

//Conta downvote
$res_down = mysqli_query($connessione,
    "SELECT COUNT(*) AS tot FROM feedback 
     WHERE ID_Post = '$id_post' AND Tipo = 'downvote'");
$downvote = mysqli_fetch_assoc($res_down);

echo json_encode([
    'upvote'   => $upvote['tot'],
    'downvote' => $downvote['tot']
]);
?>