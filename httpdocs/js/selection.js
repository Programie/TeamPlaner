$(function()
{
	// Selection modal gets closed (cancelled or saved)
	$("#selection-modal").on("hidden.bs.modal", function()
	{
		updateSelection();
	});

	// User clicks save button in selection modal
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

		loadDataFromBackend("entries/" + Teams.getCurrent(), "PUT", function()
		{
			updateData();

			$("#selection-modal").modal("hide");
		}, JSON.stringify(entries));
	});

	var selectionStart = null;
	var tableContainer = $("#table-container");

	// User presses the mouse button
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

	// User moves mouse over a selectable cell
	tableContainer.on("mouseover", "td.selectable", function()
	{
		if (selectionStart && selectionStart.memberId == $(this).data("memberid"))
		{
			updateSelection($(this).data("memberid"), selectionStart.date, new Date($(this).data("date")));
		}
	});

	// User released the mouse button
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

			$("#selection-modal-username").text(TeamMembers.list[$(this).data("memberid")].username);

			$("#selection-modal-type").val("");

			modal.modal("show");
		}
		else
		{
			updateSelection();
		}

		selectionStart = null;
	});
});
/**
 * Update the current selection (highlights selected cells, removes highlighting from not selected cells).
 *
 * @param memberId int The ID of the team member
 * @param startDate moment The start date of the selection
 * @param endDate moment The end date of the selection
 */
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

		return !(date.isBefore(startDate) || date.isAfter(endDate));
	});

	cells.not(elements).removeClass("selection-highlight");
	elements.addClass("selection-highlight");
}