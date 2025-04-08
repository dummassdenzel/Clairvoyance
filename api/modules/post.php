<?php

require_once 'global.php';

class Post extends GlobalMethods
{

    private $pdo;

    public function __construct(\PDO $pdo)
    {
        parent::__construct();
        $this->pdo = $pdo;
    }

    public function getRequestData()
    {
        return $this->encryption->processRequestData();
    }

    public function userLogin($data)
    {
        try {
            // CHECK IF USER EXISTS
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$data->email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($data->password, $user['password_hash'])) {
                return $this->sendPayload(null, "failed", "Invalid email or password", 401);
            }

            // GENERATE JWT TOKEN
            $jwt = new Jwt();
            $payload = [
                "id" => $user['id'],
                "firstName" => $user['first_name'],
                "lastName" => $user['last_name'],
                "email" => $user['email'],
                "role" => $user['role'],
                'exp' => time() + (60 * 60 * 24)
            ];

            $token = $jwt->encode($payload);
            return $this->sendPayload([
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ]
            ], "success", "Login successful", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function addUser($data)
    {
        try {
            $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (first_name, last_name, email, role, password_hash) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data->first_name,
                $data->last_name,
                $data->email,
                $data->role,
                $hashed_password

            ]);
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function createOrder($data)
    {
        try {
            $this->pdo->beginTransaction();

            // Insert the main order
            $sql = "INSERT INTO orders (
                customer_name, 
                customer_email, 
                customer_phone, 
                shipping_address, 
                total_amount
            ) VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data->customer_name,
                $data->customer_email,
                $data->customer_phone,
                $data->shipping_address,
                $data->total_amount
            ]);

            $orderId = $this->pdo->lastInsertId();

            // Insert order items
            $sqlItems = "INSERT INTO orderitems (
                order_id,
                product_id,
                quantity,
                price
            ) VALUES (?, ?, ?, ?)";

            $stmtItems = $this->pdo->prepare($sqlItems);

            foreach ($data->order_items as $item) {
                $stmtItems->execute([
                    $orderId,
                    $item->product_id,
                    $item->quantity,
                    $item->price
                ]);
            }

            $this->pdo->commit();

            // Fetch the created order with its items
            $order = $this->getOrderWithItems($orderId);

            return $this->sendPayload($order, "success", "Order created successfully", 200);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    private function getOrderWithItems($orderId)
    {
        // Get order details
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get order items
        $sqlItems = "SELECT * FROM orderitems WHERE order_id = ?";
        $stmtItems = $this->pdo->prepare($sqlItems);
        $stmtItems->execute([$orderId]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $order['order_items'] = $orderItems;
        return $order;
    }

    public function uploadPaymentProof($data, $files)
    {
        try {
            if (!isset($files['payment_proof'])) {
                return $this->sendPayload(null, "failed", "No file uploaded", 400);
            }

            $file = $files['payment_proof'];
            $orderId = $data['order_id'];

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                return $this->sendPayload(null, "failed", "Invalid file type. Only JPEG, PNG and GIF are allowed", 400);
            }

            // Validate file size (7MB limit)
            $maxSize = 7 * 1024 * 1024; // 7MB in bytes
            if ($file['size'] > $maxSize) {
                return $this->sendPayload(null, "failed", "File is too large. Maximum size is 7MB", 400);
            }

            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/payments/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return $this->sendPayload(null, "failed", "Failed to save file", 500);
            }

            // Save payment proof record in database
            $sql = "INSERT INTO paymentproofs (order_id, proof_image_url, uploaded_at) VALUES (?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId, $filename]);

            // Update order status
            $sqlUpdate = "UPDATE orders SET order_status = 'PENDING_VERIFICATION' WHERE id = ?";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$orderId]);

            return $this->sendPayload(
                ['file_path' => $filename],
                "success",
                "Payment proof uploaded successfully",
                200
            );
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function uploadProductImage($data, $files)
    {
        try {
            if (!isset($files['product_image'])) {
                return $this->sendPayload(null, "failed", "No file uploaded", 400);
            }

            $file = $files['product_image'];
            $productId = $data['product_id'];

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                return $this->sendPayload(null, "failed", "Invalid file type. Only JPEG, PNG and WEBP are allowed", 400);
            }

            // Validate file size (2MB limit for product images)
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                return $this->sendPayload(null, "failed", "File is too large. Maximum size is 2MB", 400);
            }

            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/products/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . $productId . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return $this->sendPayload(null, "failed", "Failed to save file", 500);
            }

            // Update product image URL in database
            $sql = "UPDATE products SET image_url = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$filename, $productId]);

            return $this->sendPayload(
                ['file_path' => $filename],
                "success",
                "Product image uploaded successfully",
                200
            );
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

}





