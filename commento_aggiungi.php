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
$testo = mysqli_real_escape_string($connessione, trim($_POST['testo']));
$data = date('Y-m-d H:i:s');

//Controllo che il testo non sia vuoto
if (empty($testo)) {
    echo '';
    exit();
}

//Inserisce il commento
mysqli_query($connessione,
    "INSERT INTO commento (Testo, Data, ID_Utente, ID_Post)
     VALUES ('$testo', '$data', '$id_utente', '$id_post')");

$id_commento = mysqli_insert_id($connessione);

//Prende lo username dell'utente
$res = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$id_utente'");
$utente = mysqli_fetch_assoc($res);

//Restituisce l'HTML del nuovo commento da aggiungere alla pagina
echo '
<div class="commento" id="commento-' . $id_commento . '">
    <p class="testo-commento" id="testo-commento-' . $id_commento . '">' . $testo . '</p>
    <small>
        Di <strong>' . $utente['Username'] . '</strong>
        il ' . $data . '
    </small>
    <div class="azioni-commento">
        <button class="btn-modifica-commento" data-id="' . $id_commento . '">
            <i class="fa-solid fa-pen"></i>
        </button>
        <button class="btn-elimina-commento" data-id="' . $id_commento . '">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>
</div>';