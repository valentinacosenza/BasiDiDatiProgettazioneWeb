<?php
session_start();

//Includo il file per la connessione al database
include 'connessione.php';

$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $conferma_password = $_POST['conferma_password'];

    //Controllo campi vuoti
    if (empty($username) || empty($email) || empty($password) || empty($conferma_password)) {
        $errore = "Tutti i campi sono obbligatori.";
    }
    //Controllo formato email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errore = "Email non valida.";
    }
    //Controllo username — solo lettere e numeri
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $errore = "Lo username può contenere solo lettere e numeri.";
    }
    //Controllo password — almeno 8 caratteri alfanumerici
    elseif (!preg_match('/^[a-zA-Z0-9]{8,}$/', $password)) {
        $errore = "La password deve contenere almeno 8 caratteri alfanumerici.";
    }
    //Controllo password coincidono
    elseif ($password !== $conferma_password) {
        $errore = "Le due password non coincidono.";
    }
    else {
        //Controlla se username già esiste
        $check = mysqli_query($connessione, "SELECT ID FROM utente WHERE Username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $errore = "Username già in uso.";
        }
        else {
            //Controlla se email già esiste
            $check2 = mysqli_query($connessione, "SELECT ID FROM utente WHERE Email = '$email'");
            if (mysqli_num_rows($check2) > 0) {
                $errore = "Email già registrata.";
            }
            else {
                //Hash della password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $query = "INSERT INTO utente (Username, Email, Password, Tipologia) 
                          VALUES ('$username', '$email', '$password_hash', 'regular')";

                if (mysqli_query($connessione, $query)) {
                    header("Location: login.php");
                    exit();
                } else {
                    $errore = "Errore durante la registrazione.";
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
        <title>Haven — Registrazione</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    
    <body>
        <div class="haven-header">
            <h1>Haven</h1>
            <p>Il tuo spazio per condividere</p>
        </div>
        
        <h2>Registrati</h2>
        
        <?php if ($errore): ?>
            <p style="color:red;"><?php echo $errore; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="" id="form-registrazione">
            <label>Username:</label>
            <input type="text" name="username" id="username"
                   placeholder="Solo lettere e numeri"
                   pattern="[a-zA-Z0-9]+"
                   title="Lo username può contenere solo lettere e numeri, senza spazi o caratteri speciali"
                   required><br>
                   
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
            </div>
            
            <label>Conferma password:</label>
            <div class="input-con-icona">
                <input type="password" name="conferma_password" id="conferma-password"
                       placeholder="Ripeti la password"
                       pattern="[a-zA-Z0-9]{8,}"
                       title="La password deve contenere almeno 8 caratteri, solo lettere e numeri"
                       required>
                        <button type="button" id="toggle-conferma" class="btn-occhio">
                            <i class="fa-solid fa-eye" id="icona-conferma"></i>
                        </button>
            </div>
            
            <div id="feedback-password" class="feedback-password"></div>
            
            <button type="submit">
                <i class="fa-solid fa-user-plus"></i> Registrati
            </button>

        </form>
        
        <p>Hai già un account? <a href="login.php">Accedi</a></p><br>
        
        <script>
            $(document).ready(function() {
            
                //Mostra/nascondi password
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
            
                //Mostra/nascondi conferma password
                $('#toggle-conferma').click(function() {
                    var input = $('#conferma-password');
                    var icona = $('#icona-conferma');
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icona.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        input.attr('type', 'password');
                        icona.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });
            
                //Controllo password in tempo reale
                $('#conferma-password').on('input', function() {
                    var pass = $('#password').val();
                    var conferma = $(this).val();
                    var feedback = $('#feedback-password');
                
                    if (conferma === '') {
                        feedback.html('');
                    } else if (pass === conferma) {
                        feedback.html('<span style="color:var(--verde-successo);"><i class="fa-solid fa-check"></i> Le password coincidono</span>');
                    } else {
                        feedback.html('<span style="color:var(--rosso);"><i class="fa-solid fa-xmark"></i> Le password non coincidono</span>');
                    }
                });
            
                //Controllo lato client prima di inviare il form
                $('#form-registrazione').on('submit', function(e) {
                    var pass = $('#password').val();
                    var conferma = $('#conferma-password').val();
                    if (pass !== conferma) {
                        e.preventDefault();
                        alert('Le due password non coincidono!');
                    }
                });

            });
        </script>
        
        <!--Includo il file per il footer -->
        <?php include 'footer.php'; ?>
    </body>

</html>