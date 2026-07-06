<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';
requireRole('director', 'accountant', 'manager', 'production');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Chấm Công Tay');

// ── Tiêu đề cột ──
$headers = [
    'A1' => 'STT',
    'B1' => 'Họ và Tên',
    'C1' => 'Mã NV *',
    'D1' => 'Ngày công thực tế',
    'E1' => 'Nghỉ phép tính lương',
    'F1' => 'Nghỉ không phép',
    'G1' => 'Ngày lễ',
    'H1' => 'Nghỉ bảo hiểm',
    'I1' => 'Nghỉ việc riêng',
    'J1' => 'Giờ 100%',
    'K1' => 'Giờ 130%',
    'L1' => 'Giờ 150%',
    'M1' => 'Giờ 200%',
    'N1' => 'Giờ 300%',
    'O1' => 'Giờ 210%',
    'P1' => 'Giờ 270%',
    'Q1' => 'Giờ 390%',
];

foreach ($headers as $cell => $val) {
    $sheet->setCellValue($cell, $val);
    $sheet->getStyle($cell)->applyFromArray([
        'font' => [
            'bold'  => true,
            'color' => ['rgb' => 'CC0000'],
            'size'  => 11,
        ],
        'fill' => [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFF99'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
            'wrapText'   => true,
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']],
        ],
    ]);
}

// ── Độ rộng cột ──
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(14);
$sheet->getColumnDimension('F')->setWidth(14);
$sheet->getColumnDimension('G')->setWidth(12);
$sheet->getColumnDimension('H')->setWidth(14);
$sheet->getColumnDimension('I')->setWidth(14);
$sheet->getColumnDimension('J')->setWidth(12);
$sheet->getColumnDimension('K')->setWidth(12);
$sheet->getColumnDimension('L')->setWidth(12);
$sheet->getColumnDimension('M')->setWidth(12);
$sheet->getColumnDimension('N')->setWidth(12);
$sheet->getColumnDimension('O')->setWidth(12);
$sheet->getColumnDimension('P')->setWidth(12);
$sheet->getColumnDimension('Q')->setWidth(12);
$sheet->getRowDimension(1)->setRowHeight(36);

// ── Dữ liệu mẫu ──
$samples = [
    [1, 'Nguyễn Văn A', 'NV001', 26, 0, 0, 1, 0, 0, 208, 0, 8, 0, 0, 0, 0, 0],
    [2, 'Trần Thị B',   'NV002', 24, 2, 0, 0, 0, 0, 192, 16, 0, 0, 0, 0, 0, 0],
    [3, 'Lê Văn C',     'NV003', 26, 0, 1, 0, 0, 0, 208, 0, 0, 8, 0, 0, 0, 0],
];

$cols = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q'];
foreach ($samples as $i => $row) {
    $r = $i + 2;
    foreach ($cols as $ci => $col) {
        $sheet->setCellValue("{$col}{$r}", $row[$ci]);
    }
    $sheet->getStyle("A{$r}:Q{$r}")->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
        ],
        'fill' => [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $i % 2 === 0 ? 'FFFFFF' : 'F9F9F9'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
    ]);
    // Left-align tên
    $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
}

// ── Ghi chú ──
$noteRow = count($samples) + 3;
$sheet->setCellValue("A{$noteRow}", '📌 Ghi chú:');
$sheet->getStyle("A{$noteRow}")->getFont()->setBold(true)->setColor(new Color('FF666666'));

$notes = [
    'Cột C (Mã NV): BẮT BUỘC. Mã nhân viên trong hệ thống, VD: NV001, NV077, NV999',
    'Cột B (Họ và Tên): Chỉ tham khảo, KHÔNG dùng để tra cứu — hệ thống lấy tên từ mã NV',
    'Các cột D-I: Số ngày, có thể là số thập phân (VD: 26.0, 0.5)',
    'Các cột J-Q: Số giờ làm việc theo từng mức lương, có thể là số thập phân (VD: 208.0, 8.5)',
    'Dòng trống hoặc thiếu mã NV sẽ bị bỏ qua và hiển thị cảnh báo sau import',
    'Kỳ lương (tháng/năm) được chọn trên giao diện khi import, KHÔNG lấy từ file Excel',
];
foreach ($notes as $j => $note) {
    $nr = $noteRow + 1 + $j;
    $sheet->setCellValue("A{$nr}", '  • ' . $note);
    $sheet->mergeCells("A{$nr}:Q{$nr}");
    $sheet->getStyle("A{$nr}")->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF555555'));
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
    $empSheet->setCellValue("A{$er}", $emp['employee_code']);
    $empSheet->setCellValue("B{$er}", $emp['full_name']);
    $empSheet->setCellValue("C{$er}", $emp['dept_name'] ?? '');
}

// Active sheet 1
$spreadsheet->setActiveSheetIndex(0);

$filename = 'template_chamcong_tay_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
