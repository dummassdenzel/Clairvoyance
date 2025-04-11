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
                "email" => $user['email'],
                'exp' => time() + (60 * 60 * 24)
            ];

            $token = $jwt->encode($payload);
            return $this->sendPayload([
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email']
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
            $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data->username,
                $data->email,
                $hashed_password

            ]);
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function create_kpi($data)
    {
        try {
            $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data->username,
                $data->email,
                $hashed_password

            ]);
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function create_measurement($data)
    {
        try {
            $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO measurements (username, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data->username,
                $data->email,
                $hashed_password

            ]);
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function create_category($data)
    {
        try {
            $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO categories (username, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data->username,
                $data->email,
                $hashed_password

            ]);
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }
    
}





