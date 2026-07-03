<?php
/**
 * BkavEHoaDonClient — Tích hợp BKAV eHoaDon 2.0
 * Adapted cho NTN ERP.
 *
 * TaxRateID:
 *   1 = 0%    2 = 5%    3 = 10%
 *   4 = KCT   5 = KKT   6 = Khác   9 = 8%
 *
 * ItemTypeID:
 *   0 = HangHoa (có STT)   4 = GhiChu (không STT)
 */
class BkavEHoaDonClient
{
    private string $wsUrl;
    private string $partnerGuid;
    private string $aesKey;
    private string $aesIv;

    private const ITEM_HANG_HOA = 0;
    private const ITEM_GHI_CHU  = 4;

    private const TAX_RATE_MAP = [
        0  => 1,
        5  => 2,
        8  => 9,
        10 => 3,
    ];

    private const PAY_METHOD_MAP = [
        'TM'          => 1,
        'CK'          => 2,
        'TM/CK'       => 3,
        'CHUYENKHOAN' => 2,
    ];

    public function __construct()
    {
        $this->wsUrl = defined('BKAV_ENV') && BKAV_ENV === 'production'
            ? 'https://ws.ehoadon.vn/WSPublicEhoadon.asmx'
            : 'https://wsdemo.ehoadon.vn/WSPublicEhoadon.asmx';

        $this->partnerGuid = BKAV_PARTNER_GUID;

        $aesKey = base64_decode(BKAV_AES_KEY);
        $aesIv  = base64_decode(BKAV_AES_IV);

        if (strlen($aesKey) !== 32) {
            throw new RuntimeException('BKAV_AES_KEY phải là 32 bytes sau base64_decode, hiện: ' . strlen($aesKey));
        }
        if (strlen($aesIv) !== 16) {
            throw new RuntimeException('BKAV_AES_IV phải là 16 bytes sau base64_decode, hiện: ' . strlen($aesIv));
        }

        $this->aesKey = $aesKey;
        $this->aesIv  = $aesIv;
    }

    // ──────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────

    public function createInvoice(array $invoicePayload): array
    {
        $raw = $this->execCommand([
            'CmdType'       => 100,
            'CommandObject' => [$invoicePayload],
        ]);
        return $this->unwrapResponse($raw);
    }

    public function getInvoice(int $partnerInvoiceId): array
    {
        $raw = $this->execCommand([
            'CmdType'       => 800,
            'CommandObject' => [['PartnerInvoiceID' => $partnerInvoiceId]],
        ]);
        return $this->unwrapResponse($raw);
    }

    public function cancelInvoice(int $partnerInvoiceId, string $reason): array
    {
        $raw = $this->execCommand([
            'CmdType'       => 202,
            'CommandObject' => [[
                'PartnerInvoiceID' => $partnerInvoiceId,
                'Reason'           => $reason,
            ]],
        ]);
        return $this->unwrapResponse($raw);
    }

    // ──────────────────────────────────────────────────────────────
    // BUILD INVOICE PAYLOAD
    // ──────────────────────────────────────────────────────────────

