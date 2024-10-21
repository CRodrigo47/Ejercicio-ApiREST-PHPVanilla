<?php

declare(strict_types=1);

require("src/ErrorHandler.php");
set_exception_handler("ErrorHandler::handleException");
set_error_handler("ErrorHandler::errorHandler");

require("src/Database.php");
require("src/Controllers/PistasController.php");
require("src/Controllers/ReservasController.php");
require("src/Controllers/SociosController.php");

$database = new Database("localhost", "deportes_db", "root", "");

Header("Content-Type: application/json; Charset=UTF-8");

$parts = explode("/", $_SERVER["REQUEST_URI"]);

$endpoint=$parts[2];
$id=$parts[3] ?? null;

$method = $_SERVER["REQUEST_METHOD"];

switch($endpoint) {
    case "pistas":
        $controller = new PistasController($database);
        $controller->processRequest($method, $id);
        break;
    case "reservas":
        $controller = new ReservasController($database);
        $controller->processRequest($method, $id);
        break;
    case "socios":
        $controller = new SociosController($database);
        $controller->processRequest($method, $id);
        break;
    default:
        http_response_code(404);
        break;
}