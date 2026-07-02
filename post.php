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

//Prende i dati del post
$res_post = mysqli_query($connessione, "SELECT * FROM post WHERE ID = '$id_post' AND Approvato = 1");
$post = mysqli_fetch_assoc($res_post);

//Se il post non esiste, rimanda alla home
if (!$post) {
    header("Location: home.php");
    exit();
}

//Prende il nome dell'autore del post
$res_autore = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$post[ID_Utente]'");
$autore = mysqli_fetch_assoc($res_autore);

//Prende il titolo della bacheca
$res_bacheca = mysqli_query($connessione, "SELECT Titolo FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
$bacheca = mysqli_fetch_assoc($res_bacheca);

//Prende le immagini del post
$res_immagini = mysqli_query($connessione, "SELECT * FROM immagine WHERE ID_Post = '$id_post'");

//Prende i commenti del post, dal più recente
$res_commenti = mysqli_query($connessione, "SELECT * FROM commento WHERE ID_Post = '$id_post' ORDER BY Data DESC");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — <?php echo $post['Titolo']; ?></title>
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
        
        <!-- Dati del post -->
        <div class="post">
            <h2><?php echo $post['Titolo']; ?></h2>
            <p><?php echo $post['Testo']; ?></p>
            <small>
                Di <strong><?php echo $autore['Username']; ?></strong>
                in <strong>
                        <a href="bacheca.php?id=<?php echo $post['ID_Bacheca']; ?>">
                            <?php echo $bacheca['Titolo']; ?>
                        </a>
                    </strong>
                il <?php echo $post['Data']; ?>
            </small>
            
            <!-- Immagini del post se presenti -->
            <?php while ($immagine = mysqli_fetch_assoc($res_immagini)): ?>
                <img src="uploads_post_immagine/<?php echo $immagine['Percorso']; ?>" 
                     alt="Immagine post" width="300">
            <?php endwhile; ?>
            
            <!-- Pulsanti feedback -->
            <div>
                <button class="btn-upvote" data-id="<?php echo $post['ID']; ?>"
                    <?php if ($post['ID_Utente'] == $id_utente) echo 'disabled'; ?>>
                    <i class="fa-solid fa-thumbs-up"></i> <span class="contatore-up">0</span>
                </button>

                <button class="btn-downvote" data-id="<?php echo $post['ID']; ?>"
                    <?php if ($post['ID_Utente'] == $id_utente) echo 'disabled'; ?>>
                    <i class="fa-solid fa-thumbs-down"></i> <span class="contatore-down">0</span>
                </button>
            </div>

            <br>
            
            <?php if ($post['ID_Utente'] == $id_utente): ?>
                <a href="post_modifica.php?id=<?php echo $post['ID']; ?>">
                    <i class="fa-solid fa-pen"></i> Modifica post
                </a>  &nbsp;
                <button class="btn-elimina-post" data-id="<?php echo $post['ID']; ?>">
                    <i class="fa-solid fa-trash"></i> Elimina post
                </button>
            <?php endif; ?>   
        </div>

        <hr>

        <!-- Form aggiungi commento -->
        <h3>Commenti</h3>
        
        <div id="form-commento">
            <textarea id="testo-commento" placeholder="Scrivi un commento..." rows="3"></textarea><br>
            <button id="btn-commenta" data-id="<?php echo $post['ID']; ?>">
                <i class="fa-solid fa-comment"></i> Commenta
            </button>
        </div>
        
        <br>
        
        <!-- Lista commenti -->
        <div id="lista-commenti">
            <?php while ($commento = mysqli_fetch_assoc($res_commenti)): ?>
                
                <div class="commento" id="commento-<?php echo $commento['ID']; ?>">
                    <?php
                    //Prende il nome dell'autore del commento
                    $res_autore_comm = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$commento[ID_Utente]'");
                    $autore_comm = mysqli_fetch_assoc($res_autore_comm);
                    ?>
                    
                    <p class="testo-commento" id="testo-commento-<?php echo $commento['ID']; ?>"><?php echo $commento['Testo']; ?></p>
                    <small>
                        Di <strong><?php echo $autore_comm['Username']; ?></strong>
                        il <?php echo $commento['Data']; ?>
                    </small>
                    
                    <!-- Modifica ed elimina commento (solo autore) -->
                    <?php if ($commento['ID_Utente'] == $id_utente): ?>
                        <button class="btn-modifica-commento" 
                                data-id="<?php echo $commento['ID']; ?>">
                                <i class="fa-solid fa-pen"></i>
                        </button>
                        
                        <button class="btn-elimina-commento" 
                                data-id="<?php echo $commento['ID']; ?>">
                                <i class="fa-solid fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <script>
            $(document).ready(function() {
            
                //Aggiungi commento 
                $('#btn-commenta').click(function() {
                    var idPost = $(this).data('id');
                    var testo = $('#testo-commento').val().trim();
                
                    if (testo === '') {
                        alert('Scrivi qualcosa prima di commentare!');
                        return;
                    }
                
                    $.ajax({
                        url: 'commento_aggiungi.php',
                        type: 'POST',
                        data: { id_post: idPost, testo: testo },
                        success: function(risposta) {
                           $('#lista-commenti').prepend(risposta);
                            $('#testo-commento').val('');
                        }
                    });
                });

                //Modifica commento
                $(document).on('click', '.btn-modifica-commento', function() {
                    var idCommento = $(this).data('id');
                    var paragrafo = $('#testo-commento-' + idCommento);
                    var testoAttuale = paragrafo.text();

                    var nuovoTesto = prompt('Modifica il tuo commento:', testoAttuale);
                
                    if (nuovoTesto !== null && nuovoTesto.trim() !== '') {
                        $.ajax({
                            url: 'commento_modifica.php',
                            type: 'POST',
                            data: { id_commento: idCommento, testo: nuovoTesto },
                            success: function(risposta) {
                                if (risposta == 'ok') {
                                    paragrafo.text(nuovoTesto);
                                }
                            }
                        });
                    }
                });
            
                //Elimina commento 
                $(document).on('click', '.btn-elimina-commento', function() {
                    var idCommento = $(this).data('id');
                    var div = $(this).closest('.commento');
                
                    if (confirm('Eliminare questo commento?')) {
                        $.ajax({
                            url: 'commento_elimina.php',
                            type: 'POST',
                            data: { id_commento: idCommento },
                            success: function() {
                                div.remove();
                            }
                        });
                    }
                });

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
                    var idPost  = $(this).data('id');
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
            
                //Elimina post e rimando alla home
                $(document).on('click', '.btn-elimina-post', function() {
                    var idPost = $(this).data('id');
                
                    if (confirm('Sei sicuro di voler eliminare questo post?')) {
                        $.ajax({
                            url: 'post_elimina.php',
                            type: 'POST',
                            data: { id_post: idPost },
                            success: function(risposta) {
                                if (risposta == 'ok') {
                                    window.location.href = 'home.php';
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