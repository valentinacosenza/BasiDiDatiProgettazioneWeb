<?php
session_start();

//Cancella tutti i dati della sessione
session_destroy();

//Rimanda al login
header("Location: login.php");
exit();
?>