<?php
    include_once("libs/maLibUtils.php");
    include_once("libs/modele.php");

    session_start();

    if (!valider("connected", "SESSION")) {
        header("Location: index.php");
        die;
    }
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