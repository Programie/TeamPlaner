<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../includes/Config.php";

$config = new Config();

$userAuthClassName = basename($config->getValue("userAuth"));

if (!file_exists(__DIR__ . "/../includes/userauth/" . $userAuthClassName . ".php"))
{
	header("HTTP/1.1 500 Internal Server Error");
	echo "Unable to load User Auth provider!";
	exit;
}

require_once __DIR__ . "/../includes/userauth/" . $userAuthClassName . ".php";

$userAuthClassName = "userauth\\" . $userAuthClassName;

/**
 * @var $userAuth userauth\iUserAuth
 */
$userAuth = new $userAuthClassName;

if (isset($_GET["logout"]))
{
	$userAuth->logout();
}

$userAuth->forceAuth();

if (!$userAuth->checkPermissions())
{
	header("HTTP/1.1 403 Forbidden");
	?>
	<html>
		<head>
			<title>403 Forbidden</title>
		</head>

		<body>
			<h1>Forbidden</h1>

			<p>You are currently logged in as <?php echo $userAuth->getUsername();?>.</p>

			<p>This user is not allowed to access this page!</p>

			<p>Click <a href="?logout">here</a> to relogin.</p>
		</body>
	</html>
	<?php
	exit;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Calendar</title>

		<link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css" type="text/css"/>

		<link rel="stylesheet" href="css/rotated-table-headers.css" type="text/css"/>
		<link rel="stylesheet" href="css/main.css" type="text/css"/>

		<script src="bower_components/jquery/dist/jquery.min.js" type="text/javascript"></script>
		<script src="bower_components/bootstrap/dist/js/bootstrap.min.js" type="text/javascript"></script>
		<script src="bower_components/mustache/mustache.js" type="text/javascript"></script>
		<script src="bower_components/moment/moment.js" type="text/javascript"></script>

		<script src="js/main.js" type="text/javascript"></script>

		<script type="text/html" id="table-template">
			<table class="table table-header-rotated">
				<thead>
					<tr>
						{{#months}}
							<th colspan="{{columnsPerMonth}}">{{.}}</th>
						{{/months}}
					</tr>
					<tr>
						{{#months}}
							<th></th>
							{{#users}}
								<th class="rotate-45"><div><span>{{username}}</span></div></th>
							{{/users}}
						{{/months}}
					</tr>
				</thead>
				<tbody>
					{{#rows}}
						<tr>
							{{#columns}}
								<td style="background-color: {{color}};" {{#selectable}}class="selectable"{{/selectable}} data-date="{{date}}" data-userid="{{userId}}" data-entryid="{{entryId}}">{{text}}</td>
							{{/columns}}
						</tr>
					{{/rows}}
				</tbody>
			</table>
		</script>
	</head>

	<body>
		<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
			<div class="container-fluid">
				<div class="navbar-header">
					<span class="navbar-brand">Calendar</span>
				</div>

				<ul class="nav navbar-nav navbar-left">
					<li><a href="#" id="previous-year-link">&lt;&lt;</a></li>
					<li><span class="navbar-text" id="current-year"></span></li>
					<li><a href="#" id="next-year-link">&gt;&gt;</a></li>
				</ul>

				<ul class="nav navbar-nav navbar-right">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="glyphicon glyphicon-user"></i> <span id="header-username"></span> <span class="caret"></span></a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="?logout"><i class="glyphicon glyphicon-off"></i> Logout</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</nav>

		<div id="table-container"></div>

		<div class="modal fade" id="selection-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span>&times;</span><span class="sr-only">Close</span></button>
						<h4 class="modal-title">Edit for <span id="selection-modal-username"></span></h4>
					</div>
					<div class="modal-body">
						<fieldset>
							<legend>Date</legend>
							<span id="selection-modal-date"></span>
						</fieldset>

						<fieldset>
							<legend>Type</legend>
							<select class="form-control" id="selection-modal-type"></select>
						</fieldset>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary" id="selection-modal-save">Save changes</button>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>