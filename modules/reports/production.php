<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager', 'production', 'warehouse');

include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/sidebar.php';
?>
<div class="main-content">
<div class="container-fluid py-4">

  <!-- Nav tabs điều hướng giữa các dashboard -->
  <ul class="nav nav-pills mb-4 border-bottom pb-3">
    <li class="nav-item">
      <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/reports/index') !== false || (strpos($_SERVER['REQUEST_URI'], '/reports/') !== false && strpos($_SERVER['REQUEST_URI'], 'production') === false && strpos($_SERVER['REQUEST_URI'], 'warehouse') === false && strpos($_SERVER['REQUEST_URI'], 'finance') === false && strpos($_SERVER['REQUEST_URI'], 'payroll') === false) ? 'active' : '' ?>"
         href="/erp/modules/reports/index.php">
        <i class="fas fa-tachometer-alt me-1"></i>Tổng quan
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'production') !== false ? 'active' : '' ?>"
         href="/erp/modules/reports/production.php">
        <i class="fas fa-industry me-1"></i>Sản xuất
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'warehouse') !== false ? 'active' : '' ?>"
         href="/erp/modules/reports/warehouse.php">
        <i class="fas fa-warehouse me-1"></i>Kho
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'finance') !== false ? 'active' : '' ?>"
         href="/erp/modules/reports/finance.php">
        <i class="fas fa-chart-line me-1"></i>Tài chính
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'payroll') !== false ? 'active' : '' ?>"
         href="/erp/modules/reports/payroll.php">
        <i class="fas fa-users me-1"></i>Nhân sự &amp; Lương
      </a>
    </li>
  </ul>

  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="mb-1"><i class="fas fa-industry me-2 text-success"></i>Báo cáo Sản xuất</h4>
      <p class="text-muted mb-0">Theo dõi tiến độ gia công và hiệu quả sản xuất</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <input type="date" id="dateFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" style="width:140px;">
      <input type="date" id="dateTo"   class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"  style="width:140px;">
      <button class="btn btn-success btn-sm" onclick="loadAll()">
        <i class="fas fa-sync-alt me-1"></i>Làm mới
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <?php
    $kpis = [
      ['id'=>'kpiTarget',     'label'=>'Kế hoạch SX (qty)',    'icon'=>'fa-bullseye',       'color'=>'primary'],
      ['id'=>'kpiDone',       'label'=>'Thực tế SX (qty)',      'icon'=>'fa-check-double',   'color'=>'success'],
      ['id'=>'kpiCompletion', 'label'=>'Tỷ lệ hoàn thành',     'icon'=>'fa-percent',        'color'=>'info'],
      ['id'=>'kpiInProgress', 'label'=>'Đơn đang sản xuất',    'icon'=>'fa-spinner',        'color'=>'warning'],
      ['id'=>'kpiDoneCount',  'label'=>'Đơn đã hoàn thành',    'icon'=>'fa-check-circle',   'color'=>'success'],
      ['id'=>'kpiErrorRate',  'label'=>'Tỷ lệ lỗi %',          'icon'=>'fa-exclamation-triangle','color'=>'danger'],
    ];
    foreach ($kpis as $k): ?>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1"><?= $k['label'] ?></div>
          <div class="fs-4 fw-bold text-<?= $k['color'] ?>" id="<?= $k['id'] ?>Val">—</div>
          <div class="mt-2 rounded-circle bg-<?= $k['color'] ?> bg-opacity-10 p-2 d-inline-block">
            <i class="fas <?= $k['icon'] ?> text-<?= $k['color'] ?>"></i>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3 mb-4">
    <!-- Sản lượng theo ngày -->
    <div class="col-md-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-line me-2 text-success"></i>Sản lượng hoàn thành theo ngày (30 ngày gần nhất)</h6>
          <canvas id="chartDaily" height="60"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <!-- Tiến độ từng đơn hàng -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-tasks me-2 text-info"></i>Tiến độ từng đơn hàng</h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Mã đơn</th><th>Khách hàng</th><th>Ngày tạo</th>
                  <th>Kế hoạch</th><th>Thực tế</th><th>Lỗi</th>
                  <th style="min-width:120px;">Tiến độ</th><th>TT</th>
                </tr>
              </thead>
              <tbody id="ordersBody">
                <tr><td colspan="8" class="text-center text-muted py-3">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Đơn sắp trễ -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Đơn hàng sắp trễ (7 ngày)</h6>
          <div id="soonLateList">
            <div class="text-center text-muted py-3">Đang tải...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartDailyInstance = null;

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function fmtPct(v) { return parseFloat(v).toFixed(1) + '%'; }
function statusBadge(s) {
  const map = {pending:'secondary',in_progress:'primary',done:'success',cancelled:'danger'};
  const lbl = {pending:'Chờ SX',in_progress:'Đang SX',done:'Hoàn thành',cancelled:'Đã huỷ'};
  return `<span class="badge bg-${map[s]||'secondary'}">${lbl[s]||s}</span>`;
}
function progressBar(pct) {
  const color = pct >= 95 ? '#22C55E' : pct >= 80 ? '#FACC15' : '#EF4444';
  return `<div class="d-flex align-items-center gap-1">
    <div class="progress flex-grow-1" style="height:7px;">
      <div class="progress-bar" style="width:${pct}%;background:${color};"></div>
    </div>
    <small>${pct}%</small>
  </div>`;
}

