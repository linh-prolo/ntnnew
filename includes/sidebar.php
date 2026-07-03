<?php
$currentPage = $_SERVER['REQUEST_URI'];
function isActive($path) {
    global $currentPage;
    return strpos($currentPage, $path) !== false ? 'active' : '';
}
function isGroupActive(array $paths): bool {
    global $currentPage;
    foreach ($paths as $p) {
        if (strpos($currentPage, $p) !== false) return true;
    }
    return false;
}
$sidebarUser = currentUser();

$activeGroup = '';
if (isGroupActive(['/modules/users/profile', '/modules/users/change_password'])) $activeGroup = 'personal';
elseif (isGroupActive(['/attendance/', '/leave_request', '/ot_request', '/all_attendance',
                        '/leave_manage', '/ot_manage', '/shift_schedule', '/shift_assign', '/shift_setup',
                        '/location_settings',
                        '/payroll/holidays'])) $activeGroup = 'attendance';
elseif (isGroupActive(['/payroll/'])) $activeGroup = 'payroll';
elseif (isGroupActive(['/master/'])) $activeGroup = 'master';
elseif (isGroupActive(['/warehouse/'])) $activeGroup = 'wh_sx';
elseif (isGroupActive(['/production/'])) $activeGroup = 'production';
elseif (isGroupActive(['/warehouse_admin/'])) $activeGroup = 'wh_admin';
elseif (isGroupActive(['/invoice/'])) $activeGroup = 'invoice';
elseif (isGroupActive(['/admin/expenses', '/admin/assets', '/admin/vehicles', '/admin/budget'])) $activeGroup = 'admin';
elseif (isGroupActive(['/modules/kpi/'])) $activeGroup = 'kpi';
elseif (isGroupActive(['/reports/'])) $activeGroup = 'reports';
elseif (isGroupActive(['/modules/users/index'])) $activeGroup = 'system';
?>
<div class="sidebar" id="sidebar">
  <ul class="nav flex-column pt-1">

    <!-- TỔNG QUAN -->
    <li class="nav-item">
      <a class="nav-link sidebar-top-link <?= isActive('/dashboard') ?>" href="/erp/dashboard.php">
        <i class="fas fa-home"></i><span>Tổng quan</span>
      </a>
    </li>

    <!-- CÁ NHÂN -->
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-personal">
        <i class="fas fa-user"></i><span>Cá nhân</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-personal">
        <li><a class="nav-link <?= isActive('/modules/users/profile') ?>"
               href="/erp/modules/users/profile.php?id=<?= $sidebarUser['id'] ?>">
          <i class="fas fa-id-card"></i><span>Hồ sơ của tôi</span></a></li>
        <li><a class="nav-link <?= isActive('/modules/users/change_password') ?>"
               href="/erp/modules/users/change_password.php?id=<?= $sidebarUser['id'] ?>">
          <i class="fas fa-key"></i><span>Đổi mật khẩu</span></a></li>
      </ul>
    </li>

    <!-- CHẤM CÔNG -->
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-attendance">
        <i class="fas fa-calendar-check"></i><span>Chấm công</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-attendance">
        <li><a class="nav-link <?= isActive('/attendance/index') ?>" href="/erp/modules/attendance/index.php">
          <i class="fas fa-calendar-check"></i><span>Lịch chấm công</span></a></li>
        <?php if (hasRole('employee','production','warehouse')): ?>
        <li><a class="nav-link <?= isActive('/leave_request') ?>" href="/erp/modules/attendance/leave_request.php">
          <i class="fas fa-calendar-minus"></i><span>Xin nghỉ phép</span></a></li>
        <li><a class="nav-link <?= isActive('/ot_request') ?>" href="/erp/modules/attendance/ot_request.php">
          <i class="fas fa-clock"></i><span>Đăng ký OT</span></a></li>
        <?php endif; ?>
        <?php if (hasRole('production','manager','director','accountant')): ?>
        <li><a class="nav-link <?= isActive('/all_attendance') ?>" href="/erp/modules/attendance/all_attendance.php">
          <i class="fas fa-table"></i><span>Bảng chấm công</span></a></li>
        <li><a class="nav-link <?= isActive('/leave_manage') ?>" href="/erp/modules/attendance/leave_manage.php">
          <i class="fas fa-clipboard-check"></i><span>Duyệt nghỉ phép</span>
          <span class="badge bg-warning text-dark ms-1" id="sidebarLeaveCount"></span></a></li>
        <li><a class="nav-link <?= isActive('/ot_manage') ?>" href="/erp/modules/attendance/ot_manage.php">
          <i class="fas fa-user-clock"></i><span>Duyệt OT</span>
          <span class="badge bg-info ms-1" id="sidebarOTCount"></span></a></li>
        <?php endif; ?>
        <?php if (hasRole('director','accountant','manager','production')): ?>
        <li><a class="nav-link <?= isActive('/shift_schedule') ?>" href="/erp/modules/attendance/shift_schedule.php">
          <i class="fas fa-calendar-alt"></i><span>Lịch ca tháng</span></a></li>
        <li><a class="nav-link <?= isActive('/shift_assign') ?>" href="/erp/modules/attendance/shift_assign.php">
          <i class="fas fa-users-cog"></i><span>Phân công ca</span></a></li>
        <?php endif; ?>
        <?php if (hasRole('director','accountant','manager')): ?>
        <li><a class="nav-link <?= isActive('/shift_setup') ?>" href="/erp/modules/attendance/shift_setup.php">
          <i class="fas fa-sliders-h"></i><span>Setup ca làm việc</span></a></li>
        <li><a class="nav-link <?= isActive('/location_settings') ?>" href="/erp/modules/attendance/location_settings.php">
          <i class="fas fa-map-marker-alt"></i><span>Cài đặt vị trí CC</span></a></li>
        <li><a class="nav-link <?= isActive('/payroll/holidays') ?>" href="/erp/modules/payroll/holidays.php">
          <i class="fas fa-calendar-times"></i><span>Ngày lễ</span></a></li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- BẢNG LƯƠNG -->
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-payroll">
        <i class="fas fa-money-check-alt"></i><span>Bảng lương</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-payroll">
        <?php if (hasRole('director','accountant')): ?>
        <li><a class="nav-link <?= isActive('/payroll/index') ?>" href="/erp/modules/payroll/index.php">
          <i class="fas fa-money-check-alt"></i><span>Quản lý kỳ lương</span></a></li>
        <?php endif; ?>
        <?php if (!hasRole('director')): ?>
        <li><a class="nav-link <?= isActive('/payroll/my_payroll') ?>" href="/erp/modules/payroll/my_payroll.php">
          <i class="fas fa-file-invoice-dollar"></i><span>Phiếu lương của tôi</span></a></li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- Các module sau chỉ hiển thị với role không phải employee -->
    <?php if (!hasRole('employee')): ?>

    <!-- DANH MỤC -->
    <?php if (hasRole('director','accountant','manager')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-master">
        <i class="fas fa-database"></i><span>Danh mục</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-master">
        <li><a class="nav-link <?= isActive('/master/customers') ?>" href="/erp/modules/master/customers.php">
          <i class="fas fa-users"></i><span>Khách hàng</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

    <!-- KHO SẢN XUẤT -->
    <?php if (hasRole('director','accountant','warehouse','production','manager')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-wh_sx">
        <i class="fas fa-boxes"></i><span>Kho Sản Xuất</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-wh_sx">
        <li><a class="nav-link <?= isActive('/warehouse/index') ?>" href="/erp/modules/warehouse/index.php">
          <i class="fas fa-tachometer-alt"></i><span>Tổng quan kho SX</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse/iqc_list') ?>" href="/erp/modules/warehouse/iqc_list.php">
          <i class="fas fa-file-import"></i><span>Nhập Kho IQC</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse/oqc_list') ?>" href="/erp/modules/warehouse/oqc_list.php">
          <i class="fas fa-boxes"></i><span>Kho thành phẩm OQC</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse/oqc_delivery') ?>" href="/erp/modules/warehouse/oqc_delivery.php">
          <i class="fas fa-truck"></i><span>Xuất kho / Giao hàng</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse/delivery_history') ?>" href="/erp/modules/warehouse/delivery_history.php">
          <i class="fas fa-history"></i><span>Lịch sử giao hàng</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

    <!-- SẢN XUẤT -->
    <?php if (hasRole('director','accountant','warehouse','production','manager')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-production">
        <i class="fas fa-industry"></i><span>Sản xuất</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-production">
        <li><a class="nav-link <?= isActive('/production/index') ?>" href="/erp/modules/production/index.php">
          <i class="fas fa-tasks"></i><span>Tiến độ gia công</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

    <!-- HOÁ ĐƠN -->
    <?php if (hasRole('director','accountant')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-invoice">
        <i class="fas fa-file-invoice"></i><span>Hoá đơn &amp; Công nợ</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-invoice">
        <li><a class="nav-link <?= isActive('/invoice/index') ?>" href="/erp/modules/invoice/index.php">
          <i class="fas fa-file-invoice"></i><span>Hoá đơn</span></a></li>
        <li><a class="nav-link <?= isActive('/invoice/debt') ?>" href="/erp/modules/invoice/debt.php">
          <i class="fas fa-hand-holding-usd"></i><span>Công nợ</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

    <!-- HÀNH CHÍNH -->
    <?php if (hasRole('director','accountant','manager')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-admin">
        <i class="fas fa-building"></i><span>Hành chính</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-admin">
        <li><a class="nav-link <?= isActive('/admin/expenses') ?>" href="/erp/modules/admin/expenses.php">
          <i class="fas fa-file-invoice-dollar"></i><span>Chi phí</span></a></li>
        <?php if (hasRole('director','accountant','manager')): ?>
        <li><a class="nav-link <?= isActive('/admin/assets') ?>" href="/erp/modules/admin/assets.php">
          <i class="fas fa-laptop"></i><span>Tài sản</span></a></li>
        <?php if (hasRole('director')): ?>
        <li><a class="nav-link <?= isActive('/admin/budget') ?>" href="/erp/modules/admin/budget.php">
          <i class="fas fa-chart-pie"></i><span>Ngân sách HC</span></a></li>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (hasRole('director','accountant','manager')): ?>
        <li><a class="nav-link <?= isActive('/admin/vehicles') ?>" href="/erp/modules/admin/vehicles.php">
          <i class="fas fa-car"></i><span>Phương tiện</span></a></li>
        <?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>

    <!-- KHO VẬT TƯ -->
    <?php if (hasRole('director','accountant','manager','warehouse')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-wh_admin">
        <i class="fas fa-warehouse"></i><span>Kho vật tư</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-wh_admin">
        <li><a class="nav-link <?= isActive('/warehouse_admin/index') ?>" href="/erp/modules/warehouse_admin/index.php">
          <i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse_admin/categories') ?>" href="/erp/modules/warehouse_admin/categories.php">
          <i class="fas fa-tags"></i><span>Nhóm vật tư</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse_admin/items') ?>" href="/erp/modules/warehouse_admin/items.php">
          <i class="fas fa-list-alt"></i><span>Danh mục vật tư</span></a></li>
        <li><a class="nav-link <?= isActive('/warehouse_admin/transactions') ?>" href="/erp/modules/warehouse_admin/transactions.php">
          <i class="fas fa-exchange-alt"></i><span>Nhập / Xuất kho</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

    <!-- BÁO CÁO -->
    <?php if (hasRole('director','accountant','manager')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-reports">
        <i class="fas fa-chart-bar"></i><span>Báo cáo</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-reports">
        <li><a class="nav-link <?= isActive('/reports/index') ?>" href="/erp/modules/reports/index.php">
          <i class="fas fa-tachometer-alt"></i><span>Tổng quan</span></a></li>
        <li><a class="nav-link <?= isActive('/reports/production') ?>" href="/erp/modules/reports/production.php">
          <i class="fas fa-industry"></i><span>Sản xuất</span></a></li>
        <li><a class="nav-link <?= isActive('/reports/warehouse') ?>" href="/erp/modules/reports/warehouse.php">
          <i class="fas fa-warehouse"></i><span>Kho</span></a></li>
        <li><a class="nav-link <?= isActive('/reports/finance') ?>" href="/erp/modules/reports/finance.php">
          <i class="fas fa-chart-line"></i><span>Tài chính</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

   

    <!-- QUẢN LÝ HỆ THỐNG -->
    <?php if (hasRole('director','accountant')): ?>
    <li class="nav-item">
      <a class="nav-link sidebar-group-toggle" href="#" data-target="grp-system">
        <i class="fas fa-cog"></i><span>Quản lý hệ thống</span>
        <i class="fas fa-chevron-down sidebar-arrow"></i>
      </a>
      <ul class="sidebar-submenu" id="grp-system">
        <li><a class="nav-link <?= isActive('/modules/users/index') ?>" href="/erp/modules/users/index.php">
          <i class="fas fa-users-cog"></i><span>Quản lý tài khoản</span></a></li>
      </ul>
    </li>
    <?php endif; ?>

    <?php endif; // !hasRole('employee') ?>

  </ul>
</div>

<script>
(function(){
  var activeGroup = <?= json_encode($activeGroup) ?>;
  var allToggles = document.querySelectorAll('.sidebar-group-toggle');

  function openGroup(targetId) {
    allToggles.forEach(function(t) {
      var id  = t.getAttribute('data-target');
      var sub = document.getElementById(id);
      var arr = t.querySelector('.sidebar-arrow');
      if (!sub) return;
      if (id === targetId) {
        sub.classList.add('open');
        t.classList.add('grp-active');
        if (arr) { arr.style.transform = 'rotate(180deg)'; arr.style.opacity = '1'; }
      } else {
        sub.classList.remove('open');
        t.classList.remove('grp-active');
        if (arr) { arr.style.transform = ''; arr.style.opacity = '0.45'; }
      }
    });
  }

  if (activeGroup) openGroup('grp-' + activeGroup);

  allToggles.forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      var targetId = toggle.getAttribute('data-target');
      var sub = document.getElementById(targetId);
      if (!sub) return;
      var isOpen = sub.classList.contains('open');
      if (isOpen) {
        sub.classList.remove('open');
        toggle.classList.remove('grp-active');
        var arr = toggle.querySelector('.sidebar-arrow');
        if (arr) { arr.style.transform = ''; arr.style.opacity = '0.45'; }
      } else {
        openGroup(targetId);
      }
    });
  });
})();
</script>
