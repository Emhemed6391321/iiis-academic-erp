<?php
/**
 * إعادة توجيه سلسة إلى الواجهة الموحدة للنظام الأكاديمي
 */
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'calendar';
header("Location: /?section=settings&tab=" . urlencode($tab));
exit;
