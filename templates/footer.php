<footer>
	<div id="pied">
		<?php
			// Si l'utilisateur est connecté, on affiche un lien de déconnexion
			if (valider("connected", "SESSION")) {
				echo "<a href=\"index.php?action=Logout\">Se déconnecter</a> ";
				echo "<a href=\"rules.html\">Consulter les règles du jeu</a>";
			}
		?>
	</div>
</footer>