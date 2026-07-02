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

//Prende l'ID della bacheca dall'URL
$id_bacheca = $_GET['id'];

//Prende i dati della bacheca
$query = "SELECT * FROM bacheca WHERE ID = '$id_bacheca'";
$result = mysqli_query($connessione, $query);
$bacheca = mysqli_fetch_assoc($result);

//Se la bacheca non esiste, rimanda alle bacheche
if (!$bacheca) {
    header("Location: bacheche.php");
    exit();
}

//Controlla se l'utente è il creatore
$is_creatore = ($bacheca['ID_Creatore'] == $id_utente);

//Controlla se l'utente è moderatore
$res_mod = mysqli_query($connessione, "SELECT * FROM modera WHERE ID_Utente = '$id_utente' AND ID_Bacheca = '$id_bacheca'");
$is_moderatore = (mysqli_num_rows($res_mod) > 0);

//Prende i post approvati della bacheca, dal più recente
$res_post = mysqli_query($connessione, "SELECT * FROM post WHERE ID_Bacheca = '$id_bacheca' AND Approvato = 1 ORDER BY Data DESC");

//Se è creatore o moderatore, prende anche i post in attesa di approvazione
$res_post_pending = null;
if ($is_creatore || $is_moderatore) {
    $res_post_pending = mysqli_query($connessione, "SELECT * FROM post WHERE ID_Bacheca = '$id_bacheca' AND Approvato = 0 ORDER BY Data DESC");
}

//Prende il nome della categoria
$categoria = null;
if ($bacheca['ID_Categoria']) {
    $res_cat = mysqli_query($connessione, "SELECT Nome FROM categoria WHERE ID = '$bacheca[ID_Categoria]'");
    $categoria = mysqli_fetch_assoc($res_cat);
}

//Prende il nome del creatore
$res_creatore = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$bacheca[ID_Creatore]'");
$creatore = mysqli_fetch_assoc($res_creatore);

