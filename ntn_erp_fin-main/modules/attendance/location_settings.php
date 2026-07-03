<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';

requireRole('director', 'accountant', 'manager');

$pdo  = getDBConnection();
$user = currentUser();

// Tự tạo bảng nếu chưa có
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance_location_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            location_name VARCHAR(100) NOT NULL DEFAULT 'Công ty',
            latitude DECIMAL(10,8) NOT NULL DEFAULT 0,
            longitude DECIMAL(11,8) NOT NULL DEFAULT 0,
            radius_meters INT NOT NULL DEFAULT 200,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            updated_by INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (Throwable $e) { /* Bảng đã tồn tại */ }

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $location_name = trim($_POST['location_name'] ?? 'Công ty');
    $latitude      = (float)($_POST['latitude']  ?? 0);
    $longitude     = (float)($_POST['longitude'] ?? 0);
    $radius_meters = max(50, min(2000, (int)($_POST['radius_meters'] ?? 200)));
    $is_enabled    = isset($_POST['is_enabled']) ? 1 : 0;

    if (empty($location_name)) $location_name = 'Công ty';

    try {
        $existing = $pdo->query("SELECT id FROM attendance_location_settings LIMIT 1")->fetch();
        if ($existing) {
            $pdo->prepare("
                UPDATE attendance_location_settings
                SET location_name=?, latitude=?, longitude=?, radius_meters=?, is_enabled=?, updated_by=?
                WHERE id=?
            ")->execute([$location_name, $latitude, $longitude, $radius_meters, $is_enabled, $user['id'], $existing['id']]);
        } else {
            $pdo->prepare("
                INSERT INTO attendance_location_settings
                    (location_name, latitude, longitude, radius_meters, is_enabled, updated_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$location_name, $latitude, $longitude, $radius_meters, $is_enabled, $user['id']]);
        }
        setFlash('success', '✅ Đã lưu cài đặt vị trí chấm công.');
    } catch (Throwable $e) {
        setFlash('danger', '❌ Lỗi lưu cài đặt: ' . $e->getMessage());
    }
    header('Location: /erp/modules/attendance/location_settings.php');
    exit();
}

