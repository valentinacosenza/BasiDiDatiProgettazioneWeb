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
$id_bacheca = $_GET['id'];
$errore = "";
$successo = "";

//Prende i dati della bacheca
$res = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$id_bacheca'");
$bacheca = mysqli_fetch_assoc($res);

//Solo il creatore può gestire gli inviti
if (!$bacheca || $bacheca['ID_Creatore'] != $id_utente) {
    header("Location: bacheche.php");
    exit();
}

//La bacheca deve essere privata
if ($bacheca['Tipologia'] != 'privata') {
    header("Location: bacheca.php?id=$id_bacheca");
    exit();
}

//Invia un invito
if (isset($_POST['invita'])) {
    $username = trim($_POST['username']);

    //Cerca l'utente per username
    $res_utente = mysqli_query($connessione, "SELECT * FROM utente WHERE Username = '$username'");
    $utente_invitato = mysqli_fetch_assoc($res_utente);

    if (!$utente_invitato) {
        $errore = "Utente non trovato.";
    } elseif ($utente_invitato['ID'] == $id_utente) {
        $errore = "Non puoi invitare te stesso.";
    } else {
        //Controlla se è già stato invitato
        $res_check = mysqli_query($connessione, "SELECT * FROM invita WHERE ID_Utente = '$utente_invitato[ID]' AND ID_Bacheca = '$id_bacheca'");

        if (mysqli_num_rows($res_check) > 0) {
            $errore = "Questo utente è già stato invitato.";
        } else {
            $data_invito = date('Y-m-d H:i:s');
            mysqli_query($connessione,
                "INSERT INTO invita (ID_Utente, ID_Bacheca, DataInvito, Stato)
                VALUES ('$utente_invitato[ID]', '$id_bacheca', '$data_invito', 'in attesa')");
            $successo = "Invito inviato a " . $utente_invitato['Username'] . "!";
        }
    }
}

//Revoca un invito
if (isset($_POST['revoca'])) {
    $id_invitato = $_POST['id_invitato'];
    mysqli_query($connessione, "DELETE FROM invita WHERE ID_Utente = '$id_invitato' AND ID_Bacheca = '$id_bacheca'");
    $successo = "Invito revocato.";
}

//Prende la lista degli inviti
$res_inviti = mysqli_query($connessione,
    "SELECT * FROM invita WHERE ID_Bacheca = '$id_bacheca'");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Gestione inviti</title>
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
        
        <h2>Inviti per "<?php echo $bacheca['Titolo']; ?>"</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
            <?php endif; ?>
            <?php if ($successo): ?>
                <p style="color:green;"><?php echo $successo; ?></p>
            <?php endif; ?>
            
            <!-- Form invia invito -->
            <h3>Invita un utente</h3>
            <form method="POST" action="">
                <input type="text" name="username"
                       placeholder="Username dell'utente" required>
                <button type="submit" name="invita">
                    <i class="fa-solid fa-envelope"></i> Invia invito
                </button>
            </form>
            
            <!-- Lista inviti -->
            <h3>Inviti inviati</h3>
            
            <?php if (mysqli_num_rows($res_inviti) == 0): ?>
                <p>Nessun invito inviato.</p>
                <?php else: ?>
                    <?php while ($invito = mysqli_fetch_assoc($res_inviti)): ?>
                    <?php
                    // Prende lo username dell'utente invitato
                    $res_username = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$invito[ID_Utente]'");
                    $utente_inv = mysqli_fetch_assoc($res_username);
                    ?>
                    
                    <div class="invito">
                        <span>
                            <i class="fa-solid fa-user"></i>
                            <strong><?php echo $utente_inv['Username']; ?></strong>
                            — Stato: <em><?php echo $invito['Stato']; ?></em>
                            — Inviato il: <?php echo $invito['DataInvito']; ?>
                        </span>
                    
                        <form method="POST" action="" class="form-inline-revoca">
                            <input type="hidden" name="id_invitato"
                                   value="<?php echo $invito['ID_Utente']; ?>">
                                    <button type="submit" name="revoca">
                                        <i class="fa-solid fa-user-xmark"></i> Revoca
                                    </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                
                <!--Includo il file per il footer -->
                <?php include 'footer.php'; ?>
    </body>

</html>