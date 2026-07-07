<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';

requireRole('director', 'accountant', 'manager');

$pdo  = getDBConnection();
$user = currentUser();

// Tự tạo bảng nếu chưa có (safe bootstrap, theo chuẩn dự án)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `attendance_department_policies` (
            `id`             INT           NOT NULL AUTO_INCREMENT,
            `department_id`  INT           NOT NULL,
            `policy_name`    VARCHAR(100)  NOT NULL DEFAULT '',
            `location_mode`  ENUM('strict','flexible') NOT NULL DEFAULT 'flexible'
                             COMMENT 'strict=bắt buộc trong bán kính; flexible=cho phép ngoài phạm vi',
            `latitude`       DECIMAL(10,8) NULL DEFAULT NULL,
            `longitude`      DECIMAL(11,8) NULL DEFAULT NULL,
            `radius_meters`  INT           NULL DEFAULT NULL,
            `gps_required`   TINYINT(1)    NOT NULL DEFAULT 1,
            `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
            `updated_by`     INT           NULL DEFAULT NULL,
            `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_dept_policy` (`department_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Throwable $e) { /* Bảng đã tồn tại */ }

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $postAction    = $_POST['post_action'] ?? 'save';
    $department_id = (int)($_POST['department_id'] ?? 0);

    if ($postAction === 'delete') {
        if ($department_id > 0) {
            try {
                $pdo->prepare("DELETE FROM attendance_department_policies WHERE department_id = ?")
                    ->execute([$department_id]);
                setFlash('success', '✅ Đã xoá policy phòng ban.');
            } catch (Throwable $e) {
                setFlash('danger', '❌ Lỗi xoá: ' . $e->getMessage());
            }
        }
        header('Location: /erp/modules/attendance/department_location_policy.php');
        exit();
    }

    // save
    $policy_name   = trim($_POST['policy_name'] ?? '');
    $location_mode = in_array($_POST['location_mode'] ?? '', ['strict','flexible']) ? $_POST['location_mode'] : 'flexible';
    $gps_required  = isset($_POST['gps_required']) ? 1 : 0;
    $is_active     = isset($_POST['is_active']) ? 1 : 0;

    // Tọa độ riêng cho phòng ban (nullable)
    $latitude      = ($_POST['latitude']  ?? '') !== '' ? (float)$_POST['latitude']  : null;
    $longitude     = ($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null;
    $radius_meters = ($_POST['radius_meters'] ?? '') !== '' ? max(50, min(5000, (int)$_POST['radius_meters'])) : null;

    if ($department_id <= 0) {
        setFlash('danger', '❌ Vui lòng chọn phòng ban.');
        header('Location: /erp/modules/attendance/department_location_policy.php');
        exit();
    }

    try {
        $existing = $pdo->prepare("SELECT id FROM attendance_department_policies WHERE department_id = ?");
        $existing->execute([$department_id]);
        if ($existing->fetch()) {
            $pdo->prepare("
                UPDATE attendance_department_policies
                SET policy_name=?, location_mode=?, latitude=?, longitude=?,
                    radius_meters=?, gps_required=?, is_active=?, updated_by=?
                WHERE department_id=?
            ")->execute([$policy_name, $location_mode, $latitude, $longitude, $radius_meters, $gps_required, $is_active, $user['id'], $department_id]);
        } else {
            $pdo->prepare("
                INSERT INTO attendance_department_policies
                    (department_id, policy_name, location_mode, latitude, longitude, radius_meters, gps_required, is_active, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$department_id, $policy_name, $location_mode, $latitude, $longitude, $radius_meters, $gps_required, $is_active, $user['id']]);
        }
        setFlash('success', '✅ Đã lưu policy vị trí chấm công cho phòng ban.');
    } catch (Throwable $e) {
        setFlash('danger', '❌ Lỗi lưu: ' . $e->getMessage());
    }
    header('Location: /erp/modules/attendance/department_location_policy.php');
    exit();
}

// Lấy danh sách phòng ban
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Lấy policy hiện có theo phòng ban
$policiesRaw = [];
try {
    $rows = $pdo->query("SELECT * FROM attendance_department_policies ORDER BY department_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $policiesRaw[$r['department_id']] = $r;
} catch (Throwable $e) { /* ignore */ }

// Lấy cài đặt global để hiện thị fallback
$globalSetting = null;
try {
    $globalSetting = $pdo->query("SELECT * FROM attendance_location_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

// Phòng ban đang chỉnh sửa (nếu có ?edit=id)
$editDeptId = (int)($_GET['edit'] ?? 0);
$editPolicy = $editDeptId > 0 ? ($policiesRaw[$editDeptId] ?? null) : null;

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
            <h4 class="mb-1">🏢 Policy Vị trí Chấm công theo Phòng ban</h4>
            <p class="text-muted mb-0 small">
                Cấu hình riêng cho từng phòng ban. Nếu phòng ban chưa có cấu hình, hệ thống dùng
                <a href="/erp/modules/attendance/location_settings.php">cài đặt global</a>.
            </p>
        </div>
        <a href="/erp/modules/attendance/location_settings.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Cài đặt vị trí global
        </a>
    </div>

    <?php showFlash(); ?>

    <?php if ($globalSetting): ?>
    <div class="alert alert-info py-2 mb-4 small">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Cấu hình global hiện tại:</strong>
        <?= htmlspecialchars($globalSetting['location_name']) ?> —
        <?= $globalSetting['is_enabled'] ? '<span class="text-success fw-bold">BẬT</span>' : '<span class="text-danger fw-bold">TẮT</span>' ?>,
        bán kính <?= (int)$globalSetting['radius_meters'] ?>m.
        Phòng ban không có policy riêng sẽ theo cấu hình này.
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Form thêm/sửa policy -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold bg-primary text-white">
                    <?= $editPolicy ? '✏️ Chỉnh sửa policy phòng ban' : '➕ Thêm policy mới' ?>
                </div>
                <div class="card-body">
                    <form method="POST" id="policyForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="post_action" value="save">

                        <!-- Phòng ban -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">🏢 Phòng ban</label>
                            <select name="department_id" class="form-select" required <?= $editPolicy ? 'disabled' : '' ?>>
                                <option value="">— Chọn phòng ban —</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"
                                    <?= ($editPolicy && (int)$editPolicy['department_id'] === (int)$dept['id']) ? 'selected' : '' ?>
                                    <?= (!$editPolicy && isset($policiesRaw[$dept['id']])) ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                    <?= (!$editPolicy && isset($policiesRaw[$dept['id']])) ? ' (đã có policy)' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($editPolicy): ?>
                            <input type="hidden" name="department_id" value="<?= (int)$editPolicy['department_id'] ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Tên policy -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📌 Tên / Mô tả policy</label>
                            <input type="text" name="policy_name" class="form-control" maxlength="100"
                                   placeholder="VD: Nhà máy bắt buộc, Kinh doanh linh hoạt..."
                                   value="<?= htmlspecialchars($editPolicy['policy_name'] ?? '') ?>">
                        </div>

                        <!-- Chế độ vị trí -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📍 Chế độ vị trí</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location_mode" id="modeStrict"
                                           value="strict" <?= (!$editPolicy || $editPolicy['location_mode'] === 'strict') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="modeStrict">
                                        🔒 <strong>Strict</strong> — Bắt buộc trong bán kính
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="location_mode" id="modeFlexible"
                                           value="flexible" <?= ($editPolicy && $editPolicy['location_mode'] === 'flexible') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="modeFlexible">
                                        🔓 <strong>Flexible</strong> — Cho phép ngoài phạm vi
                                    </label>
                                </div>
                            </div>
                            <div class="form-text text-muted">
                                <strong>Strict</strong>: nhân viên phòng này bị từ chối nếu ngoài bán kính.<br>
                                <strong>Flexible</strong>: cho phép chấm công từ bất kỳ đâu (ghi nhận vị trí để theo dõi).
                            </div>
                        </div>

                        <!-- Tọa độ riêng (tuỳ chọn) -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <p class="fw-semibold small mb-1">🗺️ Tọa độ riêng <span class="fw-normal text-muted">(bỏ trống = dùng tọa độ global)</span></p>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Latitude</label>
                                    <input type="number" name="latitude" id="inputLat" class="form-control form-control-sm"
                                           value="<?= $editPolicy['latitude'] ?? '' ?>"
                                           step="0.00000001" min="-90" max="90" placeholder="Để trống = global">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Longitude</label>
                                    <input type="number" name="longitude" id="inputLng" class="form-control form-control-sm"
                                           value="<?= $editPolicy['longitude'] ?? '' ?>"
                                           step="0.00000001" min="-180" max="180" placeholder="Để trống = global">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Bán kính (mét, để trống = global)</label>
                                <input type="number" name="radius_meters" class="form-control form-control-sm"
                                       value="<?= $editPolicy['radius_meters'] ?? '' ?>"
                                       min="50" max="5000" step="50" placeholder="Để trống = global">
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnGetDeptLocation">
                                <i class="fas fa-map-marker-alt me-1"></i>Lấy vị trí hiện tại
                            </button>
                            <div id="deptGpsStatus" class="mt-2 d-none small"></div>
                        </div>

                        <!-- GPS required -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="gps_required" id="gpsRequired"
                                       <?= (!$editPolicy || $editPolicy['gps_required']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="gpsRequired">
                                    📡 Bắt buộc GPS khi chấm công
                                </label>
                            </div>
                        </div>

                        <!-- is_active -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       <?= (!$editPolicy || $editPolicy['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">
                                    ✅ Policy đang hoạt động
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-fill fw-bold">
                                <i class="fas fa-save me-2"></i>Lưu policy
                            </button>
                            <?php if ($editPolicy): ?>
                            <a href="/erp/modules/attendance/department_location_policy.php"
                               class="btn btn-outline-secondary">Huỷ</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bản đồ nhỏ -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white fw-bold small">🗺️ Xem trước vị trí</div>
                <div class="card-body p-0">
                    <div id="deptMap" style="height:250px; border-radius:0 0 8px 8px;"></div>
                </div>
            </div>
        </div>

        <!-- Danh sách policy hiện có -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold bg-white">
                    📋 Danh sách policy theo phòng ban
                    <span class="badge bg-secondary ms-2"><?= count($policiesRaw) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($policiesRaw)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        Chưa có policy nào. Tất cả phòng ban đang dùng cấu hình global.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng ban</th>
                                    <th>Chế độ</th>
                                    <th>Tọa độ riêng</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $deptNameMap = [];
                            foreach ($departments as $d) $deptNameMap[$d['id']] = $d['name'];
                            foreach ($policiesRaw as $deptId => $policy):
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($deptNameMap[$deptId] ?? 'PB #' . $deptId) ?></div>
                                    <?php if ($policy['policy_name']): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($policy['policy_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($policy['location_mode'] === 'strict'): ?>
                                    <span class="badge bg-danger">🔒 Strict</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">🔓 Flexible</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?php if ($policy['latitude'] && $policy['longitude']): ?>
                                        <?= number_format((float)$policy['latitude'], 5) ?>,
                                        <?= number_format((float)$policy['longitude'], 5) ?>
                                        <?php if ($policy['radius_meters']): ?>
                                        <br><?= (int)$policy['radius_meters'] ?>m
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Dùng global</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($policy['is_active']): ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Tắt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$deptId ?>"
                                       class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Xoá policy phòng ban này?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="post_action" value="delete">
                                        <input type="hidden" name="department_id" value="<?= (int)$deptId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hướng dẫn -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body py-3">
                    <p class="fw-bold small mb-2">ℹ️ Hướng dẫn cấu hình</p>
                    <ul class="small text-muted mb-0">
                        <li><strong>Strict</strong>: nhân viên phòng ban này <em>bắt buộc</em> phải ở trong bán kính mới chấm được công. Ví dụ: Phòng Sản xuất phải chấm tại nhà máy.</li>
                        <li><strong>Flexible</strong>: cho phép chấm công từ bất kỳ đâu, vị trí vẫn được ghi nhận để theo dõi. Ví dụ: Phòng Kinh doanh, Kế toán.</li>
                        <li>Bỏ trống <em>tọa độ riêng</em> → phòng ban dùng tọa độ công ty toàn cục.</li>
                        <li>Phòng ban chưa có policy → dùng cài đặt global (<a href="/erp/modules/attendance/location_settings.php">xem ở đây</a>).</li>
                        <li>Tắt policy (is_active=false) → fallback về global, policy không bị xoá.</li>
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
const initLat = <?= (float)(($editPolicy ? $editPolicy['latitude'] : null) ?? ($globalSetting ? $globalSetting['latitude'] : 10.7769) ?? 10.7769) ?>;
const initLng = <?= (float)(($editPolicy ? $editPolicy['longitude'] : null) ?? ($globalSetting ? $globalSetting['longitude'] : 106.7009) ?? 106.7009) ?>;
const initR   = <?= (int)(($editPolicy ? $editPolicy['radius_meters'] : null) ?? ($globalSetting ? $globalSetting['radius_meters'] : 200) ?? 200) ?>;

const map = L.map('deptMap').setView([initLat, initLng], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

let marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
let circle = L.circle([initLat, initLng], { radius: initR, color: '#0d6efd', fillOpacity: 0.1 }).addTo(map);

marker.on('dragend', function() {
    const p = marker.getLatLng();
    document.getElementById('inputLat').value = p.lat.toFixed(8);
    document.getElementById('inputLng').value = p.lng.toFixed(8);
    circle.setLatLng(p);
});

function updateMapFromInputs() {
    const la = parseFloat(document.getElementById('inputLat').value);
    const ln = parseFloat(document.getElementById('inputLng').value);
    if (!isNaN(la) && !isNaN(ln)) {
        marker.setLatLng([la, ln]);
        circle.setLatLng([la, ln]);
        map.setView([la, ln], 15);
    }
}
document.getElementById('inputLat').addEventListener('change', updateMapFromInputs);
document.getElementById('inputLng').addEventListener('change', updateMapFromInputs);

document.getElementById('btnGetDeptLocation').addEventListener('click', function() {
    const st = document.getElementById('deptGpsStatus');
    st.className = 'mt-2 small text-warning';
    st.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lấy vị trí...';
    st.classList.remove('d-none');
    if (!navigator.geolocation) {
        st.className = 'mt-2 small text-danger';
        st.innerHTML = 'Trình duyệt không hỗ trợ định vị.';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const la = pos.coords.latitude, ln = pos.coords.longitude;
            document.getElementById('inputLat').value = la.toFixed(8);
            document.getElementById('inputLng').value = ln.toFixed(8);
            marker.setLatLng([la, ln]);
            circle.setLatLng([la, ln]);
            map.setView([la, ln], 16);
            st.className = 'mt-2 small text-success';
            st.innerHTML = `✅ ${la.toFixed(6)}, ${ln.toFixed(6)} (±${Math.round(pos.coords.accuracy)}m)`;
        },
        function(err) {
            st.className = 'mt-2 small text-danger';
            st.innerHTML = '❌ Không lấy được vị trí.';
        },
        { timeout: 10000, enableHighAccuracy: true }
    );
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
