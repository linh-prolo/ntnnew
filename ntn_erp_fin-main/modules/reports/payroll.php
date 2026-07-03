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
      <h4 class="mb-1"><i class="fas fa-users me-2 text-primary"></i>Báo cáo Nhân sự &amp; Lương</h4>
      <p class="text-muted mb-0">Tổng hợp lương, chuyên cần, OT, phòng ban</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <select id="payrollMonth" class="form-select form-select-sm" style="width:120px;">
        <?php
        for ($m = 1; $m <= 12; $m++) {
          $selected = ($m == (int)date('m')) ? 'selected' : '';
          echo "<option value=\"$m\" $selected>Tháng $m</option>";
        }
        ?>
      </select>
      <select id="payrollYear" class="form-select form-select-sm" style="width:90px;">
        <?php
        $curYear = (int)date('Y');
        for ($y = $curYear - 2; $y <= $curYear; $y++) {
          $selected = ($y === $curYear) ? 'selected' : '';
          echo "<option value=\"$y\" $selected>$y</option>";
        }
        ?>
      </select>
      <button class="btn btn-primary btn-sm" onclick="loadAll()">
        <i class="fas fa-sync-alt me-1"></i>Làm mới
      </button>
    </div>
  </div>

  <!-- KPI Cards hàng 1 -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng NV active</div>
          <div class="fs-5 fw-bold text-primary" id="kpiTotalActiveVal">—</div>
          <div class="mt-2 rounded-circle bg-primary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-users text-primary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng chi phí lương</div>
          <div class="fs-5 fw-bold text-danger" id="kpiTotalCostVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-coins text-danger"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Lương net TB/người</div>
          <div class="fs-5 fw-bold text-success" id="kpiAvgNetVal">—</div>
          <div class="mt-2 rounded-circle bg-success bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-wallet text-success"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng OT tháng (giờ)</div>
          <div class="fs-5 fw-bold text-warning" id="kpiTotalOTVal">—</div>
          <div class="mt-2 rounded-circle bg-warning bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-clock text-warning"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Số lần đi muộn</div>
          <div class="fs-5 fw-bold text-warning" id="kpiLateCntVal">—</div>
          <div class="mt-2 rounded-circle bg-warning bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-exclamation-triangle text-warning"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng trừ lương</div>
          <div class="fs-5 fw-bold text-danger" id="kpiTotalDeductVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-minus-circle text-danger"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Cards hàng 2: Chi tiết lương -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Lương cơ bản thực nhận</div>
          <div class="fs-6 fw-bold text-primary" id="kpiBasicVal">—</div>
          <div class="mt-2 rounded-circle bg-primary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-money-bill text-primary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng phụ cấp</div>
          <div class="fs-6 fw-bold text-info" id="kpiAllowVal">—</div>
          <div class="mt-2 rounded-circle bg-info bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-plus-circle text-info"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Tổng OT (tiền)</div>
          <div class="fs-6 fw-bold text-warning" id="kpiOTAmtVal">—</div>
          <div class="mt-2 rounded-circle bg-warning bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-hourglass-half text-warning"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">BHXH nhân viên</div>
          <div class="fs-6 fw-bold text-secondary" id="kpiBhxhNvVal">—</div>
          <div class="mt-2 rounded-circle bg-secondary bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-shield-alt text-secondary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">BHXH công ty</div>
          <div class="fs-6 fw-bold text-danger" id="kpiBhxhCtyVal">—</div>
          <div class="mt-2 rounded-circle bg-danger bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-building text-danger"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Thuế TNCN</div>
          <div class="fs-6 fw-bold text-dark" id="kpiPitVal">—</div>
          <div class="mt-2 rounded-circle bg-dark bg-opacity-10 p-2 d-inline-block">
            <i class="fas fa-file-alt text-dark"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Biểu đồ hàng 1 -->
  <div class="row g-3 mb-4">
    <!-- Line chart 12 tháng -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-line me-2 text-danger"></i>Chi phí lương 12 tháng</h6>
          <canvas id="chartPayroll12" height="130"></canvas>
        </div>
      </div>
    </div>
    <!-- Doughnut phân bố theo phòng ban -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Phân bố NV theo phòng ban</h6>
          <canvas id="chartDeptPie" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Bar chart chi phí lương theo phòng ban -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fas fa-chart-bar me-2 text-warning"></i>Chi phí lương theo phòng ban</h6>
      <canvas id="chartDeptCost" height="80"></canvas>
    </div>
  </div>

  <!-- Bảng chi tiết theo phòng ban -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fas fa-table me-2 text-primary"></i>Chi tiết lương theo phòng ban</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Phòng ban</th>
              <th class="text-end">Số NV</th>
              <th class="text-end">Gross</th>
              <th class="text-end">BHXH DN</th>
              <th class="text-end">Tổng chi phí</th>
              <th class="text-end">Net</th>
              <th class="text-end">OT</th>
            </tr>
          </thead>
          <tbody id="deptTableBody">
            <tr><td colspan="8" class="text-center text-muted py-3">Đang tải...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <!-- Bảng nhân viên đi muộn nhiều nhất -->
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-user-clock me-2 text-warning"></i>Nhân viên đi muộn nhiều nhất</h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Họ tên</th>
                  <th>Phòng ban</th>
                  <th class="text-end">Số lần muộn</th>
                  <th class="text-end">Phút muộn</th>
                </tr>
              </thead>
              <tbody id="lateTableBody">
                <tr><td colspan="5" class="text-center text-muted py-3">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- Bảng Top OT -->
    <div class="col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fas fa-star me-2 text-warning"></i>Top nhân viên OT nhiều nhất</h6>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Họ tên</th>
                  <th>Phòng ban</th>
                  <th class="text-end">Giờ OT</th>
                  <th class="text-end">Số ngày</th>
                </tr>
              </thead>
              <tbody id="otTableBody">
                <tr><td colspan="5" class="text-center text-muted py-3">Đang tải...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Thống kê nghỉ phép -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fas fa-calendar-alt me-2 text-info"></i>Thống kê nghỉ phép theo loại</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Loại nghỉ</th>
              <th class="text-end">Số đơn</th>
              <th class="text-end">Tổng ngày</th>
            </tr>
          </thead>
          <tbody id="leaveTableBody">
            <tr><td colspan="3" class="text-center text-muted py-3">Đang tải...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartPayroll12Instance = null;
