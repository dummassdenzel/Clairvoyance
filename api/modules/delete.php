<?php

require_once 'global.php';

class Delete extends GlobalMethods
{

    private $pdo;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function delete_kpi($id)
    {
        try {
            $sql = "DELETE FROM kpis WHERE id = ?";
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(
                [
                    $id
                ]
            );
            $this->pdo->commit();
            return $this->sendPayload(null, "success", "Successfully deleted record", 200);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $errmsg = $e->getMessage();
            $code = 400;
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }
    }

    public function delete_measurement($id)
    {
        try {
            $sql = "DELETE FROM measurements WHERE id = ?";
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(
                [
                    $id
                ]
            );
            $this->pdo->commit();
            return $this->sendPayload(null, "success", "Successfully deleted record", 200);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $errmsg = $e->getMessage();
            $code = 400;
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }
    }

    public function delete_category($id)
    {
        try {
            $sql = "DELETE FROM category WHERE id = ?";
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(
                [
                    $id
                ]
            );
            $this->pdo->commit();
            return $this->sendPayload(null, "success", "Successfully deleted record", 200);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $errmsg = $e->getMessage();
            $code = 400;
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }
    }

}