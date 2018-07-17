<?php
use com\selfcoders\teamplaner\auth\UserAuthFactory;
use com\selfcoders\teamplaner\BackendHandler;
use com\selfcoders\teamplaner\Config;
use com\selfcoders\teamplaner\DBConnection;
use com\selfcoders\teamplaner\service\exception\EndpointNotFoundException;
use com\selfcoders\teamplaner\service\exception\ServiceConfigurationException;

require_once __DIR__ . "/../bootstrap.php";

$config = new Config();

if (!isset($_SERVER["HTTPS"]) and $config->getValue("forceSsl")) {
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit;
}

$userAuthInstance = UserAuthFactory::getProvider($config->getValue("userAuth"));
if (!$userAuthInstance) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Unable to load User Auth provider!";
    exit;
}

$pdo = DBConnection::getConnection($config);

$token = isset($_GET["token"]) ? $_GET["token"] : null;
if ($token !== null) {
    $query = $pdo->prepare("
        SELECT `id`, `username`
        FROM `users`
        WHERE `token` = :token
    ");

    $query->execute(array
    (
        ":token" => $token
    ));

    if ($query->rowCount()) {
        $row = $query->fetch();

        $userAuthInstance->authorizeUserById($row->id, $row->username);
    }
}

$loggedIn = $userAuthInstance->checkAuth();

if ($loggedIn) {
    $username = $userAuthInstance->getUsername();

    $query = $pdo->prepare("
        SELECT `id`
        FROM `users`
        WHERE `username` = :username
    ");

    $query->execute(array
    (
        ":username" => $username
    ));

    if (!$query->rowCount()) {
        $query = $pdo->prepare("
            INSERT INTO `users`
            SET `username` = :username
        ");

        $query->execute(array
        (
            ":username" => $username
        ));
    }
}

$path = @$_SERVER["PATH_INFO"];
if (substr($path, 0, 9) == "/service/") {
    if (!$loggedIn) {
        header("HTTP/1.1 401 Unauthorized");
        echo "You have to authenticate first!";
        exit;
    }

    if (!$userAuthInstance->checkPermissions()) {
        header("HTTP/1.1 403 Forbidden");
        echo "You are not allowed to access this service!";
        exit;
    }

    $handler = new BackendHandler($config, $userAuthInstance);

    try {
        $response = $handler->handleRequest(substr($path, 8), $_SERVER["REQUEST_METHOD"], json_decode(file_get_contents("php://input")));
        if ($response !== null) {
            header("Content-Type: application/json");
            echo json_encode($response);
        }
    } catch (EndpointNotFoundException $exception) {
        header("HTTP/1.1 404 Not Found");
        echo "The requested endpoint '" . $exception->getPath() . "' (" . $exception->getMethod() . ") does not exist!";
    } catch (ServiceConfigurationException $exception) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Error in endpoint configuration!";
    } catch (Exception $exception) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Error while executing method! (" . $exception->getMessage() . ")";
    }

    exit;
}

if (isset($_GET["logout"])) {
    $userAuthInstance->logout();
}

if (!$loggedIn) {
    $userAuthInstance->forceAuth();
}

if (!$userAuthInstance->checkPermissions()) {
    header("HTTP/1.1 403 Forbidden");
    ?>
    <html>
        <head>
            <title>403 Forbidden</title>
        </head>

        <body>
            <h1>Forbidden</h1>

            <p>You are currently logged in as <?php echo $userAuthInstance->getUsername(); ?>.</p>

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
        <script src="bower_components/moment/min/moment-with-locales.min.js" type="text/javascript"></script>
        <script src="bower_components/notifyjs/dist/notify.js" type="text/javascript"></script>
        <script src="bower_components/js-cookie/src/js.cookie.js" type="text/javascript"></script>

        <script src="js/utils.js" type="text/javascript"></script>

        <script src="js/Colors.js" type="text/javascript"></script>
        <script src="js/Holidays.js" type="text/javascript"></script>
        <script src="js/TeamMembers.js" type="text/javascript"></script>
        <script src="js/Teams.js" type="text/javascript"></script>
        <script src="js/Types.js" type="text/javascript"></script>

        <script src="js/ical.js" type="text/javascript"></script>
        <script src="js/report.js" type="text/javascript"></script>
        <script src="js/selection.js" type="text/javascript"></script>

        <script src="js/main.js" type="text/javascript"></script>

        <script type="text/html" id="table-template">
            <table class="table table-header-rotated">
                <thead>
                    <tr>
                        {{#months}}
                            <th class="month-header" colspan="{{columns}}" data-month="{{number}}" title="Click to show a report for this month">{{name}}</th>
                        {{/months}}
                    </tr>
                    <tr>
                        {{#months}}
                            <th></th>
                            {{#members}}
                                <th class="rotate-90" data-toggle="tooltip" data-placement="bottom" title="{{additionalInfo}}">
                                    <div>
                                        <span>{{username}}</span>
                                    </div>
                                </th>
                            {{/members}}
                        {{/months}}
                    </tr>
                </thead>
                <tbody>
                    {{#rows}}
                        <tr>
                            {{#columns}}
                                <td style="background-color: {{color}};" class="{{classname}}" data-date="{{date}}" data-userid="{{userId}}" data-memberid="{{memberId}}" data-entryid="{{entryId}}" data-toggle="tooltip" data-placement="right" title="{{title}}">{{text}}</td>
                            {{/columns}}
                        </tr>
                    {{/rows}}
                </tbody>
            </table>
        </script>

        <script type="text/html" id="report-content-template">
            {{#.}}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{username}}
                        {{#additionalUserInfo}}
                            <div class="report-content-additional-info"><span class="label label-primary">{{.}}</span></div>
                        {{/additionalUserInfo}}
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Week day</th>
                                {{#hasMultipleTypes}}
                                    <th>Type</th>
                                {{/hasMultipleTypes}}
                            </tr>
                        </thead>
                        <tbody>
                            {{#entries}}
                                <tr>
                                    <td>{{date}}</td>
                                    <td>{{weekday}}</td>
                                    {{#hasMultipleTypes}}
                                        <td>{{type}}</td>
                                    {{/hasMultipleTypes}}
                                </tr>
                            {{/entries}}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">{{entries.length}} entries</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            {{/.}}
            {{^.}}
                <div class="alert alert-danger">
                    <i class="glyphicon glyphicon-exclamation-sign"></i> <strong>No report available!</strong>
                </div>
            {{/.}}
        </script>
    </head>

    <body>
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container-fluid">
                <div class="navbar-header">
                    <span class="navbar-brand">Calendar</span>
                </div>

                <ul class="nav navbar-nav navbar-left">
                    <li><a id="previous-year-link"><i class="glyphicon glyphicon-chevron-left"></i></a></li>
                    <li><span class="navbar-text" id="current-year"></span></li>
                    <li><a id="next-year-link"><i class="glyphicon glyphicon-chevron-right"></i></a></li>
                </ul>

                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="glyphicon glyphicon-book"></i> <span id="current-team"></span></a>
                        <ul class="dropdown-menu" role="menu" id="team-menu"></ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="glyphicon glyphicon-cog"></i> Options</a>
                        <ul class="dropdown-menu" role="menu">
                            <li><a id="year-report-button"><i class="glyphicon glyphicon-th-list"></i> Year report</a></li>
                            <li><a id="ical-button"><i class="glyphicon glyphicon-calendar"></i> iCal</a></li>
                            <li><a id="reload-button"><i class="glyphicon glyphicon-refresh"></i> Reload</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="glyphicon glyphicon-user"></i> <span id="header-username"></span> <span class="caret"></span></a>
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

        <div class="modal fade" id="report-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title">Report for <span id="report-month"></span> <span id="report-year"></span></h4>
                    </div>
                    <div class="modal-body" id="report-content">
                    </div>
                    <div class="modal-footer">
                        <?php
                        if ($config->isValueSet("reportClass")) {
                        ?>
                            <button type="button" class="btn btn-default" id="report-download"><i class="glyphicon glyphicon-download"></i> Download</button>
                            <?php
                        }
                        ?>
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ical-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title">iCal</h4>
                    </div>
                    <div class="modal-body">
                        <span class="help-block">Copy one of the following URLs and paste it into your calendar application.</span>

                        <div>
                            <label for="ical-url">Your entries</label>
                            <input type="text" class="form-control" id="ical-url" readonly="readonly"/>
                        </div>

                        <div>
                            <label for="ical-url-team">Team entries</label>
                            <input type="text" class="form-control" id="ical-url-team" readonly="readonly"/>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>