<?php
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:4200',
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/*API Endpoint Router*/

require_once "./modules/get.php";
require_once "./modules/post.php";
require_once "./modules/delete.php";
require_once "./config/database.php";
require_once "./vendor/autoload.php";
require_once "./src/Jwt.php";
require_once "./src/AuthMiddleware.php";

// INITIALIZE ESSENTIAL OBJECTS
$con = new Connection();
$pdo = $con->connect();
$get = new Get($pdo);
$post = new Post($pdo);
$put = new Put($pdo);
$delete = new Delete($pdo);
$auth = new AuthMiddleware();


// Check if 'request' parameter is set in the request
if (isset($_REQUEST['request'])) {
    // Split the request into an array based on '/'
    $request = explode('/', $_REQUEST['request']);
} else {
    // If 'request' parameter is not set, return a 404 response
    echo "Not Found";
    http_response_code(404);
}

// THIS IS THE MAIN SWITCH STATEMENT
switch ($_SERVER['REQUEST_METHOD']) {
    case 'OPTIONS':
        http_response_code(200);
        exit();

    case 'GET':
        switch ($request[0]) {

            case 'kpis':
            if (count($request) > 1) {
                echo json_encode($get->get_kpis($request[1]));
            } else {
                echo json_encode($get->get_kpis());
            }
            break;

            case 'measurements':
                if (count($request) > 1) {
                    echo json_encode($get->get_kpis($request[1]));
                } else {
                    echo "Invalid KPI ID.";
                }
                break;


            case 'categories':
                echo json_encode($get->get_categories());
            break;

            default:
                // RESPONSE FOR UNSUPPORTED REQUESTS
                echo "No Such Request";
                http_response_code(403);
                break;
        }
        break;


    case 'POST':
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $data = $post->getRequestData();
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
            $files = $_FILES;
        } else {
            echo "Unsupported Content Type";
            http_response_code(415);
            exit();
        }
        switch ($request[0]) {


            case 'kpis':
                echo json_encode($post->create_kpi($data));
                break;

            case 'measurements':
                echo json_encode($post->create_measurement($data));
                break;

            case 'categories':
                    echo json_encode($post->create_category($data));
                    break;

            case 'register':
                echo json_encode($post->addUser($data));
                break;

            case 'login':
                echo json_encode($post->userLogin($data));
                break;

            default:
                // RESPONSE FOR UNSUPPORTED REQUESTS
                echo "No Such Request";
                http_response_code(403);
                break;
        }
        break;

        case 'PUT':
            switch ($request[0]) {
    
                case 'kpis':
                    if (count($request) > 1) {
                        echo json_encode($put->update_kpi($request[1]));
                    } else {
                        echo "Invalid KPI ID.";
                    }
                    break;

                    case 'measurements':
                        if (count($request) > 1) {
                            echo json_encode($put->update_measurement($request[1]));
                        } else {
                            echo "Invalid KPI ID.";
                        }
                        break;
    
                default:
                    // Return a 403 response for unsupported requests
                    echo "No Such Request";
                    http_response_code(403);
                    break;
            }
            break;

    case 'DELETE':
        switch ($request[0]) {

            case 'kpis':
                    if (count($request) > 1) {
                        echo json_encode($delete->delete_kpi($request[1]));
                    } else {
                        echo "Invalid KPI ID.";
                    }
                    break;

                case 'measurements':
                        if (count($request) > 1) {
                            echo json_encode($delete->delete_measurement($request[1]));
                        } else {
                            echo "Invalid KPI ID.";
                        }
                        break;

                case 'categories':
                        if (count($request) > 1) {
                            echo json_encode($delete->delete_category($request[1]));
                        } else {
                            echo "Invalid KPI ID.";
                        }
                        break;

            default:
                // Return a 403 response for unsupported requests
                echo "No Such Request";
                http_response_code(403);
                break;
        }
        break;

    default:
        // Return a 404 response for unsupported HTTP methods
        echo "Unsupported HTTP method";
        http_response_code(404);
        break;
}

