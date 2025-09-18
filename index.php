<?php
// Hide warnings and notices in production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Prevent any output before JSON response
ob_start();
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'core/ApiResponse.php';

// Initialize database
$database = new Database();

// Simple routing system
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove base path if exists (adjust 'abarhail-api' to your actual folder name)
$base_path = '/abarhail-api';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
}

$path = trim($path, '/');
$segments = explode('/', $path);

// API versioning - expect /api/v1/...
if ($segments[0] !== 'api' || $segments[1] !== 'v1') {
    ApiResponse::error('Invalid API endpoint. Use /api/v1/', 404, 'INVALID_ENDPOINT');
}

$resource = $segments[2] ?? '';
$resourceId = $segments[3] ?? null;

// Route to appropriate controller
switch ($resource) {
    case 'news':
        require_once 'controllers/NewsController.php';
        $controller = new NewsController($database);

        switch ($method) {
            case 'GET':
                if ($resourceId) {
                    $controller->show($resourceId);
                } else {
                    $controller->index();
                }
                break;

            case 'POST':
                $controller->store();
                break;

            case 'PUT':
                if (!$resourceId) {
                    ApiResponse::error('News ID is required for update', 400, 'MISSING_ID');
                }
                $controller->update($resourceId);
                break;

            case 'DELETE':
                if (!$resourceId) {
                    ApiResponse::error('News ID is required for delete', 400, 'MISSING_ID');
                }
                $controller->destroy($resourceId);
                break;

            default:
                ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
        }
        break;

    case 'social':
        require_once 'controllers/SocialController.php';
        $controller = new SocialController($database);

        switch ($method) {
            case 'GET':
                if ($resourceId) {
                    $controller->show($resourceId);
                } else {
                    $controller->index();
                }
                break;

            case 'POST':
                $controller->store();
                break;

            case 'PUT':
                if (!$resourceId) {
                    ApiResponse::error('News ID is required for update', 400, 'MISSING_ID');
                }
                $controller->update($resourceId);
                break;

            case 'DELETE':
                if (!$resourceId) {
                    ApiResponse::error('News ID is required for delete', 400, 'MISSING_ID');
                }
                $controller->destroy($resourceId);
                break;

            default:
                ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
        }
        break;

    // Add this case to your existing switch statement in index.php
    case 'products':
        require_once 'controllers/ProductsController.php';
        $controller = new ProductsController($database);

        switch ($method) {
            case 'GET':
                if ($resourceId) {
                    $controller->show($resourceId);
                } else {
                    $controller->index();
                }
                break;

            case 'POST':
                $controller->store();
                break;

            case 'PUT':
                if (!$resourceId) {
                    ApiResponse::error('Product ID is required for update', 400, 'MISSING_ID');
                }
                $controller->update($resourceId);
                break;

            case 'DELETE':
                if (!$resourceId) {
                    ApiResponse::error('Product ID is required for delete', 400, 'MISSING_ID');
                }
                $controller->destroy($resourceId);
                break;

            default:
                ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
        }
        break;


    case 'slides':
        require_once 'controllers/SlidesController.php';
        $controller = new SlidesController($database);

        switch ($method) {
            case 'GET':
                if ($resourceId) {
                    $controller->show($resourceId);
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                if (isset($_GET['action']) && $_GET['action'] === 'reorder') {
                    $controller->reorder();
                } else {
                    $controller->store();
                }
                break;
            case 'PUT':
                if ($resourceId) {
                    $controller->update($resourceId);
                } else {
                    ApiResponse::error('Slide ID is required for update', 400);
                }
                break;
            case 'PATCH':
                if ($resourceId && isset($_GET['action']) && $_GET['action'] === 'sort-order') {
                    $controller->updateSortOrder($resourceId);
                } else {
                    ApiResponse::error('Invalid PATCH request', 400);
                }
                break;
            case 'DELETE':
                if ($resourceId) {
                    $controller->destroy($resourceId);
                } else {
                    ApiResponse::error('Slide ID is required for deletion', 400);
                }
                break;
            default:
                ApiResponse::error('Method not allowed', 405);
        }
        break;

    case 'pictures':
        require_once 'controllers/PicturesController.php';
        $controller = new PicturesController($database);

        switch ($method) {
            case 'GET':
                // Handle special GET endpoints
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'structured':
                            $controller->structured();
                            break;
                        case 'categories':
                            $controller->categories();
                            break;
                        case 'subcategories':
                            if (isset($_GET['category'])) {
                                $controller->subcategories($_GET['category']);
                            } else {
                                ApiResponse::error('Category parameter is required for subcategories', 400);
                            }
                            break;
                        default:
                            ApiResponse::error('Invalid action for GET request', 400);
                    }
                } else {
                    // Regular GET requests
                    if ($resourceId) {
                        $controller->show($resourceId);
                    } else {
                        $controller->index();
                    }
                }
                break;
            case 'POST':
                if (isset($_GET['action']) && $_GET['action'] === 'bulk') {
                    $controller->bulkInsert();
                } else {
                    $controller->store();
                }
                break;
            case 'PUT':
                if ($resourceId) {
                    $controller->update($resourceId);
                } else {
                    ApiResponse::error('Picture ID is required for update', 400);
                }
                break;
            case 'DELETE':
                if ($resourceId) {
                    $controller->destroy($resourceId);
                } else {
                    ApiResponse::error('Picture ID is required for deletion', 400);
                }
                break;
            default:
                ApiResponse::error('Method not allowed', 405);
        }
        break;

    case 'upload':
        require_once 'controllers/FileController.php';
        $controller = new FileController($database);

        if ($resourceId === 'images') {
            switch ($method) {
                case 'POST':
                    if (!empty($_FILES['images'])) {
                        $controller->uploadMultiple(); // handle multiple
                    } elseif (!empty($_FILES['image'])) {
                        $controller->uploadSingle();   // handle single
                    } else {
                        ApiResponse::error('No file uploaded', 400, 'NO_FILE_UPLOADED');
                    }
                    break;

                case 'DELETE':
                    if (isset($_GET['filename'])) {
                        $controller->deleteImage($_GET['filename']);
                    } else {
                        ApiResponse::error('Filename is required', 400, 'MISSING_FILENAME');
                    }
                    break;

                default:
                    ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
            }
        } else {
            ApiResponse::error('Invalid upload endpoint', 404, 'INVALID_UPLOAD_ENDPOINT');
        }
        break;



    default:
        ApiResponse::error('Resource not found', 404, 'RESOURCE_NOT_FOUND');
}

