<html>
<link rel="stylesheet" href="css/style.css">

<body>

<div id="pied">

<?php
// Si l'utilisateur est connecte, on affiche un lien de deconnexion 
if (valider("connected","SESSION"))
{
	echo "<a href=\"index.php?action=Logout\">Se déconnecter</a>";
}
?>
</div>

</body>
</html> 
