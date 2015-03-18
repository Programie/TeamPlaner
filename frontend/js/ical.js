$(function()
{
	// User clicks the ical button in the navbar
	$("#ical-button").on("click", function()
	{
		$.ajax(
		{
			cache : false,
			contentType : "application/json",
			context : this,
			error : function(xhr, error, errorThrown)
			{
				$.notify(error + ": " + errorThrown, "error");
			},
			success : function(data)
			{
				$("#ical-url").val(document.location.origin + document.location.pathname + "service/ical?token=" + data.token);

				$("#ical-modal").modal("show");
			},
			url : "service/user/token"
		});
	});

	// User clicks in the ical URL field
	$("#ical-url").on("click", function()
	{
		$(this).select();
	});
});