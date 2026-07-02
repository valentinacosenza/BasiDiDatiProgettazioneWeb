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

//Controlla il filtro scelto (default: seguiti)
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'seguiti';

//Bacheche visibili: pubbliche, oppure private dove l'utente è creatore o invitato accettato
$bacheche_visibili = "(SELECT ID FROM bacheca
                       WHERE Tipologia = 'pubblica'
                        OR ID_Creatore = '$id_utente'
                        OR ID IN (
                            SELECT ID_Bacheca FROM invita 
                            WHERE ID_Utente = '$id_utente' AND Stato = 'accettato'
                        ))";

if ($filtro == 'seguiti') {
    //Post degli utenti seguiti
    $result = mysqli_query($connessione,
        "SELECT * FROM post 
        WHERE Approvato = 1
        AND ID_Utente IN (
            SELECT ID_Seguito FROM segue WHERE ID_Seguace = '$id_utente'
        )
        AND ID_Bacheca IN $bacheche_visibili
        ORDER BY Data DESC");
} elseif ($filtro == 'votati') {
    //Post ordinati per numero di upvote
    $result = mysqli_query($connessione,
        "SELECT * FROM post 
        WHERE Approvato = 1
        AND ID_Bacheca IN $bacheche_visibili
        ORDER BY (
            SELECT COUNT(*) FROM feedback 
            WHERE feedback.ID_Post = post.ID AND Tipo = 'upvote'
        ) DESC, Data DESC");
} elseif ($filtro == 'commentati') {
    //Post ordinati per numero di commenti
    $result = mysqli_query($connessione,
        "SELECT * FROM post 
        WHERE Approvato = 1
        AND ID_Bacheca IN $bacheche_visibili
        ORDER BY (
            SELECT COUNT(*) FROM commento 
            WHERE commento.ID_Post = post.ID
        ) DESC, Data DESC");
} else {
    //Tutti i post — ordinati per data
    $result = mysqli_query($connessione,
        "SELECT * FROM post 
        WHERE Approvato = 1
        AND ID_Bacheca IN $bacheche_visibili
        ORDER BY Data DESC");
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Home</title>
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

        <h2>Feed post</h2>
        
        <!-- Pulsanti filtro -->
        <div class="filtri">
            <a href="home.php?filtro=seguiti" 
               class="<?php echo ($filtro == 'seguiti') ? 'attivo' : ''; ?>">
               <i class="fa-solid fa-user-group"></i> Utenti seguiti
            </a>
            <a href="home.php?filtro=tutti"
               class="<?php echo ($filtro == 'tutti') ? 'attivo' : ''; ?>">
               <i class="fa-solid fa-globe"></i> Tutti i post
            </a>
            <a href="home.php?filtro=votati"
               class="<?php echo ($filtro == 'votati') ? 'attivo' : ''; ?>">
               <i class="fa-solid fa-thumbs-up"></i> Più votati
            </a>
            <a href="home.php?filtro=commentati"
               class="<?php echo ($filtro == 'commentati') ? 'attivo' : ''; ?>">
               <i class="fa-solid fa-comment"></i> Più commentati
            </a>
        </div>
        
        <?php if (mysqli_num_rows($result) == 0): ?>
            <?php if ($filtro == 'seguiti'): ?>
                <div class="messaggio-vuoto">
                    <p>Non segui ancora nessun utente o i tuoi utenti seguiti non hanno post.</p>
                    <a href="segui.php">
                        <i class="fa-solid fa-user-plus"></i> Segui qualcuno
                    </a>
                </div>
                
                <?php else: ?>
                    <p>Non ci sono ancora post.</p>
                <?php endif; ?>
                <?php else: ?>
                    <?php while ($post = mysqli_fetch_assoc($result)): ?>
                        <div class="post">
                            <?php

                            //Prende il nome dell'autore del post
                            $res_autore = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$post[ID_Utente]'");
                            $autore = mysqli_fetch_assoc($res_autore);

                            //Prende il titolo della bacheca
                            $res_bacheca = mysqli_query($connessione, "SELECT Titolo FROM bacheca WHERE ID = '$post[ID_Bacheca]'");
                            $bacheca = mysqli_fetch_assoc($res_bacheca);

                            ?>
                                
                            <h3>
                                <a href="post.php?id=<?php echo $post['ID']; ?>">
                                    <?php echo $post['Titolo']; ?>
                                </a>
                            </h3>

                            <p><?php echo $post['Testo']; ?></p>
                            <small>
                                Scritto da <strong><?php echo $autore['Username']; ?></strong>
                                in <strong>
                                       <a href="bacheca.php?id=<?php echo $post['ID_Bacheca']; ?>">
                                            <?php echo $bacheca['Titolo']; ?>
                                        </a>
                                    </strong>
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
                            
                            <a href="post.php?id=<?php echo $post['ID']; ?>">
                                Vedi commenti
                            </a>

                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                        
                <script>
                    $(document).ready(function() {

                        //Carica i contatori quando la pagina si apre
                        $('.btn-upvote').each(function() {
                            var idPost = $(this).data('id')
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
                </script>
                        
            <!--Includo il file per il footer -->
            <?php include 'footer.php'; ?>
    </body>

</html>