let chartDeptPieInstance   = null;
let chartDeptCostInstance  = null;

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function fmt(n) {
  n = parseFloat(n) || 0;
  return n.toLocaleString('vi-VN') + ' đ';
}

function fmtH(n) {
  return (parseFloat(n) || 0).toLocaleString('vi-VN') + ' h';
}

function buildParams() {
  const month = document.getElementById('payrollMonth').value;
  const year  = document.getElementById('payrollYear').value;
  const mm    = String(month).padStart(2, '0');
  const lastDay = new Date(parseInt(year, 10), parseInt(month, 10), 0).getDate();
  const dateFrom = `${year}-${mm}-01`;
  const dateTo   = `${year}-${mm}-${String(lastDay).padStart(2, '0')}`;
  return new URLSearchParams({ date_from: dateFrom, date_to: dateTo }).toString();
}

const CHART_COLORS = ['#3B82F6','#22C55E','#FACC15','#EF4444','#8B5CF6','#F97316','#14B8A6','#EC4899','#94A3B8','#06B6D4'];

const LEAVE_TYPE_LABEL = {
  annual:     'Phép năm',
  sick:       'Nghỉ ốm',
  unpaid:     'Không phép',
  maternity:  'Thai sản',
  paternity:  'Nghỉ con ốm',
  other:      'Khác',
};

