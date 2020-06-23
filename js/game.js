$(function() {
    setInterval(function() {
        $.ajax({
            type: "GET",
            url: this.href,
            data: { state: 1 },
            success: function(response) {
                $('#state').html(JSON.stringify(response));
                if (response.current_player == response.username) {
                    console.log("It's our turn to play");
                    clearInterval();
                }
            },
            dataType: "json"
        });
    }, 2000);
});