<?php
// Admin/availability.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('Admin');

$success_msg = '';
$error_msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        
        if ($item_id === 0 || empty($date) || empty($reason)) {
            $error_msg = "يرجى ملء جميع الحقول المطلوبة.";
        } else {
            try {
                // Check if already blocked
                $check_stmt = $config->prepare("SELECT ID FROM item_availability WHERE Item_ID = ? AND UnAvailableDate = ?");
                $check_stmt->bind_param("is", $item_id, $date);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($exists) {
                    $error_msg = "هذا التاريخ محدد بالفعل كغير متاح لهذا العنصر.";
                } else {
                    $stmt = $config->prepare("INSERT INTO item_availability (Item_ID, UnAvailableDate, UnAvailableReason) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $item_id, $date, $reason);
                    $stmt->execute();
                    $stmt->close();
                    $success_msg = "تم إضافة قيد عدم الإتاحة بنجاح.";
                }
            } catch (Exception $e) {
                $error_msg = "فشل حجب التاريخ: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        try {
            $stmt = $config->prepare("DELETE FROM item_availability WHERE ID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $success_msg = "تم إلغاء قيد عدم الإتاحة بنجاح.";
        } catch (Exception $e) {
            $error_msg = "فشل إلغاء القيد: " . $e->getMessage();
        }
    }
}

// Fetch items for dropdown
$items_result = $config->query("SELECT Item_ID, Title FROM items ORDER BY Title ASC");
$items = $items_result ? $items_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch current blockout dates
$blocked_result = $config->query("
    SELECT a.*, i.Title as ItemTitle 
    FROM item_availability a
    JOIN items i ON a.Item_ID = i.Item_ID
    ORDER BY a.UnAvailableDate ASC, i.Title ASC
");
$blocked_dates = $blocked_result ? $blocked_result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "إعدادات عدم إتاحة العناصر";
require_once __DIR__ . '\..\includes\header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <div class="card-header" style="border: none; padding-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="hero-badge" style="margin-bottom: 8px;">قيود الأصول</span>
                <h2>التحكم في مواعيد الحجب</h2>
            </div>
            <button onclick="openModal('blockDateModal')" class="btn btn-primary"><i class="fa-solid fa-calendar-plus" style="margin-left: 5px;"></i> حجب تاريخ</button>
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

        <!-- Blocked Dates List -->
        <div class="card" style="margin-top: 25px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>تواريخ الاستعارة المقيدة حالياً</h3>
                <span class="text-muted" style="font-size: 0.9rem;"><?php echo count($blocked_dates); ?> قيود نشطة</span>
            </div>
            
            <?php if (empty($blocked_dates)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fa-solid fa-calendar-check text-muted" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p class="text-muted">لم يتم تحديد أي حظر تواريخ أو قيود على عدم الإتاحة بعد.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>عنوان العنصر</th>
                                <th>تاريخ عدم الإتاحة</th>
                                <th>وصف السبب</th>
                                <th style="text-align: left;">التحكم بالإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocked_dates as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['ItemTitle']); ?></strong></td>
                                    <td>
                                        <span style="font-weight: 600; color: #1e293b;">
                                            <i class="fa-solid fa-calendar-xmark" style="color: #ef4444; margin-left: 6px;"></i>
                                            <?php echo date('Y-m-d', strtotime($row['UnAvailableDate'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo htmlspecialchars($row['UnAvailableReason']); ?></span>
                                    </td>
                                    <td style="text-align: left;">
                                        <form action="availability.php" method="POST" style="display: inline;" data-confirm="هل تريد إلغاء حجب هذا التاريخ؟">
                                            <input type="hidden" name="id" value="<?php echo $row['ID']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: #56c2a6; border-color: rgba(86,194,166,0.2);"><i class="fa-solid fa-trash-arrow-up" style="margin-left: 5px;"></i> إلغاء القيد</button>
                                        </form>
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

<!-- Block Date Modal -->
<div class="modal-backdrop" id="blockDateModal">
    <div class="modal-content" style="text-align: right;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>تقييد تاريخ العنصر</h3>
            <button class="btn btn-secondary btn-sm" data-close-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form action="availability.php" method="POST" autocomplete="off" style="margin-top: 15px;">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="item_id" class="form-label">اختر العنصر *</label>
                <select name="item_id" id="item_id" class="form-control" required>
                    <option value="">-- اختر العنصر --</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['Item_ID']; ?>"><?php echo htmlspecialchars($item['Title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date" class="form-label">التاريخ المطلوب حجبه *</label>
                <input type="date" name="date" id="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="reason" class="form-label">السبب *</label>
                <input type="text" name="reason" id="reason" class="form-control" placeholder="مثال: صيانة سنوية / إصلاح" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">تطبيق الحجب</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
