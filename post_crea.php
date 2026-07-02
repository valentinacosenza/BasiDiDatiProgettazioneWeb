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
$id_bacheca = $_GET['id_bacheca'];
$errore = "";

//Controlla che la bacheca esista
$res_bacheca = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$id_bacheca'");
$bacheca = mysqli_fetch_assoc($res_bacheca);

if (!$bacheca) {
    header("Location: bacheche.php");
    exit();
}

//Se la bacheca è privata, controlla che l'utente sia invitato
if ($bacheca['Tipologia'] == 'privata') {
    $res_invito = mysqli_query($connessione,
        "SELECT * FROM invita 
         WHERE ID_Utente = '$id_utente' 
         AND ID_Bacheca = '$id_bacheca' 
         AND Stato = 'accettato'");
    
    //Controlla anche se è il creatore
    $is_creatore = ($bacheca['ID_Creatore'] == $id_utente);

    if (mysqli_num_rows($res_invito) == 0 && !$is_creatore) {
        header("Location: bacheche.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titolo = mysqli_real_escape_string($connessione, trim($_POST['titolo']));
    $testo = mysqli_real_escape_string($connessione, trim($_POST['testo']));
    $data = date('Y-m-d H:i:s');

    //Controllo campi vuoti
    if (empty($titolo) || empty($testo)) {
        $errore = "Titolo e testo sono obbligatori.";
    } else {
    // Valida le immagini PRIMA di inserire il post
    $errore_immagine = "";
    if (!empty($_FILES['immagini']['name'][0])) {
        $estensioni_permesse = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $dimensione_massima = 2 * 1024 * 1024;

        foreach ($_FILES['immagini']['tmp_name'] as $key => $tmp) {
            $nome_originale = $_FILES['immagini']['name'][$key];
            $dimensione = $_FILES['immagini']['size'][$key];
            $estensione = strtolower(pathinfo($nome_originale, PATHINFO_EXTENSION));

            if (!in_array($estensione, $estensioni_permesse)) {
                $errore_immagine = "Formato non valido. Usa JPG, PNG, GIF o WEBP.";
                break;
            }
            if ($dimensione > $dimensione_massima) {
                $errore_immagine = "L'immagine è troppo grande. Massimo 2MB.";
                break;
            }
        }
    }

    if (!empty($errore_immagine)) {
        $errore = $errore_immagine;
    } else {
        // Solo se le immagini sono ok, inserisce il post
        $approvato = ($bacheca['ID_Creatore'] == $id_utente) ? 1 : 0;
        $query = "INSERT INTO post (Titolo, Testo, Data, Approvato, NumPost, ID_Utente, ID_Bacheca)
                  VALUES ('$titolo', '$testo', '$data', $approvato, 0, '$id_utente', '$id_bacheca')";

        if (mysqli_query($connessione, $query)) {
            $id_post = mysqli_insert_id($connessione);

            // Carica le immagini
            if (!empty($_FILES['immagini']['name'][0])) {
                foreach ($_FILES['immagini']['tmp_name'] as $key => $tmp) {
                    $nome_originale = $_FILES['immagini']['name'][$key];
                    $nome_file = time() . '_' . $key . '_' . $nome_originale;
                    $percorso = 'uploads_post_immagine/' . $nome_file;
                    move_uploaded_file($tmp, $percorso);
                    mysqli_query($connessione,
                        "INSERT INTO immagine (Percorso, ID_Post) 
                        VALUES ('$nome_file', '$id_post')");
                }
            }

            if ($approvato == 1) {
                header("Location: bacheca.php?id=$id_bacheca");
                exit();
            } else {
                $messaggio = "Il tuo post è in attesa di approvazione!";
            }
        }
    }
}
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Nuovo post</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        
        <h2>Scrivi un post in "<?php echo $bacheca['Titolo']; ?>"</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        
        <?php if (isset($messaggio)): ?>
            <p style="color:green;"><?php echo $messaggio; ?></p>
            <a href="bacheca.php?id=<?php echo $id_bacheca; ?>">
                <i class="fa-solid fa-arrow-left"></i> Torna alla bacheca
            </a>
            
            <?php else: ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <label>Titolo:</label>
                    <input type="text" name="titolo" required><br>
                    
                    <label>Testo:</label>
                    <textarea name="testo" rows="6" required></textarea><br>

                    <label>Immagini (opzionale):</label>
                    <div id="contenitore-immagini">
                        <input type="file" name="immagini[]" accept="image/*"><br>
                    </div>
                    <button type="button" id="aggiungi-immagine">
                        <i class="fa-solid fa-plus"></i> Aggiungi altra immagine
                    </button><br><br>
                    
                    <button type="submit">
                        <i class="fa-solid fa-paper-plane"></i>Pubblica post
                    </button>

                    &nbsp;
                    
                    <a href="bacheca.php?id=<?php echo $id_bacheca; ?>">
                        <i class="fa-solid fa-xmark"></i> Annulla
                    </a>

                </form>
        <?php endif; ?>

        <script>
            $(document).ready(function() {
                $('#aggiungi-immagine').click(function() {
                    $('#contenitore-immagini').append(
                        '<input type="file" name="immagini[]" accept="image/*"><br>'
                    );
                });
            });
        </script>
        
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>