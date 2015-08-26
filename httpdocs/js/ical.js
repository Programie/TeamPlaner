$(function()
{
	// User clicks the ical button in the navbar
	$("#ical-button").on("click", function()
	{
		loadDataFromBackend("user/token", "GET", function(data)
		{
			var path;

			if (document.location.pathname.substr(-10) == "/index.php")
			{
				path = document.location.pathname;
			}
			else
			{
				path = document.location.pathname + "index.php";
			}

			$("#ical-url").val(document.location.origin + path + "/service/ical?token=" + data.token);
			$("#ical-url-team").val(document.location.origin + path + "/service/ical/" + $("#current-team").data("name") + "?token=" + data.token);

			$("#ical-modal").modal("show");
		});
	});

	// User clicks in the ical URL field
	$("#ical-url").on("click", function()
	{
		$(this).select();
	});
});