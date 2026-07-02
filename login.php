<?php
session_start();

//Includo il file per la connessione al database
include 'connessione.php';

$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    //Controllo campi vuoti
    if (empty($email) || empty($password)) {
        $errore = "Tutti i campi sono obbligatori.";
    }
    else {
        //Cerca l'utente per email
        $query = "SELECT * FROM utente WHERE Email = '$email'";
        $result = mysqli_query($connessione, $query);

        if (mysqli_num_rows($result) == 1) {
            $utente = mysqli_fetch_assoc($result);

            //Verifica la password
            if (password_verify($password, $utente['Password'])) {
                //Salva i dati in sessione
                $_SESSION['id'] = $utente['ID'];
                $_SESSION['username'] = $utente['Username'];
                $_SESSION['tipologia'] = $utente['Tipologia'];

                //Reindirizza alla home
                header("Location: home.php");
                exit();
            } else {
                $errore = "Password errata.";
            }
        } else {
            $errore = "Nessun account trovato con questa email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Haven — Login</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    
    <body>
        <div class="haven-header">
            <h1>Haven</h1>
            <p>Il tuo spazio per condividere</p>
        </div>
        
        <h2>Accedi</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Email:</label>
            <input type="email" name="email" id="email"
                   placeholder="Es. nome@esempio.com"
                   pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                   title="Inserisci un indirizzo email valido (es. nome@esempio.com)"
                   required><br>
            
            <label>Password:</label>
            <div class="input-con-icona">
                <input type="password" name="password" id="password"
                       placeholder="Almeno 8 caratteri alfanumerici"
                       pattern="[a-zA-Z0-9]{8,}"
                       title="La password deve contenere almeno 8 caratteri, solo lettere e numeri"
                       required>
                <button type="button" id="toggle-password" class="btn-occhio">
                    <i class="fa-solid fa-eye" id="icona-password"></i>
                </button>
            </div><br>

            <button type="submit">Accedi</button>
        </form>
        
        <p>Non hai un account? <a href="registrazione.php">Registrati</a></p>

        <script>
            //Mostra/nascondi password
            $(document).ready(function() {
                $('#toggle-password').click(function() {
                    var input = $('#password');
                    var icona = $('#icona-password');
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icona.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        input.attr('type', 'password');
                        icona.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });
            });
        </script>
    
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>