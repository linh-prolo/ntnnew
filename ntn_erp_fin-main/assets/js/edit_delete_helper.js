/**
 * Helper: Hiển thị modal sửa/xóa với kiểm tra quyền
 * Dùng chung cho tất cả module
 */

/**
 * Kiểm tra có được sửa/xóa không
 * @param {string} recordDate - YYYY-MM-DD
 * @param {string} userRole   - role của user hiện tại
 * @returns {{canEdit: boolean, isLocked: boolean, lockMsg: string}}
 */
function checkEditPermission(recordDate, userRole) {
    const today      = new Date().toISOString().slice(0, 10);
    const isToday    = recordDate === today;
    const isDirector = userRole === 'director';

    if (isToday || isDirector) {
        return {
            canEdit  : true,
            isLocked : !isToday && isDirector,
            lockMsg  : !isToday ? '⚠️ Bạn đang sửa bản ghi KHÔNG phải hôm nay (quyền Giám đốc)' : ''
        };
    }
    return {
        canEdit  : false,
        isLocked : true,
        lockMsg  : 'Chỉ Giám đốc mới được sửa/xóa sau ngày tạo'
    };
}

/**
 * Gọi API sửa/xóa và xử lý kết quả
 */
function callEditAPI(url, formData, onSuccess) {
    return fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                onSuccess(res);
            } else {
                alert('❌ ' + res.msg);
            }
            return res;
        })
        .catch(() => alert('❌ Lỗi kết nối server'));
}

/**
 * Confirm xóa chuẩn
 */
function confirmDelete(name, callback) {
    if (confirm(`Bạn có chắc muốn XÓA "${name}"?\nHành động này không thể hoàn tác!`)) {
        callback();
    }
}