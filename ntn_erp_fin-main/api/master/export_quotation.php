<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

requireLogin();
requireRole('director', 'accountant', 'manager');

$pdo = getDBConnection();
$customerId = (int)($_GET['customer_id'] ?? 0);

if ($customerId <= 0) {
    http_response_code(400);
    echo 'Tham số không hợp lệ';
    exit;
}

$customerStmt = $pdo->prepare("
    SELECT id, customer_code, customer_name
    FROM customers
    WHERE id = ?
    LIMIT 1
");
$customerStmt->execute([$customerId]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    echo 'Không tìm thấy khách hàng';
    exit;
}

$priceStmt = $pdo->prepare("
    SELECT cp.*, pc.product_code, pc.description, pc.unit
    FROM customer_prices cp
    JOIN product_codes pc ON cp.product_code_id = pc.id
    LEFT JOIN customer_prices cp_newer
           ON cp_newer.customer_id = cp.customer_id
          AND cp_newer.product_code_id = cp.product_code_id
          AND cp_newer.effective_date <= CURDATE()
          AND (cp_newer.expired_date IS NULL OR cp_newer.expired_date >= CURDATE())
          AND (
               cp_newer.effective_date > cp.effective_date
               OR (cp_newer.effective_date = cp.effective_date AND cp_newer.id > cp.id)
          )
    WHERE cp.customer_id = ?
      AND cp.effective_date <= CURDATE()
      AND (cp.expired_date IS NULL OR cp.expired_date >= CURDATE())
      AND cp_newer.id IS NULL
    ORDER BY pc.product_code
");
$priceStmt->execute([$customerId]);
$currentPrices = $priceStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($currentPrices)) {
    http_response_code(404);
    echo 'Không có bảng giá hiện tại để xuất';
    exit;
}

$companyName    = 'CÔNG TY CỔ PHẦN SẢN XUẤT VÀ CUNG ỨNG NTN VIỆT NAM';
$companyAddress = '';
$companyTax     = '';
$companyPhone   = '';
$companyWebsite = '';

try {
    $cfg = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('company_name','company_address','company_tax','company_phone','company_website')")
        ->fetchAll(PDO::FETCH_KEY_PAIR);
    $companyName    = $cfg['company_name']    ?? $companyName;
    $companyAddress = $cfg['company_address'] ?? $companyAddress;
    $companyTax     = $cfg['company_tax']     ?? $companyTax;
    $companyPhone   = $cfg['company_phone']   ?? $companyPhone;
    $companyWebsite = $cfg['company_website'] ?? $companyWebsite;
} catch (Throwable $e) {
    // Bỏ qua nếu bảng chưa có hoặc thiếu cột
}

$now              = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
$documentDate     = $now->format('Ymd');
$displayDate      = $now->format('d/m/Y');
$customerCode     = trim((string)($customer['customer_code'] ?? '')) ?: (string)$customer['id'];
$safeCustomerCode = preg_replace('/[^A-Za-z0-9_-]+/', '_', $customerCode) ?: (string)$customer['id'];
$quotationNo      = 'QT-' . $customerCode . '-' . $documentDate;

$companyContactParts = [];
if ($companyTax !== '')   { $companyContactParts[] = 'MST: ' . $companyTax; }
if ($companyPhone !== '') { $companyContactParts[] = 'Tel: ' . $companyPhone; }

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Quotation');

// getDefaultStyle() thuộc Spreadsheet, KHÔNG phải Worksheet
$spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(10);
$spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// ── Header công ty ──────────────────────────────────────────────
$sheet->mergeCells('A1:I1');
$sheet->setCellValue('A1', $companyName);
$sheet->mergeCells('A2:I2');
$sheet->setCellValue('A2', $companyAddress);
$sheet->mergeCells('A3:I3');
$sheet->setCellValue('A3', implode(' | ', $companyContactParts));
$sheet->mergeCells('A4:I4');
$sheet->setCellValue('A4', $companyWebsite);

// ── Số & ngày báo giá ───────────────────────────────────────────
$sheet->mergeCells('A6:C6');
$sheet->setCellValue('A6', 'No: ' . $quotationNo);
$sheet->mergeCells('G6:I6');
$sheet->setCellValue('G6', 'Date: ' . $displayDate);

// ── Tiêu đề + To ────────────────────────────────────────────────
$sheet->mergeCells('A7:I7');
$sheet->setCellValue('A7', 'QUOTATION');
$sheet->mergeCells('A8:I8');
$sheet->setCellValue('A8', 'To: ' . ($customer['customer_name'] ?? ''));

// ── Đoạn giới thiệu ─────────────────────────────────────────────
$sheet->mergeCells('A9:I9');
$sheet->setCellValue('A9', 'First of all, we would like to express our sincere thanks for your interest in our products,');
$sheet->mergeCells('A10:I10');
$sheet->setCellValue('A10', 'and believe these products will fully meet your expectations.');
$sheet->mergeCells('A11:I11');
$sheet->setCellValue('A11', 'We are pleased to quote the under-mentioned goods as per conditions and details described as follows:');

// ── Header bảng ─────────────────────────────────────────────────
$headers = ['No', 'Code', 'Description of goods', 'Unit', 'Qty', 'Maker', 'Price (VND)', 'Amount (VND)', 'Re-mark'];
$sheet->fromArray($headers, null, 'A12');

// ── Độ rộng cột ─────────────────────────────────────────────────
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(35);
$sheet->getColumnDimension('D')->setWidth(10);
$sheet->getColumnDimension('E')->setWidth(8);
$sheet->getColumnDimension('F')->setWidth(14);
$sheet->getColumnDimension('G')->setWidth(16);
$sheet->getColumnDimension('H')->setWidth(16);
$sheet->getColumnDimension('I')->setWidth(14);

// ── Styles ──────────────────────────────────────────────────────
$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Times New Roman', 'size' => 10],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17375E']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
];
$bodyBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]]];
$zebraFill  = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]];

