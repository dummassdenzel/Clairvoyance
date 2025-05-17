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

    // ✅ NEW: Download KPI File by ID (serves the file directly)
    public function download_kpi_file($kpi_id)
    {
        try {
            $sql = "SELECT file_path FROM kpis WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$kpi_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || !$result['file_path']) {
                return $this->sendPayload(null, 'failed', "No file associated with this KPI.", 404);
            }

            $filePath = $result['file_path'];

            if (!file_exists($filePath)) {
                return $this->sendPayload(null, 'failed', "File not found on server.", 404);
            }

            // Output headers for direct file download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }

    public function download_kpis_as_csv()
    {
        try {
            $kpis = $this->get_records('kpis')['data'];
            $filePath = __DIR__ . '/../../uploads/kpis/kpis.csv';
            $file = fopen($filePath, 'w');

            fputcsv($file, ['ID', 'Name', 'Value']);
            foreach ($kpis as $kpi) {
                fputcsv($file, [$kpi['id'], $kpi['name'], $kpi['value']]);
            }
            fclose($file);

            return $this->sendPayload(['file_path' => $filePath], 'success', 'KPI CSV file created successfully.', 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }

    public function download_kpis_as_excel()
    {
        try {
            $kpis = $this->get_records('kpis')['data'];

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Name');
            $sheet->setCellValue('C1', 'Value');

            $row = 2;
            foreach ($kpis as $kpi) {
                $sheet->setCellValue("A$row", $kpi['id']);
                $sheet->setCellValue("B$row", $kpi['name']);
                $sheet->setCellValue("C$row", $kpi['value']);
                $row++;
            }

            $filePath = __DIR__ . '/../../uploads/kpis/kpis.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return $this->sendPayload(['file_path' => $filePath], 'success', 'KPI Excel file created successfully.', 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }

    public function process_uploaded_kpi_file($filePath)
    {
        try {
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            if ($fileExtension === 'csv') {
                $file = fopen($filePath, 'r');
                $data = [];
                while (($row = fgetcsv($file)) !== false) {
                    $data[] = $row;
                }
                fclose($file);
            } else {
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();
            }

            $startRow = ($data[0][0] === 'ID' || $data[0][0] === 'Name') ? 1 : 0;

            for ($i = $startRow; $i < count($data); $i++) {
                $row = $data[$i];
                $this->executeQuery("INSERT INTO kpis (name, value) VALUES (?, ?)", [$row[0], $row[1]]);
            }

            return $this->sendPayload(null, 'success', 'KPI file processed successfully.', 200);
        } catch (\Exception $e) {
            return $this->sendPayload(null, 'failed', $e->getMessage(), 500);
        }
    }
}
