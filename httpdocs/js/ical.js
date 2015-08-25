$(function()
{
	// User clicks the ical button in the navbar
	$("#ical-button").on("click", function()
	{
		loadDataFromBackend("user/token", "GET", function(data)
		{
			$("#ical-url").val(document.location.origin + document.location.pathname + "service/ical?token=" + data.token);

			$("#ical-modal").modal("show");
		});
	});

	// User clicks in the ical URL field
	$("#ical-url").on("click", function()
	{
		$(this).select();
	});
});