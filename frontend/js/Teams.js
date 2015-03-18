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

Teams.list = null;
Teams.current = null;