function buildParams() {
  return new URLSearchParams({
    date_from: document.getElementById('dateFrom').value,
    date_to:   document.getElementById('dateTo').value,
  }).toString();
}

function loadAll() {
  fetch('/erp/api/reports/production.php?' + buildParams())
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      const k = d.kpi;
      document.getElementById('kpiTargetVal').textContent   = parseFloat(k.qty_target).toLocaleString();
      document.getElementById('kpiDoneVal').textContent     = parseFloat(k.qty_done).toLocaleString();
      document.getElementById('kpiCompletionVal').textContent = fmtPct(k.completion_rate);
      document.getElementById('kpiInProgressVal').textContent = k.orders_in_progress;
      document.getElementById('kpiDoneCountVal').textContent  = k.orders_done;
      document.getElementById('kpiErrorRateVal').textContent  = fmtPct(k.error_rate);

      // Chart sản lượng theo ngày
      const labels  = d.chart_daily.map(r => r.day);
      const amounts = d.chart_daily.map(r => parseFloat(r.qty_done));
      if (chartDailyInstance) chartDailyInstance.destroy();
      chartDailyInstance = new Chart(document.getElementById('chartDaily'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Sản lượng hoàn thành',
            data: amounts,
            borderColor: '#22C55E',
            backgroundColor: 'rgba(34,197,94,0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
        }
      });

      // Bảng tiến độ
      const tbody = document.getElementById('ordersBody');
      if (!d.orders.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Không có đơn hàng</td></tr>';
      } else {
        tbody.innerHTML = d.orders.map(o => `
          <tr>
            <td><strong>${esc(o.order_no)}</strong></td>
            <td>${esc(o.customer_name)}</td>
            <td>${o.created_at ? esc(o.created_at.substring(0,10)) : '<span class="text-muted">—</span>'}</td>
            <td>${parseFloat(o.qty_total).toLocaleString()}</td>
            <td>${parseFloat(o.qty_done).toLocaleString()}</td>
            <td>${parseFloat(o.qty_error).toLocaleString()}</td>
            <td>${progressBar(o.progress_pct)}</td>
            <td>${statusBadge(o.status)}</td>
          </tr>
        `).join('');
      }

      // Đơn sắp trễ
      const soonDiv = document.getElementById('soonLateList');
      if (!d.soon_late.length) {
        soonDiv.innerHTML = '<div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i>Không có đơn sắp trễ</div>';
      } else {
        soonDiv.innerHTML = d.soon_late.map(o => {
          const pct = o.qty_total > 0 ? (o.qty_done / o.qty_total * 100).toFixed(1) : 0;
          return `<div class="alert alert-warning py-2 mb-2">
            <strong>${esc(o.order_no)}</strong> — ${esc(o.customer_name)}<br>
            <small>Ngày tạo: <strong>${o.created_at ? esc(o.created_at.substring(0,10)) : '—'}</strong> | Tiến độ: ${pct}%</small>
          </div>`;
        }).join('');
      }
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', loadAll);
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
