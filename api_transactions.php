<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

function send_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

switch ($action) {

    case 'get_all':
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC, created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        $stmt->close();
        send_json(['success' => true, 'data' => $transactions]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) send_json(['success' => false, 'error' => 'Invalid JSON input'], 400);

        $type = $data['type'] ?? '';
        $category = $data['category'] ?? '';
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
        $note = $data['note'] ?? '';
        $date = $data['date'] ?? date('Y-m-d');

        if (empty($type) || empty($category) || $amount <= 0) {
            send_json(['success' => false, 'error' => 'Invalid input'], 400);
        }

        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, note, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", $user_id, $type, $category, $amount, $note, $date);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $stmt->close();
            send_json(['success' => true, 'transaction_id' => $new_id, 'message' => 'Transaction added successfully']);
        } else {
            $stmt->close();
            send_json(['success' => false, 'error' => 'Failed to create transaction'], 500);
        }
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) send_json(['success' => false, 'error' => 'Invalid JSON input'], 400);

        $transaction_id = isset($data['transaction_id']) ? (int)$data['transaction_id'] : 0;
        $type = $data['type'] ?? '';
        $category = $data['category'] ?? '';
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
        $note = $data['note'] ?? '';
        $date = $data['date'] ?? date('Y-m-d');

        if ($transaction_id <= 0 || empty($type) || empty($category) || $amount <= 0) {
            send_json(['success' => false, 'error' => 'Invalid input'], 400);
        }

        $stmt = $conn->prepare("UPDATE transactions SET type = ?, category = ?, amount = ?, note = ?, transaction_date = ? WHERE transaction_id = ? AND user_id = ?");
        $stmt->bind_param("ssdssii", $type, $category, $amount, $note, $date, $transaction_id, $user_id);

        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            send_json(['success' => true, 'message' => 'Transaction updated successfully']);
        } else {
            $stmt->close();
            send_json(['success' => false, 'error' => 'Transaction not found or no changes made'], 404);
        }
        break;

    case 'delete':
        $transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($transaction_id <= 0) send_json(['success' => false, 'error' => 'Invalid transaction ID'], 400);

        $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $transaction_id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            send_json(['success' => true, 'message' => 'Transaction deleted successfully']);
        } else {
            $stmt->close();
            send_json(['success' => false, 'error' => 'Transaction not found'], 404);
        }
        break;

    case 'get_summary':
        $month = $_GET['month'] ?? date('Y-m');

        $stmt = $conn->prepare("
            SELECT type, SUM(amount) as total
            FROM transactions
            WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
            GROUP BY type
        ");
        $stmt->bind_param("is", $user_id, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        $summary = ['income' => 0, 'expense' => 0];
        while ($row = $result->fetch_assoc()) {
            $summary[$row['type']] = (float)$row['total'];
        }
        $summary['balance'] = $summary['income'] - $summary['expense'];

        $stmt->close();
        send_json(['success' => true, 'data' => $summary]);
        break;

    default:
        send_json(['success' => false, 'error' => 'Invalid action'], 400);
        break;
}

$conn->close();
?>
