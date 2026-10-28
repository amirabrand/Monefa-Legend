<?php
$servername = "localhost";
$username = "testhamid_qqww1";
$password = "AATODBqiwz";
$dbname = "testhamid_qqww1";

// Improved error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'];

        if ($action == 'add_message') {
            $message = $conn->real_escape_string($_POST['message']);
            $messageType = $_POST['message_type'];
            $expire_date = ($messageType == 'temporary') ? date('Y-m-d H:i:s', strtotime('+2 weeks')) : NULL;
            $stmt = $conn->prepare("INSERT INTO messages (message, expire_date) VALUES (?, ?)");
            $stmt->bind_param("ss", $message, $expire_date);
            $stmt->execute();
            $stmt->close();
        } elseif ($action == 'edit_message') {
            $messageId = intval($_POST['message_id']);
            $newMessage = $conn->real_escape_string($_POST['message']);
            $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ?");
            $stmt->bind_param("si", $newMessage, $messageId);
            $stmt->execute();
            $stmt->close();
        } /*elseif ($action == 'delete_message') {
            $messageId = intval($_POST['message_id']);
            $userId = intval($_POST['user_id']);
            if ($userId == 0) { // Admin deletion
                $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->bind_param("i", $messageId);
            } else { // User deletion
                $stmt = $conn->prepare("INSERT INTO user_deleted_messages (user_id, message_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $userId, $messageId);
            }
            $stmt->execute();
            $stmt->close();
        }*/
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $action = $_GET['action'];

        if ($action == 'get_messages') {
            $userId = intval($_GET['user_id']);
            $messages = [];

            $sql = "SELECT id, message FROM messages WHERE (expire_date IS NULL OR expire_date > NOW()) AND id NOT IN (SELECT message_id FROM user_deleted_messages WHERE user_id = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            $stmt->close();
            echo json_encode($messages);
        } elseif ($action == 'count_messages') {
            $userId = intval($_GET['user_id']);
            $sql = "SELECT COUNT(*) AS count FROM messages WHERE (expire_date IS NULL OR expire_date > NOW()) AND id NOT IN (SELECT message_id FROM user_deleted_messages WHERE user_id = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();
            echo json_encode(['count' => $count]);
        }
    }

    $conn->close();
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
