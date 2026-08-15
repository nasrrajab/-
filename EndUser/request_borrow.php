<?php
// EndUser/request_borrow.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('EndUser');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$item_id = intval($_POST['item_id'] ?? 0);
$start_date = trim($_POST['start_date'] ?? '');
$end_date = trim($_POST['end_date'] ?? '');

if ($item_id === 0 || empty($start_date) || empty($end_date)) {
    $_SESSION['error'] = "معلمات طلب الاستعارة غير صالحة.";
    header("Location: dashboard.php");
    exit;
}

// 1. Fetch Item Constraints
$stmt = $config->prepare("SELECT MinBorrowDays, Title, Status FROM items WHERE Item_ID = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("العنصر غير موجود.");
}

if ($item['Status'] !== 'Available') {
    $_SESSION['error'] = "هذا العنصر غير متاح للاستعارة حالياً.";
    header("Location: item_detail.php?id=" . $item_id);
    exit;
}

// Calculate requested duration
$start_ts = strtotime($start_date);
$end_ts = strtotime($end_date);
$duration_days = ($end_ts - $start_ts) / 86400;

if ($start_ts > $end_ts) {
    $_SESSION['error'] = "يجب أن يكون تاريخ بدء الاستعارة قبل أو مساوياً لتاريخ الإرجاع.";
    header("Location: item_detail.php?id=" . $item_id);
    exit;
}

// 2. Validate Minimum Days duration
if ($duration_days < $item['MinBorrowDays']) {
    $_SESSION['error'] = "الحد الأدنى لمدد الاستعارة لهذا العنصر هو {$item['MinBorrowDays']} أيام. طلبك كان لـ {$duration_days} أيام.";
    header("Location: item_detail.php?id=" . $item_id);
    exit;
}

try {
    // 3. Check for Overlapping Blockout Dates in item_availability
    $block_check = $config->prepare("SELECT COUNT(*) FROM item_availability WHERE Item_ID = ? AND UnAvailableDate BETWEEN ? AND ?");
    $block_check->bind_param("iss", $item_id, $start_date, $end_date);
    $block_check->execute();
    $res = $block_check->get_result()->fetch_row();
    $blocked_count = $res ? intval($res[0]) : 0;
    
    if ($blocked_count > 0) {
        $_SESSION['error'] = "عذراً! العنصر لديه تواريخ حجب/غير متاحة مجدولة ضمن فترة الاستعارة المطلوبة. يرجى التحقق من قائمة التواريخ المحجوبة.";
        header("Location: item_detail.php?id=" . $item_id);
        exit;
    }
    
    // 4. Check for Overlapping Approved Borrowing Requests (S1 <= E2 AND S2 <= E1)
    $overlap_check = $config->prepare("
        SELECT COUNT(*) FROM borrowing 
        WHERE Item_ID = ? 
          AND BorrowingStatus = 'Approved' 
          AND StartDate <= ? 
          AND EndDate >= ?
    ");
    $overlap_check->bind_param("iss", $item_id, $end_date, $start_date);
    $overlap_check->execute();
    $res_overlap = $overlap_check->get_result()->fetch_row();
    $overlap_count = $res_overlap ? intval($res_overlap[0]) : 0;
    
    if ($overlap_count > 0) {
        $_SESSION['error'] = "عذراً! هذا العنصر محجوز وموافق عليه بالفعل لمستخدم آخر خلال هذه التواريخ. يرجى تجربة فترة أخرى.";
        header("Location: item_detail.php?id=" . $item_id);
        exit;
    }
    
    // 5. Success! Insert Borrowing Request
    $insert_stmt = $config->prepare("INSERT INTO borrowing (Item_ID, User_ID, StartDate, EndDate, BorrowingStatus, CreateDate) VALUES (?, ?, ?, ?, 'Requested', ?)");
    $user_id_val = $_SESSION['User_ID'];
    $date_val = date('Y-m-d');
    $insert_stmt->bind_param("iisss", $item_id, $user_id_val, $start_date, $end_date, $date_val);
    $insert_stmt->execute();
    
    $_SESSION['success'] = "تم تقديم طلب الاستعارة لـ '{$item['Title']}' بنجاح! سيقوم مسؤول النظام بمراجعته قريباً.";
    header("Location: my_borrows.php");
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = "حدث خطأ أثناء إرسال الطلب: " . $e->getMessage();
    header("Location: item_detail.php?id=" . $item_id);
    exit;
}
?>
