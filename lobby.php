<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");

    session_start();

    /* Si l'utilisateur n'est pas connecté ou bien s'il est déjà dans une partie,
    on le redirige vers la page adaptée */

    if (!valider("connected", "SESSION")) {
        header("Location: index.php");
        die;
    }

    if (getGameOf($_SESSION["userId"]) != NOT_IN_GAME) {
        header("Location: game.php");
        die;
    }

    $userId = $_SESSION["userId"];

    // En cas d'actions, on effectue les vérifications nécessaires et on redirige vers la page adaptée
    if ($action = valider("action")) {
        switch ($action) {
        case "Créer":
            $gameName = valider("create");
            $result = createGame($gameName, $userId);

            if ($result == 0) {
                $_POST["errorMessage"] = "Une partie porte déjà ce nom.";
            } else {
                header("Location: game.php");
                die;
            }

            break;
        case "Rejoindre":
            $gameId = (int) valider("game");
            $result = joinGame($userId, $gameId);

            if (!$result) {
                $_POST["errorMessage"] = "Vous êtes déjà dans une partie.";
            } else {
                header("Location: game.php");
                die;
            }

            break;
        case "refresh":
            $games = listAvailableGames();

            foreach ($games as $game) {
                $response[] = array(
                    "name" => $game["name"],
                    "admin" => nameFromId($game["admin_id"]),
                    "numPlayers" => count(getPlayers($game["id"])),
                    "id" => $game["id"]
                );
            }

            echo json_encode($response);
            die;
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Uno Online</title>
        <link rel="stylesheet" href="css/style.css">
        <script src="js/jquery-3.5.1.min.js"></script>
        <script src="js/lobby.js"></script>
    </head>
    <body>
        <div id="div-game-list" class="white-box">
            <h2> Parties en attente de joueurs : </h2>
            <h5> Double-cliquez sur une ligne pour rejoindre la partie </h5>
            <table id="game-list"></table>
            <form action="" method="POST">
                <h2> Création d'une nouvelle partie : </h2>
                <input id="create-text" type="text" name="create" placeholder="Nom de la partie">
                <input id="join-text" type="hidden" name="game">
                <input id="create-btn" type="submit" name="action" value="Créer">
                <input id="join-btn" type="submit" name="action" value="Rejoindre">
            </form>
            <?php
                if ($error = valider("errorMessage", "POST")) {
                    echo $error . "<br>";
                }
            ?>
        </div>
    </body>
    <?php
        include("templates/footer.php");
    ?>
</html>
