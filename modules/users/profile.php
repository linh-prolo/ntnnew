<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();

$pdo         = getDBConnection();
$currentUser = currentUser();

// ── Xác định user đang xem ───────────────────────────────────────────────
$targetId = isset($_GET['id']) && (int)$_GET['id'] > 0
            ? (int)$_GET['id']
            : (int)$currentUser['id'];

// Nhân viên chỉ được xem hồ sơ của chính mình → redirect thay vì báo lỗi
if (!hasRole('director', 'accountant', 'manager') && $targetId !== (int)$currentUser['id']) {
    header("Location: /erp/modules/users/profile.php?id={$currentUser['id']}");
    exit();
}

// ── Phân quyền ───────────────────────────────────────────────────────────
$isAdmin      = hasRole('director', 'accountant');
$isOwnProfile = ($targetId === (int)$currentUser['id']);

$canEdit       = $isAdmin;                    // Chỉ GĐ/KT mới sửa hồ sơ
$canEditSalary = $isAdmin;                    // Chỉ GĐ/KT mới sửa bảng lương
$canViewSalary = $isAdmin || $isOwnProfile;   // Xem lương: GĐ/KT hoặc chính mình

// ── Lấy thông tin user ───────────────────────────────────────────────────
$targetUser = null;
try {
    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role, r.display_name AS role_name, d.name AS dept_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('profile.php fetch user error: ' . $e->getMessage());
}

if (empty($targetUser)) {
    if ($isOwnProfile) {
        session_destroy();
        header('Location: /erp/login.php?msg=session_error');
        exit();
    }
    setFlash('danger', 'Không tìm thấy tài khoản.');
    $redirect = hasRole('director','accountant','manager')
                ? '/erp/modules/users/index.php'
                : '/erp/dashboard.php';
    header("Location: $redirect");
    exit();
}

// ── Lấy hồ sơ hiện tại ───────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM employee_profiles WHERE user_id = ?");
$stmt->execute([$targetId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$errors = [];

// ── XỬ LÝ FORM ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$canEdit) {
        setFlash('danger', '❌ Bạn không có quyền chỉnh sửa hồ sơ này.');
        header("Location: /erp/modules/users/profile.php?id=$targetId");
        exit();
    }

    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Yêu cầu không hợp lệ.');
        header("Location: /erp/modules/users/profile.php?id=$targetId");
        exit();
    }

    $data = [
        'gender'                  => $_POST['gender']                  ?? 'male',
        'date_of_birth'           => $_POST['date_of_birth']           ?: null,
        'date_joined'             => $_POST['date_joined']             ?: null,
        'ethnicity'               => trim($_POST['ethnicity']          ?? 'Kinh'),
        'marital_status'          => $_POST['marital_status']          ?? 'single',
        'mobile_phone'            => trim($_POST['mobile_phone']       ?? ''),
        'permanent_province'      => $_POST['permanent_province']      ?: null,
        'permanent_district_text' => trim($_POST['permanent_district_text'] ?? ''),
        'permanent_commune_text'  => trim($_POST['permanent_commune_text']  ?? ''),
        'permanent_hamlet'        => trim($_POST['permanent_hamlet']   ?? ''),
        'same_as_permanent'       => isset($_POST['same_as_permanent']) ? 1 : 0,
        'temp_province'           => $_POST['temp_province']           ?: null,
        'temp_district_text'      => trim($_POST['temp_district_text'] ?? ''),
        'temp_commune_text'       => trim($_POST['temp_commune_text']  ?? ''),
        'temp_hamlet'             => trim($_POST['temp_hamlet']        ?? ''),
        'identity_no'             => trim($_POST['identity_no']        ?? ''),
        'identity_issue_date'     => $_POST['identity_issue_date']     ?: null,
        'identity_issue_place'    => trim($_POST['identity_issue_place'] ?? ''),
        'social_book_no'          => trim($_POST['social_book_no']     ?? ''),
        'personal_tax_code'       => trim($_POST['personal_tax_code']  ?? ''),
        'bank_account'            => trim($_POST['bank_account']       ?? ''),
        'bank_name'               => trim($_POST['bank_name']          ?? ''),
        'bank_branch'             => trim($_POST['bank_branch']        ?? ''),
        'has_social_insurance'    => isset($_POST['has_social_insurance']) ? 1 : 0,
        'dependants'              => max(0, (int)($_POST['dependants'] ?? 0)),
    ];

    if ($data['same_as_permanent']) {
        $data['temp_province']      = $data['permanent_province'];
        $data['temp_district_text'] = $data['permanent_district_text'];
        $data['temp_commune_text']  = $data['permanent_commune_text'];
        $data['temp_hamlet']        = $data['permanent_hamlet'];
    }

    if (empty($data['gender']))
        $errors[] = 'Vui lòng chọn giới tính.';
    if (!empty($data['mobile_phone']) && !preg_match('/^[0-9+]{9,15}$/', $data['mobile_phone']))
        $errors[] = 'Số điện thoại không hợp lệ.';
    if (!empty($data['identity_no']) && !preg_match('/^[0-9]{9,12}$/', $data['identity_no']))
        $errors[] = 'Số CMND/CCCD phải là 9 hoặc 12 số.';

    if (empty($errors)) {
        if (empty($profile)) {
            $cols = 'user_id, ' . implode(', ', array_keys($data));
            $vals = '?, '       . implode(', ', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO employee_profiles ($cols) VALUES ($vals)")
                ->execute(array_merge([$targetId], array_values($data)));
        } else {
            $set = implode('=?, ', array_keys($data)) . '=?';
            $pdo->prepare("UPDATE employee_profiles SET $set WHERE user_id=?")
                ->execute(array_merge(array_values($data), [$targetId]));
        }
        $pdo->prepare("UPDATE users SET email=?, phone=? WHERE id=?")
            ->execute([trim($_POST['email'] ?? ''), $data['mobile_phone'], $targetId]);

        setFlash('success', '✅ Cập nhật hồ sơ thành công!');
        header("Location: /erp/modules/users/profile.php?id=$targetId");
        exit();
    }
}

