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

//Prende i dati della bacheca
$res = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID = '$id_bacheca'");
$bacheca = mysqli_fetch_assoc($res);

//Se non esiste o non è il creatore, rimanda alle bacheche
if (!$bacheca || $bacheca['ID_Creatore'] != $id_utente) {
    header("Location: bacheche.php");
    exit();
}

//Prende tutte le categorie per il menu a tendina
$res_categorie = mysqli_query($connessione, "SELECT * FROM categoria ORDER BY Nome ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titolo = mysqli_real_escape_string($connessione, trim($_POST['titolo']));
    $descrizione = mysqli_real_escape_string($connessione, trim($_POST['descrizione']));
    $id_categoria = $_POST['id_categoria'];

    //Controllo campi obbligatori
    if (empty($titolo) || empty($descrizione) || empty($id_categoria)) {
        $errore = "Titolo, descrizione e categoria sono obbligatori.";
    } else {
        //Valida il banner PRIMA di aggiornare la bacheca
        $errore_banner = "";
        $banner = $bacheca['Banner'];
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
            //Carica il banner solo se la validazione è ok
            if (!empty($_FILES['banner']['name'])) {
                //Elimina il vecchio banner se presente
                if ($bacheca['Banner']) {
                    $vecchio_percorso = 'uploads_banner/' . $bacheca['Banner'];
                    if (file_exists($vecchio_percorso)) {
                        unlink($vecchio_percorso);
                    }
                }
                $nome_file = time() . '_' . $nome_originale;
                $percorso  = 'uploads_banner/' . $nome_file;
                move_uploaded_file($_FILES['banner']['tmp_name'], $percorso);
                $banner = $nome_file;
            }

            if (empty($id_categoria)) {
                $id_categoria = "NULL";
            } else {
                $id_categoria = "'$id_categoria'";
            }

            $query = "UPDATE bacheca 
                      SET Titolo = '$titolo', 
                          Descrizione = '$descrizione',
                          Banner = " . ($banner ? "'$banner'" : "NULL") . ",
                          ID_Categoria = $id_categoria
                      WHERE ID = '$id_bacheca'";

            if (mysqli_query($connessione, $query)) {
                header("Location: bacheca.php?id=$id_bacheca");
                exit();
            } else {
                $errore = "Errore durante la modifica.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Modifica bacheca</title>
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

        <h2>Modifica bacheca</h2>

        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
            
        <form method="POST" action="" enctype="multipart/form-data">

            <label>Titolo:</label>
            <input type="text" name="titolo" 
                    value="<?php echo $bacheca['Titolo']; ?>" required><br>
                
            <label>Descrizione:</label>
            <textarea name="descrizione" rows="4" required><?php echo $bacheca['Descrizione']; ?></textarea><br>

            <label>Categoria (opzionale):</label>
            <select name="id_categoria" required>
                <option value="">-- Seleziona una categoria --</option>
                <?php while ($cat = mysqli_fetch_assoc($res_categorie)): ?>
                    <option value="<?php echo $cat['ID']; ?>"
                        <?php if ($cat['ID'] == $bacheca['ID_Categoria']) echo 'selected'; ?>>
                        <?php echo $cat['Nome']; ?>
                    </option>
                <?php endwhile; ?>
            </select><br>
                
            <!-- Banner se presente -->
            <?php if ($bacheca['Banner']): ?>
                <p>Banner attuale:</p>
                <img src="uploads_banner/<?php echo $bacheca['Banner']; ?>" 
                     alt="Banner" width="200"><br>
            <?php endif; ?>
                
            <label>Cambia banner (opzionale):</label>
                <input type="file" name="banner" accept="image/*"><br>
                <button type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Salva modifiche
                </button>
    
                <a href="bacheca.php?id=<?php echo $id_bacheca; ?>">
                    &nbsp; <i class="fa-solid fa-xmark"></i> Annulla
                </a>

        </form>
            
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>