    public function buildInvoicePayload(
        array  $inv,
        array  $items,
        int    $invoiceId,
        string $invoiceDate   = '',
        string $paymentMethod = 'Chuyển khoản',
        string $note          = ''
    ): array {
        if (empty($invoiceDate)) {
            $invoiceDate = $inv['invoice_date'] ?? date('Y-m-d');
        }

        // Validate bắt buộc
        if (empty(trim($inv['tax_code'] ?? ''))) {
            throw new RuntimeException('Khách hàng chưa có Mã số thuế (BuyerTaxCode). Vui lòng cập nhật trước khi xuất VAT.');
        }

        $vatRate   = (float)($inv['vat_rate'] ?? 0);
        $vatPct    = (int)round($vatRate);
        $taxRateId = self::TAX_RATE_MAP[$vatPct] ?? 9;

        $payKey      = strtoupper(preg_replace('/\s+/', '', trim($paymentMethod)));
        $payMethodId = self::PAY_METHOD_MAP[$payKey] ?? 3;

        // Dòng ghi chú đầu HĐ
        $lineItems = [];
        if (!empty($note)) {
            $lineItems[] = $this->buildDescItem($note);
        }

        // Dòng sản phẩm + tính tổng tiền
        $grandTotal  = 0;
        $totalTaxAmt = 0;

        foreach ($items as $it) {
            $qty       = (float)($it['quantity']   ?? 1);
            $unitPrice = (float)($it['unit_price'] ?? 0);
            $amount    = (int)round($unitPrice * $qty);
            $vatAmt    = (int)round($amount * $vatPct / 100);

            $grandTotal  += $amount;
            $totalTaxAmt += $vatAmt;

            $lineItems[] = [
                'ItemTypeID'        => self::ITEM_HANG_HOA,
                'ItemName'          => $this->cleanStr((string)($it['description'] ?? ''), 150),
                'UnitName'          => $this->cleanStr((string)($it['unit'] ?? 'Cái'), 20),
                'Qty'               => $qty,
                'Price'             => $unitPrice,
                'Amount'            => $amount,
                'TaxRateID'         => $taxRateId,
                'TaxAmount'         => $vatAmt,
                'IsDiscount'        => false,
                'UserDefineDetails' => '',
            ];
        }

        $grandWithTax = $grandTotal + $totalTaxAmt;
        $invoiceNo    = $this->cleanStr((string)($inv['invoice_no'] ?? ''), 50);
        $issuedAt     = date('Y-m-d\TH:i:s', strtotime($invoiceDate));

        $rawEmail      = trim((string)($inv['customer_email'] ?? ''));
        $emailList     = [];
        if ($rawEmail !== '') {
            $emailList = array_filter(
                array_map('trim', explode(';', $rawEmail)),
                fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
            );
        }
        $receiverEmail = implode(';', $emailList);
        $receiveTypeId = !empty($receiverEmail) ? 3 : 1;

        return [
            'Invoice' => [
                'InvoiceTypeID'         => (int)(defined('BKAV_INVOICE_TYPE') ? BKAV_INVOICE_TYPE : 1),
                'InvoiceDate'           => $issuedAt,
                'InvoiceNo'             => 0,
                'InvoiceForm'           => defined('BKAV_INVOICE_TEMPLATE') ? BKAV_INVOICE_TEMPLATE : '',
                'InvoiceSerial'         => defined('BKAV_INVOICE_SERIAL')   ? BKAV_INVOICE_SERIAL   : '',
                'InvoiceStatusID'       => 1,
                'SignedDate'            => $issuedAt,
                'BuyerName'             => $this->cleanStr((string)($inv['customer_name'] ?? ''), 120),
                'BuyerTaxCode'          => $this->cleanStr((string)($inv['tax_code']      ?? ''), 50),
                'BuyerUnitName'         => $this->cleanStr((string)($inv['customer_name'] ?? ''), 120),
                'BuyerAddress'          => $this->cleanStr((string)($inv['address']       ?? ''), 200),
                'BuyerBankAccount'      => '',
                'PayMethodID'           => $payMethodId,
                'ReceiveTypeID'         => $receiveTypeId,
                'ReceiverEmail'         => $receiverEmail,
                'ReceiverMobile'        => '',
                'ReceiverName'          => $this->cleanStr((string)($inv['customer_name'] ?? ''), 120),
                'ReceiverAddress'       => $this->cleanStr((string)($inv['address']       ?? ''), 200),
                'Note'                  => $invoiceNo,
                'BillCode'              => $invoiceNo,
                'CurrencyID'            => 'VND',
                'ExchangeRate'          => 1.0,
                'MaCuaCQT'              => '',
                'isBTH'                 => 'false',
                'UserDefine'            => '',
                // Gửi explicit tổng tiền — BKAV không cần tự tính
                'TotalAmountWithoutVAT' => $grandTotal,
                'TotalVATAmount'        => $totalTaxAmt,
                'TotalAmount'           => $grandWithTax,
                'TotalAmountInWords'    => $this->numberToWordsVN($grandWithTax),
            ],
            'ListInvoiceDetailsWS'    => $lineItems,
            'ListInvoiceAttachFileWS' => [],
            'PartnerInvoiceID'        => $invoiceId,
            'PartnerInvoiceStringID'  => $invoiceNo ?: (string)$invoiceId,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // isSuccess / getErrorMessage
    // ──────────────────────────────────────────────────────────────

    public function isSuccess(array $result): bool
    {
        if (empty($result)) return false;
        if (!empty($result['_wrapper_isErr'])) return false;
        $guid = $result['InvoiceGUID'] ?? '';
        if (!empty($guid) && $guid !== '00000000-0000-0000-0000-000000000000') return true;
        if (array_key_exists('Status', $result)) return intval($result['Status']) === 0;
        return false;
    }

    public function getErrorMessage(array $result): string
    {
        $messLog = trim($result['MessLog'] ?? '');
        if ($messLog !== '' && $messLog !== 'null') return $messLog;
        if (!empty($result['Description'])) return $result['Description'];
        if (!empty($result['Message']))     return $result['Message'];
        if (!empty($result['Object']) && is_string($result['Object'])) return $result['Object'];
        $display = array_filter($result, fn($k) => !str_starts_with((string)$k, '_'), ARRAY_FILTER_USE_KEY);
        return json_encode($display, JSON_UNESCAPED_UNICODE);
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE — helpers
    // ──────────────────────────────────────────────────────────────

    private function buildDescItem(string $text): array
    {
        return [
            'ItemTypeID'        => self::ITEM_GHI_CHU,
            'ItemName'          => $text,
            'UnitName'          => '',
            'Qty'               => 0,
            'Price'             => 0,
            'Amount'            => 0,
            'TaxRateID'         => 5,
            'TaxAmount'         => 0,
            'IsDiscount'        => false,
            'UserDefineDetails' => '',
        ];
    }

    /**
     * Làm sạch chuỗi: loại control chars, normalize whitespace, giới hạn độ dài.
     */
    private function cleanStr(string $s, int $max): string
    {
        $s = preg_replace('/[\x00-\x1F\x7F]+/u', '', trim($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return mb_substr($s, 0, $max, 'UTF-8');
    }

    /**
     * Đọc số tiền thành chữ tiếng Việt.
     */
    private function numberToWordsVN(float $amount): string
    {
        $amount = (int)round($amount);
        if ($amount === 0) return 'Không đồng';
        $ones  = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm',
                  'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
        $group = function (int $n) use ($ones, $teens): string {
            if (!$n) return '';
            $h = intdiv($n, 100); $t = intdiv($n % 100, 10); $u = $n % 10;
            $s = $h ? $ones[$h] . ' trăm' : '';
            if ($t === 1)         $s .= ($s ? ' ' : '') . $teens[$u];
            elseif ($t > 1) {
                $s .= ($s ? ' ' : '') . $ones[$t] . ' mươi';
                if ($u === 1)     $s .= ' mốt';
                elseif ($u === 5) $s .= ' lăm';
                elseif ($u)       $s .= ' ' . $ones[$u];
            } elseif ($h && $u)  $s .= ' lẻ ' . $ones[$u];
            elseif ($u)          $s .= ($s ? ' ' : '') . $ones[$u];
            return trim($s);
        };
        $parts = [];
        if ($b = intdiv($amount, 1_000_000_000))              $parts[] = $group($b) . ' tỷ';
        if ($m = intdiv($amount % 1_000_000_000, 1_000_000))  $parts[] = $group($m) . ' triệu';
        if ($k = intdiv($amount % 1_000_000, 1_000))          $parts[] = $group($k) . ' nghìn';
        if ($r = $amount % 1_000)                             $parts[] = $group($r);
        return ucfirst(implode(' ', $parts)) . ' đồng';
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE — crypto & transport
    // ──────────────────────────────────────────────────────────────

    private function encryptPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $gz   = gzencode($json, 1);
        $enc  = openssl_encrypt($gz, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
        if ($enc === false) {
            throw new RuntimeException('AES encrypt thất bại: ' . openssl_error_string());
        }
        return base64_encode($enc);
    }

    private function decryptResponse(string $base64Data): string
    {
        // base64_decode trực tiếp từ chuỗi BKAV trả về — không encode lại
        $raw = base64_decode($base64Data, true);
        if ($raw === false) {
            throw new RuntimeException('base64_decode response thất bại.');
        }
        $dec = openssl_decrypt($raw, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
        if ($dec === false) {
            throw new RuntimeException('Giải mã AES thất bại. Kiểm tra BKAV_AES_KEY và BKAV_AES_IV.');
        }
        $ungz = @gzdecode($dec);
        return $ungz !== false ? $ungz : $dec;
    }

    private function execCommand(array $payload): array
    {
        $encrypted = $this->encryptPayload($payload);
        $soapXml   =
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
            . ' xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<ExecCommand xmlns="http://tempuri.org/">'
            . '<partnerGUID>' . htmlspecialchars($this->partnerGuid, ENT_XML1) . '</partnerGUID>'
            . '<CommandData>' . htmlspecialchars($encrypted, ENT_XML1) . '</CommandData>'
            . '</ExecCommand>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $ch = curl_init($this->wsUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $soapXml,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tempuri.org/ExecCommand"',
                'Content-Length: ' . strlen($soapXml),
            ],
            CURLOPT_CONNECTTIMEOUT => (int)(defined('BKAV_TIMEOUT_CONNECT') ? BKAV_TIMEOUT_CONNECT : 10),
            CURLOPT_TIMEOUT        => (int)(defined('BKAV_TIMEOUT_READ')    ? BKAV_TIMEOUT_READ    : 60),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) throw new RuntimeException('cURL Error: ' . $curlErr);
        if ($httpCode !== 200) {
            throw new RuntimeException("BKAV HTTP {$httpCode}: " . substr((string)$response, 0, 500));
        }

        return $this->parseResponse((string)$response);
    }

    private function parseResponse(string $soapResponse): array
    {
        if (!preg_match('/<ExecCommandResult[^>]*>(.*?)<\/ExecCommandResult>/s', $soapResponse, $m)) {
            if (preg_match('/<faultstring>(.*?)<\/faultstring>/s', $soapResponse, $f)) {
                throw new RuntimeException('SOAP Fault: ' . strip_tags($f[1]));
            }
            throw new RuntimeException('Không parse được SOAP response. Raw(500): ' . substr($soapResponse, 0, 500));
        }

        $resultText = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');

        // ── Pattern lỗi plain-text từ BKAV ──
        if (str_starts_with($resultText, '[MessageForUser]')) {
            $msg = trim(str_replace('[MessageForUser]', '', $resultText));
            throw new RuntimeException('eHoaDon: ' . $msg);
        }
        // FIX: escape \| để khớp literal "[!|...|!]", không phải alternation
        if (preg_match('/\[!\|.*?\|!\]/u', $resultText) || str_contains($resultText, 'Có lỗi xảy ra')) {
            $cleanMsg = preg_replace('/\s*\[!?\|[^\]]*\|?!\]\s*/u', '', $resultText);
            $cleanMsg = preg_replace('/\s*\[#\d+\]\s*/u', '', $cleanMsg);
            throw new RuntimeException('BKAV server lỗi: ' . trim($cleanMsg ?: $resultText));
        }
        if (empty($resultText)) {
            throw new RuntimeException('eHoaDon trả về response rỗng.');
        }

        // JSON thuần?
        if (str_starts_with($resultText, '{') || str_starts_with($resultText, '[')) {
            $decoded = json_decode($resultText, true);
            if ($decoded !== null) return $decoded;
        }

        // Base64 → AES-256-CBC → gzip → JSON
        $decrypted = $this->decryptResponse($resultText);
        $result    = json_decode($decrypted, true);
        if ($result === null) {
            throw new RuntimeException('JSON decode thất bại sau decrypt. Raw(200): ' . substr($decrypted, 0, 200));
        }
        return $result;
    }

    private function unwrapResponse(array $raw): array
    {
        if (!empty($raw['isError'])) {
            if (isset($raw['Object']) && !empty($raw['Object'])) {
                $obj = $raw['Object'];
                if (is_string($obj)) {
                    $dec = json_decode($obj, true);
                    if ($dec !== null) {
                        $item = is_array($dec) && isset($dec[0]) ? $dec[0] : $dec;
                        $item['_wrapper_isErr'] = true;
                        return $item;
                    }
                }
            }
            return $raw;
        }

        if (!isset($raw['Object'])) return $raw;

        $obj = $raw['Object'];
        if (is_string($obj) && $obj !== '') {
            $dec = json_decode($obj, true);
            if ($dec !== null) $obj = $dec;
        }

        if (is_array($obj) && isset($obj[0]) && is_array($obj[0])) {
            $item = $obj[0];
        } elseif (is_array($obj) && !empty($obj)) {
            $item = $obj;
        } else {
            return array_merge($raw, ['_object_raw' => $obj, '_unwrap_failed' => true]);
        }

        $item['_wrapper_status'] = $raw['Status']  ?? null;
        $item['_wrapper_isOk']   = $raw['isOk']    ?? null;
        $item['_wrapper_isErr']  = $raw['isError']  ?? null;
        return $item;
    }
}
