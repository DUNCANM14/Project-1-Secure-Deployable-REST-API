<?php
require_once 'config.php';
header("Content-Type: application/json");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents("php://input"), true);

function authCheck() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) return false;
    $token = trim(str_replace('Bearer','',$headers['Authorization']));
    return $token === 'mysecrettoken123';
}

// ---------------- GET ----------------
if ($method === 'GET') {

    // /users/me
    if (isset($parts[1]) && $parts[1] === 'me') {
        if (!authCheck()) {
            http_response_code(401);
            echo json_encode(["error"=>"Unauthorized"]);
            exit;
        }
        echo json_encode(["id"=>1,"username"=>"demo","email"=>"demo@example.com"]);
        exit;
    }

    // /users/{id}/friends
    if (isset($parts[2]) && $parts[2] === 'friends') {
        $id = intval($parts[1]);
        $sql = "SELECT u.id, u.username, u.email FROM friends f 
                JOIN users u ON f.friend_id = u.id WHERE f.user_id = $id";
        $q = $conn->query($sql);
        $friends = [];
        while($row = $q->fetch_assoc()) $friends[] = $row;
        echo json_encode($friends);
        exit;
    }

    // /users/{id}
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $id = intval($parts[1]);
        $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_assoc() ?: ["error"=>"User not found"]);
        $stmt->close();
        exit;
    }

    // /users
    if (!isset($parts[1])) {
        $rows = $conn->query("SELECT id, username, email, created_at FROM users");
        $data = [];
        while($r = $rows->fetch_assoc()) $data[] = $r;
        echo json_encode($data);
        exit;
    }
}

// ---------------- POST ----------------
if ($method === 'POST') {

    // /users/login
    if (isset($parts[1]) && $parts[1] === 'login') {
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $u = $res->fetch_assoc();
            if (password_verify($password,$u['password'])) {
                echo json_encode(["token"=>"mysecrettoken123"]);
            } else {
                http_response_code(401);
                echo json_encode(["error"=>"Invalid password"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["error"=>"User not found"]);
        }
        $stmt->close();
        exit;
    }

    // /users/{id}/friends
    if (isset($parts[2]) && $parts[2] === 'friends') {
        if (!authCheck()) {
            http_response_code(401);
            echo json_encode(["error"=>"Unauthorized"]);
            exit;
        }
        $uid = intval($parts[1]);
        $fid = intval($body['friend_id']);
        $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?,?)");
        $stmt->bind_param("ii",$uid,$fid);
        if ($stmt->execute()) echo json_encode(["message"=>"Friend added"]);
        else echo json_encode(["error"=>$stmt->error]);
        $stmt->close();
        exit;
    }

    // /users
    $u = $body['username'] ?? '';
    $p = password_hash($body['password'] ?? '', PASSWORD_BCRYPT);
    $e = $body['email'] ?? '';
    $stmt = $conn->prepare("INSERT INTO users (username,password,email) VALUES (?,?,?)");
    $stmt->bind_param("sss",$u,$p,$e);
    if ($stmt->execute()) echo json_encode(["message"=>"User created"]);
    else echo json_encode(["error"=>$stmt->error]);
    $stmt->close();
    exit;
}

// ---------------- PUT ----------------
if ($method === 'PUT') {

    // /users/me/password
    if (isset($parts[1]) && $parts[1] === 'me' && isset($parts[2]) && $parts[2]==='password') {
        if (!authCheck()) {
            http_response_code(401);
            echo json_encode(["error"=>"Unauthorized"]);
            exit;
        }
        $newpass = password_hash($body['new_password'] ?? '', PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=1");
        $stmt->bind_param("s",$newpass);
        if ($stmt->execute()) echo json_encode(["message"=>"Password updated"]);
        else echo json_encode(["error"=>$stmt->error]);
        $stmt->close();
        exit;
    }

    // /users/{id}
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $id = intval($parts[1]);
        $u = $body['username'] ?? '';
        $e = $body['email'] ?? '';
        $stmt = $conn->prepare("UPDATE users SET username=?,email=? WHERE id=?");
        $stmt->bind_param("ssi",$u,$e,$id);
        if ($stmt->execute()) echo json_encode(["message"=>"User updated"]);
        else echo json_encode(["error"=>$stmt->error]);
        $stmt->close();
        exit;
    }
}

// ---------------- DELETE ----------------
if ($method === 'DELETE') {

    if (isset($parts[1]) && is_numeric($parts[1])) {
        if (!authCheck()) {
            http_response_code(401);
            echo json_encode(["error"=>"Unauthorized"]);
            exit;
        }
        $id = intval($parts[1]);
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i",$id);
        if ($stmt->execute()) echo json_encode(["message"=>"User deleted"]);
        else echo json_encode(["error"=>$stmt->error]);
        $stmt->close();
        exit;
    }
}

// ---------------- Default ----------------
http_response_code(405);
echo json_encode(["error"=>"Method not allowed"]);
?>
