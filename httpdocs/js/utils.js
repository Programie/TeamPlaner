function loadDataFromBackend(path, method, callback, data) {
    $.ajax({
        cache: false,
        contentType: "application/json",
        context: this,
        data: data,
        error: function (xhr, error, errorThrown) {
            $.notify(error + ": " + errorThrown, "error");
        },
        success: callback,
        type: method,
        url: "index.php/service/" + path
    });
}