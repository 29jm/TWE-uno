$(function() {
    $('.game').click(function(ev) {
        $('.game').removeClass('selected');
        $(ev.target).addClass('selected');
        $('#join-text').val(ev.target.id);
    })

    $('.game').dblclick(function(ev) {
        var gameId = ev.target.id;

        $('#join-text').val(gameId);
        $('#join-btn')[0].click();
    });
});