// Lấy cài đặt hiện tại
$setting = null;
try {
    $setting = $pdo->query("SELECT * FROM attendance_location_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="main-content">
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">📍 Cài đặt Vị trí Chấm công</h4>
            <p class="text-muted mb-0 small">Xác định vị trí công ty và bán kính cho phép chấm công</p>
        </div>
        <a href="/erp/modules/attendance/shift_setup.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Quay lại Setup ca
        </a>
    </div>

    <?php showFlash(); ?>

    <div class="row g-4">
        <!-- Form cài đặt -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold bg-primary text-white">
                    ⚙️ Cấu hình vị trí công ty
                </div>
                <div class="card-body">
                    <form method="POST" id="locationForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <!-- Toggle bật/tắt -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded"
                                 style="background: <?= ($setting && $setting['is_enabled']) ? '#d1fae5' : '#fee2e2' ?>; border: 1px solid <?= ($setting && $setting['is_enabled']) ? '#6ee7b7' : '#fca5a5' ?>;" id="toggleBg">
                                <div>
                                    <div class="fw-bold" id="toggleLabel">
                                        <?= ($setting && $setting['is_enabled']) ? '✅ Tính năng đang BẬT' : '⛔ Tính năng đang TẮT' ?>
                                    </div>
                                    <div class="small text-muted">Khi bật, nhân viên chỉ chấm công được trong bán kính cho phép</div>
                                </div>
                                <div class="form-check form-switch ms-3 mb-0" style="transform: scale(1.5); transform-origin: right center;">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" id="isEnabled"
                                           role="switch" <?= ($setting && $setting['is_enabled']) ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>

                        <!-- Tên địa điểm -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">🏢 Tên địa điểm</label>
                            <input type="text" name="location_name" class="form-control"
                                   value="<?= htmlspecialchars($setting['location_name'] ?? 'Công ty') ?>"
                                   placeholder="VD: Văn phòng Công ty ABC" maxlength="100">
                        </div>

                        <!-- Tọa độ -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <p class="fw-semibold small mb-2">🗺️ Tọa độ vị trí</p>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Latitude (Vĩ độ)</label>
                                    <input type="number" name="latitude" id="inputLatitude" class="form-control form-control-sm"
                                           value="<?= $setting['latitude'] ?? 0 ?>"
                                           step="0.00000001" min="-90" max="90">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Longitude (Kinh độ)</label>
                                    <input type="number" name="longitude" id="inputLongitude" class="form-control form-control-sm"
                                           value="<?= $setting['longitude'] ?? 0 ?>"
                                           step="0.00000001" min="-180" max="180">
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnGetLocation">
                                <i class="fas fa-map-marker-alt me-2"></i>📍 Lấy vị trí hiện tại
                            </button>
                            <div id="gpsGetStatus" class="mt-2 d-none small"></div>
                        </div>

                        <!-- Bán kính -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                📐 Bán kính cho phép
                                <span class="text-muted small fw-normal">(<span id="radiusDisplay"><?= $setting['radius_meters'] ?? 200 ?></span> mét)</span>
                            </label>
                            <input type="range" name="radius_meters" id="radiusSlider" class="form-range"
                                   min="50" max="2000" step="50"
                                   value="<?= $setting['radius_meters'] ?? 200 ?>">
                            <div class="d-flex justify-content-between text-muted" style="font-size:11px;">
                                <span>50m</span>
                                <span>500m</span>
                                <span>1000m</span>
                                <span>2000m</span>
                            </div>
                            <div class="form-text">Nhân viên cần ở trong bán kính này mới được chấm công</div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="fas fa-save me-2"></i>Lưu cài đặt
                        </button>
                    </form>

                    <?php if ($setting && $setting['updated_at']): ?>
                    <div class="text-muted small mt-3 text-center">
                        <i class="fas fa-clock me-1"></i>Cập nhật lần cuối: <?= date('d/m/Y H:i', strtotime($setting['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bản đồ preview -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    🗺️ Xem trước vị trí trên bản đồ
                </div>
                <div class="card-body p-0">
                    <div id="locationMap" style="height: 450px; border-radius: 0 0 8px 8px;"></div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body py-3">
                    <p class="fw-bold small mb-2">ℹ️ Hướng dẫn sử dụng</p>
                    <ul class="small text-muted mb-0">
                        <li>Nhấn <strong>Lấy vị trí hiện tại</strong> để tự động điền tọa độ GPS của bạn</li>
                        <li>Hoặc tìm vị trí trên bản đồ, nhấn chuột phải để copy tọa độ</li>
                        <li>Điều chỉnh bán kính phù hợp (đề xuất: 100–300m)</li>
                        <li>Khi <strong>bật tính năng</strong>, nhân viên ngoài bán kính sẽ không thể chấm công</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ── Khởi tạo bản đồ ──
const initLat = <?= (float)($setting['latitude'] ?? 10.7769) ?>;
const initLng = <?= (float)($setting['longitude'] ?? 106.7009) ?>;
const initRadius = <?= (int)($setting['radius_meters'] ?? 200) ?>;

const map = L.map('locationMap').setView([initLat, initLng], 16);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

let marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
let circle = L.circle([initLat, initLng], {
    radius: initRadius,
    color: '#0d6efd',
    fillColor: '#0d6efd',
    fillOpacity: 0.1
}).addTo(map);

// Khi kéo marker
marker.on('dragend', function(e) {
    const pos = marker.getLatLng();
    document.getElementById('inputLatitude').value  = pos.lat.toFixed(8);
    document.getElementById('inputLongitude').value = pos.lng.toFixed(8);
    circle.setLatLng(pos);
});

// Khi thay đổi input tọa độ
function updateMapFromInputs() {
    const lat = parseFloat(document.getElementById('inputLatitude').value);
    const lng = parseFloat(document.getElementById('inputLongitude').value);
    if (!isNaN(lat) && !isNaN(lng)) {
        marker.setLatLng([lat, lng]);
        circle.setLatLng([lat, lng]);
        map.setView([lat, lng], 16);
    }
}
document.getElementById('inputLatitude').addEventListener('change', updateMapFromInputs);
document.getElementById('inputLongitude').addEventListener('change', updateMapFromInputs);

// Khi thay đổi bán kính
const radiusSlider  = document.getElementById('radiusSlider');
const radiusDisplay = document.getElementById('radiusDisplay');
radiusSlider.addEventListener('input', function() {
    radiusDisplay.textContent = this.value;
    circle.setRadius(parseInt(this.value));
});

// Nút lấy vị trí hiện tại
document.getElementById('btnGetLocation').addEventListener('click', function() {
    const statusEl = document.getElementById('gpsGetStatus');
    statusEl.className = 'mt-2 small text-warning';
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lấy vị trí...';
    statusEl.classList.remove('d-none');

    if (!navigator.geolocation) {
        statusEl.className = 'mt-2 small text-danger';
        statusEl.innerHTML = '<i class="fas fa-times me-1"></i>Trình duyệt không hỗ trợ định vị.';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            document.getElementById('inputLatitude').value  = lat.toFixed(8);
            document.getElementById('inputLongitude').value = lng.toFixed(8);
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
            map.setView([lat, lng], 17);
            statusEl.className = 'mt-2 small text-success';
            statusEl.innerHTML = `<i class="fas fa-check-circle me-1"></i>✅ Đã lấy vị trí: ${lat.toFixed(6)}, ${lng.toFixed(6)} (±${Math.round(pos.coords.accuracy)}m)`;
        },
        function(err) {
            const reasons = { 1: 'Từ chối quyền truy cập.', 2: 'Không lấy được GPS.', 3: 'Hết thời gian chờ.' };
            statusEl.className = 'mt-2 small text-danger';
            statusEl.innerHTML = `<i class="fas fa-times me-1"></i>❌ ${reasons[err.code] || 'Lỗi định vị.'}`;
        },
        { timeout: 10000, enableHighAccuracy: true }
    );
});

// Toggle switch UI update
document.getElementById('isEnabled').addEventListener('change', function() {
    const bg   = document.getElementById('toggleBg');
    const lbl  = document.getElementById('toggleLabel');
    if (this.checked) {
        bg.style.background = '#d1fae5';
        bg.style.borderColor = '#6ee7b7';
        lbl.textContent = '✅ Tính năng đang BẬT';
    } else {
        bg.style.background = '#fee2e2';
        bg.style.borderColor = '#fca5a5';
        lbl.textContent = '⛔ Tính năng đang TẮT';
    }
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
