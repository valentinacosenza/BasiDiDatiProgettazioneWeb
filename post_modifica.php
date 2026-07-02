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
$id_post = $_GET['id'];
$errore = "";

//Prende i dati del post
$res = mysqli_query($connessione, "SELECT * FROM post WHERE ID = '$id_post'");
$post = mysqli_fetch_assoc($res);

//Solo l'autore può modificare il proprio post
if (!$post || $post['ID_Utente'] != $id_utente) {
    header("Location: home.php");
    exit();
}

//Prende i dati della bacheca per sapere chi è il creatore
$res_bacheca = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
$bacheca = mysqli_fetch_assoc($res_bacheca);

//Se l'autore è anche il creatore della bacheca, resta approvato
$approvato = ($bacheca['ID_Creatore'] == $id_utente) ? 1 : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titolo = mysqli_real_escape_string($connessione, trim($_POST['titolo']));
    $testo = mysqli_real_escape_string($connessione, trim($_POST['testo']));

    if (empty($titolo) || empty($testo)) {
        $errore = "Titolo e testo sono obbligatori.";
    } else {
        // Valida le immagini PRIMA di aggiornare il post
        $errore_immagine = "";
        if (!empty($_FILES['immagini']['name'][0])) {
            $estensioni_permesse = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $dimensione_massima = 2 * 1024 * 1024;

            foreach ($_FILES['immagini']['tmp_name'] as $key => $tmp) {
                $dimensione = $_FILES['immagini']['size'][$key];
                $estensione = strtolower(pathinfo($_FILES['immagini']['name'][$key], PATHINFO_EXTENSION));

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
            // Aggiorna il post
            mysqli_query($connessione,
                "UPDATE post SET Titolo = '$titolo', Testo = '$testo', Approvato = $approvato
                WHERE ID = '$id_post'");

            // Carica le nuove immagini se presenti
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

            header("Location: post.php?id=$id_post");
            exit();
        }
    }
}

//Prende le immagini attuali del post
$res_immagini = mysqli_query($connessione, "SELECT * FROM immagine WHERE ID_Post = '$id_post'");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Modifica post</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    
    <body>
        <nav>
            <a href="home.php" class="nav-logo">Haven</a><span>Ciao, <?php echo $_SESSION['username']; ?>!</span>
            <a href="home.php">Home</a>
            <a href="post.php?id=<?php echo $id_post; ?>">Torna al post</a>
            <a href="ricerca.php"><i class="fa-solid fa-magnifying-glass"></i> Cerca</a>
            <a href="profilo.php">Profilo</a>
            <a href="account.php">Account</a>
            <a href="logout.php">Esci</a>
        </nav>

        <h2>Modifica post</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">

        <label>Titolo:</label>
        <input type="text" name="titolo"
               value="<?php echo $post['Titolo']; ?>" required><br>

        <label>Testo:</label>
        <textarea name="testo" rows="6" required><?php echo $post['Testo']; ?></textarea><br>

        <!-- Immagini attuali -->
        <?php if (mysqli_num_rows($res_immagini) > 0): ?>
            <h4>Immagini attuali:</h4>
            <?php while ($immagine = mysqli_fetch_assoc($res_immagini)): ?>
                <div class="immagine" id="immagine-<?php echo $immagine['ID']; ?>">
                    <img src="uploads_post_immagine/<?php echo $immagine['Percorso']; ?>"
                         alt="Immagine" width="150">
                    <!-- Elimina singola immagine -->
                    <button type="button" class="btn-elimina-immagine"
                            data-id="<?php echo $immagine['ID']; ?>">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <label>Aggiungi nuove immagini (opzionale):</label>
            <div id="contenitore-immagini">
                <input type="file" name="immagini[]" multiple accept="image/*"><br>
            </div>
            <button type="button" id="aggiungi-immagine">
                <i class="fa-solid fa-plus"></i> Aggiungi altra immagine
            </button><br><br>

        <button type="submit">
            <i class="fa-solid fa-floppy-disk"></i> Salva modifiche
        </button>
        <a href="post.php?id=<?php echo $id_post; ?>">
            &nbsp; <i class="fa-solid fa-xmark"></i> Annulla
        </a>

        </form>

        <br>
        
        <script>
            $(document).ready(function() {

                //Elimina singola immagine 
                $(document).on('click', '.btn-elimina-immagine', function() {
                var idImmagine = $(this).data('id');
                var div = $(this).closest('.immagine');
            
                if (confirm('Eliminare questa immagine?')) {
                    $.ajax({
                        url: 'immagine_elimina.php',
                        type: 'POST',
                        data: { id_immagine: idImmagine },
                        success: function(risposta) {
                            if (risposta == 'ok') {
                                div.remove();
                            }
                        }
                    });
                }
                });

                //Aggiungi nuovo campo immagine
                $('#aggiungi-immagine').click(function() {
                    $('#contenitore-immagini').append(
                        '<input type="file" name="immagini[]" multiple accept="image/*"><br>'
                    );
                });
            });
        </script>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>