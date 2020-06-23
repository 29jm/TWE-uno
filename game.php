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
            "current_player" => nameFromId(currentPlayer($gameId)),
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

    // TODO: critical: prevent using this when player HAS to draw cards
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
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "error" => "Invalid move"));
        }

        die;
    }

    /* How to deal with black cards: an authoritative paper.
     * The server will only ever distribute uncolored black cards, i.e. one of
     * "plusfour" and "joker".
     * On the contrary, every move proposed by the client is to involve a
     * colored card, i.e. "red-3", "yellow-joker" or "green-plusfour".
     * As such, the "decks" table will contain nothing but uncolored black
     * cards, and "placed_cards" will contain only colored black cards.
     */

    /* Le plan ici: (manque l'API backend pour le faire)
     * Quand c'est pas le tour du joueur, on poll le serveur pour savoir quand
     * les tours passent (le joueur en cours est stocké dans une var locale du
     * client), et quand un tour est passé, on met à jour la carte posée au
     * milieu ainsi que la couleur actuelle (utile de l'afficher qd le joueur
     * pose une carte noire (à moins de changer le background de la carte noire
     * une fois posée?)).
     *
     * Quand c'est le tours du joueur, on active les handlers de clic sur ses cartes
     * et sa pioche. Chaque action envoie un POST ajax et la mise à jour de l'ui
     * se fait dans le success handler de la requête.
     */
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
        Jeu de uno, todo. <br>
        <?php
            echo "Vous êtes player#$userId connecté à la partie #$gameId <br>";
        ?>
        L'état actuel de la partie est ou devrait être entièrement représenté par cet objet:
        <div id="state">

        </div>
    </body>
    <?php
        include("templates/footer.php");
    ?>
</html>