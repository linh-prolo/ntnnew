<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

requireLogin();
requireRole('director', 'accountant', 'manager');

$pdo        = getDBConnection();
$customerId = (int)($_GET['customer_id'] ?? 0);
$fromDate   = trim((string)($_GET['from'] ?? date('Y-m-01')));
$toDate     = trim((string)($_GET['to'] ?? date('Y-m-d')));

if ($customerId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) || $fromDate > $toDate) {
    http_response_code(400);
    echo 'Tham số không hợp lệ';
    exit;
}

$customerStmt = $pdo->prepare("SELECT id, customer_name, customer_code, address, tax_code FROM customers WHERE id = ? LIMIT 1");
$customerStmt->execute([$customerId]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    http_response_code(404);
    echo 'Không tìm thấy khách hàng';
    exit;
}

$companyName = 'CÔNG TY CỔ PHẦN SẢN XUẤT VÀ CUNG ỨNG NTN VIỆT NAM';
$companyAddress = '';
$companyTax = '';
$companyPhone = '';
try {
    $cfg = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('company_name','company_address','company_tax','company_phone')")
        ->fetchAll(PDO::FETCH_KEY_PAIR);
    $companyName = $cfg['company_name'] ?? $companyName;
    $companyAddress = $cfg['company_address'] ?? $companyAddress;
    $companyTax = $cfg['company_tax'] ?? $companyTax;
    $companyPhone = $cfg['company_phone'] ?? $companyPhone;
} catch (Throwable $e) {
    // Bỏ qua nếu bảng chưa có
}

