<?php
// EndUser/my_borrows.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('EndUser');

$success_msg = '';
$error_msg = '';

if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle Cancel Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $borrow_id = intval($_POST['borrow_id'] ?? 0);
    
    try {
        // Ensure the borrower actually owns this request and it is still pending
        $stmt = $config->prepare("DELETE FROM borrowing WHERE Borrowing_ID = ? AND User_ID = ? AND BorrowingStatus = 'Requested'");
        $stmt->bind_param("ii", $borrow_id, $_SESSION['User_ID']);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $success_msg = "تم إلغاء طلب الاستعارة الخاص بك بنجاح.";
        } else {
            $error_msg = "تعذر إلغاء الطلب. ربما تمت الموافقة عليه أو معالجته بالفعل.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_msg = "خطأ أثناء إلغاء الطلب: " . $e->getMessage();
    }
}

// Fetch all borrows of current user
$stmt = $config->prepare("
    SELECT b.*, i.Title as ItemTitle, i.City, i.ItemCondition, img.ImageUrl 
    FROM borrowing b
    JOIN items i ON b.Item_ID = i.Item_ID
    LEFT JOIN item_images img ON i.Item_ID = img.Item_ID
    WHERE b.User_ID = ?
    GROUP BY b.Borrowing_ID
    ORDER BY b.CreateDate DESC, b.Borrowing_ID DESC
");
$stmt->bind_param("i", $_SESSION['User_ID']);
$stmt->execute();
$borrows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "استعاراتي";
require_once __DIR__ . '\..\includes\header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <!-- Dashboard Sub-Header -->
        <div class="card-header" style="border: none; padding-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="hero-badge" style="margin-bottom: 8px;">سجلات النشاط الشخصي</span>
                <h2>سجل استعاراتي</h2>
            </div>
            <a href="dashboard.php" class="btn btn-primary"><i class="fa-solid fa-shop" style="margin-left: 5px;"></i> تصفح السوق</a>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Borrows list -->
        <div class="card" style="margin-top: 25px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>سجل استعاراتي ومعاملاتي</h3>
                <span class="text-muted" style="font-size: 0.9rem;"><?php echo count($borrows); ?> حجوزات</span>
            </div>
            
            <?php if (empty($borrows)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fa-solid fa-hourglass text-muted" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p class="text-muted" style="margin-bottom: 20px;">لم تقم بطلب أو استعارة أي عناصر بعد.</p>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">استكشف العناصر المتاحة</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>عنوان العنصر</th>
                                <th>الموقع</th>
                                <th>فترة الاستعارة</th>
                                <th>الحالة</th>
                                <th>تاريخ الطلب</th>
                                <th style="text-align: left;">التحكم بالإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrows as $row): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($row['ImageUrl'] ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800'); ?>" alt="صورة العنصر" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['ItemTitle']); ?></strong>
                                        <div style="font-size: 0.75rem;" class="text-muted">الحالة: <?php 
                                            echo htmlspecialchars([
                                                'New' => 'جديد',
                                                'Good' => 'جيد',
                                                'Fair' => 'مقبول',
                                                'Poor' => 'رديء'
                                            ][$row['ItemCondition']] ?? $row['ItemCondition']);
                                        ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.88rem; font-weight: 500;" class="text-muted"><i class="fa-solid fa-location-dot" style="margin-left: 4px;"></i> <?php echo htmlspecialchars($row['City'] ?: 'الاستلام من الموقع'); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;">
                                            <i class="fa-solid fa-calendar-day text-muted" style="margin-left: 4px;"></i> 
                                            <strong><?php echo date('Y-m-d', strtotime($row['StartDate'])); ?></strong> 
                                            إلى 
                                            <strong><?php echo date('Y-m-d', strtotime($row['EndDate'])); ?></strong>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #56c2a6; margin-top: 2px;">
                                            المدة: <?php 
                                                $days = (strtotime($row['EndDate']) - strtotime($row['StartDate'])) / 86400;
                                                echo $days . " يوم/أيام"; 
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $status_class = strtolower($row['BorrowingStatus']);
                                            $status_ar = [
                                                'requested' => 'مطلوب',
                                                'approve' => 'مقبول',
                                                'rejected' => 'مرفوض',
                                                'returned' => 'مرتجع'
                                            ][$status_class] ?? $row['BorrowingStatus'];
                                        ?>
                                        <span class="status-indicator status-<?php echo $status_class; ?>">
                                            <i class="fa-solid <?php 
                                                switch($row['BorrowingStatus']) {
                                                    case 'Requested': echo 'fa-spinner fa-spin'; break;
                                                    case 'Approve': echo 'fa-circle-check'; break;
                                                    case 'Rejected': echo 'fa-circle-xmark'; break;
                                                    case 'Returned': echo 'fa-arrow-left-long'; break;
                                                }
                                            ?>"></i>
                                            <?php echo htmlspecialchars($status_ar); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;">
                                        <?php echo date('Y-m-d', strtotime($row['CreateDate'])); ?>
                                    </td>
                                    <td style="text-align: left;">
                                        <?php if ($row['BorrowingStatus'] === 'Requested'): ?>
                                            <form action="my_borrows.php" method="POST" data-confirm="هل تريد إلغاء طلب الاستعارة هذا؟">
                                                <input type="hidden" name="borrow_id" value="<?php echo $row['Borrowing_ID']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2);"><i class="fa-solid fa-trash" style="margin-left: 5px;"></i> إلغاء الطلب</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.82rem;">مقفل</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
