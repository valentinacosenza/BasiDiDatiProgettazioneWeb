<?php
session_start();

//Includo il file per la connessione al database
include 'connessione.php';

//Se non è loggato, rimanda al login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$id_utente  = $_SESSION['id'];
$id_bacheca = $_GET['id'];
$errore = "";
$successo = "";

//Prende i dati della bacheca
$res = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$id_bacheca'");
$bacheca = mysqli_fetch_assoc($res);

//Solo il creatore può gestire i moderatori
if (!$bacheca || $bacheca['ID_Creatore'] != $id_utente) {
    header("Location: bacheche.php");
    exit();
}

//Aggiunge un moderatore
if (isset($_POST['aggiungi'])) {
    $username = trim($_POST['username']);

    //Cerca l'utente per username
    $res_utente = mysqli_query($connessione, "SELECT * FROM utente WHERE Username = '$username'");
    $nuovo_mod = mysqli_fetch_assoc($res_utente);

    if (!$nuovo_mod) {
        $errore = "Utente non trovato.";
    } elseif ($nuovo_mod['ID'] == $id_utente) {
        $errore = "Non puoi aggiungere te stesso come moderatore.";
    } else {
        //Controlla se è già moderatore
        $res_check = mysqli_query($connessione, "SELECT * FROM modera WHERE ID_Utente = '$nuovo_mod[ID]' AND ID_Bacheca = '$id_bacheca'");

        if (mysqli_num_rows($res_check) > 0) {
            $errore = "Questo utente è già moderatore.";
        } else {
            mysqli_query($connessione,
                "INSERT INTO modera (ID_Utente, ID_Bacheca)
                 VALUES ('$nuovo_mod[ID]', '$id_bacheca')");
            $successo = "Moderatore aggiunto!";
        }
    }
}

//Rimuove un moderatore
if (isset($_POST['rimuovi'])) {
    $id_mod = $_POST['id_mod'];
    mysqli_query($connessione,
        "DELETE FROM modera 
         WHERE ID_Utente = '$id_mod' AND ID_Bacheca = '$id_bacheca'");
    $successo = "Moderatore rimosso!";
}

//Prende la lista dei moderatori attuali
$res_moderatori = mysqli_query($connessione, "SELECT * FROM modera WHERE ID_Bacheca = '$id_bacheca'");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Gestione moderatori</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    
    <body>
        <nav>
            <a href="home.php" class="nav-logo">Haven</a><span>Ciao, <?php echo $_SESSION['username']; ?>!</span>
            <a href="home.php">Home</a>
            <a href="bacheca.php?id=<?php echo $id_bacheca; ?>">Torna alla bacheca</a>
            <a href="ricerca.php"><i class="fa-solid fa-magnifying-glass"></i> Cerca</a>
            <a href="profilo.php">Profilo</a>
            <a href="account.php">Account</a>
            <a href="logout.php">Esci</a>
        </nav>
        
        <h2>Moderatori di "<?php echo $bacheca['Titolo']; ?>"</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        <?php if ($successo): ?>
            <p style="color:green;"><?php echo $successo; ?></p>
        <?php endif; ?>
        
        <!-- Form aggiungi moderatore -->
        <h3>Aggiungi moderatore</h3>
        <form method="POST" action="">
            <input type="text" name="username" 
                   placeholder="Username dell'utente" required>
                    <button type="submit" name="aggiungi">
                        <i class="fa-solid fa-user-plus"></i> Aggiungi
                    </button>
        </form>

        <br>
        
        <!-- Lista moderatori attuali -->
        <h3>Moderatori attuali</h3>
        
        <?php if (mysqli_num_rows($res_moderatori) == 0): ?>
            <p>Nessun moderatore aggiunto.</p>
            <?php else: ?>
                <?php while ($mod = mysqli_fetch_assoc($res_moderatori)): ?>
                <?php
                //Prende lo username del moderatore
                $res_username = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$mod[ID_Utente]'");
                $utente_mod = mysqli_fetch_assoc($res_username);
                ?>
                
                <div class="moderatore">
                    <span>
                        <i class="fa-solid fa-user-shield"></i>
                        <?php echo $utente_mod['Username']; ?>
                    </span>
                    
                    <form method="POST" action="" class="form-inline-rimuovi">
                        <input type="hidden" name="id_mod" 
                               value="<?php echo $mod['ID_Utente']; ?>">
                                <button type="submit" name="rimuovi">
                                    <i class="fa-solid fa-user-minus"></i> Rimuovi
                                </button>
                    </form>
                </div>
                <?php endwhile; ?>
        <?php endif; ?>
        
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>