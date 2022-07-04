function download(filename, text, add_date_time = false) {

    if (add_date_time) {
        var dt = new Date();
        var dd = String(dt.getDate()).padStart(2, '0');
        var mm = String(dt.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = dt.getFullYear();

        today = yyyy + mm + dd;
        var time = dt.getHours() + '' + dt.getMinutes() + '' + dt.getSeconds() + '';

        filename = today + '_' + time + '_' + filename;
    }

    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}
