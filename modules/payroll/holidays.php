<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireRole('director', 'accountant');

$pdo  = getDBConnection();
$user = currentUser();

// ── XỬ LÝ FORM ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ.');
        header('Location: /erp/modules/payroll/holidays.php'); exit;
    }

    $action = $_POST['action'] ?? '';

    // ── Thêm ngày lễ ─────────────────────────────────────────────��──────
    if ($action === 'add') {
        $date = $_POST['holiday_date'] ?? '';
        $name = trim($_POST['holiday_name'] ?? '');
        $year = $date ? (int)date('Y', strtotime($date)) : 0;

        if (!$date || !$name) {
            setFlash('danger', 'Vui lòng nhập đầy đủ ngày và tên.');
        } elseif (!strtotime($date)) {
            setFlash('danger', 'Ngày không hợp lệ.');
        } else {
            try {
                $pdo->prepare("
                    INSERT INTO holidays (holiday_date, holiday_name, year, created_by)
                    VALUES (?, ?, ?, ?)
                ")->execute([$date, $name, $year, $user['id']]);
                setFlash('success', "✅ Đã thêm ngày lễ: $name (" . date('d/m/Y', strtotime($date)) . ")");
            } catch (PDOException $e) {
                if ($e->getCode() == 23000)
                    setFlash('danger', '⚠️ Ngày ' . date('d/m/Y', strtotime($date)) . ' đã tồn tại trong danh sách!');
                else
                    setFlash('danger', 'Lỗi: ' . $e->getMessage());
            }
        }
        header('Location: /erp/modules/payroll/holidays.php?year=' . $year); exit;
    }

    // ── Sửa ngày lễ ─────────────────────────────────────────────────────
    if ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $date = $_POST['holiday_date'] ?? '';
        $name = trim($_POST['holiday_name'] ?? '');
        $year = $date ? (int)date('Y', strtotime($date)) : 0;

        if (!$id || !$date || !$name) {
            setFlash('danger', 'Dữ liệu không hợp lệ.');
        } else {
            try {
                $pdo->prepare("
                    UPDATE holidays SET holiday_date=?, holiday_name=?, year=? WHERE id=?
                ")->execute([$date, $name, $year, $id]);
                setFlash('success', '✅ Đã cập nhật ngày lễ!');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000)
                    setFlash('danger', '⚠️ Ngày ' . date('d/m/Y', strtotime($date)) . ' đã tồn tại!');
                else
                    setFlash('danger', 'Lỗi: ' . $e->getMessage());
            }
        }
        header('Location: /erp/modules/payroll/holidays.php?year=' . $year); exit;
    }

    // ── Xóa ngày lễ ─────────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM holidays WHERE id = ?")->execute([$id]);
            setFlash('success', '🗑️ Đã xóa ngày lễ.');
        }
        header('Location: /erp/modules/payroll/holidays.php?year=' . ($_POST['year'] ?? date('Y'))); exit;
    }

    // ── Thêm hàng loạt năm mới ──────────────────────────────────────────
    if ($action === 'bulk_add') {
        $year  = (int)($_POST['bulk_year'] ?? 0);
        $dates = $_POST['bulk_dates'] ?? [];
        $names = $_POST['bulk_names'] ?? [];

        if (!$year || empty($dates)) {
            setFlash('danger', 'Vui lòng nhập đầy đủ thông tin.');
            header('Location: /erp/modules/payroll/holidays.php?year=' . $year); exit;
        }

        $inserted = 0;
        $skipped  = 0;
        foreach ($dates as $i => $date) {
            $name = trim($names[$i] ?? '');
            if (!$date || !$name) continue;
            try {
                $pdo->prepare("INSERT INTO holidays (holiday_date, holiday_name, year, created_by) VALUES (?,?,?,?)")
                    ->execute([$date, $name, $year, $user['id']]);
                $inserted++;
            } catch (PDOException $e) {
                $skipped++;
            }
        }
        setFlash('success', "✅ Đã thêm $inserted ngày lễ" . ($skipped ? ", bỏ qua $skipped ngày trùng" : '') . " cho năm $year.");
        header('Location: /erp/modules/payroll/holidays.php?year=' . $year); exit;
    }
}

