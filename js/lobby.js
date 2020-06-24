$(function() {
    update();
    setInterval(update, 1000);
});

function update() {
    $.ajax({
        type: "POST",
        url: this.href,
        data: { action: "refresh" },
        dataType: "json",
        success: (games) => {
            updateList(games);
        }
    });
}

function updateList(games) {
    let list = $('#game-list');
    list.empty();

    let headers = $('<th>Id</th><th>Nom</th><th>Nombre de joueurs connectés</th><th>Créateur</th>');
    list.append(headers);

    games.forEach(game => {
        $('<tr class="game" id="'+game.id+'">')
            .append(makeTd(game.id))
            .append(makeTd(game.name))
            .append(makeTd(game.numPlayers))
            .append(makeTd(game.admin))
            .appendTo(list);
    });

    $('.game').click(function(ev) {
        let row = $(ev.target).parents("tr");
        $('.game').siblings().removeClass('selected');
        row.toggleClass('selected');
        $('#join-text').val(row.attr('id'));
    });

    $('.game').dblclick(function(ev) {
        let row = $(ev.target).parents("tr");
        $('#join-text').val(row.attr('id'));
        $('#join-btn')[0].click();
    });
}

function makeTd(content) {
    return $('<td>' + content + '</td>');
}