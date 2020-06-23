<?php

include_once("maLibSQL.pdo.php");

/** User-management stuff **/

const NOT_IN_GAME = -1;

/* Returns the id of the game a user is in, or NOT_IN_GAME.
 */
function getGameOf($userId) {
	$sql = "select game_id from users where id = $userId";
	$result = SQLGetChamp($sql);

	if ($result == 0) {
		$result = NOT_IN_GAME;
	}

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
	} while (($firstCard = $unusedCards[$rand_index]) == "plusfour");

	$firstCard = $unusedCards[$rand_index];

	SQLInsert("insert into placed_cards (game_id, card_name) values ($id, '$firstCard')");

	return $id;
}

/* Returns an array of games (id, name) that exist but haven't started yet.
 */
function listAvailableGames() {
	$sql = "select id, name, admin_id from games where has_started = 0";

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

function getGameAdmin($gameId) {
	return SQLGetChamp("select admin_id from games where id = $gameId");
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
	// black cards
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
 * If $filter == true, black cards are stripped of their assigned colors.
 */
function getPlacedCards($gameId, $filter=false) {
	$sql = "select card_name from placed_cards where game_id = $gameId order by id asc";
	$cards = mapToArray(parcoursRs(SQLSelect($sql)), "card_name");

	if (!$filter) {
		return $cards;
	}

	foreach ($cards as $index => $card) {
		$sym = cardSymbol($card);

		if ($sym == "joker" || $sym == "plusfour") {
			$cards[$index] = cardSymbol($card);
		}
	}

	return $cards;
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

	$usedCards = array_merge($usedCards, getPlacedCards($gameId, true));
	$unusedCards = allCards; // it's a copy

	foreach ($usedCards as $usedCard) {
		$index = array_search($usedCard, $unusedCards);
		array_splice($unusedCards, $index, 1);
	}

	return $unusedCards;
}

/* "$color-$symbol" => "$color".
 */
function cardColor($card) {
	if (($index = strpos($card, "-")) !== false) {
		return substr($card, 0, $index);
	}

	return null;
}

/* "$color-$symbol" => "$symbol".
 */
function cardSymbol($card) {
	if (($index = strpos($card, "-")) !== false) {
		return substr($card, $index + 1);
	}

	return $card;
}

/* Moves a card from the player's deck to the card stack, if the move is valid.
 * Returns whether the move was, in fact, valid.
 * Note: does not handle drawing cards from the pile in case of plustwo etc...
 */
function placeCard($userId, $card) {
	$gameId = getGameOf($userId);
	$info = parcoursRs(SQLSelect("select * from games where id = $gameId"))[0];
	$deck = getDeck($userId);
	$placed = getPlacedCards($gameId);
	$lastPlaced = end($placed);
	$sym = cardSymbol($card);
	$color = cardColor($card);

	// Sanity checks
	if ($gameId == NOT_IN_GAME
		|| $info["user_to_play"] != $userId
		|| !(in_array($card, $deck) || in_array($sym, $deck))
		|| $info["has_started"] != 1
		|| strpos($card, "-") === false) {

		return false;
	}

	// TODO!!! Disallow +4 when other colors could work

	// Check that this move would in fact be valid
	// i.e. if good color || joker || +4 || good symbol (number, reverse, plustwo, skip))
	if (cardColor($lastPlaced) == $color || $sym == "joker" || $sym == "plusfour"
		|| cardSymbol($lastPlaced) == $sym) {

		if ($sym == "reverse") {
			$info["direction"] = $info["direction"] == 0 ? 1 : 0;
		}

		// If there are only two players, the reverse card acts as a skip card
		if ($sym != "skip" && !($sym == "reverse" && count(getPlayers($gameId)) == 2)) {
			$info["user_to_play"] = nextToPlay($gameId, $info["direction"]);
		}

		$toDraw = 0;

		if ($sym == "plustwo") {
			$toDraw = 2;
		} else if ($sym == "plusfour") {
			$toDraw = 4;
		}

		// Delete something that actually exists in the db
		$toDelete = in_array($sym, array("joker", "plusfour")) ? $sym : $card;

		SQLDelete("delete from decks where user_id = $userId and card_name = '$toDelete'");
		SQLInsert("insert into placed_cards (game_id, card_name) values ($gameId, '$card')");
		SQLUpdate("update games set user_to_play = $info[user_to_play], direction = $info[direction] where id = $gameId");
		SQLUpdate("update users set cards_to_draw = $toDraw where id = $info[user_to_play]");

		return true;
	}

	return false;
}

/* Tirer de la pioche. Returns the drawn card.
 * TODO: check there are cards to draw from.
 */
function drawCard($userId) {
	$options = getUnusedCards(getGameOf($userId));

	if (count($options) == 0) {
		return false;
	}

	$index = array_rand($options);
	$card = $options[$index];
	$to_draw = SQLGetChamp("select cards_to_draw from users where id = $userId");

	SQLInsert("insert into decks (user_id, card_name) values ($userId, '$card')");

	if ($to_draw-- > 0) {
		SQLUpdate("update users set cards_to_draw = $to_draw where id = $userId");

		// We had been forced to draw by a +2 or +4
		if ($to_draw == 0) {
			$gameId = getGameOf($userId);
			$next = nextToPlay($gameId);

			SQLUpdate("update games set user_to_play = $next");
		}
	}

	return $card;
}

function getDirection($gameId) {
	return SQLGetChamp("select direction from games where id = $gameId");
}

function currentPlayer($gameId) {
	return SQLGetChamp("select user_to_play from games where id = $gameId");
}

function cardsToDraw($userId) {
	return SQLGetChamp("select cards_to_draw from users where id = $userId");
}

/* Not right now, but like, later.
 * Takes skips cards into account.
 */
function nextToPlay($gameId, $direction = null) {
	$sql = "select user_to_play, direction from games where id = $gameId";
	$info = parcoursRs(SQLSelect($sql))[0];
	$placed = getPlacedCards($gameId);

	if ($direction !== null) {
		$info["direction"] = $direction;
	}

	$direction = $info["direction"] == 1 ? 1 : -1;
	$to_play = $info["user_to_play"];
	$players = getPlayers($gameId);

	$index = array_search($to_play, $players) + $direction;
	$mod = count($players);

	return $players[(abs($index * $mod) + $index) % $mod];
}