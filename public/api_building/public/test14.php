<?php
require "../bootstrap.php";
use Src\Controller\BuildingController;
use Src\Controller\NewsController;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// all of our endpoints start with /building
// everything else results in a 404 Not Found
// if ($uri[1] !== 'building') {
//     header("HTTP/1.1 404 Not Found");
//     exit();
// }

// the user id is, of course, optional and must be a number:
$strataNo = null;
// if (isset($uri[2])) {
//     $strataNo =  $uri[2];
// }

$strataNo = $_GET['strata'];
$streetNoOrCondoId = trim($_GET['streetnum']?:($_GET['condoid']?:''));

$task = 'building_info';

if(!empty($_GET['task']) && $_GET['task'] != ''){
    $task = $_GET['task'];
}

// TODO : If we want to add the authentication later on
if (! authenticate()) {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized');
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

// pass the request method and user ID to the BuildingController:
if($task == 'getnews'){
	$controller = new NewsController($dbConnection, $dbConnectionMLS, $dbConnectionLES, $requestMethod);
}else{
	$controller = new BuildingController($dbConnection, $dbConnectionMLS, $dbConnectionLES, $requestMethod, $strataNo, $task,$streetNoOrCondoId);
}
$controller->processRequest();
// $controller->printTestCacheDir14092021(); // can-be-deleted function+thisLine

function authenticate() {
    return true;
}