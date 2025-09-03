<?php
// Simple PHP router + Products API (no external framework required)
// Run with: php -S 127.0.0.1:8000 -t public
// Then request http://127.0.0.1:8000/api/products etc.

declare(strict_types=1);

require_once __DIR__ . '/../src/ProductController.php';

use App\ProductController;

// Basic router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove trailing slashes
$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

header('Content-Type: application/json; charset=utf-8');

$controller = new ProductController(__DIR__ . '/../storage/products.json');

// Routes
// POST /api/products
if ($method === 'POST' && $uri === '/api/products') {
    $input = json_decode(file_get_contents('php://input'), true);
    $controller->store($input);
    exit;
}

// GET /api/products/{id}
if ($method === 'GET' && preg_match('#^/api/products/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $controller->show($id);
    exit;
}

// PUT /api/products/{id}
if ($method === 'PUT' && preg_match('#^/api/products/(\d+)$#', $uri, $m)) {
    $id = (int)$m[1];
    $input = json_decode(file_get_contents('php://input'), true);
    $controller->update($id, $input);
    exit;
}

// Not found
http_response_code(404);
echo json_encode(['message' => 'Endpoint not found.']);
exit;
