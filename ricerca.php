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

$parola = "";
$res_bacheche = null;
$res_post = null;

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $parola = mysqli_real_escape_string($connessione, trim($_GET['q']));

    //Cerca bacheche per titolo o nome autore
    $res_bacheche = mysqli_query($connessione,
        "SELECT * FROM bacheca 
         WHERE Tipologia = 'pubblica'
         AND (
             Titolo LIKE '%$parola%'
             OR ID_Creatore IN (
                 SELECT ID FROM utente WHERE Username LIKE '%$parola%'
             )
             OR ID_Categoria IN (
                 SELECT ID FROM categoria WHERE Nome LIKE '%$parola%'
             )
         )
         ORDER BY Titolo ASC");

    //Cerca post per titolo o testo o nome autore
    $res_post = mysqli_query($connessione,
        "SELECT * FROM post
         WHERE Approvato = 1
         AND (
             Titolo LIKE '%$parola%'
             OR Testo LIKE '%$parola%'
             OR ID_Utente IN (
                 SELECT ID FROM utente WHERE Username LIKE '%$parola%'
             )
         )
         AND ID_Bacheca IN (
         SELECT ID FROM bacheca 
         WHERE Tipologia = 'pubblica'
         OR ID_Creatore = '$id_utente'
         OR ID IN (
             SELECT ID_Bacheca FROM invita 
             WHERE ID_Utente = '$id_utente' AND Stato = 'accettato'
         )
     )
     ORDER BY Data DESC");
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Ricerca</title>
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

        <h2>Ricerca</h2>
        
        <!-- Form di ricerca -->
        <form method="GET" action="">
            <input type="text" name="q"
                   value="<?php echo $parola; ?>"
                   placeholder="Cerca bacheche o post..." required>
                    <button type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i> Cerca
                    </button>
        </form>
        
        <?php if ($parola): ?>

        <hr>

        <!-- Risultati bacheche -->
        <h3>Bacheche</h3>
        <?php if (mysqli_num_rows($res_bacheche) == 0): ?>
            <p>Nessuna bacheca trovata.</p>
        <?php else: ?>
            <?php while ($bacheca = mysqli_fetch_assoc($res_bacheche)): ?>
                <?php
                //Prende il nome del creatore
                $res_cr = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$bacheca[ID_Creatore]'");
                $creatore = mysqli_fetch_assoc($res_cr);

                //Prende il nome della categoria
                $categoria = null;
                if ($bacheca['ID_Categoria']) {
                    $res_cat = mysqli_query($connessione, "SELECT Nome FROM categoria WHERE ID = '$bacheca[ID_Categoria]'");
                    $categoria = mysqli_fetch_assoc($res_cat);
                }
                ?>
                <div class="bacheca">
                    <h4><?php echo $bacheca['Titolo']; ?></h4>
                    <p><?php echo $bacheca['Descrizione']; ?></p>
                    <small>
                        Categoria: <strong><?php echo $categoria['Nome'] ?? 'Nessuna'; ?></strong> |
                        Creata da: <strong><?php echo $creatore['Username']; ?></strong>
                    </small><br>
                    <a href="bacheca.php?id=<?php echo $bacheca['ID']; ?>">
                        <i class="fa-solid fa-arrow-right"></i> Apri bacheca
                    </a>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <hr>

        <!-- Risultati post -->
        <h3>Post</h3>
        <?php if (mysqli_num_rows($res_post) == 0): ?>
            <p>Nessun post trovato.</p>
        <?php else: ?>
            <?php while ($post = mysqli_fetch_assoc($res_post)): ?>
                <?php
                //Prende il nome dell'autore
                $res_aut = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$post[ID_Utente]'");
                $autore = mysqli_fetch_assoc($res_aut);

                //Prende il titolo della bacheca
                $res_bach = mysqli_query($connessione, "SELECT Titolo FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
                $bach_post = mysqli_fetch_assoc($res_bach);
                ?>
                <div class="post">
                    <h4><?php echo $post['Titolo']; ?></h4>
                    <p><?php echo $post['Testo']; ?></p>
                    <small>
                        Di <strong><?php echo $autore['Username']; ?></strong>
                        in <strong><?php echo $bach_post['Titolo']; ?></strong>
                        il <?php echo $post['Data']; ?>
                    </small><br>
                    <a href="post.php?id=<?php echo $post['ID']; ?>">
                        <i class="fa-solid fa-arrow-right"></i> Vedi post
                    </a>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
        <?php endif; ?>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>
</html>
