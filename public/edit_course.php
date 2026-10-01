<?php
/**
 * إعادة توجيه سلسة إلى قسم إدارة المناهج واللوائح الأكاديمية بالواجهة الموحدة
 */
$courseId = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['course_id']) ? $_GET['course_id'] : 1);
header("Location: /?section=curriculum&course_id=" . urlencode($courseId));
exit;
