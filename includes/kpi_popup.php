<?php
$user = currentUser();
if (!$user || !hasRole('director', 'accountant', 'manager', 'warehouse', 'production')) return;

$pdo   = getDBConnection();
$today = date('Y-m-d');
$hour  = (int)date('H');

if ($hour < 17) return;

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM kpi_assignments ka
    LEFT JOIN kpi_results kr ON kr.kpi_assignment_id = ka.id
    WHERE ka.assign_date = ?
    AND kr.id IS NULL
");
$stmt->execute([$today]);
$pendingCount = $stmt->fetchColumn();

if ($pendingCount == 0) return;
?>
<div class="modal fade" id="modalKpiWarning" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-warning border-2">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Chưa nhập kết quả KPI!
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-clipboard-list fa-4x text-warning mb-3 d-block"></i>
                <h5>Còn <span class="text-danger fw-bold"><?= $pendingCount ?></span>
                    nhân viên chưa có kết quả KPI hôm nay</h5>
                <p class="text-muted mb-0">
                    Vui lòng kiểm tra và nhập kết quả KPI
                    cho nhân viên sản xuất ngày
                    <strong><?= date('d/m/Y') ?></strong>
                </p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Để sau
                </button>
                <a href="/erp/modules/kpi/result.php?date=<?= $today ?>"
                   class="btn btn-warning fw-bold">
                    <i class="fas fa-clipboard-check me-1"></i>
                    Nhập kết quả KPI ngay
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalKpiWarning')
        ).show();
    });
</script>