// ── Dữ liệu dropdown ─────────────────────────────────────────────────────
$provinces   = $pdo->query("SELECT code, name, full_name FROM provinces ORDER BY name")->fetchAll();
$ethnicities = $pdo->query("SELECT name FROM ethnicities ORDER BY id")->fetchAll();

// ── Lấy dữ liệu lương ────────────────────────────────────────────────────
$salaryRows = [];
if ($canViewSalary) {
    $stmt = $pdo->prepare("
        SELECT es.*, sc.component_name, sc.component_name_en, sc.component_type, sc.component_code
        FROM employee_salaries es
        LEFT JOIN salary_components sc ON es.component_id = sc.id
        WHERE es.user_id = ? AND es.is_active = 1
        ORDER BY es.sort_order ASC, es.id ASC
    ");
    $stmt->execute([$targetId]);
    $salaryRows = $stmt->fetchAll();
}
$grossSalary = array_sum(array_column($salaryRows, 'amount'));

$salaryComponents = [];
if ($canEditSalary) {
    $salaryComponents = $pdo->query(
        "SELECT * FROM salary_components WHERE is_active=1 ORDER BY sort_order"
    )->fetchAll();
}

// ── Helper functions ──────────────────────────────────────────────────────
function val($key, $default = '') {
    global $profile, $errors;
    if (!empty($errors) && isset($_POST[$key])) return htmlspecialchars($_POST[$key]);
    return htmlspecialchars($profile[$key] ?? $default);
}
function valSel($key, $option) {
    global $profile, $errors;
    $v = (!empty($errors) && isset($_POST[$key])) ? $_POST[$key] : ($profile[$key] ?? '');
    return $v == $option ? 'selected' : '';
}
function valChk($key) {
    global $profile, $errors;
    $v = (!empty($errors) && isset($_POST[$key])) ? isset($_POST[$key]) : (($profile[$key] ?? 0) == 1);
    return $v ? 'checked' : '';
}

$csrf  = generateCSRF();
$badge = getRoleBadge($targetUser['role'] ?? 'employee');
$ro    = $canEdit ? '' : 'readonly';
$dis   = $canEdit ? '' : 'disabled';
$cls   = $canEdit ? '' : 'bg-light text-muted';

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>

<div class="main-content">
<div class="container-fluid py-4">

    <!-- ── Tiêu đề ── -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <?php if (hasRole('director','accountant','manager')): ?>
        <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <?php endif; ?>
        <div class="flex-grow-1">
            <h4 class="mb-0">👤 Hồ sơ nhân viên</h4>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-<?= $badge['class'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
                <strong><?= htmlspecialchars($targetUser['full_name'] ?? '') ?></strong>
                <code class="small"><?= htmlspecialchars($targetUser['employee_code'] ?? '') ?></code>
                <span class="text-muted small"><?= htmlspecialchars($targetUser['dept_name'] ?? '') ?></span>
            </div>
        </div>
        <?php if ($isAdmin): ?>
        <div class="d-flex gap-2">
            <a href="/erp/modules/users/edit.php?id=<?= $targetId ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-user-cog me-1"></i>Tài khoản
            </a>
            <a href="/erp/modules/users/change_password.php?id=<?= $targetId ?>" class="btn btn-outline-warning btn-sm">
                <i class="fas fa-key me-1"></i>Mật khẩu
            </a>
        </div>
        <?php elseif ($isOwnProfile): ?>
        <div class="d-flex gap-2">
            <a href="/erp/modules/users/change_password.php?id=<?= $targetId ?>" class="btn btn-outline-warning btn-sm">
                <i class="fas fa-key me-1"></i>Đổi mật khẩu
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php showFlash(); ?>

    <?php if (!$canEdit): ?>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-eye fs-5"></i>
        <div>
            <strong>Chế độ xem:</strong> Bạn chỉ có thể xem hồ sơ, không thể chỉnh sửa.
            Liên hệ <strong>Kế toán</strong> hoặc <strong>Giám đốc</strong> để cập nhật thông tin.
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>❌ Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" id="profileForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <!-- ══════════════════════════════════════════
             SECTION 1: THÔNG TIN CƠ BẢN
        ══════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header section-header">
                <i class="fas fa-id-card me-2"></i>1. Thông tin cơ bản
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">ID Nhân viên</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= htmlspecialchars($targetUser['employee_code'] ?? '') ?>" readonly>
                        <div class="form-text">Mã tự động, không thể đổi</div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Họ và tên</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= htmlspecialchars($targetUser['full_name'] ?? '') ?>" readonly>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            Giới tính <?= $canEdit ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <select name="gender" class="form-select <?= $cls ?>" <?= $dis ?>>
                            <option value="male"   <?= valSel('gender','male') ?>>👨 Nam</option>
                            <option value="female" <?= valSel('gender','female') ?>>👩 Nữ</option>
                            <option value="other"  <?= valSel('gender','other') ?>>Khác</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Hôn nhân</label>
                        <select name="marital_status" class="form-select <?= $cls ?>" <?= $dis ?>>
                            <option value="single"   <?= valSel('marital_status','single') ?>>Độc thân</option>
                            <option value="married"  <?= valSel('marital_status','married') ?>>Đã kết hôn</option>
                            <option value="divorced" <?= valSel('marital_status','divorced') ?>>Ly hôn</option>
                            <option value="widowed"  <?= valSel('marital_status','widowed') ?>>Góa</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Ngày sinh</label>
                        <input type="date" name="date_of_birth"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('date_of_birth') ?>"
                               max="<?= date('Y-m-d', strtotime('-16 years')) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Ngày vào công ty</label>
                        <input type="date" name="date_joined"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('date_joined') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Dân tộc</label>
                        <select name="ethnicity" class="form-select <?= $cls ?>" <?= $dis ?>>
                            <?php foreach ($ethnicities as $eth): ?>
                            <option value="<?= htmlspecialchars($eth['name']) ?>"
                                    <?= valSel('ethnicity', $eth['name']) ?>>
                                <?= htmlspecialchars($eth['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Số điện thoại</label>
                        <div class="input-group">
                            <span class="input-group-text">📱</span>
                            <input type="tel" name="mobile_phone"
                                   class="form-control <?= $cls ?>" <?= $ro ?>
                                   value="<?= val('mobile_phone', $targetUser['phone'] ?? '') ?>"
                                   placeholder="0901234567"
                                   pattern="[0-9+]{9,15}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">✉️</span>
                            <input type="email" name="email"
                                   class="form-control <?= $cls ?>" <?= $ro ?>
                                   value="<?= htmlspecialchars($_POST['email'] ?? $targetUser['email'] ?? '') ?>"
                                   placeholder="email@company.com">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 2: ĐỊA CHỈ THƯỜNG TRÚ
        ══════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header section-header">
                <i class="fas fa-home me-2"></i>2. Địa chỉ thường trú
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tỉnh / Thành phố</label>
                        <select name="permanent_province" class="form-select <?= $cls ?>" <?= $dis ?>>
                            <option value="">-- Chọn tỉnh/thành --</option>
                            <?php foreach ($provinces as $p): ?>
                            <option value="<?= $p['code'] ?>" <?= valSel('permanent_province', $p['code']) ?>>
                                <?= htmlspecialchars($p['full_name'] ?? $p['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Xã / Phường</label>
                        <input type="text" name="permanent_district_text"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('permanent_district_text') ?>"
                               placeholder="VD: Xã Thiên Lộc">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Đường / Phố</label>
                        <input type="text" name="permanent_commune_text"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('permanent_commune_text') ?>"
                               placeholder="VD: Đường Liên Xã">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Số nhà / Thôn / Ấp</label>
                        <input type="text" name="permanent_hamlet"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('permanent_hamlet') ?>"
                               placeholder="VD: 58 Đường Liên Xã, Thôn Nhuế">
                    </div>

                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 3: ĐỊA CHỈ TẠM TRÚ
        ══════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header section-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-map-marker-alt me-2"></i>3. Địa chỉ tạm trú</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox"
                           name="same_as_permanent" id="sameAsPermanent"
                           <?= valChk('same_as_permanent') ?>
                           <?= $dis ?>
                           onchange="toggleTempAddress(this.checked)">
                    <label class="form-check-label fw-semibold small" for="sameAsPermanent">
                        ✅ Giống địa chỉ thường trú
                    </label>
                </div>
            </div>
            <div class="card-body" id="tempAddressBlock"
                 style="<?= ($profile['same_as_permanent'] ?? 0) ? 'opacity:.4; pointer-events:none;' : '' ?>">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tỉnh / Thành phố</label>
                        <select name="temp_province" id="temp_province_sel"
                                class="form-select <?= $cls ?>" <?= $dis ?>>
                            <option value="">-- Chọn tỉnh/thành --</option>
                            <?php foreach ($provinces as $p): ?>
                            <option value="<?= $p['code'] ?>" <?= valSel('temp_province', $p['code']) ?>>
                                <?= htmlspecialchars($p['full_name'] ?? $p['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Phường / Xã</label>
                        <input type="text" name="temp_district_text" id="temp_district_text"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('temp_district_text') ?>"
                               placeholder="VD: Xã Thiên Lộc">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Đường / Phố</label>
                        <input type="text" name="temp_commune_text" id="temp_commune_text"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('temp_commune_text') ?>"
                               placeholder="VD: Đường Liên Xã">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Số nhà / Thôn / Ấp</label>
                        <input type="text" name="temp_hamlet" id="temp_hamlet"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('temp_hamlet') ?>"
                               placeholder="VD: Số 58, Thôn Nhuế">
                    </div>

                </div>
            </div>
        </div>

        <!-- ════════════════════════════��═════════════
             SECTION 4: GIẤY TỜ TÙY THÂN
        ══════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header section-header">
                <i class="fas fa-id-badge me-2"></i>4. Giấy tờ tùy thân
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số CMND / CCCD</label>
                        <div class="input-group">
                            <span class="input-group-text">🪪</span>
                            <input type="text" name="identity_no"
                                   class="form-control <?= $cls ?>" <?= $ro ?>
                                   value="<?= val('identity_no') ?>"
                                   placeholder="001095553551"
                                   maxlength="12"
                                   pattern="[0-9]{9,12}"
                                   <?= $canEdit ? 'oninput="this.value=this.value.replace(/\D/g,\'\')"' : '' ?>>
                        </div>
                        <div class="form-text">Căn Cước Công Dân hoặc Chứng Minh Thư Nhân Dân: 12 số</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ngày cấp</label>
                        <input type="date" name="identity_issue_date"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('identity_issue_date') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nơi cấp</label>
                        <input type="text" name="identity_issue_place"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('identity_issue_place') ?>"
                               placeholder="VD: Cục Cảnh Sát">
                    </div>

                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 5: THÔNG TIN TÀI CHÍNH & BHXH
        ════════════════════════════════════════���═ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header section-header">
                <i class="fas fa-university me-2"></i>5. Tài chính & Bảo hiểm
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số sổ BHXH</label>
                        <div class="input-group">
                            <span class="input-group-text">🏥</span>
                            <input type="text" name="social_book_no"
                                   class="form-control <?= $cls ?>" <?= $ro ?>
                                   value="<?= val('social_book_no') ?>"
                                   placeholder="Số sổ bảo hiểm xã hội (nếu có)">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mã số thuế cá nhân</label>
                        <div class="input-group">
                            <span class="input-group-text">🧾</span>
                            <input type="text" name="personal_tax_code"
                                   class="form-control <?= $cls ?>" <?= $ro ?>
                                   value="<?= val('personal_tax_code') ?>"
                                   placeholder="10 số MST cá nhân"
                                   maxlength="13"
                                   <?= $canEdit ? 'oninput="this.value=this.value.replace(/[^0-9\-]/g,\'\')"' : '' ?>>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tên ngân hàng</label>
                        <select name="bank_name" id="bankSelect"
                                class="form-select <?= $cls ?>" <?= $dis ?>
                                onchange="updateBankPreview()">
                            <option value="">-- Chọn ngân hàng --</option>
                            <?php
                            $banks = [
                                'Vietcombank','VietinBank','BIDV','Agribank','Techcombank',
                                'MBBank','ACB','VPBank','TPBank','Sacombank','HDBank',
                                'VIB','OCB','MSB','SeABank','Eximbank','SHB','LPBank',
                                'NCB','BacABank','VietBank','PVcomBank','Kienlongbank',
                                'ABBank','NamABank','BaoVietBank','VietABank','CBBank',
                                'OceanBank','GPBank','Vietbank','Cake','Timo','CAKE',
                                'Ngân hàng khác'
                            ];
                            $selectedBank = $profile['bank_name'] ?? '';
                            foreach ($banks as $b):
                            ?>
                            <option value="<?= $b ?>" <?= $selectedBank === $b ? 'selected' : '' ?>>
                                <?= $b ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số tài khoản ngân hàng</label>
                        <div class="input-group">
                            <span class="input-group-text">💳</span>
                            <input type="text" name="bank_account"
                                   class="form-control <?= $cls ?>" <?= $ro ?>
                                   value="<?= val('bank_account') ?>"
                                   placeholder="Số tài khoản"
                                   <?= $canEdit ? 'oninput="this.value=this.value.replace(/\D/g,\'\')"' : '' ?>>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="copyToClipboard('bank_account')" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Chi nhánh ngân hàng</label>
                        <input type="text" name="bank_branch"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('bank_branch') ?>"
                               placeholder="VD: Chi nhánh Hà Nội (nếu có)">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bảo hiểm xã hội</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="has_social_insurance"
                                   id="hasSocialInsurance" value="1"
                                   <?= valChk('has_social_insurance') ?>
                                   <?= $canEdit ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="hasSocialInsurance">
                                Nhân viên có đóng BHXH
                                <small class="text-muted d-block">Trừ 10.5% × lương cơ bản khi tính lương</small>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số người phụ thuộc (giảm trừ thuế)</label>
                        <input type="number" name="dependants"
                               class="form-control <?= $cls ?>" <?= $ro ?>
                               value="<?= val('dependants', '0') ?>"
                               min="0" max="10" step="1"
                               placeholder="0">
                        <div class="form-text">Mỗi người giảm trừ 6,200,000đ/tháng</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Xem trước</label>
                        <div class="bank-preview p-3 rounded border bg-light" id="bankPreview">
                            <div class="small text-muted">Chọn ngân hàng để xem</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 6: THÔNG TIN LƯƠNG
        ══════════════════════════════════════════ -->
        <?php if ($canViewSalary): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header section-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-money-bill-wave me-2 text-success"></i>
                    6. Thông tin lương / Salary information
                    <?php if (!$canEditSalary): ?>
                    <span class="badge bg-secondary ms-2" style="font-size:10px;">
                        <i class="fas fa-eye me-1"></i>Chỉ xem
                    </span>
                    <?php endif; ?>
                </span>
                <?php if ($canEditSalary): ?>
                <button type="button" class="btn btn-success btn-sm" onclick="addSalaryRow()">
                    <i class="fas fa-plus me-1"></i>Thêm khoản
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0" id="salaryTable">
                    <thead class="table-dark">
                        <tr>
                            <?php if ($canEditSalary): ?>
                            <th width="32" class="text-center">☰</th>
                            <?php endif; ?>
                            <th>Khoản lương / Salary component</th>
                            <th width="200" class="text-end">Số tiền (VNĐ)</th>
                            <?php if ($canEditSalary): ?>
                            <th width="100" class="text-center">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="salaryBody">
                        <tr class="table-warning fw-bold" id="grossRow">
                            <?php if ($canEditSalary): ?><td></td><?php endif; ?>
                            <td>
                                Lương Tổng / <strong>Gross salary</strong>
                                <small class="text-muted fw-normal">(=1+2+3+...)</small>
                            </td>
                            <td class="text-end text-danger fs-6 fw-bold" id="grossTotal">
                                <?= number_format($grossSalary, 0, '.', ',') ?>
                            </td>
                            <?php if ($canEditSalary): ?><td></td><?php endif; ?>
                        </tr>

                        <?php if (empty($salaryRows)): ?>
                        <tr id="emptyRow">
                            <?php if ($canEditSalary): ?><td></td><?php endif; ?>
                            <td colspan="2" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                Chưa có thông tin lương.
                                <?php if ($canEditSalary): ?>
                                <a href="#" onclick="addSalaryRow()">+ Thêm khoản lương</a>
                                <?php endif; ?>
                            </td>
                            <?php if ($canEditSalary): ?><td></td><?php endif; ?>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($salaryRows as $i => $row):
                            $num      = $i + 1;
                            $name     = $row['custom_name']    ?: ($row['component_name']    ?? '');
                            $nameEn   = $row['custom_name_en'] ?: ($row['component_name_en'] ?? '');
                            $typeClass = match($row['component_type'] ?? 'earning') {
                                'deduction' => 'text-danger',
                                'bonus'     => 'text-success',
                                default     => ''
                            };
                        ?>
                        <tr class="salary-row" data-row-id="<?= $row['id'] ?>" data-amount="<?= $row['amount'] ?>">
                            <?php if ($canEditSalary): ?>
                            <td class="text-center drag-handle" style="cursor:grab; color:#aaa;">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <?php endif; ?>
                            <td class="<?= $typeClass ?>">
                                <span class="row-num text-muted me-1">- (<?= $num ?>)</span>
                                <span class="row-name fw-semibold"><?= htmlspecialchars($name) ?></span>
                                <?php if ($nameEn): ?>
                                <span class="text-muted small"> / <?= htmlspecialchars($nameEn) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($row['note'])): ?>
                                <span class="badge bg-light text-muted ms-1 border"
                                      title="<?= htmlspecialchars($row['note']) ?>">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($row['effective_date'])): ?>
                                <span class="badge bg-light text-muted ms-1 border" style="font-size:10px;">
                                    Từ <?= date('d/m/Y', strtotime($row['effective_date'])) ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold <?= $typeClass ?>">
                                <?= number_format($row['amount'], 0, '.', ',') ?>
                            </td>
                            <?php if ($canEditSalary): ?>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-xs btn-outline-primary"
                                            onclick="editSalaryRow(<?= $row['id'] ?>, <?= htmlspecialchars(json_encode($row)) ?>)"
                                            title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger"
                                            onclick="deleteSalaryRow(<?= $row['id'] ?>, '<?= htmlspecialchars($name) ?>')"
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($salaryRows)): ?>
                <div class="p-3 bg-light border-top">
                    <div class="row text-center g-3">
                        <?php
                        $totalEarning   = array_sum(array_map(fn($r) =>
                            ($r['component_type'] ?? 'earning') !== 'deduction' ? $r['amount'] : 0, $salaryRows));
                        $totalDeduction = array_sum(array_map(fn($r) =>
                            ($r['component_type'] ?? 'earning') === 'deduction' ? $r['amount'] : 0, $salaryRows));
                        ?>
                        <div class="col-4">
                            <div class="small text-muted">💰 Tổng thu nhập</div>
                            <div class="fw-bold text-success"><?= number_format($totalEarning,0,'.',',') ?> ₫</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">➖ Tổng khấu trừ</div>
                            <div class="fw-bold text-danger"><?= number_format($totalDeduction,0,'.',',') ?> ₫</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">📊 Gross salary</div>
                            <div class="fw-bold text-primary fs-6"><?= number_format($grossSalary,0,'.',',') ?> ₫</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; // canViewSalary ?>

        <!-- ── Nút lưu ── -->
        <div class="d-flex gap-3 justify-content-end mb-5">
            <?php if (hasRole('director','accountant','manager')): ?>
            <a href="/erp/modules/users/index.php" class="btn btn-outline-secondary px-4">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <button type="button" class="btn btn-outline-secondary px-4" onclick="resetForm()">
                <i class="fas fa-undo me-2"></i>Đặt lại
            </button>
            <button type="submit" class="btn btn-primary px-5 fw-bold">
                <i class="fas fa-save me-2"></i>Lưu hồ sơ
            </button>
            <?php else: ?>
            <div class="alert alert-warning mb-0 py-2 px-4 d-flex align-items-center gap-2">
                <i class="fas fa-lock"></i>
                Liên hệ <strong class="mx-1">Kế toán</strong> hoặc <strong class="mx-1">Giám đốc</strong> để cập nhật hồ sơ
            </div>
            <?php endif; ?>
        </div>

    </form>
</div>
</div>

<!-- ── Thanh tiến trình hồ sơ ── -->
<div class="profile-progress-bar" id="profileProgressBar">
    <div class="d-flex align-items-center gap-3 px-3">
        <span class="small fw-semibold text-white">Mức độ hoàn thiện hồ sơ:</span>
        <div class="progress flex-grow-1" style="height:10px;">
            <div class="progress-bar bg-success" id="progressBarFill" style="width:0%"></div>
        </div>
        <span class="small text-white fw-bold" id="progressPercent">0%</span>
    </div>
</div>

<style>
.section-header {
    background: linear-gradient(90deg, #f8f9fa, #fff);
    font-weight: 700; color: #333;
    border-bottom: 2px solid #e9ecef;
    padding: 12px 20px;
}
.required-field::after { content: ' *'; color: #dc3545; }
.profile-progress-bar {
    position: fixed; bottom: 0; left: 240px; right: 0;
    background: linear-gradient(90deg, #0f3460, #533483);
    padding: 8px 20px; z-index: 1000; transition: left 0.3s;
}
.bank-preview { min-height: 60px; display: flex; align-items: center; }
.card-body .row .col-md-3 label,
.card-body .row .col-md-4 label,
.card-body .row .col-md-2 label { font-size: 13px; }
.btn-xs { padding: 2px 8px; font-size: 12px; }
</style>

<script>
// ══════════════════════════════════════════════════
// Toggle tạm trú giống thường trú
// ══════════════════════════════════════════════════
function toggleTempAddress(checked) {
    const block = document.getElementById('tempAddressBlock');
    block.style.opacity       = checked ? '0.4' : '1';
    block.style.pointerEvents = checked ? 'none' : '';
    if (checked) syncTempAddress();
}
function syncTempAddress() {
    const permProv = document.querySelector('[name="permanent_province"]');
    const tempProv = document.getElementById('temp_province_sel');
    if (permProv && tempProv) tempProv.value = permProv.value;
    [
        ['permanent_district_text','temp_district_text'],
        ['permanent_commune_text', 'temp_commune_text'],
        ['permanent_hamlet',       'temp_hamlet'],
    ].forEach(([src, dst]) => {
        const s = document.querySelector(`[name="${src}"]`);
        const d = document.querySelector(`[name="${dst}"]`) || document.getElementById(dst);
        if (s && d) d.value = s.value;
    });
}
['permanent_province','permanent_district_text','permanent_commune_text','permanent_hamlet']
    .forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        el?.addEventListener('change', () => { if (document.getElementById('sameAsPermanent').checked) syncTempAddress(); });
        el?.addEventListener('input',  () => { if (document.getElementById('sameAsPermanent').checked) syncTempAddress(); });
    });
if (document.getElementById('sameAsPermanent')?.checked) toggleTempAddress(true);

// ══════════════════════════════════════════════════
// Bank preview
// ══════════════════════════════════════════════════
const bankLogos = {
    'Vietcombank':'🟩','VietinBank':'🟦','BIDV':'🟥','Agribank':'🟫',
    'Techcombank':'🔴','MBBank':'⬛','ACB':'🔵','VPBank':'🟧','TPBank':'🟣'
};
function updateBankPreview() {
    const bank    = document.getElementById('bankSelect').value;
    const account = document.querySelector('[name="bank_account"]').value;
    const branch  = document.querySelector('[name="bank_branch"]').value;
    const preview = document.getElementById('bankPreview');
    if (bank) {
        const logo = bankLogos[bank] || '🏦';
        preview.innerHTML = `<div>
            <div class="fw-bold">${logo} ${bank}</div>
            ${account ? `<div class="text-primary fw-bold font-monospace">${account}</div>` : ''}
            ${branch  ? `<div class="small text-muted">${branch}</div>` : ''}
        </div>`;
    } else {
        preview.innerHTML = '<div class="small text-muted">Chọn ngân hàng để xem</div>';
    }
}
document.getElementById('bankSelect')?.addEventListener('change', updateBankPreview);
document.querySelector('[name="bank_account"]')?.addEventListener('input', updateBankPreview);
document.querySelector('[name="bank_branch"]')?.addEventListener('input', updateBankPreview);
updateBankPreview();

// ══════════════════════════════════════════════════
// Copy to clipboard
// ══════════════════════════════════════════════════
function copyToClipboard(name) {
    const el = document.querySelector(`[name="${name}"]`);
    if (el && el.value) {
        navigator.clipboard.writeText(el.value);
        const btn = el.nextElementSibling;
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500);
    }
}

// ══════════════════════════════════════════════════
// Thanh tiến trình hoàn thiện hồ sơ
// ══════════════════════════════════════════════════
const trackedFields = [
    'gender','date_of_birth','date_joined','ethnicity','marital_status',
    'mobile_phone','email',
    'permanent_province','permanent_district_text','permanent_commune_text','permanent_hamlet',
    'identity_no','identity_issue_date','identity_issue_place',
    'social_book_no','personal_tax_code','bank_account','bank_name'
];
function updateProgress() {
    let filled = 0;
    trackedFields.forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el && el.value && el.value.trim() !== '') filled++;
    });
    const pct = Math.round((filled / trackedFields.length) * 100);
    document.getElementById('progressBarFill').style.width = pct + '%';
    document.getElementById('progressPercent').textContent = pct + '%';
    const bar = document.getElementById('progressBarFill');
    bar.className = 'progress-bar ' + (pct < 40 ? 'bg-danger' : pct < 70 ? 'bg-warning' : 'bg-success');
}
document.querySelectorAll('input,select,textarea').forEach(el => {
    el.addEventListener('change', updateProgress);
    el.addEventListener('input',  updateProgress);
});
updateProgress();

// ══════════════════════════════════════════════════
// Reset form
// ══════════════════════════════════════════════════
function resetForm() {
    if (confirm('Đặt lại tất cả thay đổi chưa lưu?')) {
        document.getElementById('profileForm').reset();
        updateProgress();
        updateBankPreview();
    }
}

// ══════════════════════════════════════════════════
// Sidebar collapse
// ══════════════════════════════════════════════════
const sidebarEl = document.getElementById('sidebar');
if (sidebarEl) {
    new MutationObserver(() => {
        const bar = document.getElementById('profileProgressBar');
        if (bar) bar.style.left = sidebarEl.classList.contains('collapsed') ? '60px' : '240px';
    }).observe(sidebarEl, { attributes: true, attributeFilter: ['class'] });
}

// ══════════════════════════════════════════════════
// SALARY TABLE JS (chỉ GĐ & KT)
// ══════════════════════════════════════════════════
<?php if ($canEditSalary): ?>
let salaryModal = null;
document.addEventListener('DOMContentLoaded', () => {
    salaryModal = new bootstrap.Modal(document.getElementById('salaryModal'));
});
function addSalaryRow() {
    document.getElementById('salaryModalTitle').textContent  = '➕ Thêm khoản lương';
    document.getElementById('sm_row_id').value               = 0;
    document.getElementById('sm_component_id').value         = '';
    document.getElementById('sm_custom_name').value          = '';
    document.getElementById('sm_custom_name_en').value       = '';
    document.getElementById('sm_amount').value               = '';
    document.getElementById('sm_type').value                 = 'earning';
    document.getElementById('sm_note').value                 = '';
    document.getElementById('sm_effective_date').value       = '<?= date('Y-m-d') ?>';
    document.getElementById('sm_amount_words').textContent   = '';
    salaryModal.show();
}
function editSalaryRow(rowId, row) {
    document.getElementById('salaryModalTitle').textContent  = '✏️ Sửa khoản lương';
    document.getElementById('sm_row_id').value               = rowId;
    document.getElementById('sm_component_id').value         = row.component_id   || '';
    document.getElementById('sm_custom_name').value          = row.custom_name    || row.component_name    || '';
    document.getElementById('sm_custom_name_en').value       = row.custom_name_en || row.component_name_en || '';
    document.getElementById('sm_amount').value               = Number(row.amount).toLocaleString('vi-VN');
    document.getElementById('sm_type').value                 = row.component_type || 'earning';
    document.getElementById('sm_note').value                 = row.note           || '';
    document.getElementById('sm_effective_date').value       = row.effective_date || '';
    updateAmountWords(Number(row.amount));
    salaryModal.show();
}
function fillFromComponent(compId) {
    const sel = document.getElementById('sm_component_id');
    const opt = sel.options[sel.selectedIndex];
    if (!compId) return;
    document.getElementById('sm_custom_name').value    = opt.dataset.name   || '';
    document.getElementById('sm_custom_name_en').value = opt.dataset.nameEn || '';
    document.getElementById('sm_type').value           = opt.dataset.type   || 'earning';
}
async function saveSalaryRow() {
    const name = document.getElementById('sm_custom_name').value.trim();
    const amt  = document.getElementById('sm_amount').value.replace(/[^0-9]/g,'');
    if (!name) { alert('Vui lòng nhập tên khoản lương!'); return; }
    if (!amt)  { alert('Vui lòng nhập số tiền!'); return; }
    const payload = {
        row_id:         document.getElementById('sm_row_id').value,
        user_id:        <?= $targetId ?>,
        component_id:   document.getElementById('sm_component_id').value || 0,
        custom_name:    name,
        custom_name_en: document.getElementById('sm_custom_name_en').value.trim(),
        amount:         amt,
        component_type: document.getElementById('sm_type').value,
        note:           document.getElementById('sm_note').value.trim(),
        effective_date: document.getElementById('sm_effective_date').value,
    };
    try {
        const res  = await fetch('/erp/api/salary/save_row.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.ok) { salaryModal.hide(); showToast('success', data.msg); setTimeout(() => location.reload(), 600); }
        else alert('Lỗi: ' + data.msg);
    } catch(e) { alert('Lỗi kết nối server!'); }
}
async function deleteSalaryRow(rowId, name) {
    if (!confirm(`Xóa khoản "${name}"?\nHành động này không thể hoàn tác!`)) return;
    try {
        const res  = await fetch('/erp/api/salary/delete_row.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ row_id: rowId, user_id: <?= $targetId ?> })
        });
        const data = await res.json();
        if (data.ok) {
            document.querySelector(`tr[data-row-id="${rowId}"]`)?.remove();
            recalcGross(); renumberRows();
            showToast('success', 'Đã xóa khoản lương');
        }
    } catch(e) { alert('Lỗi kết nối!'); }
}
function formatAmountInput(el) {
    let raw = el.value.replace(/[^0-9]/g,'');
    el.value = raw ? Number(raw).toLocaleString('vi-VN') : '';
    updateAmountWords(Number(raw));
}
function updateAmountWords(amount) {
    const el = document.getElementById('sm_amount_words');
    if (!el || !amount) { if (el) el.textContent = ''; return; }
    const units = ['','nghìn','triệu','tỷ'];
    let parts = [], n = amount;
    while (n > 0) { parts.unshift(n % 1000); n = Math.floor(n / 1000); }
    const str = parts.map((p,i) => p ? `${p.toLocaleString('vi-VN')} ${units[parts.length-1-i]}` : '')
                     .filter(Boolean).join(' ') + ' đồng';
    el.textContent = '≈ ' + str.trim();
}
function recalcGross() {
    let total = 0;
    document.querySelectorAll('.salary-row').forEach(tr => { total += parseInt(tr.dataset.amount || 0); });
    const el = document.getElementById('grossTotal');
    if (el) el.textContent = total.toLocaleString('vi-VN');
}
function renumberRows() {
    document.querySelectorAll('.salary-row').forEach((tr,i) => {
        const el = tr.querySelector('.row-num');
        if (el) el.textContent = `- (${i+1}) `;
    });
}
<?php endif; ?>

function showToast(type, msg) {
    const colors = {success:'#198754',danger:'#dc3545',warning:'#ffc107',info:'#0dcaf0'};
    const toast  = document.createElement('div');
    toast.style.cssText = `position:fixed;bottom:70px;right:20px;z-index:9999;
        background:${colors[type]||'#333'};color:#fff;padding:10px 20px;
        border-radius:8px;font-size:14px;font-weight:600;
        box-shadow:0 4px 12px rgba(0,0,0,.2);`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}
</script>

<?php if ($canEditSalary): ?>
<div class="modal fade" id="salaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold" id="salaryModalTitle">➕ Thêm khoản lương</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sm_row_id" value="0">
                <input type="hidden" id="sm_user_id" value="<?= $targetId ?>">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Chọn từ danh sách có sẵn</label>
                        <select id="sm_component_id" class="form-select" onchange="fillFromComponent(this.value)">
                            <option value="">-- Hoặc tự nhập tên bên dưới --</option>
                            <?php foreach ($salaryComponents as $sc): ?>
                            <option value="<?= $sc['id'] ?>"
                                    data-name="<?= htmlspecialchars($sc['component_name']) ?>"
                                    data-name-en="<?= htmlspecialchars($sc['component_name_en'] ?? '') ?>"
                                    data-type="<?= $sc['component_type'] ?>">
                                <?= htmlspecialchars($sc['component_name']) ?>
                                <?= $sc['component_name_en'] ? ' / '.$sc['component_name_en'] : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tên (Tiếng Việt) <span class="text-danger">*</span></label>
                        <input type="text" id="sm_custom_name" class="form-control" placeholder="VD: Lương cơ bản">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tên (English)</label>
                        <input type="text" id="sm_custom_name_en" class="form-control" placeholder="VD: Basic salary">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Số tiền (VNĐ) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="sm_amount" class="form-control text-end fw-bold"
                                   placeholder="0" oninput="formatAmountInput(this)">
                            <span class="input-group-text">₫</span>
                        </div>
                        <div class="form-text text-end text-success small" id="sm_amount_words"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Loại khoản</label>
                        <select id="sm_type" class="form-select">
                            <option value="earning">💰 Thu nhập</option>
                            <option value="bonus">🎁 Thưởng</option>
                            <option value="deduction">➖ Khấu trừ</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Áp dụng từ ngày</label>
                        <input type="date" id="sm_effective_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Ghi chú</label>
                        <textarea id="sm_note" class="form-control" rows="2"
                                  placeholder="Ghi chú thêm (không bắt buộc)"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveSalaryRow()">
                    <i class="fas fa-save me-2"></i>Lưu
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>