$summaryStmt = $pdo->prepare("
    SELECT pc.product_code,
           pc.description,
           iri.unit,
           COALESCE(
               (
                   SELECT cp.unit_price
                   FROM customer_prices cp
                   WHERE cp.customer_id = ?
                     AND cp.product_code_id = iri.product_code_id
                     AND cp.effective_date <= d.delivery_date
                     AND (cp.expired_date IS NULL OR cp.expired_date >= d.delivery_date)
                     AND cp.is_active = 1
                   ORDER BY cp.effective_date DESC, cp.id DESC
                   LIMIT 1
               ),
               0
           ) AS unit_price,
           SUM(di.qty_deliver) AS total_qty,
           SUM(di.qty_deliver * COALESCE(
               (
                   SELECT cp.unit_price
                   FROM customer_prices cp
                   WHERE cp.customer_id = ?
                     AND cp.product_code_id = iri.product_code_id
                     AND cp.effective_date <= d.delivery_date
                     AND (cp.expired_date IS NULL OR cp.expired_date >= d.delivery_date)
                     AND cp.is_active = 1
                   ORDER BY cp.effective_date DESC, cp.id DESC
                   LIMIT 1
               ),
               0
           )) AS total_amount
    FROM oqc_delivery_items di
    JOIN oqc_deliveries d ON d.id = di.delivery_id
    JOIN production_items pi ON di.production_item_id = pi.id
    JOIN iqc_receipt_items iri ON pi.iqc_item_id = iri.id
    JOIN product_codes pc ON pc.id = iri.product_code_id
    WHERE d.customer_id = ?
      AND d.delivery_date BETWEEN ? AND ?
      AND d.status <> 'invoiced'
      AND di.type = 'done'
    GROUP BY iri.product_code_id, pc.product_code, pc.description, iri.unit, unit_price
    ORDER BY pc.product_code, unit_price
");
$summaryStmt->execute([$customerId, $customerId, $customerId, $fromDate, $toDate]);
$summaryRows = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

$detailStmt = $pdo->prepare("
    SELECT d.delivery_date,
           d.delivery_no,
           pc.product_code,
           pc.description,
           iri.unit,
           di.qty_deliver,
           COALESCE(
               (
                   SELECT cp.unit_price
                   FROM customer_prices cp
                   WHERE cp.customer_id = ?
                     AND cp.product_code_id = iri.product_code_id
                     AND cp.effective_date <= d.delivery_date
                     AND (cp.expired_date IS NULL OR cp.expired_date >= d.delivery_date)
                     AND cp.is_active = 1
                   ORDER BY cp.effective_date DESC, cp.id DESC
                   LIMIT 1
               ),
               0
           ) AS unit_price
    FROM oqc_delivery_items di
    JOIN oqc_deliveries d ON d.id = di.delivery_id
    JOIN production_items pi ON di.production_item_id = pi.id
    JOIN iqc_receipt_items iri ON pi.iqc_item_id = iri.id
    JOIN product_codes pc ON pc.id = iri.product_code_id
    WHERE d.customer_id = ?
      AND d.delivery_date BETWEEN ? AND ?
      AND d.status <> 'invoiced'
      AND di.type = 'done'
    ORDER BY d.delivery_date, d.delivery_no, pc.product_code
");
$detailStmt->execute([$customerId, $customerId, $fromDate, $toDate]);
$detailRows = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($summaryRows) && empty($detailRows)) {
    http_response_code(404);
    echo 'Không có dữ liệu để xuất';
    exit;
}

function numberToWordsVn(int $n): string {
    if ($n === 0) return 'Không đồng chẵn';
    $ones  = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    $teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
    $units = ['', 'nghìn', 'triệu', 'tỷ'];

    $readHundreds = static function(int $val) use ($ones, $teens): string {
        $out = '';
        if ($val >= 100) {
            $out .= $ones[intdiv($val, 100)] . ' trăm ';
            $val %= 100;
        }
        if ($val >= 20) {
            $chuc = intdiv($val, 10);
            $donvi = $val % 10;
            $out .= $ones[$chuc] . ' mươi ';
            if ($donvi > 0) {
                $out .= ($donvi === 5 ? 'lăm' : $ones[$donvi]) . ' ';
            }
        } elseif ($val >= 10) {
            $out .= $teens[$val - 10] . ' ';
        } elseif ($val > 0) {
            $out .= ($out !== '' ? 'lẻ ' : '') . $ones[$val] . ' ';
        }
        return trim($out);
    };

    $parts = [];
    $i = 0;
    while ($n > 0) {
        $chunk = $n % 1000;
        if ($chunk > 0) {
            $parts[] = trim($readHundreds($chunk) . ' ' . ($units[$i] ?? ''));
        }
        $n = intdiv($n, 1000);
        $i++;
    }
    return ucfirst(trim(implode(' ', array_reverse($parts)))) . ' đồng chẵn';
}

function setupSheetHeader($sheet, array $meta): int {
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', $meta['company_name']);
    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', trim($meta['company_address'] . ($meta['company_phone'] ? ' | ĐT: ' . $meta['company_phone'] : '')));
    $sheet->mergeCells('A3:G3');
    $sheet->setCellValue('A3', 'MST: ' . ($meta['company_tax'] ?: '—'));

    $sheet->mergeCells('A5:G5');
    $sheet->setCellValue('A5', 'BẢNG KÊ CHI TIẾT XUẤT HÀNG [' . $meta['customer_code'] . ']');
    $sheet->mergeCells('A6:G6');
    $sheet->setCellValue('A6', 'Từ ngày ' . date('d/m/Y', strtotime($meta['from'])) . ' đến ngày ' . date('d/m/Y', strtotime($meta['to'])));

    $sheet->mergeCells('A8:G8');
    $sheet->setCellValue('A8', 'BÊN MUA: ' . $meta['customer_name']);
    $sheet->mergeCells('A9:G9');
    $sheet->setCellValue('A9', 'Địa chỉ: ' . ($meta['customer_address'] ?: '—'));
    $sheet->mergeCells('A10:G10');
    $sheet->setCellValue('A10', 'MST: ' . ($meta['customer_tax'] ?: '—'));

    $sheet->mergeCells('A12:G12');
    $sheet->setCellValue('A12', 'BÊN BÁN: ' . $meta['company_name']);
    $sheet->mergeCells('A13:G13');
    $sheet->setCellValue('A13', 'Địa chỉ: ' . ($meta['company_address'] ?: '—'));
    $sheet->mergeCells('A14:G14');
    $sheet->setCellValue('A14', 'MST: ' . ($meta['company_tax'] ?: '—'));

    $sheet->getStyle('A1:G14')->getFont()->setName('Times New Roman')->setSize(10);
    $sheet->getStyle('A1:G3')->getFont()->setBold(true);
    $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(13);
    $sheet->getStyle('A5:A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    return 16;
}

$meta = [
    'company_name' => $companyName,
    'company_address' => $companyAddress,
    'company_tax' => $companyTax,
    'company_phone' => $companyPhone,
    'customer_name' => $customer['customer_name'] ?? '',
    'customer_code' => $customer['customer_code'] ?? '',
    'customer_address' => $customer['address'] ?? '',
    'customer_tax' => $customer['tax_code'] ?? '',
    'from' => $fromDate,
    'to' => $toDate,
];

$spreadsheet = new Spreadsheet();
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Tổng hợp');
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Chi tiết');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Times New Roman', 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17375E']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
];
$bodyBorder = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
];

