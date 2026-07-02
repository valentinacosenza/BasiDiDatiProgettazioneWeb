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

//Prende le bacheche pubbliche + le private dove l'utente è invitato o creatore
$result = mysqli_query($connessione,
    "SELECT * FROM bacheca 
    WHERE Tipologia = 'pubblica'
    OR ID_Creatore = '$id_utente'
    OR ID IN (
        SELECT ID_Bacheca FROM invita 
        WHERE ID_Utente = '$id_utente' AND Stato = 'accettato'
    )
    ORDER BY Titolo ASC");
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Bacheche</title>
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

        <h2>Tutte le bacheche</h2>

        <!-- Pulsante crea bacheca -->
        <div class="btn-crea-bacheca">
            <a href="bacheca_crea.php">
                <i class="fa-solid fa-plus"></i> Crea nuova bacheca
            </a>
        </div>
        
        <!-- Lista bacheche -->
        <?php while ($bacheca = mysqli_fetch_assoc($result)): ?>
            
            <div class="bacheca">
                
                <?php
                //Prende il nome della categoria
                $res_cat = mysqli_query($connessione, "SELECT Nome FROM categoria WHERE ID = '$bacheca[ID_Categoria]'");
                $categoria = mysqli_fetch_assoc($res_cat);

                //Prende il nome del creatore
                $res_creatore = mysqli_query($connessione, "SELECT Username FROM utente WHERE ID = '$bacheca[ID_Creatore]'");
                $creatore = mysqli_fetch_assoc($res_creatore);
                ?>
                
                <!-- Banner se presente -->
                <?php if ($bacheca['Banner']): ?>
                    <img src="uploads_banner/<?php echo $bacheca['Banner']; ?>" 
                         alt="Banner" width="200">
                        <?php endif; ?>
                        
                        <h3><?php echo $bacheca['Titolo']; ?></h3>
                        <p><?php echo $bacheca['Descrizione']; ?></p>

                        <small>
                            Categoria: <strong><?php echo $categoria['Nome'] ?? 'Nessuna'; ?></strong> |
                            Creata da: <strong><?php echo $creatore['Username']; ?></strong>
                        </small>
                        
                        <a href="bacheca.php?id=<?php echo $bacheca['ID']; ?>">
                            <i class="fa-solid fa-arrow-right"></i> Apri bacheca
                        </a>

            </div>

        <?php endwhile; ?>

        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>