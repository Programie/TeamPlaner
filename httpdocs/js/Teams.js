function Teams()
{
}

Teams.load = function(callback)
{
	if (this.list !== null)
	{
		if (callback)
		{
			callback();
		}
		return;
	}

	loadDataFromBackend("teams", "GET", function(data)
	{
		this.list = data;

		if (callback)
		{
			callback();
		}
	}.bind(this));
};

Teams.setCurrent = function(team)
{
	Cookies.set("team", team,
	{
		expires: 365,
		path: ""
	});
};

Teams.getCurrent = function()
{
	Cookies.get("team");
};

Teams.list = null;