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

//Prende i dati dell'utente
$res = mysqli_query($connessione, "SELECT * FROM utente WHERE ID = '$id_utente'");
$utente = mysqli_fetch_assoc($res);

//Conta quante persone lo seguono
$res_seguaci = mysqli_query($connessione, "SELECT COUNT(*) AS tot FROM segue WHERE ID_Seguito = '$id_utente'");
$seguaci = mysqli_fetch_assoc($res_seguaci);

//Conta quante persone segue
$res_seguiti = mysqli_query($connessione, "SELECT COUNT(*) AS tot FROM segue WHERE ID_Seguace = '$id_utente'");
$seguiti = mysqli_fetch_assoc($res_seguiti);

//Tab attivo (default: bacheche)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'bacheche';

//Prende le bacheche dell'utente
$res_bacheche = mysqli_query($connessione, "SELECT * FROM bacheca WHERE ID_Creatore = '$id_utente' ORDER BY Titolo ASC");

//Prende i post dell'utente approvati
$res_post = mysqli_query($connessione, "SELECT * FROM post WHERE ID_Utente = '$id_utente' AND Approvato = 1 ORDER BY Data DESC");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Profilo</title>
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

        <!-- Header profilo -->
        <div class="profilo-header">
            <h2><?php echo $utente['Username']; ?></h2>
            <p class="profilo-tipologia">
                <?php if ($utente['Tipologia'] == 'premium'): ?>
                    <i class="fa-solid fa-crown"></i> Premium
                <?php else: ?>
                    <i class="fa-solid fa-user"></i> Regular
                <?php endif; ?>
            </p>
            <div class="profilo-contatori">
                <div class="contatore-profilo">
                    <strong><?php echo $seguaci['tot']; ?></strong>
                    <span>Seguito da</span>
                </div>
                <div class="contatore-profilo">
                    <strong><?php echo $seguiti['tot']; ?></strong>
                    <span>Segue</span>
                </div>
            </div>
        </div>

        <!-- Tab bacheche/post -->
        <div class="filtri">
            <a href="profilo.php?tab=bacheche"
               class="<?php echo ($tab == 'bacheche') ? 'attivo' : ''; ?>">
                <i class="fa-solid fa-table-columns"></i> Bacheche
            </a>
            <a href="profilo.php?tab=post"
               class="<?php echo ($tab == 'post') ? 'attivo' : ''; ?>">
                <i class="fa-solid fa-file-lines"></i> Post
            </a>
        </div>

        <!-- Contenuto tab bacheche -->
        <?php if ($tab == 'bacheche'): ?>
            <?php if (mysqli_num_rows($res_bacheche) == 0): ?>
                <p>Non hai ancora creato nessuna bacheca.</p>
                <a href="bacheche.php">
                    <i class="fa-solid fa-plus"></i> Crea la tua prima bacheca
                </a>
            <?php else: ?>
                <?php while ($bacheca = mysqli_fetch_assoc($res_bacheche)): ?>
                    <div class="bacheca">
                        <?php if ($bacheca['Banner']): ?>
                            <img src="uploads_banner/<?php echo $bacheca['Banner']; ?>"
                                 alt="Banner" class="banner-bacheca">
                        <?php endif; ?>
                        <h3><?php echo $bacheca['Titolo']; ?></h3>
                        <p><?php echo $bacheca['Descrizione']; ?></p>
                        <?php
                        $res_cat = mysqli_query($connessione, "SELECT Nome FROM categoria WHERE ID = '$bacheca[ID_Categoria]'");
                        $cat = mysqli_fetch_assoc($res_cat);
                        ?>
                        <small>
                            Categoria: <strong><?php echo $cat['Nome'] ?? 'Nessuna'; ?></strong> |
                            <?php if ($bacheca['Tipologia'] == 'privata'): ?>
                                <i class="fa-solid fa-lock"></i> Privata
                            <?php else: ?>
                                <i class="fa-solid fa-globe"></i> Pubblica
                            <?php endif; ?>
                        </small>
                        <a href="bacheca.php?id=<?php echo $bacheca['ID']; ?>">
                            <i class="fa-solid fa-arrow-right"></i> Apri bacheca
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

        <!-- Contenuto tab post -->
        <?php else: ?>
            <?php if (mysqli_num_rows($res_post) == 0): ?>
                <p>Non hai ancora scritto nessun post.</p>
            <?php else: ?>
                <?php while ($post = mysqli_fetch_assoc($res_post)): ?>
                    <?php
                    $res_bach = mysqli_query($connessione, "SELECT Titolo FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
                    $bach_post = mysqli_fetch_assoc($res_bach);
                    ?>
                    <div class="post">
                        <h4>
                            <a href="post.php?id=<?php echo $post['ID']; ?>">
                                <?php echo $post['Titolo']; ?>
                            </a>
                        </h4>
                        <p><?php echo $post['Testo']; ?></p>
                        <small>
                            In <strong>
                                   <a href="bacheca.php?id=<?php echo $post['ID_Bacheca']; ?>">
                                       <?php echo $bach_post['Titolo']; ?>
                                    </a>
                                </strong>
                            il <?php echo $post['Data']; ?>
                        </small>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        <?php endif; ?>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>