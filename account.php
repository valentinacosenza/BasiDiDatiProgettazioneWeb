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

//Prende i dati dell'utente
$res = mysqli_query($connessione, "SELECT * FROM utente WHERE ID = '$id_utente'");
$utente = mysqli_fetch_assoc($res);

//Prende gli inviti in attesa
$res_inviti = mysqli_query($connessione, "SELECT * FROM invita WHERE ID_Utente = '$id_utente' AND Stato = 'in attesa'");

//Accetta o rifiuta invito
if (isset($_POST['accetta']) || isset($_POST['rifiuta'])) {
    $id_bacheca_inv = $_POST['id_bacheca'];
    $nuovo_stato = isset($_POST['accetta']) ? 'accettato' : 'rifiutato';
    mysqli_query($connessione, "UPDATE invita SET Stato = '$nuovo_stato' WHERE ID_Utente = '$id_utente' AND ID_Bacheca = '$id_bacheca_inv'");
    header("Location: account.php");
    exit();
}

//Modifica username
if (isset($_POST['modifica_username'])) {
    $nuovo_username = trim($_POST['username']);
    if (empty($nuovo_username)) {
        $errore = "Lo username non può essere vuoto.";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $nuovo_username)) {
        $errore = "Lo username può contenere solo lettere e numeri.";
    } else {
        $res_check = mysqli_query($connessione, "SELECT ID FROM utente WHERE Username = '$nuovo_username'");
        if (mysqli_num_rows($res_check) > 0) {
            $errore = "Username già in uso.";
        } else {
            mysqli_query($connessione, "UPDATE utente SET Username = '$nuovo_username' WHERE ID = '$id_utente'");
            $_SESSION['username'] = $nuovo_username;
            $successo = "Username aggiornato!";
            $res = mysqli_query($connessione, "SELECT * FROM utente WHERE ID = '$id_utente'");
            $utente = mysqli_fetch_assoc($res);
        }
    }
}

//Modifica email
if (isset($_POST['modifica_email'])) {
    $nuova_email = trim($_POST['email']);
    if (!filter_var($nuova_email, FILTER_VALIDATE_EMAIL)) {
        $errore = "Email non valida.";
    } else {
        $res_check = mysqli_query($connessione, "SELECT ID FROM utente WHERE Email = '$nuova_email'");
        if (mysqli_num_rows($res_check) > 0) {
            $errore = "Email già in uso.";
        } else {
            mysqli_query($connessione, "UPDATE utente SET Email = '$nuova_email' WHERE ID = '$id_utente'");
            $successo = "Email aggiornata!";
            $res = mysqli_query($connessione, "SELECT * FROM utente WHERE ID = '$id_utente'");
            $utente = mysqli_fetch_assoc($res);
        }
    }
}

