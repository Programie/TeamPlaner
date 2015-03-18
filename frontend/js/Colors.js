function Colors()
{
}

Colors.load = function(callback)
{
	if (this.list !== null)
	{
		if (callback)
		{
			callback();
		}
		return;
	}

	loadDataFromBackend("colors", "GET", function(data)
	{
		this.list = data;

		if (callback)
		{
			callback();
		}
	}.bind(this));
};

Colors.list = null;