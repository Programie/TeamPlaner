$(function()
{
	// User clicks download button in report modal
	$("#report-download").on("click", function()
	{
		var path =
		[
			"index.php/service/report/download",
			$("#current-team").data("name"),
			$("#report-year").data("value")
		];

		var monthElement = $("#report-month");
		if (monthElement.data("value"))
		{
			path.push(monthElement.data("value"));
		}

		document.location = path.join("/");
	});

	// User clicks year report button in navbar
	$("#year-report-button").on("click", function()
	{
		loadReport();
	});

	// User clicks month header
	$("#table-container").on("click", ".month-header", function()
	{
		loadReport($(this).data("month"));
	});
});

/**
 * Load the report data of the current year with an optional month.
 *
 * @param month int The month (1 = January) or null/0 to show a report of the whole year
 */
function loadReport(month)
{
	var year = $("#current-year").text();

	var path =
	[
		"report/data",
		$("#current-team").data("name"),
		year
	];

	if (month)
	{
		path.push(month);
	}

	loadDataFromBackend(path.join("/"), "GET", function(data)
	{
		Types.load(function()
		{
			var monthElement = $("#report-month");
			if (month)
			{
				monthElement.text(moment.months()[month - 1]);
				monthElement.data("value", month);
				monthElement.show();
			}
			else
			{
				monthElement.text("");
				monthElement.removeData("value");
				monthElement.hide();
			}

			var yearElement = $("#report-year");
			yearElement.text(year);
			yearElement.data("value", year);

			for (var userIndex in data)
			{
				var userData = data[userIndex];

				for (var entryIndex in userData.entries)
				{
					var entryData = userData.entries[entryIndex];

					var momentDate = moment(entryData.date);

					entryData.weekday = momentDate.format("dddd");
					entryData.type = Types.list[entryData.type].title;
					entryData.date = momentDate.format("L");
				}
			}

			var typeCount = 0;
			for (var index in Types.list)
			{
				if (Types.list[index].showInReport)
				{
					typeCount++;
				}
			}

			data.hasMultipleTypes = typeCount > 1;

			$("#report-content").html(Mustache.render($("#report-content-template").html(), data));

			$("#report-modal").modal("show");
		});
	});
}