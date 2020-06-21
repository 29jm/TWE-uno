<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");

    session_start();

    /* If we were sent here from the index form.
     * Move to a global controller.php if needed/wanted.
     */
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
        case "Creer un compte":
            $result = createAccount($name, $password);

            if ($result == 1) {
                checkUser($name, $password);
            } else {
                $_POST["errorMessage"] = "Ce pseudo existe déjà.";
            }

            break;
        }
    }

   /* If the user is connected:
    *     If the user is marked as "in game $game_id" in the DB
    *          Redirect to the game page with proprer session variables
    *     Else
    *          Redirect to the lobby
    * Else
    *     Show the index
    */
    if (valider("connected", "SESSION")) {
        if (getGameOf($_SESSION["userId"]) != NOT_IN_GAME) {
            header("Location: game.php");
        } else {
            header("Location: lobby.php");
        }

        die;
    }

    // If we get here, show the index
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Uno Online</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <h1>Uno</h1>
        <div>
            <form action="" method="post">
                <input type="text" name="name" placeholder="Nom d'utilisateur">
                <input type="password" name="password" placeholder="Mot de passe">
                <input type="submit" name="action" value="Creer un compte">
                <input type="submit" name="action" value="Connexion">
            </form>
            <?php
                if ($message = valider("errorMessage", "POST")) {
                    echo $message;
                }
            ?>
        </div>
    </body>
</html>