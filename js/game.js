$(function() {
    setInterval(function() {
        $.ajax({
            type: "GET",
            url: this.href,
            data: { state: 1 },
            success: function(response) {
                console.log("success");
                $('#state').html(JSON.stringify(response));
            },
            dataType: "json"
        });
    }, 500);
});
