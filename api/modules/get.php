<?php

require_once 'global.php';

class Get extends GlobalMethods
{
    private $pdo;
    public function __construct(\PDO $pdo)
    {
        parent::__construct();
        $this->pdo = $pdo;
    }

    //ESSENTIALS
    private function get_records($table = null, $conditions = null, $columns = '*', $customSqlStr = null, $params = [])
    {
        if ($customSqlStr != null) {
            $sqlStr = $customSqlStr;
        } else {
            $sqlStr = "SELECT $columns FROM $table";
            if ($conditions != null) {
                $sqlStr .= " WHERE " . $conditions;
            }
        }
        $result = $this->executeQuery($sqlStr, $params);

        if ($result['code'] == 200) {
            return $this->sendPayload($result['data'], 'success', "Successfully retrieved data.", $result['code']);
        }
        return $this->sendPayload(null, 'failed', "Failed to retrieve data.", $result['code']);
    }

    private function executeQuery($sql, $params = [])
    {
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            $statement = $this->pdo->prepare($sql);
            if ($statement->execute($params)) {
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $record) {
                    // Handle BLOB data
                    if (isset($record['file_data'])) {
                        $record['file_data'] = base64_encode($record['file_data']);
                    }
                    array_push($data, $record);
                }
                $code = 200;
                return array("code" => $code, "data" => $data);
            } else {
                $errmsg = "No data found.";
                $code = 404;
                return array("code" => $code, "errmsg" => $errmsg);
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }
        return array("code" => $code, "errmsg" => $errmsg);
    }




    //ADMIN: GET USERS
    public function get_users($id = null)
    {
        $condition = null;
        if ($id != null) {
            $condition = "id=$id";
        }
        return $this->get_records('users', $condition);
    }

    public function get_products($id = null)
    {
        try {
            $baseUrl = "http://localhost/mubu/api"; // Adjust this to your API base URL
            $sql = "SELECT p.*, 
                    CONCAT('$baseUrl/uploads/products/', p.image_url) as image_url 
                    FROM products p";

            if ($id != null) {
                $sql .= " WHERE p.id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$id]);
            } else {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->sendPayload($result, "success", "Successfully retrieved products", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function get_orders($id = null)
    {
        try {
            if ($id !== null) {
                $baseUrl = "http://localhost/mubu/api"; // Same base URL as in get_products
                // Get specific order with its items
                $sql = "SELECT o.*, 
                              u.first_name as customer_first_name,
                              u.last_name as customer_last_name
                       FROM orders o
                       LEFT JOIN users u ON o.customer_email = u.email
                       WHERE o.id = ?";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    return $this->sendPayload(null, "failed", "Order not found", 404);
                }

                // Get order items
                $sqlItems = "SELECT oi.*, p.name as product_name, 
                          CONCAT('$baseUrl/uploads/products/', p.image_url) as image_url
                           FROM orderitems oi
                           LEFT JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";

                $stmtItems = $this->pdo->prepare($sqlItems);
                $stmtItems->execute([$id]);
                $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                $order['order_items'] = $orderItems;

                return $this->sendPayload($order, "success", "Successfully retrieved order", 200);
            } else {
                // Get all orders with basic information
                $sql = "SELECT o.*, 
                              u.first_name as customer_first_name,
                              u.last_name as customer_last_name,
                              COUNT(oi.id) as total_items
                       FROM orders o
                       LEFT JOIN users u ON o.customer_email = u.email
                       LEFT JOIN orderitems oi ON o.id = oi.order_id
                       GROUP BY o.id
                       ORDER BY o.created_at DESC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $this->sendPayload($orders, "success", "Successfully retrieved orders", 200);
            }
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function get_receipt($orderId)
    {
        try {
            $baseUrl = "http://localhost/mubu/api";

            // Get order with customer and items details
            $sql = "SELECT o.*, 
                           u.first_name as customer_first_name,
                           u.last_name as customer_last_name,
                           pp.uploaded_at as payment_date
                    FROM orders o
                    LEFT JOIN users u ON o.customer_email = u.email
                    LEFT JOIN paymentproofs pp ON o.id = pp.order_id
                    WHERE o.id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return $this->sendPayload(null, "failed", "Order not found", 404);
            }

            // Get order items
            $sqlItems = "SELECT oi.*, p.name as product_name, 
                        CONCAT('$baseUrl/uploads/products/', p.image_url) as image_url
                        FROM orderitems oi
                        LEFT JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?";

            $stmtItems = $this->pdo->prepare($sqlItems);
            $stmtItems->execute([$orderId]);
            $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            $order['order_items'] = $orderItems;
            $order['receipt_number'] = 'MUBU-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);

            return $this->sendPayload($order, "success", "Successfully retrieved receipt", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

}
