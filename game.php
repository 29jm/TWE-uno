<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");

    session_start();

    if (!valider("connected", "SESSION") || !isInGame($_SESSION["userId"])) {
        header("Location: index.php");
        die;
    }

    /* Le plan ici:
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
    </head>
    <body>
        Jeu de uno, todo.
    </body>
</html>