// ── Lấy dữ liệu ──────────────────────────────────────────────────────────
$selectedYear = (int)($_GET['year'] ?? date('Y'));
if ($selectedYear < 2020 || $selectedYear > 2100) $selectedYear = (int)date('Y');

$holidays = $pdo->prepare("
    SELECT h.*, u.full_name AS created_name
    FROM holidays h
    LEFT JOIN users u ON h.created_by = u.id
    WHERE h.year = ?
    ORDER BY h.holiday_date ASC
");
$holidays->execute([$selectedYear]);
$holidays = $holidays->fetchAll();

// Thống kê số ngày lễ theo năm
$yearStats = $pdo->query("
    SELECT year, COUNT(*) AS total
    FROM holidays
    GROUP BY year
    ORDER BY year DESC
")->fetchAll();

$csrf = generateCSRF();
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <!-- ── Tiêu đề ── -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="/erp/modules/payroll/index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h4 class="mb-0">🗓️ Quản lý ngày lễ</h4>
            </div>
            <p class="text-muted small mb-0 ms-5">
                Ngày lễ dùng để tính ngày công chuẩn và hệ số OT ngày lễ (300%)
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkModal">
                <i class="fas fa-calendar-plus me-1"></i>Thêm hàng loạt
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>Thêm ngày lễ
            </button>
        </div>
    </div>

    <?php showFlash(); ?>

    <div class="row g-4">

        <!-- ── Cột trái: Thống kê theo năm ── -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold border-0 pt-3">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>Theo năm
                </div>
                <div class="list-group list-group-flush">
                    <?php
                    // Tạo range năm luôn hiển thị dù chưa có data
                    $allYears = [];
                    foreach ($yearStats as $ys) $allYears[$ys['year']] = $ys['total'];
                    for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++) {
                        if (!isset($allYears[$y])) $allYears[$y] = 0;
                    }
                    krsort($allYears);
                    foreach ($allYears as $y => $total):
                    ?>
                    <a href="/erp/modules/payroll/holidays.php?year=<?= $y ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                              <?= $y == $selectedYear ? 'active' : '' ?>">
                        <span class="fw-semibold">Năm <?= $y ?></span>
                        <span class="badge <?= $y == $selectedYear ? 'bg-white text-primary' : 'bg-primary' ?>">
                            <?= $total ?> ngày
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Thông tin tính lương -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white fw-bold border-0 pt-3">
                    <i class="fas fa-info-circle me-2 text-info"></i>Ghi chú
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2">
                        <i class="fas fa-calculator me-1 text-primary"></i>
                        <strong>Ngày công chuẩn</strong> = Ngày trong tháng - Chủ nhật - Ngày lễ
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-percentage me-1 text-danger"></i>
                        <strong>OT ngày lễ</strong> = Lương 1h × 300%
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                        Sau khi thêm/sửa ngày lễ, cần <strong>tính lại lương</strong> cho kỳ liên quan.
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Cột phải: Danh sách ngày lễ ── -->
        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">
                        📅 Danh sách ngày lễ năm <?= $selectedYear ?>
                        <span class="badge bg-primary ms-2"><?= count($holidays) ?> ngày</span>
                    </h6>
                    <?php
                    // Tính ngày công chuẩn sơ bộ cả năm
                    $totalWorkingDays = 0;
                    for ($m = 1; $m <= 12; $m++) {
                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $selectedYear);
                        $from = "$selectedYear-" . str_pad($m,2,'0',STR_PAD_LEFT) . "-01";
                        $to   = "$selectedYear-" . str_pad($m,2,'0',STR_PAD_LEFT) . "-$daysInMonth";
                        $current = new DateTime($from);
                        $end     = new DateTime($to);
                        $holidayDates = array_column($holidays, 'holiday_date');
                        while ($current <= $end) {
                            if ($current->format('N') != 7 && !in_array($current->format('Y-m-d'), $holidayDates))
                                $totalWorkingDays++;
                            $current->modify('+1 day');
                        }
                    }
                    ?>
                    <span class="text-muted small">
                        <i class="fas fa-briefcase me-1"></i>
                        Ước tính <strong><?= $totalWorkingDays ?></strong> ngày công cả năm
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($holidays)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-calendar-times fa-3x mb-3 d-block opacity-25"></i>
                        <p class="mb-2">Chưa có ngày lễ nào cho năm <?= $selectedYear ?></p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-1"></i>Thêm ngày lễ
                        </button>
                    </div>
                    <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th width="140">Ngày</th>
                                <th>Tên ngày lễ</th>
                                <th width="100" class="text-center">Thứ</th>
                                <th width="130" class="text-muted small">Người thêm</th>
                                <th width="100" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($holidays as $i => $h):
                            $dow = date('N', strtotime($h['holiday_date']));
                            $dowNames = ['1'=>'Thứ 2','2'=>'Thứ 3','3'=>'Thứ 4','4'=>'Thứ 5',
                                         '5'=>'Thứ 6','6'=>'Thứ 7','7'=>'Chủ nhật'];
                            $dowClass = $dow == 7 ? 'text-danger fw-bold' : ($dow == 6 ? 'text-warning' : '');
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i + 1 ?></td>
                            <td class="fw-semibold">
                                <?= date('d/m/Y', strtotime($h['holiday_date'])) ?>
                            </td>
                            <td><?= htmlspecialchars($h['holiday_name']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border <?= $dowClass ?>">
                                    <?= $dowNames[$dow] ?? '' ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars($h['created_name'] ?? 'Hệ thống') ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-xs btn-outline-primary"
                                            onclick="editHoliday(<?= htmlspecialchars(json_encode($h)) ?>)"
                                            title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger"
                                            onclick="deleteHoliday(<?= $h['id'] ?>, '<?= htmlspecialchars($h['holiday_name']) ?>', <?= $selectedYear ?>)"
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<!-- ── Modal Thêm ngày lễ ── -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">➕ Thêm ngày lễ</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"     value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">
                                Ngày lễ <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="holiday_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">
                                Tên ngày lễ <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="holiday_name" class="form-control"
                                   placeholder="VD: Tết Dương lịch" required
                                   list="holidayNameSuggestions">
                            <datalist id="holidayNameSuggestions">
                                <option value="Tết Dương lịch">
                                <option value="Nghỉ Tết Nguyên Đán (Mùng 1)">
                                <option value="Nghỉ Tết Nguyên Đán (Mùng 2)">
                                <option value="Nghỉ Tết Nguyên Đán (Mùng 3)">
                                <option value="Nghỉ Tết Nguyên Đán (27 tháng Chạp)">
                                <option value="Nghỉ Tết Nguyên Đán (28 tháng Chạp)">
                                <option value="Nghỉ Tết Nguyên Đán (29 tháng Chạp)">
                                <option value="Giỗ Tổ Hùng Vương">
                                <option value="Ngày Giải phóng miền Nam">
                                <option value="Ngày Quốc tế Lao động">
                                <option value="Ngày Quốc khánh">
                                <option value="Nghỉ bù Quốc khánh">
                            </datalist>
                        </div>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Ngày Chủ nhật <strong>không cần thêm</strong> vào đây vì đã được tự động loại khỏi ngày công chuẩn.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-plus me-2"></i>Thêm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal Sửa ngày lễ ── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">✏️ Sửa ngày lễ</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token"    value="<?= $csrf ?>">
                <input type="hidden" name="action"        value="edit">
                <input type="hidden" name="id"            id="edit_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">
                                Ngày lễ <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="holiday_date" id="edit_date" class="form-control" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">
                                Tên ngày lễ <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="holiday_name" id="edit_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i>Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal Xóa ── -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="action"     value="delete">
    <input type="hidden" name="id"         id="delete_id">
    <input type="hidden" name="year"       value="<?= $selectedYear ?>">
</form>

<!-- ── Modal Thêm hàng loạt ── -->
<div class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">📅 Thêm hàng loạt ngày lễ</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action"     value="bulk_add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Năm <span class="text-danger">*</span></label>
                        <select name="bulk_year" class="form-select w-auto" id="bulkYear">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div id="bulkRows">
                        <!-- Rows sẽ được thêm bằng JS -->
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addBulkRow()">
                        <i class="fas fa-plus me-1"></i>Thêm dòng
                    </button>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i>Lưu tất cả
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.btn-xs { padding: 2px 8px; font-size: 12px; }
</style>

<script>
// ── Sửa ngày lễ ──────────────────────────────────────────────────────────
function editHoliday(h) {
    document.getElementById('edit_id').value   = h.id;
    document.getElementById('edit_date').value = h.holiday_date;
    document.getElementById('edit_name').value = h.holiday_name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ── Xóa ngày lễ ──────────────────────────────────────────────────────────
function deleteHoliday(id, name, year) {
    if (!confirm(`Xóa ngày lễ "${name}"?\nHành động này không thể hoàn tác!`)) return;
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteForm').submit();
}

// ── Bulk: thêm dòng ──────────────────────────────────────────────────────
const bulkSuggestions = [
    { name: 'Tết Dương lịch',                     month: '01', day: '01' },
    { name: 'Nghỉ Tết Nguyên Đán (27 tháng Chạp)', month: '', day: '' },
    { name: 'Nghỉ Tết Nguyên Đán (28 tháng Chạp)', month: '', day: '' },
    { name: 'Nghỉ Tết Nguyên Đán (29 tháng Chạp)', month: '', day: '' },
    { name: 'Nghỉ Tết Nguyên Đán (Mùng 1)',         month: '', day: '' },
    { name: 'Nghỉ Tết Nguyên Đán (Mùng 2)',         month: '', day: '' },
    { name: 'Nghỉ Tết Nguyên Đán (Mùng 3)',         month: '', day: '' },
    { name: 'Giỗ Tổ Hùng Vương',                   month: '04', day: '' },
    { name: 'Ngày Giải phóng miền Nam',             month: '04', day: '30' },
    { name: 'Ngày Quốc tế Lao động',               month: '05', day: '01' },
    { name: 'Ngày Quốc khánh',                     month: '09', day: '02' },
    { name: 'Nghỉ bù Quốc khánh',                  month: '09', day: '' },
];

let bulkRowCount = 0;

function addBulkRow(name = '', date = '') {
    const year  = document.getElementById('bulkYear').value;
    const idx   = bulkRowCount++;
    const row   = document.createElement('div');
    row.className = 'row g-2 mb-2 bulk-row';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="date" name="bulk_dates[]" class="form-control form-control-sm"
                   value="${date}" required>
        </div>
        <div class="col-md-6">
            <input type="text" name="bulk_names[]" class="form-control form-control-sm"
                   placeholder="Tên ngày lễ" value="${name}" required
                   list="holidayNameSuggestions">
        </div>
        <div class="col-md-1 d-flex align-items-center">
            <button type="button" class="btn btn-outline-danger btn-sm"
                    onclick="this.closest('.bulk-row').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    document.getElementById('bulkRows').appendChild(row);
}

// Khi mở modal bulk → tự điền các dòng gợi ý
document.getElementById('bulkModal').addEventListener('show.bs.modal', () => {
    const container = document.getElementById('bulkRows');
    container.innerHTML = '';
    bulkRowCount = 0;
    const year = document.getElementById('bulkYear').value;
    bulkSuggestions.forEach(s => {
        const date = (s.month && s.day) ? `${year}-${s.month}-${s.day}` : '';
        addBulkRow(s.name, date);
    });
});

// Cập nhật năm khi đổi select
document.getElementById('bulkYear')?.addEventListener('change', function() {
    document.querySelectorAll('#bulkRows .bulk-row').forEach(row => {
        const dateInput = row.querySelector('input[type="date"]');
        if (dateInput && dateInput.value) {
            const parts = dateInput.value.split('-');
            if (parts.length === 3) {
                parts[0] = this.value;
                dateInput.value = parts.join('-');
            }
        }
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>