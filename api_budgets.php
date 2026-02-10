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
        $month = $_GET['month'] ?? date('Y-m');

        $stmt = $conn->prepare("SELECT * FROM budgets WHERE user_id = ? AND month = ?");
        $stmt->bind_param("is", $user_id, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        $budgets = [];
        while ($row = $result->fetch_assoc()) {
            $budgets[] = $row;
        }

        $stmt->close();
        send_json(['success' => true, 'data' => $budgets]);
        break;

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) send_json(['success' => false, 'error' => 'Invalid JSON input'], 400);

        $category = $data['category'] ?? '';
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
        $month = $data['month'] ?? date('Y-m');

        if (empty($category) || $amount <= 0) {
            send_json(['success' => false, 'error' => 'Invalid input'], 400);
        }

        // Check if budget already exists for this category and month
        $stmt = $conn->prepare("SELECT budget_id FROM budgets WHERE user_id = ? AND category = ? AND month = ?");
        $stmt->bind_param("iss", $user_id, $category, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing budget
            $row = $result->fetch_assoc();
            $budget_id = $row['budget_id'];

            $stmt = $conn->prepare("UPDATE budgets SET amount = ? WHERE budget_id = ?");
            $stmt->bind_param("di", $amount, $budget_id);

            if ($stmt->execute()) {
                $stmt->close();
                send_json(['success' => true, 'budget_id' => $budget_id, 'message' => 'Budget updated successfully']);
            } else {
                $stmt->close();
                send_json(['success' => false, 'error' => 'Failed to update budget'], 500);
            }
        } else {
            // Create new budget
            $stmt = $conn->prepare("INSERT INTO budgets (user_id, category, amount, month) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $user_id, $category, $amount, $month);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $stmt->close();
                send_json(['success' => true, 'budget_id' => $new_id, 'message' => 'Budget created successfully']);
            } else {
                $stmt->close();
                send_json(['success' => false, 'error' => 'Failed to create budget'], 500);
            }
        }
        break;

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) send_json(['success' => false, 'error' => 'Invalid JSON input'], 400);

        $budget_id = isset($data['budget_id']) ? (int)$data['budget_id'] : 0;
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;

        if ($budget_id <= 0 || $amount <= 0) {
            send_json(['success' => false, 'error' => 'Invalid input'], 400);
        }

        $stmt = $conn->prepare("UPDATE budgets SET amount = ? WHERE budget_id = ? AND user_id = ?");
        $stmt->bind_param("dii", $amount, $budget_id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            send_json(['success' => true, 'message' => 'Budget updated successfully']);
        } else {
            $stmt->close();
            send_json(['success' => false, 'error' => 'Budget not found or no changes made'], 404);
        }
        break;

    case 'delete':
        $budget_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($budget_id <= 0) send_json(['success' => false, 'error' => 'Invalid budget ID'], 400);

        $stmt = $conn->prepare("DELETE FROM budgets WHERE budget_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $budget_id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            send_json(['success' => true, 'message' => 'Budget deleted successfully']);
        } else {
            $stmt->close();
            send_json(['success' => false, 'error' => 'Budget not found'], 404);
        }
        break;

    case 'get_spending':
        $month = $_GET['month'] ?? date('Y-m');

        $stmt = $conn->prepare("
            SELECT category, SUM(amount) as spent
            FROM transactions
            WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
            GROUP BY category
        ");
        $stmt->bind_param("is", $user_id, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        $spending = [];
        while ($row = $result->fetch_assoc()) {
            $spending[$row['category']] = (float)$row['spent'];
        }

        $stmt->close();
        send_json(['success' => true, 'data' => $spending]);
        break;

    default:
        send_json(['success' => false, 'error' => 'Invalid action'], 400);
        break;
}

$conn->close();
?>
