<?php

require_once 'global.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;


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

    public function get_kpis($id = null)
    {
        $condition = null;
        if ($id != null) {
            $condition = "id=$id";
        }
        return $this->get_records('kpis', $condition);
    }

    public function get_categories($id = null)
    {
        $condition = null;
        if ($id != null) {
            $condition = "id=$id";
        }
        return $this->get_records('categories', $condition);
    }

    public function get_kpi_file($kpi_id)
    {
        try {
            // Get the file path from the database
            $sql = "SELECT file_path FROM kpis WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kpi_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || !$result['file_path']) {
                return $this->sendPayload(null, 'failed', "No file associated with this KPI.", 404);
            }

            return $this->sendPayload(['file_path' => $result['file_path']], 'success', "File path retrieved.", 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }

    public function download_kpis_as_csv()
    {
        try {
            // Fetch KPI data from the database
            $kpis = $this->get_records('kpis')['data'];

            // Define the file path
            $filePath = __DIR__ . '/../../uploads/kpis/kpis.csv';

            // Open the file for writing
            $file = fopen($filePath, 'w');

            // Add header row
            fputcsv($file, ['ID', 'Name', 'Value']);

            // Add data rows
            foreach ($kpis as $kpi) {
                fputcsv($file, [$kpi['id'], $kpi['name'], $kpi['value']]);
            }

            // Close the file
            fclose($file);

            return $this->sendPayload(['file_path' => $filePath], 'success', 'KPI CSV file created successfully.', 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }

    public function download_kpis_as_excel()
    {
        try {
            // Fetch KPI data from the database
            $kpis = $this->get_records('kpis')['data'];

            // Define the file path
            $filePath = __DIR__ . '/../../uploads/kpis/kpis.xls';

            // Open the file for writing
            $file = fopen($filePath, 'w');

            // Add header row
            fwrite($file, "ID\tName\tValue\n");

            // Add data rows
            foreach ($kpis as $kpi) {
                fwrite($file, "{$kpi['id']}\t{$kpi['name']}\t{$kpi['value']}\n");
            }

            // Close the file
            fclose($file);

            return $this->sendPayload(['file_path' => $filePath], 'success', 'KPI Excel file created successfully.', 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }

    public function process_uploaded_kpi_file($filePath)
    {
        try {
            // Read the uploaded file (CSV or Excel)
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            if ($fileExtension === 'csv') {
                $file = fopen($filePath, 'r');
                $data = [];
                while (($row = fgetcsv($file)) !== false) {
                    $data[] = $row;
                }
                fclose($file);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();
            }

            // Process the data (e.g., save to database or display)
            foreach ($data as $row) {
                // Example: Save each row to the database
                $this->executeQuery("INSERT INTO kpis (name, value) VALUES (?, ?)", [$row[0], $row[1]]);
            }

            return $this->sendPayload(null, 'success', 'KPI file processed successfully.', 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }
}
