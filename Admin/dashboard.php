<?php
require_once __DIR__ . '\..\includes\auth.php';

require_role('Admin');

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $borrow_id = intval($_POST['borrow_id'] ?? 0);
    $action = $_POST['action'];

    try {
        if ($action === 'approve') {
            $stmt = $config->prepare("UPDATE borrowing SET BorrowingStatus = 'Approve' WHERE Borrowing_ID = ?");
            $stmt->bind_param("i",$borrow_id);
            $stmt->execute();
            $stmt->close();

            $item_stmt = $config->prepare("UPDATE items SET Status = 'Borrowed' WHERE Item_ID = (SELECT Item_ID FROM borrowing WHERE Borrowing_ID = ?)");
            $item_stmt->bind_param("i",$borrow_id);
            $item_stmt->execute();
            $item_stmt->close();

            $success_msg = "تمت الموافقة على طلب الاستعارة بنجاح! تم تحديد حالة العنصر ك معار";
        }elseif ($action === 'reject') {
            $stmt = $config->prepare("UPDATE borrowing SET BorrowingStatus = 'Rejected' WHERE Borrowing_ID = ?");
            $stmt->bind_param("i", $borrow_id);
            $stmt->execute();
            $stmt->close();

            $success_msg="تم رفض طلب الاستعارة.";
        }elseif ($action === 'return'){
             $stmt = $config->prepare("UPDATE borrowing SET BorrowingStatus = 'return' WHERE Borrowing_ID = ?");
            $stmt->bind_param("i", $borrow_id);
            $stmt->execute();
            $stmt->close();

             $item_stmt = $config->prepare("UPDATE items SET Status = 'Available' WHERE Item_ID = (SELECT Item_ID FROM borrowing WHERE Borrowing_ID = ?)");
            $item_stmt->bind_param("i",$borrow_id);
            $item_stmt->execute();
            $item_stmt->close();

            $success_msg = "تم تحديد العنصر كمرجع بنجاح وهو متاح الأن في السوق!";
        }

    } catch (Exception $e){
        $error_msg = "فشل تحديث المعاملة: " .$e->getMessage();
    }
}

$res = $config->query("SELECT COUNT(*) FROM items")->fetch_row();
$total_items = $res ? intval($res[0]) : 0;

$res = $config->query("SELECT COUNT(*) FROM borrowing WHERE BorrowingStatus = 'Approve'")->fetch_row();
$active_borrows = $res ? intval($res[0]) : 0;

$res = $config->query("SELECT COUNT(*) FROM borrowing WHERE BorrowingStatus = 'Requested'")->fetch_row();
$pending_requests = $res ? intval($res[0]) : 0;

$res = $config->query("SELECT COUNT(*) FROM users WHERE UserRole = 'EndUser'")->fetch_row();
$total_borrowers = $res ? intval($res[0]) : 0;

$borrows_result = $config->query("
SELECT b.*, i.Title as ItemTitle, u.FullName as BorrowerName, u.Email as BorrowerEmail 
    FROM borrowing b
    JOIN items i ON b.Item_ID = i.Item_ID
    JOIN users u ON b.User_ID = u.User_ID
    ORDER BY b.CreateDate DESC, b.Borrowing_ID DESC
");
$borrows = $borrows_result ? $borrows_result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "لوحة تحكم المسؤول (Admin)";
require_once __DIR__ . '\..\includes\header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <div class="card-header" style="border: none; padding-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="hero-badge" style="margin-bottom: 8px;">وحدة المسؤول (Admin)</span>
                <h2>لوحة التحكم الرئيسية</h2>
            </div>
            <a href="\استعارة\Admin\items.php" class="btn btn-primary"><i class="fa-solid fa-circle-plus" style="margin-left: 5px;"></i> إضافة للمخزون</a>
        </div>
        
        <!-- Stats Widgets -->
        <div class="stats-grid" style="margin-top: 30px;">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(66, 129, 164, 0.15); color: #4281a4;">
                <i class="fa-solid fa-box-open"></i>   
                <!-- <i class="fa-solid fa-boxes-stacked"></i> -->
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_items; ?></h3>
                    <p class="text-muted">إجمالي العناصر</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(221, 147, 68, 0.15); color: #dd9344;">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_requests; ?></h3>
                    <p class="text-muted">الطلبات المعلقة</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(86, 194, 166, 0.15); color: #56c2a6;">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $active_borrows; ?></h3>
                    <p class="text-muted">الاستعارات النشطة</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(72, 159, 181, 0.15); color: #2f6384;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_borrowers; ?></h3>
                    <p class="text-muted">المستعيرين النشطين</p>
                </div>
            </div>
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

        <!-- Borrow Requests Management Table -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>طلبات ومعاملات الاستعارة</h3>
                <span class="text-muted" style="font-size: 0.9rem;"><?php echo count($borrows); ?> قائمة إجمالية</span>
            </div>
            
            <?php if (empty($borrows)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fa-solid fa-folder-open text-muted" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p class="text-muted">لم يتم العثور على طلبات استعارة أو معاملات في قاعدة البيانات.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>اسم العنصر</th>
                                <th>المستعير</th>
                                <th>فترة الاستعارة</th>
                                <th>الحالة</th>
                                <th>تاريخ الطلب</th>
                                <th style="text-align: left;">التحكم بالإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrows as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['ItemTitle']); ?></strong></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['BorrowerName']); ?></div>
                                        <div style="font-size: 0.8rem;" class="text-muted"><?php echo htmlspecialchars($row['BorrowerEmail']); ?></div>
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
                                                'return' => 'مرتجع'
                                            ][$status_class] ?? $row['BorrowingStatus'];
                                        ?>
                                        <span class="status-indicator status-<?php echo $status_class; ?>">
                                            <i class="fa-solid <?php 
                                                switch($row['BorrowingStatus']) {
                                                    case 'Requested': echo 'fa-spinner fa-spin'; break;
                                                    case 'Approve': echo 'fa-circle-check'; break;
                                                    case 'Rejected': echo 'fa-circle-xmark'; break;
                                                    case 'Return': echo 'fa-arrow-left-long'; break;
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
                                            <form action="dashboard.php" method="POST" style="display: inline;" data-confirm="هل تريد الموافقة على هذا الطلب؟">
                                                <input type="hidden" name="borrow_id" value="<?php echo $row['Borrowing_ID']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm" style="background-color: #56c2a6; color: #ffffff;"><i class="fa-solid fa-check" style="margin-left: 5px;"></i> موافقة</button>
                                            </form>
                                            <form action="dashboard.php" method="POST" style="display: inline;" data-confirm="هل تريد رفض هذا الطلب؟">
                                                <input type="hidden" name="borrow_id" value="<?php echo $row['Borrowing_ID']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: rgba(239,68,68,0.2);"><i class="fa-solid fa-xmark" style="margin-left: 5px;"></i> رفض</button>
                                            </form>
                                        <?php elseif ($row['BorrowingStatus'] === 'Approve'): ?>
                                            <form action="dashboard.php" method="POST" style="display: inline;" data-confirm="هل تريد تحديد هذا العنصر كمرتجع؟">
                                                <input type="hidden" name="borrow_id" value="<?php echo $row['Borrowing_ID']; ?>">
                                                <input type="hidden" name="action" value="return">
                                                <button type="submit" class="btn btn-accent btn-sm"><i class="fa-solid fa-arrow-rotate-left" style="margin-left: 5px;"></i> تحديد كمرتجع</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85rem;">مكتمل</span>
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