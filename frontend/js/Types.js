function Types()
{
}

Types.load = function(callback)
{
	if (this.list !== null)
	{
		if (callback)
		{
			callback();
		}
		return;
	}

	loadDataFromBackend("types", "GET", function(data)
	{
		this.list = data;

		if (callback)
		{
			callback();
		}
	}.bind(this));
};

Types.list = null;