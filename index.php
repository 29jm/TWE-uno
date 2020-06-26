<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");
    
    /* Page de connexion */

    session_start();

    /* En cas de tentatives de connexion, déconnexion ou création de compte, on vérifie la validité des informations et on redirige vers la page adaptée */
    if ($action = valider("action")) {
        $name = valider("name");
        $password = valider("password");

        switch ($action) {
        case "Connexion":
            // If the login works, "connected" will be set by the following call.
            $result = checkUser($name, $password);

            if (!$result) {
                $_POST["errorMessage"] = "Identifiants incorrects.";
            }

            break;
        case "Creer":
            $result = createAccount($name, $password);

            if ($result) {
                checkUser($name, $password);
            } else {
                $_POST["errorMessage"] = "Ce pseudo existe déjà.";
            }

            break;
        case "Logout":
            session_destroy();
            header("Location: index.php");
            break;
        }
    }

   /* Si l'utilisateur est connecté, il est redirigé vers la page lobby.php si il n'est pas en jeu, game.php sinon
    */
    if (valider("connected", "SESSION")) {
        if (getGameOf($_SESSION["userId"]) != NOT_IN_GAME) {
            header("Location: game.php");
        } else {
            header("Location: lobby.php");
        }

        die;
    }

    // Si l'utilisateur n'est pas connecté, on affiche le formulaire :
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Uno Online</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div id="divAccueilConnexion" class="white-box">
            <div id="accueilConnexion">
            <img src="ressources/logo_uno.png" alt="logo uno">
                <form action="" method="post">
                    <input type="text" name="name" placeholder="Nom d'utilisateur">
                    <input type="password" name="password" placeholder="Mot de passe">
                    <input type="submit" name="action" value="Connexion">
                    <button type="submit" name="action" value="Creer">Créer un compte</button>
                </form>
                <?php
                    if ($message = valider("errorMessage", "POST")) {
                        echo $message;
                    }
                ?>
            </div>
        </div>
    </body>
</html>


