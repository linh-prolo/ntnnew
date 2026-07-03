<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
requireLogin();
requireRole('director', 'accountant');

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
      <h4 class="mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Báo cáo Tài chính</h4>
      <p class="text-muted mb-0">Doanh thu, chi phí lương &amp; hành chính, công nợ</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <input type="date" id="dateFrom" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" style="width:140px;">
      <input type="date" id="dateTo"   class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"  style="width:140px;">
      <button class="btn btn-primary btn-sm" onclick="loadAll()">
        <i class="fas fa-sync-alt me-1"></i>Làm mới
      </button>
    </div>
  </div>

  <!-- KPI Cards hàng 1: Doanh thu / Lợi nhuận / Công nợ -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Doanh thu tháng</div>
          <div class="fs-5 fw-bold text-primary" id="kpiRevenueVal">—</div>
          <div class="mt-2 rounded-circle bg-primary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-dollar-sign text-primary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Chi phí lương</div>
          <div class="fs-5 fw-bold text-danger" id="kpiPayrollVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-users text-danger"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Chi phí hành chính</div>
          <div class="fs-5 fw-bold text-warning" id="kpiAdminVal">—</div>
          <div class="mt-2 rounded-circle bg-warning bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-receipt text-warning"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng chi phí</div>
          <div class="fs-5 fw-bold text-danger" id="kpiExpTotalVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-file-invoice text-danger"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Lợi nhuận tạm tính</div>
          <div class="fs-5 fw-bold text-success" id="kpiProfitVal">—</div>
          <div class="mt-2 rounded-circle bg-success bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-chart-line text-success"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Công nợ phải thu</div>
          <div class="fs-5 fw-bold text-danger" id="kpiDebtVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-hand-holding-usd text-danger"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Cards hàng 2: Chi tiết thanh toán -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">HĐ chưa thanh toán</div>
          <div class="fs-5 fw-bold text-secondary" id="kpiUnpaidVal">—</div>
          <div class="mt-2 rounded-circle bg-secondary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-file-invoice text-secondary"></i>
          </div>
        </div>

      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Đã thu tháng</div>
          <div class="fs-5 fw-bold text-info" id="kpiPaidVal">—</div>
          <div class="mt-2 rounded-circle bg-info bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-money-bill-wave text-info"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Gross lương</div>
          <div class="fs-5 fw-bold text-danger" id="kpiPayrollGrossVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-coins text-danger"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">BHXH công ty</div>
          <div class="fs-5 fw-bold text-danger" id="kpiSiCompanyVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-shield-alt text-danger"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Lương Net</div>
          <div class="fs-5 fw-bold text-success" id="kpiPayrollNetVal">—</div>
          <div class="mt-2 rounded-circle bg-success bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-wallet text-success"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Số NV tính lương</div>
          <div class="fs-5 fw-bold text-primary" id="kpiHeadcountVal">—</div>
          <div class="mt-2 rounded-circle bg-primary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-user-check text-primary"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI: Tồn kho vật tư -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng tồn kho (qty)</div>
          <div class="fs-5 fw-bold text-info" id="kpiInventoryQtyVal">—</div>
          <div class="mt-2 rounded-circle bg-info bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-boxes text-info"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Số mặt hàng còn tồn</div>
          <div class="fs-5 fw-bold text-secondary" id="kpiInventoryItemsVal">—</div>
          <div class="mt-2 rounded-circle bg-secondary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-list text-secondary"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h6 class="card-title mb-3">
        <i class="fas fa-receipt me-2 text-warning"></i>Chi tiết chi phí hành chính
      </h6>
      <div class="row g-3">
        <div class="col-md-3">
          <div class="text-muted small">Chi phí văn phòng &amp; hành chính</div>
          <div class="fw-bold" id="kpiAdminBaseVal">—</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Khấu hao tài sản</div>
          <div class="fw-bold" id="kpiDeprVal">—</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Chi phí phương tiện</div>
          <div class="fw-bold" id="kpiVehicleVal">—</div>
          <div class="text-muted" style="font-size:11px" id="kpiVehicleDetail">—</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small fw-semibold">Tổng chi phí hành chính</div>
          <div class="fw-bold text-warning fs-6" id="kpiAdminTotalVal">—</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Biểu đồ hàng 1 -->
  <div class="row g-3 mb-4">
    <!-- So sánh 12 tháng -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Doanh thu / Chi phí 12 tháng</h6>
          <canvas id="chartRevenue" height="120"></canvas>
        </div>
      </div>
    </div>
    <!-- Công nợ theo KH -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-bar me-2 text-danger"></i>Công nợ theo khách hàng</h6>
          <canvas id="chartDebt" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Biểu đồ hàng 2 -->
  <div class="row g-3 mb-4">
    <!-- Chi phí hành chính theo nhóm -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-pie me-2 text-warning"></i>Chi phí hành chính theo nhóm</h6>
          <canvas id="chartExpense" height="200"></canvas>
        </div>
      </div>
    </div>
    <!-- Chi phí lương 12 tháng -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-users me-2 text-danger"></i>Chi phí lương 12 tháng</h6>
          <canvas id="chartPayroll" height="120"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Top 10 khách hàng còn nợ -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fas fa-list me-2 text-danger"></i>Top 10 khách hàng còn nợ</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Khách hàng</th>
              <th class="text-end">Tổng nợ</th>
              <th>Hạn lâu nhất</th>
              <th>Quá hạn</th>
            </tr>
          </thead>
          <tbody id="topDebtorsBody">
            <tr><td colspan="5" class="text-center text-muted py-3">Đang tải...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Top vật tư tồn nhiều -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fas fa-boxes me-2 text-info"></i>Top vật tư tồn kho nhiều nhất</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Tên vật tư</th>
                <th>ĐVT</th>
                <th class="text-end">Tồn hiện tại</th>
              </tr>
            </thead>
            <tbody id="topInventoryBody">
              <tr><td colspan="4" class="text-center text-muted py-3">Đang tải...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartRevInstance  = null;
