<?php

include_once("maLibSQL.pdo.php");

/** User-management stuff **/

const NOT_IN_GAME = -1;

/* Returns the id of the game a user is in, or NOT_IN_GAME.
 */
function getGameOf($userId) {
	$sql = "select game_id from users where id = $userId";
	$result = SQLGetChamp($sql);

	return $result;
}

/* Logs in a user. Returns whether the operation succeeded or not.
 */
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

/* Returns the new userId if successful, and probably null otherwise.
 */
function createAccount($name, $password) {
	$sql = "insert ignore into users (name, password) values ('$name', '$password')";
	return SQLInsert($sql);
}

/** Game properties stuff **/

/* Returns the id of the created game (do check though).
 * Returns 0 and fails if the name exists.
 */
function createGame($name, $adminId) {
	$sql = "insert ignore into games (name, admin_id, user_to_play) values ('$name', $adminId, $adminId)";
	$id = SQLInsert($sql);

	if ($id != 0) {
		$sql = "update users set game_id = $id where id = $adminId";
		SQLUpdate($sql);

		distributeInitialCards($adminId);
	}

	// TODO?: choosing before dealing would allow us to skip that expansive call
	$unusedCards = getUnusedCards($id);

	do {
		$rand_index = array_rand($unusedCards);
	} while (in_array(($firstCard = $unusedCards[$rand_index]), array("joker", "plusfour")));

	$firstCard = $unusedCards[$rand_index];

	SQLInsert("insert into placed_cards (game_id, card_name) values ($id, '$firstCard')");

	return $id;
}

/* Returns an array of games (id, name) that exist but haven't started yet.
 */
function listAvailableGames() {
	$sql = "select id, name from games where has_started=0";

	return parcoursRs(SQLSelect($sql));
}

/* Joins a game, returns whether joining that game was a success.
 * Deals the player their initial hand.
 * TODO: check that $gameId hasn't started.
 */
function joinGame($userId, $gameId) {
	$oldGameId = getGameOf($userId);

	if ($oldGameId != NOT_IN_GAME) {
		return false;
	}

	$sql = "update users set game_id = $gameId where id = $userId";
	SQLUpdate($sql);

	distributeInitialCards($userId);

	return true;
}

function isGameStarted($gameId) {
	return SQLGetChamp("select has_started from games where id = $gameId") == 1;
}

function getPlayers($gameId) {
	$sql = "select id from users where game_id = $gameId";
	$players = parcoursRs(SQLSelect($sql));

	return mapToArray(parcoursRs(SQLSelect($sql)), "id");
}

function nameFromId($userId) {
	return SQLGetChamp("select name from users where id = $userId");
}

function startGame($gameId) {
	$sql = "update games set has_started=1 where id = $gameId";
	SQLUpdate($sql);
}

function endGame($gameId) {
	$players = getPlayers($gameId);

	// Could be batched in two query
	foreach ($players as $userId) {
		SQLDelete("delete from decks where user_id = $userId");
		SQLUpdate("update users set game_id = -1 where id = $userId");
	}

	SQLDelete("delete from placed_cards where game_id = $gameId");
	SQLDelete("delete from games where id = $gameId");
}

/** Actual Uno stuff **/

/* Card names will be CSS class names, with colored cards being named
 * "color-value" and leading to two classes, "color" and "value".
 * Or maybe just "color", whoever does that will know better.
 */
const allCards = array(
	// most special cards
	"joker", "joker", "joker", "joker",
	"plusfour", "plusfour", "plusfour", "plusfour",
	// skip turn cards, two of each color
	"red-skip", "green-skip", "yellow-skip", "blue-skip",
	"red-skip", "green-skip", "yellow-skip", "blue-skip",
	// reverse cards
	"red-reverse", "green-reverse", "yellow-reverse", "blue-reverse",
	"red-reverse", "green-reverse", "yellow-reverse", "blue-reverse",
	// +2
	"red-plustwo", "green-plustwo", "yellow-plustwo", "blue-plustwo",
	"red-plustwo", "green-plustwo", "yellow-plustwo", "blue-plustwo",
	// numbers
	"red-0", "red-1", "red-2", "red-3", "red-4", "red-5", "red-6", "red-7", "red-8", "red-9",
	         "red-1", "red-2", "red-3", "red-4", "red-5", "red-6", "red-7", "red-8", "red-9",
	"green-0", "green-1", "green-2", "green-3", "green-4", "green-5", "green-6", "green-7", "green-8", "green-9",
	           "green-1", "green-2", "green-3", "green-4", "green-5", "green-6", "green-7", "green-8", "green-9",
	"yellow-0", "yellow-1", "yellow-2", "yellow-3", "yellow-4", "yellow-5", "yellow-6", "yellow-7", "yellow-8", "yellow-9",
	            "yellow-1", "yellow-2", "yellow-3", "yellow-4", "yellow-5", "yellow-6", "yellow-7", "yellow-8", "yellow-9",
	"blue-0", "blue-1", "blue-2", "blue-3", "blue-4", "blue-5", "blue-6", "blue-7", "blue-8", "blue-9",
	          "blue-1", "blue-2", "blue-3", "blue-4", "blue-5", "blue-6", "blue-7", "blue-8", "blue-9",
);

