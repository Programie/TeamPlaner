$(window).on("hashchange", updateTeamYear);

$(function()
{
	// Fix height of table user headers
	var tableHeaders = $("th.table-header-user");
	tableHeaders.css("height", tableHeaders.width());

	$("#reload-button").on("click", updateData);

	loadDataFromBackend("user/name", "GET", function(data)
	{
		$("#header-username").text(data.username);
	});

	Colors.load(function()
	{
		Types.load(function()
		{
			var typeSelection = $("#selection-modal-type");

			typeSelection.empty();

			for (var index in Types.list)
			{
				var typeData = Types.list[index];

				typeSelection.append($("<option>").val(typeData.name).text(typeData.title));
			}

			updateTeamYear();
		});
	});
});

function updateTeamYear()
{
	var hashString = document.location.hash.substring(1);
	hashString = hashString.split("/");

	Teams.current = hashString[0];// 0 = team
	var year = hashString[1];// 1 = year

	Teams.load(function()
	{
		var menuElement = $("#team-menu");

		var foundValidTeam = false;
		for (var index in Teams.list)
		{
			var teamData = Teams.list[index];

			var linkElement = $("<a>");
			linkElement.attr("href", "#" + teamData.name + "/" + year);
			linkElement.addClass("team-entry");
			linkElement.data("name", teamData.name);
			linkElement.text(teamData.title);

			var listEntry = $("<li>");
			listEntry.append(linkElement);
			menuElement.append(listEntry);

			if (teamData.name == Teams.current)
			{
				$("#current-team").data("name", teamData.name).text(teamData.title);
				foundValidTeam = true;
			}
		}

		if (!foundValidTeam)
		{
			var firstTeam = Teams.list[0];
			Teams.current = firstTeam.name;
			$("#current-team").data("name", firstTeam.name).text(firstTeam.title);
		}

		if (!year)
		{
			year = new Date().getFullYear();
		}

		year = parseInt(year);

		$("#previous-year-link").prop("href", "#" + Teams.current + "/" + (year - 1));
		$("#next-year-link").prop("href", "#" + Teams.current + "/" + (year + 1));

		$("#current-year").text(year);

		updateData();
	});
}

function updateData()
{
	var year = parseInt($("#current-year").text());

	loadDataFromBackend("entries/" + Teams.current + "/" + year, "GET", function(data)
	{
		TeamMembers.load(function()
		{
			Holidays.load(year, function()
			{
				var months = moment.months();

				for (var month in months)
				{
					month = parseInt(month);

					var monthStart = moment(new Date(year, month, 1));
					var monthEnd = monthStart.clone().endOf("month");

					var monthMembers = [];

					for (var memberId in TeamMembers.list)
					{
						var memberData = TeamMembers.list[memberId];

						// Check whether the team member should be visible in this month
						if ((memberData.startDate && memberData.startDate.isAfter(monthEnd)) || (memberData.endDate && memberData.endDate.isBefore(monthStart)))
						{
							continue;
						}

						monthMembers.push(memberData);
					}

					months[month] =
					{
						number : month + 1,
						name : months[month],
						members : monthMembers,
						columns : monthMembers.length + 1
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
						var date = new Date(year, month, day);

						if (date.getMonth() != month || date.getDate() != day)
						{
							valid = false;
						}

						var isToday = nowDate.getFullYear() == date.getFullYear() && nowDate.getMonth() == date.getMonth() && nowDate.getDate() == date.getDate();

						var dateDay = date.getDay();
						var isWeekend = dateDay == 6 || dateDay == 0;
						var isoDate = moment(date).format("YYYY-MM-DD");

						var color = "white";

						if (isWeekend && Colors.list.weekend)
						{
							color = Colors.list.weekend;
						}

						var title = null;

						if (Colors.list.holiday)
						{
							if (Holidays.list.hasOwnProperty(isoDate))
							{
								color = Colors.list.holiday;
								title = Holidays.list[isoDate];
							}
						}

						columns.push(
						{
							title : title,
							text : valid ? moment(date).format("dd, L") : "",
							color : valid ? (isToday && Colors.list.today ? Colors.list.today : color) : "white"
						});

						var dayEntries = valid ? data[moment(date).format("YYYY-MM-DD")] : null;

						if (!dayEntries)
						{
							dayEntries = [];
						}

						for (var memberIndex in monthData.members)
						{
							var memberData = monthData.members[memberIndex];
							var userColor = color;

							var entryId = 0;

							for (var entryIndex in dayEntries)
							{
								if (dayEntries[entryIndex].memberId == memberData.memberId)
								{
									userColor = Types.list[dayEntries[entryIndex].type].color;
									entryId = dayEntries[entryIndex].id;
									break;
								}
							}

							columns.push(
							{
								selectable : valid,
								color : valid ? userColor : "white",
								date : date,
								userId : memberData.userId,
								memberId : memberData.memberId,
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

				$("#table-container").html(Mustache.render($("#table-template").html(), tableData));

				$("[data-toggle='tooltip']").tooltip(
				{
					container : "body"
				});
			});
		});
	});
}