let chartExpInstance  = null;
let chartDebtInstance = null;
let chartPayrollInstance = null;

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function fmt(n) {
  n = parseFloat(n) || 0;
  return n.toLocaleString('vi-VN') + ' đ';
}

function buildParams() {
  return new URLSearchParams({
    date_from: document.getElementById('dateFrom').value,
    date_to:   document.getElementById('dateTo').value,
  }).toString();
}

function loadAll() {
  fetch('/erp/api/reports/finance.php?' + buildParams())
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      const k = d.kpi;

      document.getElementById('kpiRevenueVal').textContent   = fmt(k.revenue);
      document.getElementById('kpiPayrollVal').textContent   = fmt(k.expense_payroll);
      document.getElementById('kpiAdminVal').textContent     = fmt(k.expense_admin);
      document.getElementById('kpiExpTotalVal').textContent  = fmt(k.expense_total);

      const profitEl = document.getElementById('kpiProfitVal');
      profitEl.textContent = fmt(k.profit);
      profitEl.className = 'fs-5 fw-bold ' + (parseFloat(k.profit) >= 0 ? 'text-success' : 'text-danger');

      document.getElementById('kpiDebtVal').textContent         = fmt(k.debt);
      document.getElementById('kpiUnpaidVal').textContent       = (k.unpaid_count ?? 0) + ' HĐ';
      document.getElementById('kpiPaidVal').textContent         = fmt(k.paid_month);
      document.getElementById('kpiPayrollGrossVal').textContent = fmt(k.payroll_gross);
      document.getElementById('kpiSiCompanyVal').textContent    = fmt(k.payroll_si_company);
      document.getElementById('kpiPayrollNetVal').textContent   = fmt(k.payroll_net);
      document.getElementById('kpiHeadcountVal').textContent    = (k.payroll_headcount ?? 0) + ' NV';

      if (document.getElementById('kpiAdminBaseVal')) {
        document.getElementById('kpiAdminBaseVal').textContent = fmt(k.expense_admin_base);
        document.getElementById('kpiDeprVal').textContent = fmt(k.expense_depr);
        document.getElementById('kpiVehicleVal').textContent = fmt(k.expense_vehicle);
        document.getElementById('kpiVehicleDetail').textContent =
          'Dầu: ' + fmt(k.expense_fuel) + ' | Bảo dưỡng: ' + fmt(k.expense_maintenance) + ' | Cầu đường: ' + fmt(k.expense_toll);
        document.getElementById('kpiAdminTotalVal').textContent = fmt(k.expense_admin);
      }

      // KPI tồn kho
      if (document.getElementById('kpiInventoryQtyVal')) {
        document.getElementById('kpiInventoryQtyVal').textContent = (parseFloat(k.inventory_total_qty) || 0).toLocaleString('vi-VN');
      }
      if (document.getElementById('kpiInventoryItemsVal')) {
        document.getElementById('kpiInventoryItemsVal').textContent = (k.inventory_items ?? 0) + ' mặt hàng';
      }

      // ── Chart: Doanh thu / Chi phí lương / Chi phí hành chính 12 tháng ──
      // Gộp tất cả labels từ 3 bộ dữ liệu
      const allMonths = [...new Set([
        ...(d.chart_revenue       || []).map(r => r.month),
        ...(d.chart_payroll       || []).map(r => r.month),
        ...(d.chart_expense_admin || []).map(r => r.month),
      ])].sort();

      const revMap     = Object.fromEntries((d.chart_revenue       || []).map(r => [r.month, parseFloat(r.amount)]));
      const payMap     = Object.fromEntries((d.chart_payroll       || []).map(r => [r.month, parseFloat(r.tong_chi_phi)]));
      const adminMap   = Object.fromEntries((d.chart_expense_admin || []).map(r => [r.month, parseFloat(r.tong_chi_phi)]));

      if (chartRevInstance) chartRevInstance.destroy();
      chartRevInstance = new Chart(document.getElementById('chartRevenue'), {
        type: 'line',
        data: {
          labels: allMonths,
          datasets: [
            {
              label: 'Doanh thu',
              data: allMonths.map(m => revMap[m] ?? 0),
              borderColor: '#3B82F6',
              backgroundColor: 'rgba(59,130,246,0.06)',
              fill: true, tension: 0.3, pointRadius: 4,
            },
            {
              label: 'Chi phí lương',
              data: allMonths.map(m => payMap[m] ?? 0),
              borderColor: '#EF4444',
              backgroundColor: 'rgba(239,68,68,0.06)',
              fill: true, tension: 0.3, pointRadius: 4,
            },
            {
              label: 'Chi phí hành chính',
              data: allMonths.map(m => adminMap[m] ?? 0),
              borderColor: '#F97316',
              backgroundColor: 'rgba(249,115,22,0.06)',
              fill: true, tension: 0.3, pointRadius: 4,
            },
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('vi-VN') + ' đ' } }
          },
          scales: { y: { ticks: { callback: v => v.toLocaleString('vi-VN') } } }
        }
      });

      // ── Chart: Chi phí hành chính theo nhóm (Doughnut) ──
      const expLabels = (d.chart_expense || []).map(r => r.category_name);
      const expAmts   = (d.chart_expense || []).map(r => parseFloat(r.total));
      const expColors = ['#3B82F6','#22C55E','#FACC15','#EF4444','#8B5CF6','#F97316','#14B8A6','#EC4899','#94A3B8','#06B6D4'];
      if (chartExpInstance) chartExpInstance.destroy();
      chartExpInstance = new Chart(document.getElementById('chartExpense'), {
        type: 'doughnut',
        data: {
          labels: expLabels,
          datasets: [{ data: expAmts, backgroundColor: expColors }]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } }
        }
      });

      // ── Chart: Công nợ theo KH (Bar ngang) ──
      const debtLabels = (d.chart_debt || []).map(r => r.customer_name);
      const debtAmts   = (d.chart_debt || []).map(r => parseFloat(r.debt_amount));
      if (chartDebtInstance) chartDebtInstance.destroy();
      chartDebtInstance = new Chart(document.getElementById('chartDebt'), {
        type: 'bar',
        data: {
          labels: debtLabels,
          datasets: [{ label: 'Công nợ', data: debtAmts, backgroundColor: '#EF4444' }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.x.toLocaleString('vi-VN') + ' đ' } }
          },
          scales: { x: { ticks: { callback: v => v.toLocaleString('vi-VN') } } }
        }
      });

      // ── Chart: Chi phí lương 12 tháng ──
      const payMonths = (d.chart_payroll || []).map(r => r.month);
      const payCosts  = (d.chart_payroll || []).map(r => parseFloat(r.tong_chi_phi));
      if (chartPayrollInstance) chartPayrollInstance.destroy();
      chartPayrollInstance = new Chart(document.getElementById('chartPayroll'), {
        type: 'bar',
        data: {
          labels: payMonths,
          datasets: [{
            label: 'Chi phí lương (Gross + BHXH DN)',
            data: payCosts,
            backgroundColor: 'rgba(239,68,68,0.7)',
            borderColor: '#EF4444',
            borderWidth: 1,
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('vi-VN') + ' đ' } }
          },
          scales: { y: { ticks: { callback: v => v.toLocaleString('vi-VN') } } }
        }
      });

      // ── Bảng Top Debtors ──
      const tbody = document.getElementById('topDebtorsBody');
      if (!d.top_debtors || !d.top_debtors.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Không có công nợ</td></tr>';
      } else {
        tbody.innerHTML = d.top_debtors.map((r, i) => {
          const overdue = parseInt(r.overdue_days) || 0;
          const badge = overdue > 0
            ? `<span class="badge bg-danger">${overdue} ngày</span>`
            : `<span class="badge bg-success">Chưa quá hạn</span>`;
          return `<tr>
            <td>${i + 1}</td>
            <td>${esc(r.customer_name)}</td>
            <td class="text-end fw-bold">${fmt(r.total_debt)}</td>
            <td>${r.oldest_due_date ? esc(r.oldest_due_date) : '—'}</td>
            <td>${badge}</td>
          </tr>`;
        }).join('');
      }

      // Bảng top tồn kho
      const invBody = document.getElementById('topInventoryBody');
      if (invBody) {
        if (!d.top_inventory || !d.top_inventory.length) {
          invBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Không có dữ liệu tồn kho</td></tr>';
        } else {
          invBody.innerHTML = d.top_inventory.map((r, i) => `<tr>
            <td>${i + 1}</td>
            <td class="fw-semibold">${esc(r.item_name)}</td>
            <td>${esc(r.unit || '—')}</td>
            <td class="text-end fw-bold text-primary">${(parseFloat(r.remaining) || 0).toLocaleString('vi-VN')}</td>
          </tr>`).join('');
        }
      }
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', loadAll);
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
