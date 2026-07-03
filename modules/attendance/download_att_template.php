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
use PhpOffice\PhpSpreadsheet\Style\Color;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Chấm Công');

// ── Header row ──
$headers = ['A1' => 'Ngày', 'B1' => 'Mã Nhân Viên', 'C1' => 'Giờ vào', 'D1' => 'Giờ ra'];
foreach ($headers as $cell => $val) {
    $sheet->setCellValue($cell, $val);
    $sheet->getStyle($cell)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'CC0000']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF99']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ]);
}
$sheet->getColumnDimension('A')->setWidth(16);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(12);

// ── Sample data ──
$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$samples  = [
    [$today,    'NV001', '07:30', '16:30'],
    [$today,    'NV002', '08:05', '17:00'],
    [$tomorrow, 'NV001', '07:45', ''],
];
foreach ($samples as $i => $row) {
    $r = $i + 2;
    $sheet->setCellValue("A$r", $row[0]);
    $sheet->setCellValue("B$r", $row[1]);
    $sheet->setCellValue("C$r", $row[2]);
    $sheet->setCellValue("D$r", $row[3]);
}

// ── Note ──
$sheet->setCellValue('A6', '📌 Ghi chú: Ngày định dạng YYYY-MM-DD hoặc DD/MM/YYYY. Giờ định dạng HH:MM. Giờ ra có thể để trống.');
$sheet->mergeCells('A6:D6');
$sheet->getStyle('A6')->getFont()->setItalic(true)->setColor(new Color('FF666666'));

// ── Sheet 2: Hướng dẫn ──
$guide = $spreadsheet->createSheet();
$guide->setTitle('Hướng dẫn');
$guide->setCellValue('A1', 'HƯỚNG DẪN NHẬP CHẤM CÔNG');
$guide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$rows = [
    ['Cột A - Ngày',          'Bắt buộc. Định dạng YYYY-MM-DD (VD: 2026-01-15) hoặc DD/MM/YYYY (VD: 15/01/2026)'],
    ['Cột B - Mã Nhân Viên',  'Bắt buộc. Mã nhân viên trong hệ thống (VD: NV001)'],
    ['Cột C - Giờ vào',       'Bắt buộc. Định dạng HH:MM (VD: 07:30)'],
    ['Cột D - Giờ ra',        'Không bắt buộc. Định dạng HH:MM (VD: 16:30). Để trống nếu chưa ra'],
    [''],
    ['⚠️ Lưu ý quan trọng', 'Nếu đã có bản ghi chấm công ngày đó → hệ thống sẽ CẬP NHẬT lại (không tạo trùng)'],
    ['',                     'Dữ liệu từ dòng 2 trở đi (dòng 1 là tiêu đề cột, không được xóa)'],
];
foreach ($rows as $i => $r) {
    if (!empty($r[0])) $guide->setCellValue('A' . ($i + 3), $r[0]);
    if (!empty($r[1])) $guide->setCellValue('B' . ($i + 3), $r[1]);
}
$guide->getColumnDimension('A')->setWidth(24);
$guide->getColumnDimension('B')->setWidth(70);

// ── Output ──
$filename = 'template_chamcong_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;