//Modifica password
if (isset($_POST['modifica_password'])) {
    $vecchia = $_POST['vecchia_password'];
    $nuova = $_POST['nuova_password'];
    $conferma = $_POST['conferma_password'];
    if (!password_verify($vecchia, $utente['Password'])) {
        $errore = "La password attuale non è corretta.";
    } elseif (!preg_match('/^[a-zA-Z0-9]{8,}$/', $nuova)) {
        $errore = "La nuova password deve contenere almeno 8 caratteri alfanumerici.";
    } elseif ($nuova != $conferma) {
        $errore = "Le due password non coincidono.";
    } else {
        $nuova_hash = password_hash($nuova, PASSWORD_DEFAULT);
        mysqli_query($connessione, "UPDATE utente SET Password = '$nuova_hash' WHERE ID = '$id_utente'");
        $successo = "Password aggiornata!";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Account</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        <h2>Il tuo account</h2>

        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        <?php if ($successo): ?>
            <p style="color:green;"><?php echo $successo; ?></p>
        <?php endif; ?>

        <!-- Dati account -->
        <div class="profilo-info">
            <p><i class="fa-solid fa-user"></i> <strong>Username:</strong> <?php echo $utente['Username']; ?></p>
            <p><i class="fa-solid fa-envelope"></i> <strong>Email:</strong> <?php echo $utente['Email']; ?></p>
            <p><i class="fa-solid fa-star"></i> <strong>Tipologia:</strong> <?php echo $utente['Tipologia']; ?></p>

            <?php if ($utente['Tipologia'] == 'regular'): ?>
                <p>
                    <i class="fa-solid fa-crown"></i>
                    <a href="premium_diventa.php">Passa a Premium</a>
                </p>
            <?php endif; ?>

            <?php if ($utente['Tipologia'] == 'premium'): ?>
                <?php
                $res_abb = mysqli_query($connessione, "SELECT * FROM abbonamento WHERE ID_Utente = '$id_utente'");
                $abbonamento = mysqli_fetch_assoc($res_abb);
                ?>
                <?php if ($abbonamento): ?>
                    <p>
                        <i class="fa-solid fa-calendar"></i>
                        <strong>Abbonamento attivo fino al:</strong>
                        <?php
                        $data = new DateTime($abbonamento['DataScadenza']);
                        echo $data->format('m/Y');
                        ?>
                    </p>
                <?php endif; ?>
                <p>
                    <i class="fa-solid fa-crown"></i>
                    <strong>Abbonamento:</strong>
                    <a href="abbonamento.php">Gestisci abbonamento</a>
                </p>
            <?php endif; ?>
        </div>

        <hr>

        <!-- Inviti in attesa -->
        <?php if (mysqli_num_rows($res_inviti) > 0): ?>
            <h3><i class="fa-solid fa-envelope"></i> Inviti in attesa</h3>
            <?php while ($invito = mysqli_fetch_assoc($res_inviti)): ?>
                <?php
                $res_bach = mysqli_query($connessione, "SELECT Titolo FROM bacheca WHERE ID = '$invito[ID_Bacheca]'");
                $bach_inv = mysqli_fetch_assoc($res_bach);
                ?>
                <div class="invito">
                    <span>
                        Sei stato invitato nella bacheca
                        <strong><?php echo $bach_inv['Titolo']; ?></strong>
                    </span>
                    <form method="POST" action="" class="form-inline-invito">
                        <input type="hidden" name="id_bacheca" value="<?php echo $invito['ID_Bacheca']; ?>">
                        <button type="submit" name="accetta">
                            <i class="fa-solid fa-check"></i> Accetta
                        </button>
                        <button type="submit" name="rifiuta">
                            <i class="fa-solid fa-xmark"></i> Rifiuta
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
            <hr>
        <?php endif; ?>

        <!-- Bacheche dove sei moderatore -->
        <?php
        $res_mod = mysqli_query($connessione, 
            "SELECT ID, Titolo FROM bacheca WHERE ID IN (
            SELECT ID_Bacheca FROM modera WHERE ID_Utente = '$id_utente'
            )");
        if (mysqli_num_rows($res_mod) > 0): ?>
            <h3>Sei moderatore di</h3>
            <?php while ($b = mysqli_fetch_assoc($res_mod)): ?>
                <div class="moderatore">
                    <span>
                        <i class="fa-solid fa-user-shield"></i>
                        <a href="bacheca.php?id=<?php echo $b['ID']; ?>">
                            <strong><?php echo $b['Titolo']; ?></strong>
                        </a>
                    </span>
                </div>
            <?php endwhile; ?>
            <hr>
        <?php endif; ?>

        <!-- Cambia username — solo premium -->
        <h3>Cambia username</h3>
        <?php if ($utente['Tipologia'] == 'premium'): ?>
            <form method="POST" action="">
                <input type="text" name="username" value="<?php echo $utente['Username']; ?>" required>
                <button type="submit" name="modifica_username">
                    <i class="fa-solid fa-floppy-disk"></i> Salva
                </button>
            </form>
        <?php else: ?>
            <p style="color:var(--testo-chiaro);">
                <i class="fa-solid fa-lock"></i> Funzionalità riservata agli utenti
                <a href="premium_diventa.php">
                    <i class="fa-solid fa-crown"></i> Premium
                </a>
            </p>
        <?php endif; ?>

        <hr>

        <!-- Cambia email -->
        <h3>Cambia email</h3>
        <form method="POST" action="">
            <label>Nuova email:</label>
            <input type="email" name="email" value="<?php echo $utente['Email']; ?>" required>
            <button type="submit" name="modifica_email">
                <i class="fa-solid fa-floppy-disk"></i> Salva
            </button>
        </form>

        <hr>

        <!-- Cambia password -->
        <h3>Cambia password</h3>
        <form method="POST" action="">
            <label>Password attuale:</label>
            <input type="password" name="vecchia_password" required><br>
            <label>Nuova password:</label>
            <input type="password" name="nuova_password" required><br>
            <label>Conferma nuova password:</label>
            <input type="password" name="conferma_password" required><br>
            <button type="submit" name="modifica_password">
                <i class="fa-solid fa-floppy-disk"></i> Aggiorna password
            </button>
        </form>

        <hr>

        <!-- Utenti seguiti -->
        <h3>Utenti che segui</h3>
        
            <a href="segui.php">
                <i class="fa-solid fa-users"></i> Gestisci utenti seguiti
            </a>
        

        <hr>

        <!-- Elimina account -->
        <h3 class="titolo-pericolo">Elimina account</h3>
        <form method="POST" action="">
            <label>Questa azione è irreversibile: tutti i tuoi dati verranno eliminati permanentemente.</label>
            <button type="button" id="btn-elimina-account" class="btn-pericolo">
                <i class="fa-solid fa-trash"></i> Elimina il mio account
            </button>
        </form>

        <br>

        <script>
            $(document).ready(function() {
                $('#btn-elimina-account').click(function() {
                    if (confirm('Sei davvero sicura di voler eliminare il tuo account? Questa azione non può essere annullata.')) {
                       window.location.href = 'account_elimina.php';
                    }
                });
            });
        </script>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>