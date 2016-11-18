function Holidays() {
}

Holidays.load = function (year, callback) {
    if (this.list !== null && year == this.year) {
        if (callback) {
            callback();
        }
        return;
    }

    loadDataFromBackend("holidays/" + year, "GET", function (data) {
        this.year = year;
        this.list = data;

        if (callback) {
            callback();
        }
    }.bind(this));
};

Holidays.year = null;
Holidays.list = null;