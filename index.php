<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'configs/db.config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_settings':
        $sql = "SELECT setting_value FROM " . DB_PREFIX . "settings WHERE setting_key = 'secret_code'";
        $result = $conn->query($sql);
        $setting = $result->fetch_assoc();
        echo json_encode(["status" => "success", "secret_code" => $setting['setting_value'] ?? '11+08+1993']);
        break;

    case 'update_settings':
        $data = json_decode(file_get_contents("php://input"));
        if(!empty($data->secret_code)) {
            $sql = "UPDATE " . DB_PREFIX . "settings SET setting_value = ? WHERE setting_key = 'secret_code'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $data->secret_code);
            if($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Secret code updated successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update secret code"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Incomplete data"]);
        }
        break;

    case 'get_transactions':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        $sql = "SELECT * FROM " . DB_PREFIX . "transactions WHERE date BETWEEN ? AND ? ORDER BY date DESC, created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        // Get balance summary
        $sqlSum = "SELECT 
                    SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credit,
                    SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debit
                   FROM " . DB_PREFIX . "transactions";
        $sumResult = $conn->query($sqlSum);
        $summary = $sumResult->fetch_assoc();
        
        echo json_encode([
            "status" => "success",
            "data" => $transactions,
            "summary" => [
                "total_credit" => $summary['total_credit'] ?? 0,
                "total_debit" => $summary['total_debit'] ?? 0,
                "balance" => ($summary['total_credit'] ?? 0) - ($summary['total_debit'] ?? 0)
            ]
        ]);
        break;

    case 'add_transaction':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->title) && !empty($data->amount) && !empty($data->type) && !empty($data->date)) {
            $sql = "INSERT INTO " . DB_PREFIX . "transactions (title, amount, type, date, category) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsss", $data->title, $data->amount, $data->type, $data->date, $data->category);
            
            if($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Transaction added successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to add transaction"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Incomplete data"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        break;
}

$conn->close();
?>