/* Gives 7 cards to a player.
 * Note: could reuse `drawCard` but that'd be extra slow because of the inner
 *   `getUnusedCards` call.
 */
function distributeInitialCards($userId) {
	$gameId = getGameOf($userId);
	$options = getUnusedCards($gameId);
	$hand = array();

	for ($i = 0; $i < 7; $i++) {
		$chosen = array_rand($options); // index, not the card

		$sql = "insert into decks (user_id, card_name) values ($userId, '$options[$chosen]')";
		SQLInsert($sql);
		array_push($hand, $options[$chosen]);

		array_splice($options, $chosen, 1); // avoids calling getUnusedCards again
	}
}

function getDeck($userId) {
	$gameId = getGameOf($userId);
	$sql = "select card_name from decks where user_id = $userId";

	return mapToArray(parcoursRs(SQLSelect($sql)), "card_name");
}

/* Returns the cards placed by players in the order they were placed in.
 */
function getPlacedCards($gameId) {
	$sql = "select card_name from placed_cards where game_id = $gameId order by id asc";

	return mapToArray(parcoursRs(SQLSelect($sql)), "card_name");
}

/* Cartes dans la pioche, i.e. toutes celles qui ne sont ni placées ni dans la
 * main d'un joueur.
 * TODO: (low priority) cache results for p e r f o r m a n c e.
 */
function getUnusedCards($gameId) {
	$usedCards = array();
	$players = getPlayers($gameId);

	foreach ($players as $userId) {
		$usedCards = array_merge($usedCards, getDeck($userId));
	}

	$usedCards = array_merge($usedCards, getPlacedCards($gameId));
	$unusedCards = allCards; // it's a copy

	foreach ($usedCards as $usedCard) {
		$index = array_search($usedCard, $unusedCards);
		array_splice($unusedCards, $index, 1);
	}

	return $unusedCards;
}

function cardColor($card) {
	if (($index = strpos($card, "-")) !== false) {
		return substr($card, 0, $index);
	}

	return null;
}

function cardSymbol($card) {
	if (($index = strpos($card, "-")) !== false) {
		return substr($card, $index + 1);
	}

	return $card;
}

/* Moves a card from the player's deck to the card stack, if the move is valid.
 * Returns whether the move was, in fact, valid.
 */
function placeCard($userId, $card) {
	$gameId = getGameOf($userId);
	$info = parcoursRs(SQLSelect("select * from games where id = $gameId"))[0];
	$deck = getDeck($userId);
	$placed = getPlacedCards($gameId);

	// Sanity checks
	if ($gameId == NOT_IN_GAME
		|| $info["user_to_play"] != $userId
		|| !in_array($card, $deck)
		|| $info["has_started"] != 1
		|| count($placed) == 0) {

		return false;
	}

	// Check that this move would in fact be valid
	// i.e. if good color || joker || +4 || good symbol (number, reverse, plustwo, skip))
	if ($info["color"] == cardColor($card) || $card == "joker" || $card == "plusfour"
		|| cardSymbol($placed[count($placed) - 1]) == cardSymbol($card)) {

		SQLDelete("delete from decks where user_id = $userId and card_name = $card");
		SQLInsert("insert into placed_cards (game_id, card_name) values ($gameId, '$card')");
		return true;
	}

	return false;
}

/* Tirer de la pioche.
 * Returns the drawn card. Not certain this is useful.
 */
function drawCard($userId) {
	$options = getUnusedCards(getGameOf($userId));
	$index = array_rand($options);

	$sql = "insert into decks (user_id, card_name) values ($userId, '$options[$index]')";
	SQLInsert($sql);

	return $options[$index];
}

function getDirection($gameId) {
	return SQLGetChamp("select direction from games where id = $gameId");
}

function currentPlayer($gameId) {
	return SQLGetChamp("select user_to_play from games where id = $gameId");
}

/* Not right now, but like, later.
 */
function nextToPlay($gameId) {
	$sql = "select user_to_play, direction from games where id = $gameId";
	$info = parcoursRs(SQLSelect($sql))[0];

	$direction = $info["direction"] == 1 ? 1 : -1;
	$to_play = $info["user_to_play"];
	$players = getPlayers($gameId);

	$index = array_search($to_play, $players);

	if ($direction == -1 && $index == 0) {
		return $players[count($players) - 1];
	}

	return $players[($index + $direction) % count($players)];
}

/* May not be useful, prefer issuing a custom query.
 */
function reverseDirection($gameId) {
	$direction = getDirection($gameId) == 0 ? 1 : 0;

	SQLUpdate("update games set direction = $direction where id = $gameId");
}

function getColor($gameId) {
	return SQLGetChamp("select color from games where id = $gameId");
}