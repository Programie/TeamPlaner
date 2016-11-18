function TeamMembers() {
}

TeamMembers.load = function (callback) {
    if (this.list !== null && Teams.getCurrent() == this.team) {
        if (callback) {
            callback();
        }
        return;
    }

    loadDataFromBackend("teams/" + Teams.getCurrent() + "/members", "GET", function (data) {
        this.team = Teams.getCurrent();
        this.list = {};

        for (var index in data) {
            var memberData = data[index];

            if (memberData.startDate) {
                memberData.startDate = moment(memberData.startDate);
            }

            if (memberData.endDate) {
                memberData.endDate = moment(memberData.endDate);
            }

            this.list[memberData.memberId] = memberData;
        }

        if (callback) {
            callback();
        }
    }.bind(this));
};

TeamMembers.team = null;
TeamMembers.list = null;