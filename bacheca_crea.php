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
$errore    = "";

//Prende tutte le categorie per il menu a tendina
$res_categorie = mysqli_query($connessione, "SELECT * FROM categoria ORDER BY Nome ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titolo  = mysqli_real_escape_string($connessione, $_POST['titolo']);
    $descrizione = mysqli_real_escape_string($connessione, $_POST['descrizione']);
    $id_categoria = $_POST['id_categoria'];
    $tipologia = $_POST['tipologia'];

    //Controllo campi obbligatori
    if (empty($titolo) || empty($descrizione) || empty($id_categoria)) {
        $errore = "Titolo, descrizione e categoria sono obbligatori.";
    } else {
        //Valida il banner PRIMA di inserire la bacheca
        $errore_banner = "";
        $banner = null;
        if (!empty($_FILES['banner']['name'])) {
            $estensioni_permesse = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $dimensione_massima  = 2 * 1024 * 1024;
            $nome_originale = $_FILES['banner']['name'];
            $dimensione = $_FILES['banner']['size'];
            $estensione = strtolower(pathinfo($nome_originale, PATHINFO_EXTENSION));

            if (!in_array($estensione, $estensioni_permesse)) {
                $errore_banner = "Formato non valido. Usa JPG, PNG, GIF o WEBP.";
            } elseif ($dimensione > $dimensione_massima) {
                $errore_banner = "L'immagine è troppo grande. Massimo 2MB.";
            }
        }

        if (!empty($errore_banner)) {
            $errore = $errore_banner;
        } else {
            // Carica il banner solo se la validazione è ok
            if (!empty($_FILES['banner']['name'])) {
                $nome_file = time() . '_' . $nome_originale;
                $percorso = 'uploads_banner/' . $nome_file;
                move_uploaded_file($_FILES['banner']['tmp_name'], $percorso);
                $banner = $nome_file;
            }

            if (empty($id_categoria)) {
                $id_categoria = "NULL";
            } else {
                $id_categoria = "'$id_categoria'";
            }

            if ($tipologia == 'privata' && $_SESSION['tipologia'] != 'premium') {
                $errore = "Solo gli utenti premium possono creare bacheche private.";
            } else {
                $query = "INSERT INTO bacheca (Titolo, Descrizione, Banner, Tipologia, ID_Creatore, ID_Categoria)
                         VALUES ('$titolo', '$descrizione', " . ($banner ? "'$banner'" : "NULL") . ", '$tipologia', '$id_utente', $id_categoria)";

                if (mysqli_query($connessione, $query)) {
                    $id_bacheca = mysqli_insert_id($connessione);
                    header("Location: bacheca.php?id=$id_bacheca");
                    exit();
                } else {
                    $errore = "Errore durante la creazione della bacheca.";
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
        <title>Haven — Crea bacheca</title>
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

        <h2>Crea una nuova bacheca</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <label>Titolo:</label>
            <input type="text" name="titolo" required><br>
            
            <label>Descrizione:</label>
            <textarea name="descrizione" rows="4" required></textarea><br>

            <label>Categoria (opzionale):</label>
            <select name="id_categoria" required>
                <option value="">-- Seleziona una categoria --</option>
                <?php while ($cat = mysqli_fetch_assoc($res_categorie)): ?>
                    <option value="<?php echo $cat['ID']; ?>">
                        <?php echo $cat['Nome']; ?>
                    </option>
                <?php endwhile; ?>
            </select><br>

            <label>Tipologia:</label>
            <select name="tipologia">
                <option value="pubblica">Pubblica</option>
                <?php if ($_SESSION['tipologia'] == 'premium'): ?>
                    <option value="privata">Privata (solo premium)</option>
                <?php endif; ?>
            </select><br>

            <label>Banner (opzionale):</label>
            <input type="file" name="banner" accept="image/*"><br>
            
            <button type="submit">
                <i class="fa-solid fa-plus"></i> Crea bacheca 
            </button>

            <a href="bacheche.php">
                &nbsp; <i class="fa-solid fa-xmark"></i> Annulla
            </a>

        </form>
        
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body> 
    
</html> 