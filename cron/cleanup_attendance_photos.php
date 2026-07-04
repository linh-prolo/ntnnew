<?php
/**
 * Cron: Xóa ảnh chấm công cũ hơn 2 tháng
 * Chạy mỗi ngày lúc 02:00: 0 2 * * * php /path/to/erp/cron/cleanup_attendance_photos.php
 */

$uploadBase = dirname(__DIR__) . '/uploads/attendance/';
$retentionInterval = '-2 months';
$cutoff = date('Y-m-d', strtotime($retentionInterval));

if (!is_dir($uploadBase)) {
    exit(0);
}

$deleted = 0;
$freed = 0;

foreach (glob($uploadBase . '*', GLOB_ONLYDIR) as $dayDir) {
    $dirDate = basename($dayDir);
    if ($dirDate < $cutoff) {
        foreach (glob($dayDir . '/*.jpg') as $file) {
            $fileSize = filesize($file);
            if ($fileSize !== false) {
                $freed += $fileSize;
            }
            @unlink($file);
            $deleted++;
        }
        @rmdir($dayDir);
    }
}

echo date('Y-m-d H:i:s') . " - Cleaned up {$deleted} photos, freed " . round($freed / 1024 / 1024, 2) . " MB\n";
