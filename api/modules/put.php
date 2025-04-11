<?php

require_once 'global.php';

class Put extends GlobalMethods
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

    
    public function update_kpi($id)
    {
        try {
            $sql = "";
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(
                [
                    $id
                ]
            );
            $this->pdo->commit();
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

    public function update_measurement($id)
    {
        try {
            $sql = "";
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(
                [
                    $id
                ]
            );
            $this->pdo->commit();
            return $this->sendPayload(null, "success", "Successfully created a new record", 200);
        } catch (PDOException $e) {
            return $this->sendPayload(null, "failed", $e->getMessage(), 400);
        }
    }

}





