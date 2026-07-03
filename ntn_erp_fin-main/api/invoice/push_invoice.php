<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/bkav.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/BkavEHoaDonClient.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant');

define('BKAV_RAW_RESPONSE_MAX_LENGTH', 65535);

function bkav_log(string $msg): void {
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/erp/tmp';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/bkav_debug.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function numberToWordsVN(float $amount): string {
    $amount = (int) round($amount);
    if ($amount === 0) return 'Không đồng';
    $ones  = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    $teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm',
              'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
    $readThree = function(int $n) use ($ones, $teens, &$readThree): string {
        if ($n === 0) return '';
        if ($n < 10) return $ones[$n];
        if ($n < 20) return $teens[$n - 10];
        if ($n < 100) {
            $r = $ones[(int)($n / 10)] . ' mươi'; $u = $n % 10;
            if ($u === 5) $r .= ' lăm'; elseif ($u === 1) $r .= ' mốt'; elseif ($u > 0) $r .= ' ' . $ones[$u];
            return $r;
        }
        $r = $ones[(int)($n / 100)] . ' trăm'; $rem = $n % 100;
        if ($rem > 0 && $rem < 10) $r .= ' linh ' . $ones[$rem];
        elseif ($rem > 0) $r .= ' ' . $readThree($rem);
        return $r;
    };
    $units = ['', ' nghìn', ' triệu', ' tỷ']; $groups = [];
    while ($amount > 0) { $groups[] = $amount % 1000; $amount = (int)($amount / 1000); }
    $parts = [];
    foreach (array_reverse($groups, true) as $i => $g) {
        if ($g > 0) $parts[] = $readThree($g) . $units[$i];
    }
    return ucfirst(implode(' ', $parts)) . ' đồng';
}

