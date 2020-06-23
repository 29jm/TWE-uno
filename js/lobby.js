$(function() {
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
});

function update() {
    setTimeout(function() { document.location.reload(true); }, 2000);
}
