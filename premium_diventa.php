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

if ($_SESSION['tipologia'] == 'premium') {
    header("Location: account.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_titolare = mysqli_real_escape_string($connessione, trim($_POST['nome_titolare']));
    $carta = trim($_POST['carta']);
    $scadenza = trim($_POST['scadenza']);
    $cvv = trim($_POST['cvv']);

    //Controllo nome titolare
    if (empty($nome_titolare)) {
        $errore = "Inserisci il nome del titolare della carta.";
    } elseif (!preg_match('/^[a-zA-ZàèéìòùÀÈÉÌÒÙ]+\s+[a-zA-ZàèéìòùÀÈÉÌÒÙ]+$/', $nome_titolare)) {
        $errore = "Inserisci nome e cognome completi (es. Mario Rossi).";
    }

    //Controllo numero carta — tra 13 e 19 cifre (copre tutti i circuiti)
    elseif (!preg_match('/^\d{13,19}$/', $carta)) {
        $errore = "Il numero carta deve contenere tra 13 e 19 cifre numeriche.";
    } 

    //Controllo scadenza formato MM/YY
    elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $scadenza)) {
        $errore = "La scadenza deve essere nel formato MM/AA (es. 12/27).";
    }

    //Controllo CVV — 3 o 4 cifre
    elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errore = "Il CVV deve contenere 3 o 4 cifre.";
    }
    else {
        $parts = explode('/', $scadenza);
        $data_scadenza_carta = '20' . $parts[1] . '-' . $parts[0] . '-01';
        
        if ($data_scadenza_carta < date('Y-m-d')) {
            $errore = "La carta è scaduta.";
            } else {
                //Data scadenza abbonamento = oggi + 1 anno
                $data_scadenza_abb = date('Y-m-d', strtotime('+1 year'));
                
                mysqli_query($connessione, "UPDATE utente SET Tipologia = 'premium' WHERE ID = '$id_utente'");
                
                mysqli_query($connessione,
                    "INSERT INTO abbonamento (NomeTitolare, DataScadenza, CartaCredito, ID_Utente)
                    VALUES ('$nome_titolare', '$data_scadenza_abb', '$carta', '$id_utente')");

                // Aggiorna la sessione
                $_SESSION['tipologia'] = 'premium';

                header("Location: account.php");
                exit();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Passa a Premium</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    
    <body>
        <nav>
            <a href="home.php" class="nav-logo">Haven</a> <span>Ciao, <?php echo $_SESSION['username']; ?>!</span>
            <a href="home.php">Home</a>
            <a href="bacheche.php">Bacheche</a>
            <a href="ricerca.php"><i class="fa-solid fa-magnifying-glass"></i> Cerca</a>
            <a href="profilo.php">Profilo</a>
            <a href="account.php">Account</a>
            <a href="logout.php">Esci</a>
        </nav>
        
        <h2><i class="fa-solid fa-crown"></i> Passa a Premium</h2>
        
        <!-- Vantaggi premium -->
        <div class="profilo-info">
            <p><i class="fa-solid fa-check"></i> Crea bacheche <strong>private</strong></p>
            <p><i class="fa-solid fa-check"></i> <strong>Invita</strong> utenti nelle tue bacheche private</p>
            <p><i class="fa-solid fa-check"></i> Cambia il tuo <strong>username</strong> ogni volta che vuoi</p>
            <p><i class="fa-solid fa-check"></i> Accesso a tutte le funzionalità base</p>
            <hr>
            <p class="prezzo-premium"><i class="fa-solid fa-tag"></i> Solo <strong>€0,99/anno</strong></p>
        </div>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        
        <h3>Inserisci i dati di pagamento</h3>
        
        <form method="POST" action="">
            <label>Nome e cognome titolare:</label>
            <input type="text" name="nome_titolare" id="nome_titolare"
                   placeholder="Es. Mario Rossi"
                   pattern="[a-zA-ZàèéìòùÀÈÉÌÒÙ]+\s+[a-zA-ZàèéìòùÀÈÉÌÒÙ]+"
                   title="Inserisci nome e cognome separati da uno spazio (es. Mario Rossi)"
                   value="<?php echo isset($nome_titolare) ? $nome_titolare : ''; ?>"
                   required><br>

            <label>Numero carta:</label>
            <input type="text" name="carta" id="carta"
                   placeholder="Es. 4532015112830366"
                   pattern="\d{13,19}"
                   maxlength="19"
                   title="Il numero carta deve contenere tra 13 e 19 cifre numeriche"
                   value="<?php echo isset($carta) ? $carta : ''; ?>"
                   required><br>

            <label>Scadenza (MM/AA):</label>
            <input type="text" name="scadenza" id="scadenza"
                   placeholder="Es. 12/27"
                   pattern="(0[1-9]|1[0-2])\/\d{2}"
                   title="Inserisci la scadenza nel formato MM/AA (es. 12/27)"
                   maxlength="5"
                   value="<?php echo isset($scadenza) ? $scadenza : ''; ?>"
                   required><br>

            <label>CVV:</label>
            <input type="text" name="cvv" id="cvv"
                   placeholder="Es. 123"
                   pattern="\d{3,4}"
                   title="Il CVV deve contenere 3 o 4 cifre numeriche"
                   maxlength="4"
                   required><br>

            <button type="submit">
                <i class="fa-solid fa-crown"></i> Attiva Premium
            </button>
            
            <a href="account.php" class="link-annulla">
                <i class="fa-solid fa-xmark"></i> Annulla
            </a>

        </form>

        <br>

        <script>
            //Inserisco automaticamente lo slash / nella data scadenza
            $(document).ready(function() {
                $('#scadenza').on('input', function() {
                    var val = this.value.replace(/[^0-9]/g, '');
                    if (val.length > 2) {
                        val = val.substring(0, 2) + '/' + val.substring(2, 4);
                    }
                    this.value = val;
                });
            });
        </script>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>