function getInvoiceData(PDO $pdo, int $invoiceId): ?array {
    $stmt = $pdo->prepare("
        SELECT i.*, c.customer_name, c.address, c.tax_code, c.email AS customer_email
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$invoiceId]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inv) return null;
    $stmt2 = $pdo->prepare("
        SELECT ii.*, pc.product_code
        FROM invoice_items ii
        JOIN product_codes pc ON ii.product_code_id = pc.id
        WHERE ii.invoice_id = ? ORDER BY ii.id
    ");
    $stmt2->execute([$invoiceId]);
    $inv['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    return $inv;
}

// ════════════════════════════════════════════════════════
$pdo    = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET preview ──────────────────────────────────────────
if ($method === 'GET' && isset($_GET['preview'])) {
    $invoiceId = (int)($_GET['invoice_id'] ?? 0);
    if (!$invoiceId) { echo json_encode(['success' => false, 'message' => 'Thiếu invoice_id']); exit; }
    $inv = getInvoiceData($pdo, $invoiceId);
    if (!$inv) { echo json_encode(['success' => false, 'message' => 'Không tìm thấy hoá đơn']); exit; }

    $vatRate  = (float)($inv['vat_rate']     ?? 0);
    $subtotal = (float)($inv['subtotal']     ?? 0);
    $vatAmt   = (float)($inv['vat_amount']   ?? 0);
    $total    = (float)($inv['total_amount'] ?? 0);

    echo json_encode([
        'success' => true,
        'preview' => [
            'invoice_no'     => $inv['invoice_no'],
            'invoice_date'   => $inv['invoice_date'],
            'customer_name'  => $inv['customer_name'],
            'address'        => $inv['address']  ?? '',
            'tax_code'       => $inv['tax_code'] ?? '',
            'subtotal'       => $subtotal,
            'vat_rate'       => $vatRate,
            'vat_amount'     => $vatAmt,
            'total_amount'   => $total,
            'total_words'    => numberToWordsVN($total),
            'items'          => $inv['items'],
            'seller_name'    => defined('COMPANY_NAME')    ? COMPANY_NAME    : '',
            'seller_tax'     => defined('COMPANY_TAX')     ? COMPANY_TAX     : '',
            'seller_address' => defined('COMPANY_ADDRESS') ? COMPANY_ADDRESS : '',
            'bkav_invoice_no'=> $inv['bkav_invoice_no'] ?? null,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── POST gửi BKAV ────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { echo json_encode(['success' => false, 'message' => 'Request body không hợp lệ']); exit; }
    if (!verifyCSRF($body['csrf_token'] ?? '')) { echo json_encode(['success' => false, 'message' => 'CSRF không hợp lệ']); exit; }

    $invoiceId = (int)($body['invoice_id'] ?? 0);
    if (!$invoiceId) { echo json_encode(['success' => false, 'message' => 'Thiếu invoice_id']); exit; }

    $inv = getInvoiceData($pdo, $invoiceId);
    if (!$inv) { echo json_encode(['success' => false, 'message' => 'Không tìm thấy hoá đơn']); exit; }

    try {
        bkav_log("=== START invoice_id={$invoiceId} invoice_no={$inv['invoice_no']} env=" . (defined('BKAV_ENV') ? BKAV_ENV : '?'));

        $client  = new BkavEHoaDonClient();
        $payload = $client->buildInvoicePayload(
            $inv,
            $inv['items'],
            $invoiceId,
            $inv['invoice_date'] ?? date('Y-m-d'),
            defined('BKAV_PAYMENT_METHOD') ? BKAV_PAYMENT_METHOD : 'Chuyển khoản',
            $inv['invoice_no'] ?? ''
        );

        bkav_log('Payload Invoice keys: ' . implode(',', array_keys($payload)));

        $result = $client->createInvoice($payload);
        bkav_log('Result: ' . json_encode($result, JSON_UNESCAPED_UNICODE));

        if ($client->isSuccess($result)) {
            $bkavGuid    = (string)($result['InvoiceGUID']   ?? '');
            $bkavInvNo   = (string)($result['InvoiceNo']     ?? '');
            $bkavSerial  = (string)($result['InvoiceSerial'] ?? '');
            $bkavForm    = (string)($result['InvoiceForm']   ?? '');
            $bkavMtc     = (string)($result['MTC']           ?? '');

            // Lưu vào DB — dùng column nào có thì dùng
            try {
                $pdo->prepare("
                    UPDATE invoices
                    SET bkav_invoice_no=?, bkav_status='issued', bkav_issued_at=NOW(), bkav_raw_response=?
                    WHERE id=?
                ")->execute([$bkavInvNo, json_encode($result, JSON_UNESCAPED_UNICODE), $invoiceId]);
            } catch (Throwable $dbErr) {
                bkav_log('DB update warning: ' . $dbErr->getMessage());
            }

            bkav_log("Success: guid={$bkavGuid} invNo={$bkavInvNo}");
            echo json_encode([
                'success'   => true,
                'invoiceNo' => $bkavInvNo,
                'guid'      => $bkavGuid,
                'serial'    => $bkavSerial,
                'form'      => $bkavForm,
                'mtc'       => $bkavMtc,
                'message'   => 'Xuất hoá đơn thành công',
            ], JSON_UNESCAPED_UNICODE);

        } else {
            $errMsg = $client->getErrorMessage($result);
            bkav_log('BKAV error: ' . $errMsg);
            try {
                $pdo->prepare("UPDATE invoices SET bkav_status='error', bkav_raw_response=? WHERE id=?")
                    ->execute([json_encode($result, JSON_UNESCAPED_UNICODE), $invoiceId]);
            } catch (Throwable $dbErr) {
                bkav_log('DB update warning: ' . $dbErr->getMessage());
            }
            echo json_encode(['success' => false, 'message' => 'BKAV lỗi: ' . $errMsg], JSON_UNESCAPED_UNICODE);
        }

    } catch (Throwable $e) {
        bkav_log('Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ'], JSON_UNESCAPED_UNICODE);
