var users = {};

$(window).on("hashchange", updateData);

$(function()
{
	var tableHeaders = $("th.table-header-user");

	tableHeaders.css("height", tableHeaders.width());

	var tableContainer = $("#table-container");

	$("#selection-modal").on("hidden.bs.modal", function()
	{
		updateSelection();
	});

	$("#selection-modal-save").on("click", function()
	{
		var entries = [];

		$("td.selection-highlight").each(function()
		{
			entries.push(
			{
				id : $(this).data("entryid"),
				date : moment(new Date($(this).data("date"))).format("YYYY-MM-DD"),
				type : $("#selection-modal-type").val(),
				userId : $(this).data("userid")
			});
		});

		$.ajax(
		{
			cache : false,
			contentType : "application/json",
			data : JSON.stringify(entries),
			context : this,
			success : function()
			{
				updateData();

				$("#selection-modal").modal("hide");
			},
			type : "POST",
			url : "../service/?type=setData"
		});
	});

	var selectionStart = null;

	tableContainer.on("mousedown", "td.selectable", function()
	{
		selectionStart =
		{
			date : new Date($(this).data("date")),
			userId : $(this).data("userid")
		};

		updateSelection($(this).data("userid"), selectionStart.date, new Date($(this).data("date")));

		return false;// Prevent showing text selection
	});

	tableContainer.on("mouseover", "td.selectable", function()
	{
		if (selectionStart && selectionStart.userId == $(this).data("userid"))
		{
			updateSelection($(this).data("userid"), selectionStart.date, new Date($(this).data("date")));
		}
	});

	tableContainer.on("mouseup", "td.selectable", function()
	{
		if (selectionStart && selectionStart.userId == $(this).data("userid"))
		{
			updateSelection($(this).data("userid"), selectionStart.date, new Date($(this).data("date")));

			var modal = $("#selection-modal");

			var startDate = moment(selectionStart.date);
			var endDate = moment(new Date($(this).data("date")));

			if (startDate.isSame(endDate))
			{
				$("#selection-modal-date").text(startDate.format("L"));
			}
			else
			{
				$("#selection-modal-date").text(startDate.format("L") + " - " + endDate.format("L"));
			}

			$("#selection-modal-username").text(users[$(this).data("userid")].username);

			modal.modal("show");
		}
		else
		{
			updateSelection();
		}

		selectionStart = null;
	});

	updateData();
});

function updateData()
{
	var year = document.location.hash.substring(1);
	if (!year)
	{
		year = new Date().getFullYear();
	}

	$.ajax(
	{
		cache : false,
		dataType : "json",
		context : this,
		headers :
		{
			Accept : "application/json"
		},
		success : readData,
		url : "../service/?type=getData&year=" + year
	});
}

function readData(data)
{
	data.year = parseInt(data.year);

	$("#previous-year-link").prop("href", "#" + (data.year - 1));
	$("#next-year-link").prop("href", "#" + (data.year + 1));

	$("#current-year").text(data.year);

	users = {};
	for (var userIndex in data.users)
	{
		users[data.users[userIndex].id] = data.users[userIndex];
	}

	var types = {};
	for (var typeIndex in data.types)
	{
		var type = data.types[typeIndex];

		types[type.name] = type;
	}

	var tableData =
	{
		months : moment.months(),
		users : data.users,
		rows : []
	};

	tableData.columnsPerMonth = tableData.users.length + 1;

	for (var day = 1; day <= 31; day++)
	{
		var columns = [];

		for (var month in tableData.months)
		{
			var valid = true;
			var date = new Date(data.year, month, day);

			if (date.getMonth() != month || date.getDate() != day)
			{
				valid = false;
			}

			var dateDay = date.getDay();
			var isWeekend = dateDay == 6 || dateDay == 0;
			var isoDate = moment(date).format("YYYY-MM-DD");

			var color = "white";

			if (isWeekend)
			{
				color = data.colors.weekend;
			}

			for (var holidayIndex in data.holidays)
			{
				if (data.holidays[holidayIndex] == isoDate)
				{
					color = data.colors.holiday;
					break;
				}
			}

			columns.push(
			{
				text : valid ? moment(date).format("dd, L") : "",
				color : valid ? color : "white"
			});

			var dayEntries = valid ? data.entries[moment(date).format("YYYY-MM-DD")] : null;

			if (!dayEntries)
			{
				dayEntries = [];
			}

			for (var userIndex in data.users)
			{
				var userColor = color;

				var entryId = 0;

				for (var entryIndex in dayEntries)
				{
					if (dayEntries[entryIndex].userId == data.users[userIndex].id)
					{
						userColor = types[dayEntries[entryIndex].type].color;
						entryId = dayEntries[entryIndex].id;
						break;
					}
				}

				columns.push(
				{
					selectable : valid,
					color : valid ? userColor : "white",
					date : date,
					userId : data.users[userIndex].id,
					entryId : entryId
				});
			}
		}

		tableData.rows.push(
		{
			day : day,
			columns : columns
		});
	}

	$("#header-username").text(data.username);

	$("#table-container").html(Mustache.render($("#table-template").html(), tableData));

	var typeSelection = $("#selection-modal-type");

	typeSelection.empty();

	for (var index in data.types)
	{
		var type = data.types[index];

		typeSelection.append($("<option>").val(type.name).text(type.title));
	}
}

function updateSelection(userId, startDate, endDate)
{
	var cells = $("#table-container").find("td.selectable");

	var elements = cells.filter(function()
	{
		if ($(this).data("userid") != userId)
		{
			return false;
		}

		var date = moment(new Date($(this).data("date")));

		if (date.isBefore(startDate))
		{
			return false;
		}

		if (date.isAfter(endDate))
		{
			return false;
		}

		return true;
	});

	cells.not(elements).removeClass("selection-highlight");
	elements.addClass("selection-highlight");
}