$startRow1 = setupSheetHeader($sheet1, $meta);
$sheet1->fromArray(['STT', 'Mã SP', 'Tên hàng', 'ĐVT', 'Đơn giá', 'Tổng SL', 'Thành tiền'], null, 'A' . $startRow1);
$sheet1->getStyle("A{$startRow1}:G{$startRow1}")->applyFromArray($headerStyle);

$row = $startRow1 + 1;
$totalSummary = 0;
foreach ($summaryRows as $idx => $r) {
    $lineAmount = (float)$r['total_amount'];
    $totalSummary += $lineAmount;
    $sheet1->setCellValue("A{$row}", $idx + 1);
    $sheet1->setCellValue("B{$row}", $r['product_code']);
    $sheet1->setCellValue("C{$row}", $r['description']);
    $sheet1->setCellValue("D{$row}", $r['unit']);
    $sheet1->setCellValue("E{$row}", (float)$r['unit_price']);
    $sheet1->setCellValue("F{$row}", (float)$r['total_qty']);
    $sheet1->setCellValue("G{$row}", $lineAmount);

    $sheet1->getStyle("A{$row}:G{$row}")->applyFromArray($bodyBorder);
    $sheet1->getStyle("A{$row}:G{$row}")->getFont()->setName('Times New Roman')->setSize(10);
    $sheet1->getStyle("A{$row}:A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("D{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet1->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FCE4D6');
    $sheet1->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(($idx % 2 === 0) ? 'FFFFFF' : 'F2F7FF');
    $row++;
}

$sheet1->mergeCells("A{$row}:F{$row}");
$sheet1->setCellValue("A{$row}", 'TỔNG CỘNG');
$sheet1->setCellValue("G{$row}", $totalSummary);
$sheet1->getStyle("A{$row}:G{$row}")->applyFromArray([
    'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'B7B7B7']]],
]);
$sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$row++;

$sheet1->mergeCells("A{$row}:G{$row}");
$sheet1->setCellValue("A{$row}", 'Số tiền bằng chữ: ' . numberToWordsVn((int)round($totalSummary)));
$sheet1->getStyle("A{$row}:G{$row}")->getFont()->setName('Times New Roman')->setSize(10)->setItalic(true);
$row += 2;

$sheet1->mergeCells("A{$row}:C{$row}");
$sheet1->mergeCells("E{$row}:G{$row}");
$sheet1->setCellValue("A{$row}", 'ĐẠI DIỆN BÊN MUA');
$sheet1->setCellValue("E{$row}", 'ĐẠI DIỆN BÊN BÁN');
$sheet1->getStyle("A{$row}:G{$row}")->getFont()->setBold(true)->setName('Times New Roman')->setSize(10);
$sheet1->getStyle("A{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$moneyFmt = '#,##0';
$sheet1->getStyle("E" . ($startRow1 + 1) . ":G{$row}")->getNumberFormat()->setFormatCode($moneyFmt);
$sheet1->getStyle("F" . ($startRow1 + 1) . ":F{$row}")->getNumberFormat()->setFormatCode('#,##0.###');

$sheet1->getColumnDimension('A')->setWidth(7);
$sheet1->getColumnDimension('B')->setWidth(16);
$sheet1->getColumnDimension('C')->setWidth(34);
$sheet1->getColumnDimension('D')->setWidth(10);
$sheet1->getColumnDimension('E')->setWidth(16);
$sheet1->getColumnDimension('F')->setWidth(12);
$sheet1->getColumnDimension('G')->setWidth(18);

$startRow2 = setupSheetHeader($sheet2, $meta);
$sheet2->fromArray(['STT', 'Ngày giao', 'Số biên bản', 'Mã SP', 'Tên hàng', 'ĐVT', 'SL', 'Đơn giá', 'Thành tiền'], null, 'A' . $startRow2);
$sheet2->getStyle("A{$startRow2}:I{$startRow2}")->applyFromArray($headerStyle);

$row2 = $startRow2 + 1;
$totalDetail = 0;
foreach ($detailRows as $idx => $r) {
    $lineAmount = (float)$r['qty_deliver'] * (float)$r['unit_price'];
    $totalDetail += $lineAmount;
    $sheet2->setCellValue("A{$row2}", $idx + 1);
    $sheet2->setCellValue("B{$row2}", date('d/m/Y', strtotime($r['delivery_date'])));
    $sheet2->setCellValue("C{$row2}", $r['delivery_no']);
    $sheet2->setCellValue("D{$row2}", $r['product_code']);
    $sheet2->setCellValue("E{$row2}", $r['description']);
    $sheet2->setCellValue("F{$row2}", $r['unit']);
    $sheet2->setCellValue("G{$row2}", (float)$r['qty_deliver']);
    $sheet2->setCellValue("H{$row2}", (float)$r['unit_price']);
    $sheet2->setCellValue("I{$row2}", $lineAmount);

    $sheet2->getStyle("A{$row2}:I{$row2}")->applyFromArray($bodyBorder);
    $sheet2->getStyle("A{$row2}:I{$row2}")->getFont()->setName('Times New Roman')->setSize(10);
    $sheet2->getStyle("A{$row2}:A{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle("B{$row2}:B{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle("F{$row2}:F{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle("G{$row2}:I{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet2->getStyle("G{$row2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FCE4D6');
    $sheet2->getStyle("A{$row2}:I{$row2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(($idx % 2 === 0) ? 'FFFFFF' : 'F2F7FF');
    $row2++;
}

$sheet2->mergeCells("A{$row2}:H{$row2}");
$sheet2->setCellValue("A{$row2}", 'TỔNG CỘNG');
$sheet2->setCellValue("I{$row2}", $totalDetail);
$sheet2->getStyle("A{$row2}:I{$row2}")->applyFromArray([
    'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'B7B7B7']]],
]);
$sheet2->getStyle("A{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle("I{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$row2++;

$sheet2->mergeCells("A{$row2}:I{$row2}");
$sheet2->setCellValue("A{$row2}", 'Số tiền bằng chữ: ' . numberToWordsVn((int)round($totalDetail)));
$sheet2->getStyle("A{$row2}:I{$row2}")->getFont()->setName('Times New Roman')->setSize(10)->setItalic(true);
$row2 += 2;

$sheet2->mergeCells("A{$row2}:D{$row2}");
$sheet2->mergeCells("F{$row2}:I{$row2}");
$sheet2->setCellValue("A{$row2}", 'ĐẠI DIỆN BÊN MUA');
$sheet2->setCellValue("F{$row2}", 'ĐẠI DIỆN BÊN BÁN');
$sheet2->getStyle("A{$row2}:I{$row2}")->getFont()->setBold(true)->setName('Times New Roman')->setSize(10);
$sheet2->getStyle("A{$row2}:I{$row2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet2->getStyle("G" . ($startRow2 + 1) . ":I{$row2}")->getNumberFormat()->setFormatCode($moneyFmt);
$sheet2->getStyle("G" . ($startRow2 + 1) . ":G{$row2}")->getNumberFormat()->setFormatCode('#,##0.###');

$sheet2->getColumnDimension('A')->setWidth(7);
$sheet2->getColumnDimension('B')->setWidth(12);
$sheet2->getColumnDimension('C')->setWidth(18);
$sheet2->getColumnDimension('D')->setWidth(14);
$sheet2->getColumnDimension('E')->setWidth(30);
$sheet2->getColumnDimension('F')->setWidth(10);
$sheet2->getColumnDimension('G')->setWidth(12);
$sheet2->getColumnDimension('H')->setWidth(16);
$sheet2->getColumnDimension('I')->setWidth(18);

foreach ([$sheet1, $sheet2] as $sheet) {
    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
    $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setLeft(0.35)->setBottom(0.5);
}

$filename = 'BangKe_' . ($customer['customer_code'] ?: $customer['id']) . '_' . str_replace('-', '', $fromDate) . '_' . str_replace('-', '', $toDate) . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit;
