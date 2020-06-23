const interval = 2000; // in ms
let username;
let deck;
let topCard;
let queryHandler;

$(function() {
    queryHandler = setInterval(queryGameInfo, interval);
});

function queryGameInfo() {
    $.ajax({
        type: "GET",
        url: this.href,
        data: { state: 1 },
        success: function(response) {
            $('#state').html(JSON.stringify(response));

            username = response.username;

            if (!response.started && response.is_admin) {
                $('#start-game').show().click(function() {
                    startGame();
                    $('#start-game').hide();
                });
            }

            updateOthers(response.players_info);
            updatePiles(response.top_of_pile);
            updateCards(response.deck);

            if (response.current_player == response.username) {
                console.log("It's our turn to play");
                enterTurn();
            }
        },
        dataType: "json"
    });
}

function startGame() {
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
    })
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

    queryHandler = setInterval(queryGameInfo, interval);
}

function updateOthers(players) {
    let info = "";

    for (name in players) {
        info += name + " (" + players[name] + " cards); ";
    }

    $('#players-list').html(info);
}

function updatePiles(newTopCard) {
    if (topCard == newTopCard) {
        return;
    }

    topCard = newTopCard;

    $('#draw-pile').empty();
    $('#draw-pile').append(makeCard("backside").click(function(ev) {
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

    $('#placed-pile > *').remove();
    $('#placed-pile').append(makeCard(newTopCard));
}

function updateCards(newDeck) {
    if (arrayEquals(deck, newDeck)) {
        return;
    }

    deck = newDeck;
    console.log("updating cards");

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
    let elem = $('<div class="card"></div>');


    if (card.includes("-")) {
        let parts = card.split("-");

        // Replace the color by a class for more control
        elem.css('background-color', parts[0]) // color
        elem.html(parts[1]); // card name
    } else {
        elem.css('background-color', 'black');
        elem.css('color', 'white');
        elem.html(card);
    }

    if (card == "backside") {
        elem.html("Pioche");
    }

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
