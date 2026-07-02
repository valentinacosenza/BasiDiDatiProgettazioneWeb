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

//Controlla che sia un utente premium
$res = mysqli_query($connessione, "SELECT * FROM utente WHERE ID = '$id_utente'");
$utente = mysqli_fetch_assoc($res);

if ($utente['Tipologia'] != 'premium') {
    header("Location: home.php");
    exit();
}

//Prende i dati dell'abbonamento
$res_abb = mysqli_query($connessione, "SELECT * FROM abbonamento WHERE ID_Utente = '$id_utente'");
$abbonamento = mysqli_fetch_assoc($res_abb);
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Il mio abbonamento</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    
    <body>
        <nav>
            <a href="home.php" class="nav-logo">Haven</a><span>Ciao, <?php echo $_SESSION['username']; ?>!</span>
            <a href="home.php">Home</a>
            <a href="bacheche.php">Bacheche</a>
            <a href="ricerca.php"><i class="fa-solid fa-magnifying-glass"></i> Cerca</a>
            <a href="profilo.php">Profilo</a>
            <a href="account.php">Account</a>
            <a href="logout.php">Esci</a>
        </nav>
        
        <h2><i class="fa-solid fa-crown"></i> Il mio abbonamento</h2>
        
        <?php if ($abbonamento): ?>
            <div class="profilo-info">
                <p>
                    <i class="fa-solid fa-user"></i>
                    <strong>Titolare:</strong> <?php echo $abbonamento['NomeTitolare']; ?>
                </p>
                
                <p>
                    <i class="fa-solid fa-credit-card"></i>
                    <strong>Carta:</strong> <?php echo $abbonamento['CartaCredito']; ?>
                </p>
                
                <p>
                    <i class="fa-solid fa-calendar"></i>
                    <strong>Abbonamento attivo fino al:</strong>
                    
                    <?php
                    $data = new DateTime($abbonamento['DataScadenza']);
                    echo $data->format('d/m/Y');
                    ?>
                </p>
            </div>
            
            <div class="azioni-abbonamento";>
                <a href="abbonamento_rinnova.php"
                   onclick="return confirm('Vuoi rinnovare il tuo abbonamento per un altro anno?')"
                   class="btn-rinnova">
                   <i class="fa-solid fa-rotate-right"></i> Rinnova abbonamento
                </a>
            
                <a href="abbonamento_annulla.php"
                   onclick="return confirm('Sei sicuro di voler annullare il tuo abbonamento?')"
                   class="btn-annulla">
                   <i class="fa-solid fa-xmark"></i> Annulla abbonamento
                </a>
            </div>
        <?php endif; ?>
        
        <br>
        
        <a href="account.php">
            <i class="fa-solid fa-arrow-left"></i> Torna al tuo account
        </a>
        
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>
    
</html>