var users = {};

$(window).on("hashchange", updateData);

$(function()
{
	var tableHeaders = $("th.table-header-user");

	tableHeaders.css("height", tableHeaders.width());

	var tableContainer = $("#table-container");

	$("#report-download").on("click", function()
	{
		var path =
		[
			"service/report/download",
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

	$("#generate-entries-button").on("click", function()
	{
		$("#generate-entries-modal").modal("show");
	});

	$("#reload-button").on("click", updateData);

	$("#year-report-button").on("click", function()
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
				readReportData(data);

				$("#report-modal").modal("show");
			},
			url : "service/report/data/" + $("#current-team").data("name") + "/" + $("#current-year").text()
		});
	});

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

	$("#ical-url").on("click", function()
	{
		$(this).select();
	});

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
				memberId : $(this).data("memberid")
			});
		});

		$.ajax(
		{
			cache : false,
			contentType : "application/json",
			data : JSON.stringify(entries),
			context : this,
			error : function(xhr, error, errorThrown)
			{
				$.notify(error + ": " + errorThrown, "error");
			},
			success : function()
			{
				updateData();

				$("#selection-modal").modal("hide");
			},
			type : "PUT",
			url : "service/entries/" + $("#current-team").data("name")
		});
	});

	tableContainer.on("click", ".month-header", function()
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
				readReportData(data);

				$("#report-modal").modal("show");
			},
			url : "service/report/data/" + $("#current-team").data("name") + "/" + $("#current-year").text() + "/" + $(this).data("month")
		});
	});

	var selectionStart = null;

	tableContainer.on("mousedown", "td.selectable", function()
	{
		selectionStart =
		{
			date : new Date($(this).data("date")),
			memberId : $(this).data("memberid")
		};

		updateSelection($(this).data("memberid"), selectionStart.date, new Date($(this).data("date")));

		return false;// Prevent showing text selection
	});

	tableContainer.on("mouseover", "td.selectable", function()
	{
		if (selectionStart && selectionStart.memberId == $(this).data("memberid"))
		{
			updateSelection($(this).data("memberid"), selectionStart.date, new Date($(this).data("date")));
		}
	});

	tableContainer.on("mouseup", "td.selectable", function()
	{
		if (selectionStart && selectionStart.memberId == $(this).data("memberid"))
		{
			updateSelection($(this).data("memberid"), selectionStart.date, new Date($(this).data("date")));

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
	var hashString = document.location.hash.substring(1);
	hashString = hashString.split("/");

	var path = ["service/entries"];

	if (hashString[0])
	{
		path.push(hashString[0]);
	}

	if (hashString[1])
	{
		path.push(hashString[1]);
	}

	$.ajax(
	{
		cache : false,
		dataType : "json",
		context : this,
		error : function(xhr, error, errorThrown)
		{
			$.notify(error + ": " + errorThrown, "error");
		},
		headers :
		{
			Accept : "application/json"
		},
		success : readData,
		url : path.join("/")
	});
}

function readData(data)
{
	data.year = parseInt(data.year);

	var currentYearElement = $("#current-year");
	var currentTeamElement = $("#current-team");

	currentTeamElement.data("name", data.currentTeam);

	var menuElement = $("#team-menu");
	menuElement.empty();

	for (var index in data.teams)
	{
		var teamData = data.teams[index];

		var linkElement = $("<a>");
		linkElement.attr("href", "#" + teamData.name + "/" + currentYearElement.text());
		linkElement.addClass("team-entry");
		linkElement.data("name", teamData.name);
		linkElement.text(teamData.title);

		var listEntry = $("<li>");
		listEntry.append(linkElement);
		menuElement.append(listEntry);

		if (teamData.name == data.currentTeam)
		{
			currentTeamElement.text(teamData.title);
		}
	}

	$("#previous-year-link").prop("href", "#" + data.currentTeam + "/" + (data.year - 1));
	$("#next-year-link").prop("href", "#" + data.currentTeam + "/" + (data.year + 1));

	currentYearElement.text(data.year);

	users = {};
	for (var userIndex in data.users)
	{
		var userData = data.users[userIndex];

		if (userData.startDate)
		{
			userData.startDate = moment(userData.startDate);
		}

		if (userData.endDate)
		{
			userData.endDate = moment(userData.endDate);
		}

		users[userData.userId] = userData;
	}

	var types = {};
	for (var typeIndex in data.types)
	{
		var type = data.types[typeIndex];

		types[type.name] = type;
	}

	var months = moment.months();

	for (var index in months)
	{
		var monthStart = moment(new Date(data.year, index, 1));
		var monthEnd = monthStart.clone().endOf("month");

		var monthUsers = [];

		for (var userIndex in data.users)
		{
			var userData = data.users[userIndex];

			// Check if the user should be visible in this month
			if ((userData.startDate && userData.startDate.isAfter(monthEnd)) || (userData.endDate && userData.endDate.isBefore(monthStart)))
			{
				continue;
			}

			monthUsers.push(userData);
		}

		months[index] =
		{
			number : parseInt(index) + 1,
			name : months[index],
			users : monthUsers,
			columns : monthUsers.length + 1
		};
	}

	var tableData =
	{
		months : months,
		rows : []
	};

	for (var day = 1; day <= 31; day++)
	{
		var columns = [];

		for (var month in tableData.months)
		{
			var monthData = tableData.months[month];
			var valid = true;
			var nowDate = new Date();
			var date = new Date(data.year, month, day);

			if (date.getMonth() != month || date.getDate() != day)
			{
				valid = false;
			}

			var isToday = nowDate.getFullYear() == date.getFullYear() && nowDate.getMonth() == date.getMonth() && nowDate.getDate() == date.getDate();

			var dateDay = date.getDay();
			var isWeekend = dateDay == 6 || dateDay == 0;
			var isoDate = moment(date).format("YYYY-MM-DD");

			var color = "white";

			if (isWeekend && data.colors.weekend)
			{
				color = data.colors.weekend;
			}

			var title = null;

			if (data.colors.holiday)
			{
				if (data.holidays.hasOwnProperty(isoDate))
				{
					color = data.colors.holiday;
					title = data.holidays[isoDate];
				}
			}

			columns.push(
			{
				title : title,
				text : valid ? moment(date).format("dd, L") : "",
				color : valid ? (isToday && data.colors.today ? data.colors.today : color) : "white"
			});

			var dayEntries = valid ? data.entries[moment(date).format("YYYY-MM-DD")] : null;

			if (!dayEntries)
			{
				dayEntries = [];
			}

			for (var userIndex in monthData.users)
			{
				var userData = monthData.users[userIndex];
				var userColor = color;

				var entryId = 0;

				for (var entryIndex in dayEntries)
				{
					if (dayEntries[entryIndex].memberId == userData.memberId)
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
					userId : userData.userId,
					memberId : userData.memberId,
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

	$("[data-toggle='tooltip']").tooltip(
	{
		container : "body"
	});
}

function readReportData(data)
{
	var monthElement = $("#report-month");
	if (data.month)
	{
		monthElement.text(moment.months()[data.month - 1]);
		monthElement.data("value", data.month);
		monthElement.show();
	}
	else
	{
		monthElement.text("");
		monthElement.removeData("value");
		monthElement.hide();
	}

	var yearElement = $("#report-year");
	yearElement.text(data.year);
	yearElement.data("value", data.year);

	for (var userIndex in data.data)
	{
		var userData = data.data[userIndex];

		for (var entryIndex in userData.entries)
		{
			var entryData = userData.entries[entryIndex];

			var momentDate = moment(entryData.date);

			entryData.weekday = momentDate.format("dddd");
			entryData.type = data.types[entryData.type];
			entryData.date = momentDate.format("L");
		}
	}

	data.data.hasMultipleTypes = Object.keys(data.types).length > 1;

	$("#report-content").html(Mustache.render($("#report-content-template").html(), data.data));
}

function updateSelection(memberId, startDate, endDate)
{
	var cells = $("#table-container").find("td.selectable");

	var elements = cells.filter(function()
	{
		if ($(this).data("memberid") != memberId)
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