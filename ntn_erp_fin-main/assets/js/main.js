// ======= SIDEBAR TOGGLE =======
const sidebarEl      = document.getElementById('sidebar');
const mainContentEl  = document.querySelector('.main-content');
const toggleBtn      = document.getElementById('sidebarToggle');

if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
            sidebarEl.classList.toggle('mobile-open');
        } else {
            sidebarEl.classList.toggle('collapsed');
            if (mainContentEl) mainContentEl.classList.toggle('expanded');
        }
    });
}

// ======= DATE VALIDATION (Đơn nghỉ phép) =======
const startDateInput = document.querySelector('input[name="start_date"]');
const endDateInput   = document.querySelector('input[name="end_date"]');
if (startDateInput && endDateInput) {
    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });
}

// ======= ĐỒNG HỒ REAL-TIME =======
function updateClock() {
    const el = document.getElementById('liveClock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('vi-VN');
    }
}
setInterval(updateClock, 1000);
updateClock();

// ======= KPI BADGE - Đếm chưa nhập kết quả =======
(function loadKpiBadge() {
    const badge = document.getElementById('sidebarKpiCount');
    if (!badge) return;
    fetch('/erp/api/kpi/count_pending.php')
        .then(r => r.json())
        .then(res => {
            if (res.count > 0) {
                badge.textContent = res.count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(() => {});
})();