function loadAll() {
  fetch('/erp/api/reports/payroll.php?' + buildParams())
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      const k   = d.kpi   || {};
      const att = d.att_kpi || {};
      const ot  = d.ot_kpi  || {};

      // ── KPI hàng 1 ──
      document.getElementById('kpiTotalActiveVal').textContent = (k.total_active ?? 0) + ' người';
      document.getElementById('kpiTotalCostVal').textContent   = fmt(k.tong_chi_phi_luong);

      const soPhieu = parseInt(k.so_phieu) || 0;
      const tongNet = parseFloat(k.tong_net) || 0;
      const avgNet  = soPhieu > 0 ? tongNet / soPhieu : 0;
      document.getElementById('kpiAvgNetVal').textContent     = fmt(avgNet);

      const totalOTh = (parseFloat(ot.gio_ot_thuong) || 0)
                     + (parseFloat(ot.gio_ot_cuoi_tuan) || 0)
                     + (parseFloat(ot.gio_ot_le) || 0)
                     + (parseFloat(ot.gio_ot_dem) || 0);
      document.getElementById('kpiTotalOTVal').textContent    = totalOTh.toLocaleString('vi-VN') + ' h';
      document.getElementById('kpiLateCntVal').textContent    = (att.so_lan_muon ?? 0) + ' lần';

      const totalDeduct = (parseFloat(k.tong_tru_kpi) || 0) + (parseFloat(k.tong_tru_muon) || 0);
      document.getElementById('kpiTotalDeductVal').textContent = fmt(totalDeduct);

      // ── KPI hàng 2 ──
      document.getElementById('kpiBasicVal').textContent    = fmt(k.tong_luong_cb);
      document.getElementById('kpiAllowVal').textContent    = fmt(k.tong_phu_cap);
      document.getElementById('kpiOTAmtVal').textContent    = fmt(k.tong_ot);
      document.getElementById('kpiBhxhNvVal').textContent   = fmt(k.tong_bhxh_nv);
      document.getElementById('kpiBhxhCtyVal').textContent  = fmt(k.tong_bhxh_cty);
      document.getElementById('kpiPitVal').textContent      = fmt(k.tong_thue);

      // ── Chart 12 tháng ──
      const months = (d.chart_12months || []).map(r => r.month);
      const costs  = (d.chart_12months || []).map(r => parseFloat(r.tong_chi_phi) || 0);
      const nets   = (d.chart_12months || []).map(r => parseFloat(r.tong_net)     || 0);
      const bhxhs  = (d.chart_12months || []).map(r => parseFloat(r.tong_bhxh_cty)|| 0);
      if (chartPayroll12Instance) chartPayroll12Instance.destroy();
      chartPayroll12Instance = new Chart(document.getElementById('chartPayroll12'), {
        type: 'line',
        data: {
          labels: months,
          datasets: [
            { label: 'Tổng chi phí (Gross+BHXH DN)', data: costs, borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.06)', fill: true, tension: 0.3, pointRadius: 4 },
            { label: 'Lương Net',                    data: nets,  borderColor: '#22C55E', backgroundColor: 'rgba(34,197,94,0.06)',  fill: true, tension: 0.3, pointRadius: 4 },
            { label: 'BHXH công ty',                 data: bhxhs, borderColor: '#F97316', backgroundColor: 'rgba(249,115,22,0.06)', fill: true, tension: 0.3, pointRadius: 4 },
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

      // ── Chart Doughnut phân bố phòng ban ──
      const deptLabels = (d.dept_distribution || []).map(r => r.dept_name);
      const deptNvs    = (d.dept_distribution || []).map(r => parseInt(r.so_nv));
      if (chartDeptPieInstance) chartDeptPieInstance.destroy();
      chartDeptPieInstance = new Chart(document.getElementById('chartDeptPie'), {
        type: 'doughnut',
        data: {
          labels: deptLabels,
          datasets: [{ data: deptNvs, backgroundColor: CHART_COLORS }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom', labels: { font: { size: 10 } } },
            tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' người' } }
          }
        }
      });

      // ── Chart Bar chi phí theo phòng ban ──
      const bdLabels = (d.by_dept || []).map(r => r.dept_name);
      const bdCosts  = (d.by_dept || []).map(r => parseFloat(r.tong_chi_phi) || 0);
      if (chartDeptCostInstance) chartDeptCostInstance.destroy();
      chartDeptCostInstance = new Chart(document.getElementById('chartDeptCost'), {
        type: 'bar',
        data: {
          labels: bdLabels,
          datasets: [{
            label: 'Tổng chi phí lương',
            data: bdCosts,
            backgroundColor: 'rgba(59,130,246,0.7)',
            borderColor: '#3B82F6', borderWidth: 1,
          }]
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

      // ── Bảng chi tiết phòng ban ──
      const deptBody = document.getElementById('deptTableBody');
      if (!d.by_dept || !d.by_dept.length) {
        deptBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Không có dữ liệu</td></tr>';
      } else {
        deptBody.innerHTML = d.by_dept.map((r, i) => `<tr>
          <td>${i + 1}</td>
          <td>${esc(r.dept_name)}</td>
          <td class="text-end">${r.so_nv}</td>
          <td class="text-end">${fmt(r.tong_gross)}</td>
          <td class="text-end">${fmt(r.tong_bhxh_cty)}</td>
          <td class="text-end fw-bold text-danger">${fmt(r.tong_chi_phi)}</td>
          <td class="text-end">${fmt(r.tong_net)}</td>
          <td class="text-end">${fmt(r.tong_ot)}</td>
        </tr>`).join('');
      }

      // ── Bảng nhân viên đi muộn ──
      const lateBody = document.getElementById('lateTableBody');
      if (!d.late_employees || !d.late_employees.length) {
        lateBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Không có dữ liệu đi muộn</td></tr>';
      } else {
        lateBody.innerHTML = d.late_employees.map((r, i) => `<tr>
          <td>${i + 1}</td>
          <td>${esc(r.full_name)}</td>
          <td>${esc(r.dept_name)}</td>
          <td class="text-end"><span class="badge bg-warning text-dark">${r.so_lan_muon} lần</span></td>
          <td class="text-end">${r.tong_phut_muon} phút</td>
        </tr>`).join('');
      }

      // ── Bảng Top OT ──
      const otBody = document.getElementById('otTableBody');
      if (!d.top_ot || !d.top_ot.length) {
        otBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Không có dữ liệu OT</td></tr>';
      } else {
        otBody.innerHTML = d.top_ot.map((r, i) => `<tr>
          <td>${i + 1}</td>
          <td>${esc(r.full_name)}</td>
          <td>${esc(r.dept_name)}</td>
          <td class="text-end fw-bold">${parseFloat(r.tong_gio_ot).toLocaleString('vi-VN')} h</td>
          <td class="text-end">${r.so_ngay_ot} ngày</td>
        </tr>`).join('');
      }

      // ── Bảng nghỉ phép ──
      const leaveBody = document.getElementById('leaveTableBody');
      if (!d.leave_summary || !d.leave_summary.length) {
        leaveBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Không có đơn nghỉ phép</td></tr>';
      } else {
        leaveBody.innerHTML = d.leave_summary.map(r => `<tr>
          <td>${esc(LEAVE_TYPE_LABEL[r.leave_type] ?? r.leave_type)}</td>
          <td class="text-end">${r.so_don}</td>
          <td class="text-end">${parseFloat(r.tong_ngay).toLocaleString('vi-VN')} ngày</td>
        </tr>`).join('');
      }
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', loadAll);
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/erp/includes/footer.php'; ?>
