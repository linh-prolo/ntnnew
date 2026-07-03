<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';
requireRole('production', 'manager', 'director', 'accountant');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Đăng ký OT');

// ── Tiêu đề cột ──
$headers = [
    'A1' => 'ngày',
    'B1' => 'mã nhân viên',
    'C1' => 'số giờ đăng ký OT',
    'D1' => 'lý do (nếu có)',
];

foreach ($headers as $cell => $val) {
    $sheet->setCellValue($cell, $val);
    $sheet->getStyle($cell)->applyFromArray([
        'font' => [
            'bold'  => true,
            'color' => ['rgb' => 'CC0000'],
            'size'  => 12,
        ],
        'fill' => [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFF99'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']],
        ],
    ]);
}

// ── Độ rộng cột ──
$sheet->getColumnDimension('A')->setWidth(18);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(22);
$sheet->getColumnDimension('D')->setWidth(35);
$sheet->getRowDimension(1)->setRowHeight(22);

// ── Dữ liệu mẫu ──
$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$samples  = [
    [$today,    'NV001', 2, 'Hoàn thiện báo cáo tháng'],
    [$today,    'NV002', 3, 'Xử lý đơn hàng khẩn'],
    [$tomorrow, 'NV003', 2, ''],
    [$tomorrow, 'NV001', 4, 'Giao hàng cuối tuần'],
];

foreach ($samples as $i => $row) {
    $r = $i + 2;
    $sheet->setCellValue("A$r", $row[0]);
    $sheet->setCellValue("B$r", $row[1]);
    $sheet->setCellValue("C$r", $row[2]);
    $sheet->setCellValue("D$r", $row[3]);

    // Style dòng dữ liệu mẫu
    $sheet->getStyle("A$r:D$r")->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
        ],
        'fill' => [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $i % 2 === 0 ? 'FFFFFF' : 'F9F9F9'],
        ],
    ]);
}

// ── Ghi chú ──
$noteRow = count($samples) + 3;
$sheet->setCellValue("A$noteRow", '📌 Ghi chú:');
$sheet->getStyle("A$noteRow")->getFont()->setBold(true)->setColor(new Color('FF666666'));

$notes = [
    'Cột A (ngày): Định dạng YYYY-MM-DD (VD: 2026-05-15) hoặc DD/MM/YYYY (VD: 15/05/2026)',
    'Cột B (mã nhân viên): Mã nhân viên trong hệ thống, VD: NV001',
    'Cột C (số giờ): Số giờ OT, VD: 2 hoặc 2.5 (tối đa 12 giờ/ngày)',
    'Cột D (lý do): Không bắt buộc, có thể để trống',
    'Hệ thống sẽ tự xác định loại OT: Ngày thường / Cuối tuần / Ngày lễ',
    'Giờ bắt đầu OT mặc định = giờ kết thúc ca. Nếu chưa có ca → mặc định 17:00',
];
foreach ($notes as $j => $note) {
    $nr = $noteRow + 1 + $j;
    $sheet->setCellValue("A$nr", '  • ' . $note);
    $sheet->mergeCells("A$nr:D$nr");
    $sheet->getStyle("A$nr")->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF555555'));
}

// ── Sheet 2: Danh sách mã nhân viên ──
$pdo = getDBConnection();
$empSheet = $spreadsheet->createSheet();
$empSheet->setTitle('Mã nhân viên');
$empSheet->setCellValue('A1', 'Mã NV');
$empSheet->setCellValue('B1', 'Họ tên');
$empSheet->setCellValue('C1', 'Phòng ban');
$empSheet->getStyle('A1:C1')->getFont()->setBold(true);
$empSheet->getStyle('A1:C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBFF');
$empSheet->getColumnDimension('A')->setWidth(14);
$empSheet->getColumnDimension('B')->setWidth(28);
$empSheet->getColumnDimension('C')->setWidth(24);

$emps = $pdo->query("
    SELECT u.employee_code, u.full_name, d.name AS dept_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.is_active = 1
    ORDER BY u.employee_code
")->fetchAll();

foreach ($emps as $ei => $emp) {
    $er = $ei + 2;
    $empSheet->setCellValue("A$er", $emp['employee_code']);
    $empSheet->setCellValue("B$er", $emp['full_name']);
    $empSheet->setCellValue("C$er", $emp['dept_name'] ?? '');
}

// Active sheet 1
$spreadsheet->setActiveSheetIndex(0);

$filename = 'dang_ky_OT_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
