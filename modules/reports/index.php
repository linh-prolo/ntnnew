<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant', 'manager');

$pdo = getDBConnection();
$customers = $pdo->query("SELECT id, customer_name FROM customers WHERE is_active = 1 ORDER BY customer_name")->fetchAll();

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
      <h4 class="mb-1"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Tổng quan điều hành</h4>
      <p class="text-muted mb-0">Dashboard điều hành doanh nghiệp</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <input type="date" id="dateFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" style="width:140px;">
      <input type="date" id="dateTo"   class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"  style="width:140px;">
      <select id="customerFilter" class="form-select form-select-sm" style="width:180px;">
        <option value="">Tất cả khách hàng</option>
        <?php foreach ($customers as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" onclick="loadAll()">
        <i class="fas fa-sync-alt me-1"></i>Làm mới
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4" id="kpiRow">
    <?php
    $kpis = [
      ['id'=>'kpiRevenue',     'label'=>'Doanh thu tháng',       'icon'=>'fa-dollar-sign',   'color'=>'primary'],
      ['id'=>'kpiDebt',        'label'=>'Công nợ phải thu',      'icon'=>'fa-hand-holding-usd','color'=>'warning'],
      ['id'=>'kpiPayroll',     'label'=>'Chi phí lương tháng',   'icon'=>'fa-users',         'color'=>'danger'],
      ['id'=>'kpiOrders',      'label'=>'Đơn đang sản xuất',     'icon'=>'fa-industry',      'color'=>'info'],
      ['id'=>'kpiCompletion',  'label'=>'Hoàn thành SX',         'icon'=>'fa-check-circle',  'color'=>'success'],
      ['id'=>'kpiOqc',         'label'=>'Tỷ lệ lỗi OQC',         'icon'=>'fa-exclamation-triangle','color'=>'danger'],
      ['id'=>'kpiStock',       'label'=>'Mặt hàng còn tồn kho',  'icon'=>'fa-boxes',         'color'=>'secondary'],
      ['id'=>'kpiLate',        'label'=>'Đơn bị trễ',            'icon'=>'fa-clock',         'color'=>'danger'],
    ];
    foreach ($kpis as $k): ?>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small"><?= $k['label'] ?></div>
              <div class="fs-4 fw-bold text-<?= $k['color'] ?> mt-1" id="<?= $k['id'] ?>Val">—</div>
              <div class="small mt-1" id="<?= $k['id'] ?>Cmp"></div>
            </div>
            <div class="rounded-circle bg-<?= $k['color'] ?> bg-opacity-10 p-3">
              <i class="fas <?= $k['icon'] ?> text-<?= $k['color'] ?> fs-5"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3 mb-4">
    <!-- Biểu đồ doanh thu 12 tháng -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Doanh thu 12 tháng gần nhất</h6>
          <canvas id="chartRevenue" height="90"></canvas>
        </div>
      </div>
    </div>
    <!-- Top 5 khách hàng -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-trophy me-2 text-warning"></i>Top 5 khách hàng</h6>
          <canvas id="chartTopCustomers" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <!-- Tiến độ đơn hàng -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-tasks me-2 text-info"></i>Tiến độ đơn hàng</h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Mã đơn</th><th>Khách hàng</th><th>Ngày giao</th>
                  <th style="min-width:120px;">Tiến độ</th><th>Trạng thái</th>
                </tr>
              </thead>
              <tbody id="ordersProgressBody">
                <tr><td colspan="5" class="text-center text-muted py-3">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Cảnh báo -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-bell me-2 text-danger"></i>Cảnh báo</h6>
          <div id="alertsList">
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
let chartRevInstance = null;
let chartCustInstance = null;

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function fmtPct(v) { return v.toFixed(1) + '%'; }
function cmpArrow(cur, prev) {
  if (prev === 0) return '';
  const diff = ((cur - prev) / prev * 100).toFixed(1);
  const up = diff >= 0;
  const cls = up ? 'text-success' : 'text-danger';
  const icon = up ? 'fa-arrow-up' : 'fa-arrow-down';
  return `<span class="${cls}"><i class="fas ${icon}"></i> ${up?'+':''}${diff}%</span> <span class="text-muted ms-1">vs tháng trước</span>`;
}
function statusBadge(s) {
  const map = {pending:'secondary',in_progress:'primary',done:'success',cancelled:'danger'};
  const lbl = {pending:'Chờ SX',in_progress:'Đang SX',done:'Hoàn thành',cancelled:'Đã huỷ'};
  return `<span class="badge bg-${map[s]||'secondary'}">${lbl[s]||s}</span>`;
}
function progressBar(pct) {
  const color = pct >= 95 ? '#22C55E' : pct >= 80 ? '#FACC15' : '#EF4444';
  return `<div class="d-flex align-items-center gap-2">
    <div class="progress flex-grow-1" style="height:8px;">
      <div class="progress-bar" style="width:${pct}%;background:${color};"></div>
    </div>
    <small style="min-width:36px;">${pct}%</small>
  </div>`;
}

function buildParams() {
  const p = new URLSearchParams({
    date_from: document.getElementById('dateFrom').value,
    date_to:   document.getElementById('dateTo').value,
  });
  const cust = document.getElementById('customerFilter').value;
  if (cust) p.set('customer_id', cust);
  return p.toString();
}

function loadAll() {
  fetch('/erp/api/reports/overview.php?' + buildParams())
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      const k = d.kpi;

      document.getElementById('kpiRevenueVal').textContent = k.revenue_fmt;
      document.getElementById('kpiRevenueCmp').innerHTML = cmpArrow(k.revenue, k.revenue_prev);
      document.getElementById('kpiDebtVal').textContent = k.debt_fmt;
      document.getElementById('kpiDebtCmp').innerHTML = '';
      if (document.getElementById('kpiPayrollVal')) {
        const payFmt = k.payroll_cost_fmt ?? (k.payroll_cost != null ? parseFloat(k.payroll_cost).toLocaleString('vi-VN') + ' đ' : '—');
        document.getElementById('kpiPayrollVal').textContent = payFmt;
        document.getElementById('kpiPayrollCmp').innerHTML = '';
      }
      document.getElementById('kpiOrdersVal').textContent = k.orders_in_progress;
      document.getElementById('kpiOrdersCmp').innerHTML = '';
      document.getElementById('kpiCompletionVal').textContent = fmtPct(k.completion_rate);
      document.getElementById('kpiCompletionCmp').innerHTML = '';
      document.getElementById('kpiOqcVal').textContent = fmtPct(k.oqc_error_rate);
      document.getElementById('kpiOqcCmp').innerHTML = '';
      document.getElementById('kpiStockVal').textContent = k.stock_items + ' mặt hàng';
      document.getElementById('kpiStockCmp').innerHTML = '';
      document.getElementById('kpiLateVal').textContent = k.orders_late + ' đơn';
      document.getElementById('kpiLateCmp').innerHTML = '';

      // Chart doanh thu
      const labels = d.chart_revenue.map(r => r.month);
      const amounts = d.chart_revenue.map(r => parseFloat(r.amount));
      if (chartRevInstance) chartRevInstance.destroy();
      chartRevInstance = new Chart(document.getElementById('chartRevenue'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Doanh thu',
            data: amounts,
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('vi-VN') + ' đ'
              }
            }
          },
          scales: {
            y: { ticks: { callback: v => v.toLocaleString('vi-VN') } }
          }
        }
      });

      // Top 5 KH
      const custLabels = d.top_customers.map(r => r.customer_name);
      const custAmounts = d.top_customers.map(r => parseFloat(r.total_amount));
      if (chartCustInstance) chartCustInstance.destroy();
      chartCustInstance = new Chart(document.getElementById('chartTopCustomers'), {
        type: 'bar',
        data: {
          labels: custLabels,
          datasets: [{
            label: 'Doanh thu',
            data: custAmounts,
            backgroundColor: '#22C55E',
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.x.toLocaleString('vi-VN') + ' đ'
              }
            }
          },
          scales: {
            x: { ticks: { callback: v => v.toLocaleString('vi-VN') } }
          }
        }
      });

      // Tiến độ đơn hàng
      const tbody = document.getElementById('ordersProgressBody');
      if (!d.orders_progress.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Không có đơn hàng</td></tr>';
      } else {
        tbody.innerHTML = d.orders_progress.map(o => `
          <tr>
            <td><strong>${esc(o.order_no)}</strong></td>
            <td>${esc(o.customer_name)}</td>
            <td><span class="text-muted">—</span></td>
            <td>${progressBar(o.progress_pct)}</td>
            <td>${statusBadge(o.status)}</td>
          </tr>
        `).join('');
      }

      // Cảnh báo
      const alertsList = document.getElementById('alertsList');
      if (!d.alerts.length) {
        alertsList.innerHTML = '<div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i>Không có cảnh báo</div>';
      } else {
        alertsList.innerHTML = d.alerts.map(a => {
          const icon = a.level === 'danger' ? 'fa-exclamation-circle' : a.level === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle';
          return `<div class="alert alert-${esc(a.level)} py-2 mb-2"><i class="fas ${icon} me-2"></i>${esc(a.message)}</div>`;
        }).join('');
      }
    })
    .catch(() => {
      document.getElementById('alertsList').innerHTML = '<div class="alert alert-danger">Lỗi tải dữ liệu</div>';
    });
}

document.addEventListener('DOMContentLoaded', loadAll);
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
