<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../models/Dashboard.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class ExportService
{
    private $pdo;
    
    public function __construct()
    {
        $conn = new Connection();
        $this->pdo = $conn->connect();
    }
    
    /**
     * Export KPI data to Excel
     * 
     * @param int $kpiId KPI ID
     * @param array $timeRange Optional time range filter
     * @return string Path to the generated Excel file
     */
    public function exportKpiToExcel($kpiId, $timeRange = null)
    {
        // Get KPI data
        $kpiModel = new Kpi();
        $kpi = $kpiModel->getById($kpiId);
        
        if (!$kpi) {
            throw new \Exception('KPI not found');
        }
        
        // Prepare query to get measurements
        $sql = "
            SELECT value, timestamp
            FROM measurements
            WHERE kpi_id = :kpi_id
        ";
        
        $params = [':kpi_id' => $kpiId];
        
        // Add time range filter if provided
        if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
            $sql .= " AND timestamp BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $timeRange['start'];
            $params[':end_date'] = $timeRange['end'];
        }
        
        $sql .= " ORDER BY timestamp ASC";
        
        // Execute query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KPI Data');
        
        // Set headers
        $sheet->setCellValue('A1', 'KPI');
        $sheet->setCellValue('B1', 'Value');
        $sheet->setCellValue('C1', 'Timestamp');
        $sheet->setCellValue('D1', 'Unit');
        $sheet->setCellValue('E1', 'Target');
        
        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        // Add data rows
        $row = 2;
        foreach ($measurements as $measurement) {
            $sheet->setCellValue('A' . $row, $kpi['name']);
            $sheet->setCellValue('B' . $row, $measurement['value']);
            $sheet->setCellValue('C' . $row, $measurement['timestamp']);
            $sheet->setCellValue('D' . $row, $kpi['unit'] ?? '');
            $sheet->setCellValue('E' . $row, $kpi['target'] ?? '');
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add a chart if there are measurements
        if (count($measurements) > 0) {
            $dataSeriesLabels = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'KPI Data!$A$1', null, 1)];
            
            // Create X-axis labels (timestamps)
            $xAxisTickValues = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'KPI Data!$C$2:$C$' . ($row - 1), null, count($measurements))];
            
            // Create data series
            $dataSeriesValues = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'KPI Data!$B$2:$B$' . ($row - 1), null, count($measurements))];
            
            // Build the dataseries
            $series = new DataSeries(
                DataSeries::TYPE_LINECHART,
                DataSeries::GROUPING_STANDARD,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );
            
            // Set up the chart
            $plotArea = new PlotArea(null, [$series]);
            $legend = new Legend(Legend::POSITION_RIGHT, null, false);
            $title = new Title($kpi['name'] . ' Trend');
            
            // Create the chart
            $chart = new Chart(
                'chart1',
                $title,
                $legend,
                $plotArea
            );
            
            // Set the position where the chart should appear
            $chart->setTopLeftPosition('A' . ($row + 2));
            $chart->setBottomRightPosition('H' . ($row + 15));
            
            // Add the chart to the worksheet
            $sheet->addChart($chart);
            
            // Add a new sheet for the chart
            $chartSheet = $spreadsheet->createSheet();
            $chartSheet->setTitle('Chart');
            $chartSheet->addChart($chart);
        }
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'kpi_export_');
        $excelFile = $tempFile . '.xlsx';
        rename($tempFile, $excelFile);
        
        // Save to Excel file
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($excelFile);
        
        return $excelFile;
    }
    
    /**
     * Export KPI data to CSV
     * 
     * @param int $kpiId KPI ID
     * @param array $timeRange Optional time range filter
     * @return string Path to the generated CSV file
     */
    public function exportKpiToCsv($kpiId, $timeRange = null)
    {
        // Get KPI data
        $kpiModel = new Kpi();
        $kpi = $kpiModel->getById($kpiId);
        
        if (!$kpi) {
            throw new \Exception('KPI not found');
        }
        
        // Prepare query to get measurements
        $sql = "
            SELECT value, timestamp
            FROM measurements
            WHERE kpi_id = :kpi_id
        ";
        
        $params = [':kpi_id' => $kpiId];
        
        // Add time range filter if provided
        if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
            $sql .= " AND timestamp BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $timeRange['start'];
            $params[':end_date'] = $timeRange['end'];
        }
        
        $sql .= " ORDER BY timestamp ASC";
        
        // Execute query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'KPI');
        $sheet->setCellValue('B1', 'Value');
        $sheet->setCellValue('C1', 'Timestamp');
        $sheet->setCellValue('D1', 'Unit');
        
        // Add data rows
        $row = 2;
        foreach ($measurements as $measurement) {
            $sheet->setCellValue('A' . $row, $kpi['name']);
            $sheet->setCellValue('B' . $row, $measurement['value']);
            $sheet->setCellValue('C' . $row, $measurement['timestamp']);
            $sheet->setCellValue('D' . $row, $kpi['unit'] ?? '');
            $row++;
        }
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'kpi_export_');
        $csvFile = $tempFile . '.csv';
        rename($tempFile, $csvFile);
        
        // Save to CSV file
        $writer = new Csv($spreadsheet);
        $writer->save($csvFile);
        
        return $csvFile;
    }
    
    /**
     * Export KPI data to PDF
     * 
     * @param int $kpiId KPI ID
     * @param array $timeRange Optional time range filter
     * @return string Path to the generated PDF file
     */
    public function exportKpiToPdf($kpiId, $timeRange = null)
    {
        // Get KPI data
        $kpiModel = new Kpi();
        $kpi = $kpiModel->getById($kpiId);
        
        if (!$kpi) {
            throw new \Exception('KPI not found');
        }
        
        // Prepare query to get measurements
        $sql = "
            SELECT value, timestamp
            FROM measurements
            WHERE kpi_id = :kpi_id
        ";
        
        $params = [':kpi_id' => $kpiId];
        
        // Add time range filter if provided
        if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
            $sql .= " AND timestamp BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $timeRange['start'];
            $params[':end_date'] = $timeRange['end'];
        }
        
        $sql .= " ORDER BY timestamp ASC";
        
        // Execute query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Clairvoyance KPI System');
        $pdf->SetAuthor('Clairvoyance');
        $pdf->SetTitle($kpi['name'] . ' Report');
        $pdf->SetSubject('KPI Report');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'Clairvoyance KPI Report', $kpi['name'] . ' - Generated on ' . date('Y-m-d H:i:s'));
        
        // Set header and footer fonts
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Set margins
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Set image scale factor
        $pdf->setImageScale(1.25);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, $kpi['name'] . ' Report', 0, 1, 'C');
        $pdf->Ln(5);
        
        // KPI details
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'KPI Details:', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 7, 'Name:', 0, 0);
        $pdf->Cell(0, 7, $kpi['name'], 0, 1);
        $pdf->Cell(40, 7, 'Unit:', 0, 0);
        $pdf->Cell(0, 7, $kpi['unit'] ?? 'N/A', 0, 1);
        $pdf->Cell(40, 7, 'Target:', 0, 0);
        $pdf->Cell(0, 7, $kpi['target'] ?? 'N/A', 0, 1);
        $pdf->Cell(40, 7, 'Category:', 0, 0);
        $pdf->Cell(0, 7, $kpi['category_name'] ?? 'N/A', 0, 1);
        
        if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
            $pdf->Cell(40, 7, 'Time Range:', 0, 0);
            $pdf->Cell(0, 7, $timeRange['start'] . ' to ' . $timeRange['end'], 0, 1);
        }
        
        $pdf->Ln(5);
        
        // Measurements table
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Measurements:', 0, 1);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(71, 114, 196);
        $pdf->SetTextColor(255);
        $pdf->Cell(80, 7, 'Timestamp', 1, 0, 'C', 1);
        $pdf->Cell(80, 7, 'Value', 1, 0, 'C', 1);
        $pdf->Ln();
        
        // Table data
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0);
        $fill = false;
        
        foreach ($measurements as $measurement) {
            $pdf->SetFillColor(224, 235, 255);
            $pdf->Cell(80, 7, $measurement['timestamp'], 1, 0, 'L', $fill);
            $pdf->Cell(80, 7, $measurement['value'] . ' ' . ($kpi['unit'] ?? ''), 1, 0, 'R', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        
        // Add chart if there are measurements
        if (count($measurements) > 0) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'KPI Trend Chart:', 0, 1);
            
            // Extract data for the chart
            $timestamps = [];
            $values = [];
            foreach ($measurements as $measurement) {
                $timestamps[] = date('M d', strtotime($measurement['timestamp']));
                $values[] = $measurement['value'];
            }
            
            // Create temporary image file for the chart
            $chartFile = $this->createChartImage($kpi['name'], $timestamps, $values, $kpi['target'] ?? null);
            
            if ($chartFile) {
                // Add the chart image to PDF
                $pdf->Image($chartFile, 15, 50, 180, 100, 'PNG');
                
                // Clean up the temporary file
                @unlink($chartFile);
            }
        }
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'kpi_export_');
        $pdfFile = $tempFile . '.pdf';
        rename($tempFile, $pdfFile);
        
        // Save to PDF file
        $pdf->Output($pdfFile, 'F');
        
        return $pdfFile;
    }
    
    /**
     * Create a chart image using GD library
     * 
     * @param string $title Chart title
     * @param array $labels X-axis labels
     * @param array $values Y-axis values
     * @param float $target Optional target line
     * @return string|null Path to the generated image file or null on failure
     */
    private function createChartImage($title, $labels, $values, $target = null)
    {
        if (empty($values) || !function_exists('imagecreate')) {
            return null;
        }
        
        // Chart dimensions
        $width = 800;
        $height = 400;
        $padding = 40;
        
        // Create the image
        $image = imagecreatetruecolor($width, $height);
        
        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 71, 114, 196);
        $red = imagecolorallocate($image, 255, 0, 0);
        $lightGray = imagecolorallocate($image, 230, 230, 230);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw title
        $titleFontSize = 3;
        $titleText = $title . ' Trend';
        $titleBox = imagettfbbox($titleFontSize, 0, 'arial.ttf', $titleText);
        $titleWidth = abs($titleBox[4] - $titleBox[0]);
        $titleX = ($width - $titleWidth) / 2;
        imagestring($image, $titleFontSize, $titleX, 10, $titleText, $black);
        
        // Chart area
        $chartX = $padding * 2;
        $chartY = $padding;
        $chartWidth = $width - $padding * 3;
        $chartHeight = $height - $padding * 2;
        
        // Draw chart border
        imagerectangle($image, $chartX, $chartY, $chartX + $chartWidth, $chartY + $chartHeight, $black);
        
        // Calculate scale
        $maxValue = max($values);
        if ($target !== null && $target > $maxValue) {
            $maxValue = $target;
        }
        $minValue = min($values);
        $valueRange = $maxValue - $minValue;
        if ($valueRange == 0) {
            $valueRange = 1; // Avoid division by zero
        }
        
        // Draw grid lines and Y-axis labels
        $gridLines = 5;
        for ($i = 0; $i <= $gridLines; $i++) {
            $y = $chartY + $chartHeight - ($i * $chartHeight / $gridLines);
            imageline($image, $chartX, $y, $chartX + $chartWidth, $y, $lightGray);
            $labelValue = $minValue + ($i * $valueRange / $gridLines);
            imagestring($image, 2, $chartX - 35, $y - 7, number_format($labelValue, 1), $black);
        }
        
        // Draw X-axis labels
        $labelCount = count($labels);
        $xStep = $chartWidth / ($labelCount > 1 ? $labelCount - 1 : 1);
        for ($i = 0; $i < $labelCount; $i++) {
            $x = $chartX + ($i * $xStep);
            $y = $chartY + $chartHeight + 10;
            imagestring($image, 2, $x - 15, $y, $labels[$i], $black);
        }
        
        // Draw data points and lines
        $points = [];
        for ($i = 0; $i < $labelCount; $i++) {
            $x = $chartX + ($i * $xStep);
            $y = $chartY + $chartHeight - (($values[$i] - $minValue) / $valueRange * $chartHeight);
            $points[] = ['x' => $x, 'y' => $y];
            imagefilledellipse($image, $x, $y, 6, 6, $blue);
        }
        
        // Connect points with lines
        for ($i = 0; $i < count($points) - 1; $i++) {
            imageline(
                $image,
                $points[$i]['x'],
                $points[$i]['y'],
                $points[$i + 1]['x'],
                $points[$i + 1]['y'],
                $blue
            );
        }
        
        // Draw target line if provided
        if ($target !== null) {
            $targetY = $chartY + $chartHeight - (($target - $minValue) / $valueRange * $chartHeight);
            imageline($image, $chartX, $targetY, $chartX + $chartWidth, $targetY, $red);
            imagestring($image, 2, $chartX + $chartWidth + 5, $targetY - 7, 'Target: ' . $target, $red);
        }
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_');
        $chartFile = $tempFile . '.png';
        rename($tempFile, $chartFile);
        
        // Save image to file
        imagepng($image, $chartFile);
        imagedestroy($image);
        
        return $chartFile;
    }
    
    /**
     * Export KPI data to JSON
     * 
     * @param int $kpiId KPI ID
     * @param array $timeRange Optional time range filter
     * @return string Path to the generated JSON file
     */
    public function exportKpiToJson($kpiId, $timeRange = null)
    {
        // Get KPI data
        $kpiModel = new Kpi();
        $kpi = $kpiModel->getById($kpiId);
        
        if (!$kpi) {
            throw new Exception('KPI not found');
        }
        
        // Prepare query to get measurements
        $sql = "
            SELECT value, timestamp
            FROM measurements
            WHERE kpi_id = :kpi_id
        ";
        
        $params = [':kpi_id' => $kpiId];
        
        // Add time range filter if provided
        if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
            $sql .= " AND timestamp BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $timeRange['start'];
            $params[':end_date'] = $timeRange['end'];
        }
        
        $sql .= " ORDER BY timestamp ASC";
        
        // Execute query
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare export data
        $exportData = [
            'kpi' => [
                'id' => $kpi['id'],
                'name' => $kpi['name'],
                'unit' => $kpi['unit'] ?? '',
                'target' => $kpi['target'] ?? null,
                'category' => $kpi['category_name'] ?? null
            ],
            'metadata' => [
                'exported_at' => date('Y-m-d H:i:s'),
                'count' => count($measurements),
                'time_range' => $timeRange ?? 'all'
            ],
            'measurements' => $measurements
        ];
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'kpi_export_');
        $jsonFile = $tempFile . '.json';
        rename($tempFile, $jsonFile);
        
        // Write data to JSON file
        file_put_contents($jsonFile, json_encode($exportData, JSON_PRETTY_PRINT));
        
        return $jsonFile;
    }
    
    /**
     * Export dashboard data to CSV
     * 
     * @param int $dashboardId Dashboard ID
     * @param array $timeRange Optional time range filter
     * @return string Path to the generated CSV file
     */
    public function exportDashboardToCsv($dashboardId, $timeRange = null)
    {
        // Get dashboard data
        $dashboardModel = new Dashboard();
        $dashboard = $dashboardModel->getById($dashboardId);
        
        if (!$dashboard) {
            throw new Exception('Dashboard not found');
        }
        
        // Get widgets for the dashboard
        $sql = "
            SELECT w.id, w.title, w.kpi_id, k.name as kpi_name, k.unit
            FROM widgets w
            JOIN kpis k ON w.kpi_id = k.id
            WHERE w.dashboard_id = :dashboard_id
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':dashboard_id', $dashboardId, PDO::PARAM_INT);
        $stmt->execute();
        $widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'dashboard_export_');
        $csvFile = $tempFile . '.csv';
        rename($tempFile, $csvFile);
        
        // Write data to CSV
        $handle = fopen($csvFile, 'w');
        
        // Write header row
        fputcsv($handle, ['Dashboard', 'Widget', 'KPI', 'Value', 'Timestamp', 'Unit']);
        
        // For each widget, get measurements and write to CSV
        foreach ($widgets as $widget) {
            // Get measurements for the KPI
            $sql = "
                SELECT value, timestamp
                FROM measurements
                WHERE kpi_id = :kpi_id
            ";
            
            $params = [':kpi_id' => $widget['kpi_id']];
            
            // Add time range filter if provided
            if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
                $sql .= " AND timestamp BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $timeRange['start'];
                $params[':end_date'] = $timeRange['end'];
            }
            
            $sql .= " ORDER BY timestamp ASC";
            
            // Execute query
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Write data rows
            foreach ($measurements as $measurement) {
                fputcsv($handle, [
                    $dashboard['name'],
                    $widget['title'],
                    $widget['kpi_name'],
                    $measurement['value'],
                    $measurement['timestamp'],
                    $widget['unit'] ?? ''
                ]);
            }
        }
        
        fclose($handle);
        
        return $csvFile;
    }
    
    /**
     * Export dashboard data to JSON
     * 
     * @param int $dashboardId Dashboard ID
     * @param array $timeRange Optional time range filter
     * @return string Path to the generated JSON file
     */
    public function exportDashboardToJson($dashboardId, $timeRange = null)
    {
        // Get dashboard data
        $dashboardModel = new Dashboard();
        $dashboard = $dashboardModel->getById($dashboardId);
        
        if (!$dashboard) {
            throw new Exception('Dashboard not found');
        }
        
        // Get widgets for the dashboard
        $sql = "
            SELECT w.id, w.title, w.kpi_id, k.name as kpi_name, k.unit, k.target
            FROM widgets w
            JOIN kpis k ON w.kpi_id = k.id
            WHERE w.dashboard_id = :dashboard_id
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':dashboard_id', $dashboardId, PDO::PARAM_INT);
        $stmt->execute();
        $widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare export data
        $exportData = [
            'dashboard' => [
                'id' => $dashboard['id'],
                'name' => $dashboard['name'],
                'description' => $dashboard['description'] ?? ''
            ],
            'metadata' => [
                'exported_at' => date('Y-m-d H:i:s'),
                'widget_count' => count($widgets),
                'time_range' => $timeRange ?? 'all'
            ],
            'widgets' => []
        ];
        
        // For each widget, get measurements
        foreach ($widgets as $widget) {
            // Get measurements for the KPI
            $sql = "
                SELECT value, timestamp
                FROM measurements
                WHERE kpi_id = :kpi_id
            ";
            
            $params = [':kpi_id' => $widget['kpi_id']];
            
            // Add time range filter if provided
            if ($timeRange && isset($timeRange['start']) && isset($timeRange['end'])) {
                $sql .= " AND timestamp BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $timeRange['start'];
                $params[':end_date'] = $timeRange['end'];
            }
            
            $sql .= " ORDER BY timestamp ASC";
            
            // Execute query
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add widget data to export
            $exportData['widgets'][] = [
                'id' => $widget['id'],
                'title' => $widget['title'],
                'kpi' => [
                    'id' => $widget['kpi_id'],
                    'name' => $widget['kpi_name'],
                    'unit' => $widget['unit'] ?? '',
                    'target' => $widget['target'] ?? null
                ],
                'measurements' => $measurements
            ];
        }
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'dashboard_export_');
        $jsonFile = $tempFile . '.json';
        rename($tempFile, $jsonFile);
        
        // Write data to JSON file
        file_put_contents($jsonFile, json_encode($exportData, JSON_PRETTY_PRINT));
        
        return $jsonFile;
    }
}
