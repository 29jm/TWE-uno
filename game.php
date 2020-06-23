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

    if (valider("state", "GET") == "1") {
        $top_of_pile = getPlacedCards($gameId);

        if (count($top_of_pile) == 0) {
            $top_of_pile = "";
        } else {
            $top_of_pile = $top_of_pile[count($top_of_pile) - 1];
        }

        // TODO?: returning (id, name) pairs would save us quite some queries later
        $players = getPlayers($gameId);

        foreach ($players as $other) {
            $others[nameFromId($other)] = count(getDeck($other));
        }

        $response = array(
            "started" => isGameStarted($gameId),
            // Who are we waiting for?
            "current_player" => nameFromId(currentPlayer($gameId)),
            // Who are we? so many questions
            "username" => nameFromId($userId),
            // Who should be stressing right now?
            "next_player" => nameFromId(nextToPlay($gameId)),
            // The card to show on the pile
            "top_of_pile" => $top_of_pile,
            // The user's deck, displayed at the bottom of the screen
            "deck" => getDeck($userId),
            // Array mapping player names to their number of cards
            "players_info" => $others,
            // 0 means the next to play is the player of next highest id, 1 the opposite
            "direction" => getDirection($gameId),
            // Current color to play. Not obvious from top_of_pile when it's a +4
            "color" => getColor($gameId)
        );

        echo json_encode($response);
        die;
    }

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
</html>

<?php 
    include("templates/footer.php");
?>
