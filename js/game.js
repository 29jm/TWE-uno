const interval = 2000; // in ms
let username;
let deck;
let topCard;
let queryHandler;

$(function() {
    queryGameInfo();
    queryHandler = setInterval(queryGameInfo, interval);
});

function queryGameInfo() {
    $.ajax({
        type: "GET",
        url: this.href,
        data: { state: 1 },
        success: function(response) {
            // $('#state').html(JSON.stringify(response, null, 2));

            username = response.username;

            if (!response.started && Object.keys(response.players_info).length >= 2 && response.is_admin) {
                $('#start-game').show().click(startGame);
            }

            if (response.started && response.is_admin) {
                $('#end-game').show().click(endGame);
            }

            updateOthers(response.players_info, response.current_player);
            updatePiles(response.top_of_pile);
            updateCards(response.deck);

            if (response.current_player == response.username) {
                enterTurn();
            }
        },
        error: function() {
            window.location.reload();
        },
        dataType: "json"
    });
}

function startGame() {
    $('#start-game').hide();

    $.ajax({
        type: "POST",
        url: this.href,
        data: { start: 1 },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                console.log("The game has started");
            }
        }
    });
}

function endGame() {
    $.ajax({
        type: "POST",
        url: this.href,
        data: { start: 2 },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                window.location.reload();
            }
        }
    });
}

function enterTurn() {
    clearInterval(queryHandler);

    // TODO:
    // - make our cards clickable
    // - make the pile clickable
    // - only if we don't have to draw cards though
    //   (mind how many)
    // - when we've either drawn the cards we had to, or placed a card,
    //   call exitTurn
}

function exitTurn() {
    // TODO:
    // - disable click handlers

    queryGameInfo();
    queryHandler = setInterval(queryGameInfo, interval);
}

function updateOthers(players, currentPlayer) {
    let playersList = $('#players-list');

    playersList.empty();

    for (name in players) {
        let playerInfo = makePlayerInfo(name, players[name]);

        if (name == currentPlayer) {
            playerInfo.addClass('player-current');
        }

        playersList.append(playerInfo);
    }
}

function updatePiles(newTopCard) {
    if (topCard == newTopCard) {
        return;
    }

    topCard = newTopCard;

    let piles = $('#card-piles');

    piles.empty();
    piles.append(makeCard("backside").click(function(ev) {
        console.log("Pioche cliquée");
        $.ajax({
            type: "POST",
            url: this.href,
            data: { draw: 1 },
            success: function(response) {
                if (response.success) {
                    console.log("carte piochée");
                    queryGameInfo();
                } else {
                    console.log("impossible de piocher maintenant");
                }
            },
            dataType: "json"
        });
    }));

    piles.append(makeCard(newTopCard));
}

function updateCards(newDeck) {
    if (arrayEquals(deck, newDeck)) {
        return;
    }

    deck = newDeck;

    let container = $('#player-deck');
    container.empty();

    newDeck.forEach(card => {
        container.append(makeCard(card).click(function(ev) {
            console.log("card clicked: "+card);
            if (card == "plusfour" || card == "joker") {
                console.log("placing black cards is unimplemented right now");
                return;
            }

            $.ajax({
                type: "POST",
                url: this.href,
                data: { place: card },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        console.log("placed card");
                        exitTurn();
                    } else {
                        console.log("you can't place this now");
                    }
                }
            });
        }));
    });
}

/* Makes the proper div, no handler.
 */
function makeCard(card) {
    let div = $('<div class="card"></div>');
    let elem = $('<span></span>');
    let colors = {
        "red": '#ff5555',
        "green": '#55aa55',
        "yellow": '#ffaa00',
        "blue": '#5555ff',
        "black": '#000000'
    };
    let contents = {
        "plusfour": '+4',
        "plustwo": '+2',
        "joker": '★',
        "backside": 'Uno',
        "reverse": '⟳'
    };

    let color = "black";
    let content = contents[card];

    if (card.includes("-")) {
        let parts = card.split("-");
        color = parts[0];
        content = parts[1];

        if (content in contents) {
            content = contents[content];
        }
    }

    div.css('color', 'white');
    div.css('background-color', colors[color]);
    elem.html(content);

    return div.append(elem);
}

function makePlayerInfo(name, numCards) {
    let elem = $('<div class=player-info></div>')
        .append('<span>' + name + '<br>' + numCards + ' cartes' + '</span>');

    return elem;
}

/* Compensate for js dumbness.
 * https://masteringjs.io/tutorials/fundamentals/compare-arrays
 */
function arrayEquals(a, b) {
    return Array.isArray(a) &&
        Array.isArray(b) &&
        a.length === b.length &&
        a.every((val, index) => val === b[index]);
}
