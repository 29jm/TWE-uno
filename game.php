<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");

    session_start();

    if (!valider("connected", "SESSION") || getGameOf($_SESSION["userId"]) == NOT_IN_GAME) {
        header("Location: index.php");
        die;
    }

    $userId = $_SESSION["userId"];
    $gameId = getGameOf($userId);
    $started = isGameStarted($gameId);

    if (valider("state", "GET") == "1") {
        $placed = getPlacedCards($gameId);
        $top_of_pile = end($placed);

        // TODO?: returning (id, name) pairs would save us quite some queries later
        $players = getPlayers($gameId);

        foreach ($players as $other) {
            $others[nameFromId($other)] = count(getDeck($other));
        }

        $response = array(
            // Who are we?
            "username" => nameFromId($userId),
            // Who are we waiting for?
            "current_player" => $started ? nameFromId(currentPlayer($gameId)) : "",
            // Who should be stressing right now?
            "next_player" => nameFromId(nextToPlay($gameId)),
            // Are you the admin?
            "is_admin" => getGameAdmin($gameId) == $userId,
            // The card to show on the pile
            "top_of_pile" => $top_of_pile,
            // The user's deck, displayed at the bottom of the screen
            "deck" => getDeck($userId),
            // Array mapping player names to their number of cards
            "players_info" => $others,
            // 0 means the next to play is the player of next highest id, 1 the opposite
            "direction" => getDirection($gameId),
            "started" => $started
        );

        echo json_encode($response);
        die;
    }

    if ($start = valider("start", "POST")) {
        if ($userId != getGameAdmin($gameId)) {
            echo json_encode(array("success" => false, "error" => "Not the admin"));
            die;
        }

        if ($start == "1") {
            startGame($gameId);
        } else {
            endGame($gameId);
        }

        echo json_encode(array("success" => true));
        die;
    }

    if (valider("draw", "POST") == "1") {
        if ($userId != currentPlayer($gameId)) {
            echo json_encode(array("success" => false, "error" => "Not your turn"));
            die;
        }

        $card = drawCard($userId);

        if ($card) {
            echo json_encode(array("success" => true, "card" => $card));
        } else {
            echo json_encode(array("success" => false, "error" => "No cards to draw from"));
        }

        die;
    }

    if ($card = valider("place", "POST")) {
        if ($userId != currentPlayer($gameId)) {
            echo json_encode(array("success" => false, "error" => "Not your turn"));
            die;
        }

        if (cardsToDraw($userId) > 0) {
            echo json_encode(array("success" => false, "error" => "You need to draw cards"));
            die;
        }

        if (placeCard($userId, $card)) {
            $won = count(getDeck($userId)) == 0;

            if ($won) {
                endGame($gameId);
            }

            echo json_encode(array("success" => true, "won" => $won));
        } else {
            echo json_encode(array("success" => false, "error" => "Invalid move"));
        }

        die;
    }

    if ($uno = valider("uno", "POST")) {
        // A player declares Uno
        if ($uno == "1") {
            $result = screamUno($userId);
            echo json_encode(array("sucess" => $result));
        }

        // A players calls contr'Uno
        if ($uno == "2") {
            $players = getPlayers($gameId);

            foreach ($players as $player) {
                if (count(getDeck($player)) == 1 && !hasUnoed($player)) {
                    drawCard($player);
                    drawCard($player);
                }
            }
        }

        die;
    }
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Uno Online</title>
        <link rel="stylesheet" href="css/style.css">
        <script src="js/jquery-3.5.1.min.js"></script>
        <script src="js/game.js"></script>
    </head>
    <body>
        <h1 id="game-name">
            <?php echo getGameName($gameId); ?>
        </h1>
        <button id="start-game" class="game-btn">Lancer la partie</button>
        <button id="end-game" class="game-btn">Terminer la partie</button>
        <br>
        <div id="players-list"> </div>
        <div id="card-piles"> </div>
        <div id="player-deck"> </div>
        <button id="uno-btn" class="game-btn">Uno !</button>
        <button id="anti-uno-btn" class="game-btn">Contr'Uno !</button>
        <pre>
            <code id="state"> </code>
        </pre>
    </body>
    <?php
        include("templates/footer.php");
    ?>
</html>