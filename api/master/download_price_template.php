<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
requireLogin();

$autoloadPath = $_SERVER['DOCUMENT_ROOT'] . '/erp/vendor/autoload.php';
$hasSpreadsheet = file_exists($autoloadPath);

if ($hasSpreadsheet) {
    require_once $autoloadPath;

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template');

    $headers = [
        'A1' => 'Mã SP',
        'B1' => 'Tên SP',
        'C1' => 'Đơn vị',
        'D1' => 'Đơn giá',
        'E1' => 'Ngày áp dụng (YYYY-MM-DD)',
        'F1' => 'Đến ngày (YYYY-MM-DD)',
        'G1' => 'Ghi chú',
    ];
    foreach ($headers as $cell => $label) {
        $sheet->setCellValue($cell, $label);
    }

    $sheet->fromArray([
        ['SP-001', 'Tên sản phẩm 1', 'cái', 3690, date('Y-m-d'), '', ''],
        ['SP-002', 'Tên sản phẩm 2', 'cái', 5000, date('Y-m-d'), '', ''],
    ], null, 'A2');

    $sheet->setCellValue('A5', 'Lưu ý: Cột "Ngày áp dụng" và "Đến ngày" phải nhập đúng định dạng YYYY-MM-DD.');
    $sheet->mergeCells('A5:G5');
    $sheet->getStyle('A5')->getFont()->setItalic(true);

    $sheet->getStyle('A1:G1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF198754'],
        ],
    ]);

    $sheet->getColumnDimension('A')->setWidth(16);
    $sheet->getColumnDimension('B')->setWidth(28);
    $sheet->getColumnDimension('C')->setWidth(14);
    $sheet->getColumnDimension('D')->setWidth(14);
    $sheet->getColumnDimension('E')->setWidth(24);
    $sheet->getColumnDimension('F')->setWidth(24);
    $sheet->getColumnDimension('G')->setWidth(30);

    $filename = 'mau_import_san_pham_' . date('Ymd') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mau_import_san_pham.csv"');
echo "\xEF\xBB\xBF";
echo "Mã SP,Tên SP,Đơn vị,Đơn giá,Ngày áp dụng (YYYY-MM-DD),Đến ngày (YYYY-MM-DD),Ghi chú\n";
echo "SP-001,Tên sản phẩm 1,cái,3690," . date('Y-m-d') . ",,\n";
echo "SP-002,Tên sản phẩm 2,cái,5000," . date('Y-m-d') . ",,\n";
