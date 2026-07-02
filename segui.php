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
$errore = "";
$successo = "";

//Segui un utente
if (isset($_POST['segui'])) {
    $username = trim($_POST['username']);

    //Cerca l'utente per username
    $res_utente = mysqli_query($connessione, "SELECT * FROM utente WHERE Username = '$username'");
    $utente_trovato = mysqli_fetch_assoc($res_utente);

    if (!$utente_trovato) {
        $errore = "Utente non trovato.";
    } elseif ($utente_trovato['ID'] == $id_utente) {
        $errore = "Non puoi seguire te stesso.";
    } else {
        //Controlla se lo segue già
        $res_check = mysqli_query($connessione, "SELECT * FROM segue WHERE ID_Seguace = '$id_utente' AND ID_Seguito = '$utente_trovato[ID]'");

        if (mysqli_num_rows($res_check) > 0) {
            $errore = "Stai già seguendo questo utente.";
        } else {
            mysqli_query($connessione,
                "INSERT INTO segue (ID_Seguace, ID_Seguito)
                 VALUES ('$id_utente', '$utente_trovato[ID]')");
            $successo = "Ora segui " . $utente_trovato['Username'] . "!";
        }
    }
}

//Smetti di seguire un utente
if (isset($_POST['smetti'])) {
    $id_seguito = $_POST['id_seguito'];
    mysqli_query($connessione,
        "DELETE FROM segue 
         WHERE ID_Seguace = '$id_utente' AND ID_Seguito = '$id_seguito'");
    $successo = "Hai smesso di seguire questo utente.";
}

//Prende la lista degli utenti seguiti
$res_seguiti = mysqli_query($connessione, "SELECT * FROM segue WHERE ID_Seguace = '$id_utente'");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Utenti seguiti</title>
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
        
        <h2>Utenti che segui</h2>
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>

        <?php if ($successo): ?>
            <p style="color:green;"><?php echo $successo; ?></p>
        <?php endif; ?>
        
        <!-- Form cerca e segui utente -->
        <h3>Segui un nuovo utente</h3>
        <form method="POST" action="">
            <input type="text" name="username"
                   placeholder="Username dell'utente" required>
                    <button type="submit" name="segui">
                        <i class="fa-solid fa-user-plus"></i> Segui
                    </button>
        </form>

        <hr>
        
        <!-- Lista utenti seguiti -->
        <h3>Stai seguendo</h3>
        
        <?php if (mysqli_num_rows($res_seguiti) == 0): ?>
            <p>Non stai seguendo nessun utente.</p>
        <?php else: ?>

        <?php while ($seguito = mysqli_fetch_assoc($res_seguiti)): ?>
            <?php
            //Prende lo username dell'utente seguito
            $res_username = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$seguito[ID_Seguito]'");
            $utente_seguito = mysqli_fetch_assoc($res_username);
            ?>

            <div class="utente-seguito">
                <span>
                    <i class="fa-solid fa-user"></i>
                    <strong><?php echo $utente_seguito['Username']; ?></strong>
                </span>
                <form method="POST" action="" class="form-inline-smetti">
                    <input type="hidden" name="id_seguito"
                           value="<?php echo $seguito['ID_Seguito']; ?>">
                    <button type="submit" name="smetti">
                        <i class="fa-solid fa-user-minus"></i> Smetti di seguire
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
        <?php endif; ?>
    
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
   </body>
</html>