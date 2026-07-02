<?php
session_start();

//Se è già loggato, rimanda alla home
//Altrimenti rimanda al login
if (isset($_SESSION['id'])) {
    header("Location: home.php");
} else {
    header("Location: login.php");
}
exit();
?>