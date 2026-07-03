<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();

// ===================== SHEET 1: Template =====================
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template');

// Dòng 1: Tiêu đề lớn
$sheet->mergeCells('A1:N1');
$sheet->setCellValue('A1', 'DANH SÁCH NHẬP NHÂN VIÊN - TEMPLATE');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(28);

// Dòng 2: Headers
$headers = [
    'A' => 'Mã nhân viên *',
    'B' => 'Họ và tên *',
    'C' => 'Tên đăng nhập *',
    'D' => 'Mật khẩu *',
    'E' => 'Email',
    'F' => 'Số điện thoại',
    'G' => 'Phân quyền *',
    'H' => 'Phòng ban',
    'I' => 'Lương cơ bản',
    'J' => 'Phụ cấp ăn ca',
    'K' => 'Phụ cấp trang phục',
    'L' => 'Phụ cấp điện thoại',
    'M' => 'Phụ cấp đi lại',
    'N' => 'Thưởng chuyên cần',
];
foreach ($headers as $col => $label) {
    $sheet->setCellValue($col . '2', $label);
}
$sheet->getStyle('A2:N2')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
    ],
]);
$sheet->getRowDimension(2)->setRowHeight(22);

// Dòng 3: Mẫu 1
$sample1 = ['NV001', 'Nguyễn Văn A', 'nguyenvana', 'password123', 'nguyenvana@company.com', '0901234567', 'employee', 'Sản xuất', 6000000, 500000, 200000, 100000, 300000, 500000];
$cols3 = range('A', 'N');
foreach ($cols3 as $i => $col) {
    $sheet->setCellValue($col . '3', $sample1[$i]);
}

// Dòng 4: Mẫu 2
$sample2 = ['NV002', 'Trần Thị B', 'tranthib', 'password456', 'tranthib@company.com', '0912345678', 'manager', 'Kế toán', 8000000, 500000, 200000, 200000, 300000, 500000];
foreach ($cols3 as $i => $col) {
    $sheet->setCellValue($col . '4', $sample2[$i]);
}

// Style cho dữ liệu mẫu
$sheet->getStyle('A3:N4')->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']],
    ],
]);

// Độ rộng cột
$colWidths = [
    'A' => 15, 'B' => 22, 'C' => 18, 'D' => 16,
    'E' => 28, 'F' => 16, 'G' => 16, 'H' => 18,
    'I' => 16, 'J' => 18, 'K' => 20, 'L' => 20, 'M' => 16, 'N' => 20,
];
foreach ($colWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

// ===================== SHEET 2: Hướng dẫn =====================
$guide = $spreadsheet->createSheet();
$guide->setTitle('Hướng dẫn');

$guide->setCellValue('A1', 'HƯỚNG DẪN NHẬP DỮ LIỆU NHÂN VIÊN');
$guide->mergeCells('A1:C1');
$guide->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$guide->getRowDimension(1)->setRowHeight(24);

$guide->setCellValue('A3', 'Cột');
$guide->setCellValue('B3', 'Tên cột');
$guide->setCellValue('C3', 'Ghi chú');
$guide->getStyle('A3:C3')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
]);

$guideData = [
    ['A', 'Mã nhân viên *', 'Bắt buộc. Ví dụ: NV001, NV002... Không được trùng.'],
    ['B', 'Họ và tên *', 'Bắt buộc. Họ tên đầy đủ.'],
    ['C', 'Tên đăng nhập *', 'Bắt buộc. Chỉ dùng chữ không dấu, số, dấu gạch dưới. Không được trùng.'],
    ['D', 'Mật khẩu *', 'Bắt buộc. Tối thiểu 6 ký tự.'],
    ['E', 'Email', 'Không bắt buộc.'],
    ['F', 'Số điện thoại', 'Không bắt buộc.'],
    ['G', 'Phân quyền *', 'Bắt buộc. Nhập một trong các giá trị: director, accountant, manager, warehouse, production, employee'],
    ['H', 'Phòng ban', 'Không bắt buộc. Nhập tên phòng ban chính xác. Để trống nếu chưa xác định.'],
    ['I', 'Lương cơ bản', 'Không bắt buộc. Nhập số tiền (VNĐ). Để trống hoặc 0 nếu không có.'],
    ['J', 'Phụ cấp ăn ca', 'Không bắt buộc. Nhập số tiền (VNĐ).'],
    ['K', 'Phụ cấp trang phục', 'Không bắt buộc. Nhập số tiền (VNĐ).'],
    ['L', 'Phụ cấp điện thoại', 'Không bắt buộc. Nhập số tiền (VNĐ).'],
    ['M', 'Phụ cấp đi lại', 'Không bắt buộc. Nhập số tiền (VNĐ).'],
    ['N', 'Thưởng chuyên cần', 'Không bắt buộc. Nhập số tiền (VNĐ).'],
];
$row = 4;
foreach ($guideData as $gRow) {
    $guide->setCellValue('A' . $row, $gRow[0]);
    $guide->setCellValue('B' . $row, $gRow[1]);
    $guide->setCellValue('C' . $row, $gRow[2]);
    $row++;
}
$guide->getStyle('A3:C' . ($row - 1))->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
]);

$guide->getColumnDimension('A')->setWidth(8);
$guide->getColumnDimension('B')->setWidth(22);
$guide->getColumnDimension('C')->setWidth(70);

$guide->setCellValue('A' . ($row + 1), 'Giá trị hợp lệ cho cột Phân quyền (G):');
$guide->getStyle('A' . ($row + 1))->getFont()->setBold(true);
$guide->setCellValue('A' . ($row + 2), 'director');
$guide->setCellValue('B' . ($row + 2), 'Giám đốc');
$guide->setCellValue('A' . ($row + 3), 'accountant');
$guide->setCellValue('B' . ($row + 3), 'Kế toán');
$guide->setCellValue('A' . ($row + 4), 'manager');
$guide->setCellValue('B' . ($row + 4), 'Quản lý');
$guide->setCellValue('A' . ($row + 5), 'warehouse');
$guide->setCellValue('B' . ($row + 5), 'Quản lý Kho');
$guide->setCellValue('A' . ($row + 6), 'production');
$guide->setCellValue('B' . ($row + 6), 'Quản lý Sản xuất');
$guide->setCellValue('A' . ($row + 7), 'employee');
$guide->setCellValue('B' . ($row + 7), 'Nhân viên');

// Trở về sheet 1
$spreadsheet->setActiveSheetIndex(0);

// Output file
$filename = 'template_nhan_vien_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
