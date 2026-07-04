<?php
/**
 * Cron: Xóa ảnh chấm công cũ hơn 2 tháng
 * Chạy mỗi ngày lúc 02:00: 0 2 * * * php /path/to/erp/cron/cleanup_attendance_photos.php
 */

$uploadBase = dirname(__DIR__) . '/uploads/attendance/';
$retentionIntervalEnv = getenv('ATTENDANCE_PHOTO_RETENTION_INTERVAL') ?: '';
$retentionInterval = preg_match('/^-\d+\s+(day|days|month|months)$/', $retentionIntervalEnv)
    ? $retentionIntervalEnv
    : '-2 months';
$cutoffTimestamp = strtotime($retentionInterval);
if ($cutoffTimestamp === false) {
    $cutoffTimestamp = strtotime('-2 months');
}
$cutoff = date('Y-m-d', $cutoffTimestamp);

if (!is_dir($uploadBase)) {
    exit(0);
}

$deleted = 0;
$freed = 0;

foreach (glob($uploadBase . '*', GLOB_ONLYDIR) as $dayDir) {
    $dirDate = basename($dayDir);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dirDate)) {
        continue;
    }
    if ($dirDate < $cutoff) {
        foreach (glob($dayDir . '/*.jpg') as $file) {
            $fileSize = filesize($file);
            if ($fileSize !== false) {
                $freed += $fileSize;
            }
            if (is_file($file) && unlink($file)) {
                $deleted++;
            } else {
                error_log('cleanup_attendance_photos: cannot delete file ' . $file);
            }
        }
        if (!rmdir($dayDir)) {
            error_log('cleanup_attendance_photos: cannot remove directory ' . $dayDir);
        }
    }
}

echo date('Y-m-d H:i:s') . " - Cleaned up {$deleted} photos, freed " . round($freed / 1024 / 1024, 2) . " MB\n";
