<html>
<link rel="stylesheet" href="css/style.css">

<body>

<div id="footer" class="white-box">

<?php
// Si l'utilisateur est connecte, on affiche un lien de deconnexion 
if (valider("connected","SESSION"))
{
	echo "<a href=\"index.php?action=Logout\">Se déconnecter</a>";
	echo "<a href=\"rules.html\">Consulter les règles du jeu</a>";
}
?>
</div>

</body>
</html> 
