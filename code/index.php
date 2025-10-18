<?php
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if ($uri == '/' || $uri == '/index.php') {
    echo json_encode([
        'message' => 'Welcome to the Project 1 REST API!',
        'endpoints' => [
            'GET /users' => 'List all users',
            'GET /users/{id}' => 'Get user by ID',
            'POST /users' => 'Create a new user',
            'PUT /users/{id}' => 'Update a user',
            'DELETE /users/{id}' => 'Delete a user (secure)',
            'POST /users/login' => 'Authenticate user and get token',
            'GET /users/me' => 'Get current authenticated user',
            'PUT /users/me/password' => 'Change password (secure)',
            'GET /users/{id}/friends' => 'Get user’s friends',
            'POST /users/{id}/friends' => 'Add a friend (secure)'
        ]
    ]);
    exit;
}

require_once 'users.php';
?>