$sheet->getStyle('A1:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A1:I4')->getAlignment()->setWrapText(true);
$sheet->getStyle('A6:C6')->getFont()->setBold(true);
$sheet->getStyle('G6:I6')->getFont()->setBold(true);
$sheet->getStyle('G6:I6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A7:I7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A8')->getFont()->setItalic(true);
$sheet->getStyle('A9:I11')->getAlignment()->setWrapText(true);
$sheet->getStyle('A12:I12')->applyFromArray($headerStyle);

// ── Dữ liệu sản phẩm ────────────────────────────────────────────
$row = 13;
foreach ($currentPrices as $idx => $priceRow) {
    $amountFormula = sprintf('=IF(OR(E%d="",G%d=""),"",E%d*G%d)', $row, $row, $row, $row);
    $sheet->setCellValue("A{$row}", $idx + 1);
    $sheet->setCellValue("B{$row}", $priceRow['product_code'] ?? '');
    $sheet->setCellValue("C{$row}", $priceRow['description'] ?? '');
    $sheet->setCellValue("D{$row}", $priceRow['unit'] ?? '');
    $sheet->setCellValue("E{$row}", '');
    $sheet->setCellValue("F{$row}", '');
    $sheet->setCellValue("G{$row}", (float)($priceRow['unit_price'] ?? 0));
    $sheet->setCellValue("H{$row}", $amountFormula);
    $sheet->setCellValue("I{$row}", '');

    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($bodyBorder);
    if ((($idx + 1) % 2) === 0) {
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($zebraFill);
    }
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("G{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("C{$row}:I{$row}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("G{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0');
    $row++;
}

// ── Ghi chú cuối ─────────────────────────────────────────────────
$noteRow = $row + 2;
$sheet->mergeCells("A{$noteRow}:I{$noteRow}");
$sheet->setCellValue("A{$noteRow}", 'All prices are exclusive of VAT');
$sheet->getStyle("A{$noteRow}")->getFont()->setItalic(true);

// ── Page setup ───────────────────────────────────────────────────
$sheet->getRowDimension(7)->setRowHeight(24);
$sheet->getRowDimension(12)->setRowHeight(22);
$sheet->freezePane('A13');
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setLeft(0.35)->setBottom(0.5);

// ── Xuất file ────────────────────────────────────────────────────
$filename = 'Quotation_' . $safeCustomerCode . '_' . $documentDate . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit;