//Prende i moderatori della bacheca
$res_moderatori = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID IN (SELECT ID_Utente FROM modera WHERE ID_Bacheca = '$id_bacheca')");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — <?php echo $bacheca['Titolo']; ?></title>
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

        <!-- Banner se presente -->
        <?php if ($bacheca['Banner']): ?>
            <img src="uploads_banner/<?php echo $bacheca['Banner']; ?>" alt="Banner" class="banner-bacheca">
        <?php endif; ?>

        <h2><?php echo $bacheca['Titolo']; ?></h2>
        <p><?php echo $bacheca['Descrizione']; ?></p>
        
        <small>
            Categoria: <strong><?php echo $categoria['Nome'] ?? 'Nessuna'; ?></strong> |
            Creata da: <strong><?php echo $creatore['Username']; ?></strong>
            <?php if (mysqli_num_rows($res_moderatori) > 0): ?>
                | Moderata da:
                <?php while ($mod = mysqli_fetch_assoc($res_moderatori)): ?>
                    <strong><?php echo $mod['Username']; ?></strong>
                <?php endwhile; ?>
            <?php endif; ?>
        </small>

        <!-- Pulsanti visibili solo al creatore -->
        <?php if ($is_creatore): ?>
            <div class="azioni-bacheca">
                <a href="bacheca_modifica.php?id=<?php echo $id_bacheca; ?>">
                    <i class="fa-solid fa-pen"></i> Modifica bacheca
                </a>
                <a href="moderatori.php?id=<?php echo $id_bacheca; ?>">
                    <i class="fa-solid fa-users"></i> Gestisci moderatori
                </a>
                
                <?php if ($bacheca['Tipologia'] == 'privata'): ?>
                    <a href="inviti.php?id=<?php echo $id_bacheca; ?>">
                        <i class="fa-solid fa-envelope"></i> Gestisci inviti
                    </a>
                <?php endif; ?>
                    <a href="bacheca_elimina.php?id=<?php echo $id_bacheca; ?>"
                       onclick="return confirm('Sei sicuro di voler eliminare questa bacheca?')">
                       <i class="fa-solid fa-trash"></i> Elimina bacheca
                    </a>
            </div>
        <?php endif; ?>
        
        <hr>
        
        <!-- Pulsante scrivi post -->
        <div class="scrivi">
            <a href="post_crea.php?id_bacheca=<?php echo $id_bacheca; ?>">
                <i class="fa-solid fa-plus"></i> Scrivi un post
            </a>
        </div>
        
        <h3>Post</h3>
        
        <!-- Post approvati -->
        <?php while ($post = mysqli_fetch_assoc($res_post)): ?>
            
            <div class="post">
                
                <?php
                //Prende il nome dell'autore
                $res_autore = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$post[ID_Utente]'");
                $autore = mysqli_fetch_assoc($res_autore);
                ?>
                
                <h4>
                    <a href="post.php?id=<?php echo $post['ID']; ?>">
                        <?php echo $post['Titolo']; ?>
                    </a>
                </h4>

                <p><?php echo $post['Testo']; ?></p>
                <small>
                    Di <strong><?php echo $autore['Username']; ?></strong>
                    il <?php echo $post['Data']; ?>
                </small>
                
                <!-- Pulsanti feedback -->
                <button class="btn-upvote" data-id="<?php echo $post['ID']; ?>"
                    <?php if ($post['ID_Utente'] == $id_utente) echo 'disabled'; ?>>
                    <i class="fa-solid fa-thumbs-up"></i> <span class="contatore-up">0</span>
                </button>
                <button class="btn-downvote" data-id="<?php echo $post['ID']; ?>"
                    <?php if ($post['ID_Utente'] == $id_utente) echo 'disabled'; ?>>
                    <i class="fa-solid fa-thumbs-down"></i> <span class="contatore-down">0</span>
                </button>

                &nbsp;
                
                <!-- Elimina post (solo creatore/moderatore o autore) -->
                <?php if ($is_creatore || $is_moderatore || $post['ID_Utente'] == $id_utente): ?>
                    <button class="btn-elimina-post" data-id="<?php echo $post['ID']; ?>">
                        <i class="fa-solid fa-trash"></i> Elimina
                    </button>
                <?php endif; ?>
                
                &nbsp; <a href="post.php?id=<?php echo $post['ID']; ?>">Vedi commenti</a>

            </div>

        <?php endwhile; ?>
        
        <!-- Post in attesa di approvazione (solo creatore/moderatore) -->
        <?php if ($is_creatore || $is_moderatore): ?>
            <h3>Post in attesa di approvazione</h3>
            
            <?php while ($post = mysqli_fetch_assoc($res_post_pending)): ?>
                
                <div class="post pending">
                    
                    <?php
                    $res_autore = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$post[ID_Utente]'");
                    $autore = mysqli_fetch_assoc($res_autore);
                    ?>
                    
                    <h4><?php echo $post['Titolo']; ?></h4>
                    <p><?php echo $post['Testo']; ?></p>
                    <small>Di <strong><?php echo $autore['Username']; ?></strong></small>
                    
                    <!-- Pulsanti feedback -->
                    <button class="btn-upvote" data-id="<?php echo $post['ID']; ?>"
                       <?php if ($post['ID_Utente'] == $id_utente) echo 'disabled'; ?>>
                       <i class="fa-solid fa-thumbs-up"></i> <span class="contatore-up">0</span>
                    </button>
                    
                    <button class="btn-downvote" data-id="<?php echo $post['ID']; ?>"
                        <?php if ($post['ID_Utente'] == $id_utente) echo 'disabled'; ?>>
                        <i class="fa-solid fa-thumbs-down"></i> <span class="contatore-down">0</span>
                    </button>

                    <!-- Pulsanti approva/rifiuta -->
                    <button class="btn-approva" data-id="<?php echo $post['ID']; ?>">
                        <i class="fa-solid fa-check"></i> Approva
                    </button>

                    <button class="btn-rifiuta" data-id="<?php echo $post['ID']; ?>">
                        <i class="fa-solid fa-xmark"></i> Rifiuta
                    </button>

                </div>

            <?php endwhile; ?>
        <?php endif; ?>
        
        <script>
            $(document).ready(function() {

                //Carica i contatori quando la pagina si apre
                $('.btn-upvote').each(function() {
                    var idPost = $(this).data('id');
                    var btnUp = $(this);
                    var btnDown = $('[data-id="' + idPost + '"].btn-downvote');

                    $.ajax({
                        url: 'feedback_conteggio.php',
                        type: 'POST',
                        data: { id_post: idPost },
                        success: function(risposta) {
                            var dati = JSON.parse(risposta);
                            btnUp.find('.contatore-up').text(dati.upvote);
                            btnDown.find('.contatore-down').text(dati.downvote);
                        }
                    });
               });

               //Clicca upvote o downvote
               $(document).on('click', '.btn-upvote, .btn-downvote', function() {
                    var idPost = $(this).data('id');
                    var tipo = $(this).hasClass('btn-upvote') ? 'upvote' : 'downvote';
                    var btnUp = $('[data-id="' + idPost + '"].btn-upvote');
                    var btnDown = $('[data-id="' + idPost + '"].btn-downvote');

                    $.ajax({
                        url: 'feedback_aggiungi.php',
                        type: 'POST',
                        data: { id_post: idPost, tipo: tipo },
                        success: function(risposta) {
                            var dati = JSON.parse(risposta);
                            btnUp.find('.contatore-up').text(dati.upvote);
                            btnDown.find('.contatore-down').text(dati.downvote);
                        }
                    });
                });
            
                //Approva post
                $(document).on('click', '.btn-approva', function() {
                    var idPost = $(this).data('id');
                    var div = $(this).closest('.post');
                
                    $.ajax({
                        url: 'post_approva.php',
                        type: 'POST',
                        data: { id_post: idPost },
                        success: function(risposta) {
                            if (risposta == 'ok') {
                                window.location.reload();
                            }
                        }
                    });
                });
        
                //Rifiuta post
                $(document).on('click', '.btn-rifiuta', function() {
                    var idPost = $(this).data('id');
                    var div = $(this).closest('.post');
                
                    if (confirm('Sei sicuro di voler eliminare questo post?')) {
                        $.ajax({
                            url: 'post_rifiuta.php',
                            type: 'POST',
                            data: { id_post: idPost },
                            success: function(risposta) {
                                if (risposta == 'ok') {
                                    div.remove();
                                }
                            }
                        });
                    }
                });
            
                //Elimina post approvato (creatore/moderatore/autore)
                $(document).on('click', '.btn-elimina-post', function() {
                    var idPost = $(this).data('id');
                    var div = $(this).closest('.post');
                
                    if (confirm('Sei sicuro di voler eliminare questo post?')) {
                        $.ajax({
                            url: 'post_rifiuta.php',
                            type: 'POST',
                            data: { id_post: idPost },
                            success: function(risposta) {
                                if (risposta == 'ok') {
                                    div.remove();
                                }
                            }
                        });
                    }
                });

            });
        </script>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>
    
</html>