<?php

include_once("maLibSQL.pdo.php");

function isInGame($userId) {
	$sql = "select game_id from users where id = $userId";
	$result = SQLGetChamp($sql);

	return $result == '1';
}

function checkUser($name, $password) {
	$id = checkUserBdd($name, $password);

	if ($id) {
		$_SESSION["name"] = $name;
		$_SESSION["userId"] = $id;
		$_SESSION["connected"] = true;

		return true;
	}

	return false;
}

function checkUserBdd($name, $password) {
	$SQL = "select id from users where name='$name' and password='$password'";
	return SQLGetChamp($SQL);
}

function createAccount($name, $password) {
	$sql = "insert ignore into users (name, password) values ('$name', '$password')";
	return SQLInsert($sql);
}

/* Returns the id of the created game (do check though).
 * Returns 0 and fails if the name exists.
 */
function createGame($name, $adminId) {
	$sql = "insert ignore into games (name, admin_id, user_to_play) values ($name, $adminId, $adminId)";
	$id = SQLInsert($sql);
	$sql = "update users set game_id = $id where id = $adminId";
	SQLUpdate($sql);

	return $id;
}

/* Returns an array of games (id, name) that exist but haven't started yet.
 */
function listAvailableGames() {
	$sql = "select id, name from games where has_started=0";

	return parcoursRs(SQLSelect($sql));
}

function joinGame($userId, $gameId) {
	// TODO
}

function numberOfPlayers($gameId) {
	// TODO
}

/*
 *
 * Tout ce qui reste ici est à supprimer, ça sert d'exemple.
 *
 */

function getComptes($idUser) {
	$sql = "select ID_UTILISATEUR, LIB_COMPTE, SOLDE from T_COMPTE where ID_COMPTE=$idUser;";
	return parcoursRs(SQLSelect($sql));
}

function ajouterMouvement($idCompte, $type, $montant, $commentaire) {
	$sql = "insert into T_MOUVEMENT (ID_COMPTE, COMMENTAIRE, MONTANT_MOUVEMENT, NATURE_MOUVEMENT) VALUES ($idCompte, '$commentaire', $montant, '$type')";
	SQLInsert($sql);

	if ($type == "D")
		$montant = "-" . $montant;

	$sql = "update T_COMPTE set SOLDE = SOLDE + $montant where ID_COMPTE=$idCompte";
	SQLUpdate($sql);
}

function getMouvements($idUser) {
	$sql = "select COMMENTAIRE, MONTANT_MOUVEMENT, DATE_MOUVEMENT, NATURE_MOUVEMENT
			from T_MOUVEMENT join T_COMPTE on T_MOUVEMENT.ID_COMPTE = T_COMPTE.ID_COMPTE
			where ID_UTILISATEUR = $idUser";
	return parcoursRs(SQLSelect($sql));
}