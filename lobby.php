<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");

    session_start();

    if (!valider("connected", "SESSION")) {
        header("Location: index.php");
        die;
    }

    if (getGameOf($_SESSION["userId"]) != NOT_IN_GAME) {
        header("Location: game.php");
        die;
    }

    $userId = $_SESSION["userId"];

    // API handlers
    if ($action = valider("action")) {
        switch ($action) {
        case "Creer":
            $gameName = valider("create");
            $result = createGame($gameName, $userId);

            if ($result == 0) {
                $_POST["errorMessage"] = "Une partie porte déjà ce nom";
            } else {
                header("Location: game.php");
                die;
            }

            break;
        case "Rejoindre":
            $gameId = (int) valider("game");
            $result = joinGame($userId, $gameId);

            if (!$result) {
                $_POST["errorMessage"] = "Vous êtes déjà dans une partie";
            } else {
                header("Location: game.php");
                die;
            }

            break;
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
        <form action="" method="POST">
            Nouvelle partie:
            <input id="create-text" type="text" name="create" placeholder="Nom de la partie">
            <input id="join-text" type="hidden" name="game">
            <input id="create-btn" type="submit" name="action" value="Creer">
            <input id="join-btn" type="submit" name="action" value="Rejoindre">
        </form>
        Liste des parties en cours, todo.<br>
        <ul id="game-list">
        <?php
            $games = listAvailableGames();

            foreach ($games as $game) {
                $num = count(getPlayers($game["id"]));
                echo "<li class=\"game\" id=\"$game[id]\">$game[name] #$game[id]: $num joueurs connectés</li>";
            }

            // TODO: !design!, actualisation auto de la liste?
        ?>
        </ul>
    </body>
</html>