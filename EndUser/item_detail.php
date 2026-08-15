<?php
// EndUser/item_detail.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('EndUser');

$item_id = intval($_GET['id'] ?? 0);
if ($item_id === 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch item details
$stmt = $config->prepare("
    SELECT i.*, c.category_Name, u.FullName as OwnerName, u.Email as OwnerEmail, u.PhoneNumber as OwnerPhone
    FROM items i
    JOIN categories c ON i.Category_ID = c.Category_ID
    JOIN users u ON i.User_ID = u.User_ID
    WHERE i.Item_ID = ?
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("العنصر غير موجود.");
}

// Fetch images
$img_stmt = $config->prepare("SELECT ImageUrl FROM item_images WHERE Item_ID = ?");
$img_stmt->bind_param("i", $item_id);
$img_stmt->execute();
$images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$img_stmt->close();

// Fetch unavailable dates
$avail_stmt = $config->prepare("SELECT UnAvailableDate, UnAvailableReason FROM item_availability WHERE Item_ID = ? ORDER BY UnAvailableDate ASC");
$avail_stmt->bind_param("i", $item_id);
$avail_stmt->execute();
$blocked_dates = $avail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$avail_stmt->close();

$page_title = $item['Title'];
require_once __DIR__ . '\..\includes\header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <!-- Breadcrumb navigation -->
        <div style="margin-bottom: 25px;">
            <a href="dashboard.php" class="text-muted"><i class="fa-solid fa-arrow-right-long" style="margin-left: 5px;"></i> العودة إلى سوق الإعارة</a>
        </div>

        <div class="dashboard-grid">
            <!-- Left Column: Details & Images -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Product Details Card -->
                <div class="card" style="padding: 0; overflow: hidden;">
                    <!-- Image Showcase -->
                    <div style="height: 380px; width: 100%; background: #111827; position: relative;">
                        <?php if (empty($images)): ?>
                            <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800" alt="صورة العنصر الافتراضية" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <!-- Show the first image, or a carousel structure if multiple images were used -->
                            <img src="<?php echo htmlspecialchars($images[0]['ImageUrl']); ?>" alt="<?php echo htmlspecialchars($item['Title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                        
                        <?php 
                            $status_class = strtolower($item['Status']);
                            $status_ar = [
                                'available' => 'متاح',
                                'unavailable' => 'غير متاح',
                                'borrowed' => 'معار',
                                'pending' => 'معلق'
                            ][$status_class] ?? $item['Status'];
                        ?>
                        <span class="item-badge badge-<?php echo $status_class; ?>" style="top: 20px; left: 20px; right: auto; font-size: 0.95rem; padding: 6px 14px;">
                            <?php echo htmlspecialchars($status_ar); ?>
                        </span>
                    </div>

                    <div style="padding: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                            <div>
                                <h1 style="font-size: 2.2rem; margin-bottom: 5px;"><?php echo htmlspecialchars($item['Title']); ?></h1>
                                <div style="display: flex; gap: 15px; font-size: 0.9rem;" class="text-muted">
                                    <span><i class="fa-solid fa-tag" style="margin-left: 4px;"></i> <strong><?php echo htmlspecialchars($item['category_Name']); ?></strong></span>
                                    <span><i class="fa-solid fa-location-dot" style="margin-left: 4px;"></i> <strong><?php echo htmlspecialchars($item['City'] ?: 'الاستلام من الموقع'); ?></strong></span>
                                    <span><i class="fa-solid fa-shield" style="margin-left: 4px;"></i> الحالة: <strong><?php 
                                        echo htmlspecialchars([
                                            'New' => 'جديد',
                                            'Good' => 'جيد',
                                            'Fair' => 'مقبول',
                                            'Poor' => 'رديء'
                                        ][$item['ItemCondition']] ?? $item['ItemCondition']);
                                    ?></strong></span>
                                </div>
                            </div>
                        </div>

                        <h3 style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-top: 30px;">وصف العنصر</h3>
                        <p style="font-size: 1.05rem; line-height: 1.7; margin-bottom: 30px; white-space: pre-line;">
                            <?php echo htmlspecialchars($item['Description'] ?: 'لم يقم الناشر بتقديم وصف لهذا العنصر.'); ?>
                        </p>
                        
                        <h3 style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">معلومات المالك / المعير</h3>
                        <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                            <div class="stat-icon" style="width: 50px; height: 50px; border-radius: 50%; font-size: 1.25rem;">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div>
                                <strong style="font-size: 1.1rem; color: #1e293b;"><?php echo htmlspecialchars($item['OwnerName']); ?></strong>
                                <div style="font-size: 0.88rem;" class="text-muted">
                                    <i class="fa-solid fa-envelope" style="margin-left: 4px;"></i> <?php echo htmlspecialchars($item['OwnerEmail']); ?>
                                    <?php if (!empty($item['OwnerPhone'])): ?>
                                        <span style="margin: 0 10px;">|</span>
                                        <i class="fa-solid fa-phone" style="margin-left: 4px;"></i> <?php echo htmlspecialchars($item['OwnerPhone']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Availability Calendar & Borrow Action Form -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Reservation Action Form -->
                <div class="card" style="border-color: #bfdbf7; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);">
                    <h3>طلب استعارة العنصر</h3>
                    <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;">الحد الأدنى لمدد الاستعارة المطلوبة: <strong><?php echo $item['MinBorrowDays']; ?> يوم/أيام</strong></p>
                    
                    <?php if ($item['Status'] !== 'Available'): ?>
                        <div class="alert alert-danger" style="margin-bottom: 0;">
                            <i class="fa-solid fa-ban" style="margin-left: 5px;"></i>
                            هذا العنصر حالياً <?php echo htmlspecialchars($status_ar); ?> للاستعارة.
                        </div>
                    <?php else: ?>
                        <form action="request_borrow.php" method="POST" autocomplete="off">
                            <input type="hidden" name="item_id" value="<?php echo $item['Item_ID']; ?>">
                            
                            <div class="form-group">
                                <label for="start_date" class="form-label">تاريخ بدء الاستعارة *</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" onchange="document.getElementById('end_date').min = this.value">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 25px;">
                                <label for="end_date" class="form-label">تاريخ إرجاع الاستعارة *</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;"><i class="fa-solid fa-handshake" style="margin-left: 5px;"></i> إرسال طلب الاستعارة</button>
                        </form>
                    <?php endif; ?>

                </div>

                <!-- Calendar Blackout Dates Widget -->
                <div class="card">
                    <div class="card-header" style="padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                        <h4 style="margin: 0;"><i class="fa-solid fa-calendar-xmark" style="color: #ef4444; margin-left: 6px;"></i> التواريخ المحجوبة</h4>
                    </div>
                    
                    <?php if (empty($blocked_dates)): ?>
                        <p class="text-muted" style="font-size: 0.85rem; margin: 0; text-align: center; padding: 15px 0;">هذا العنصر ليس لديه أي مواعيد صيانة مجدولة أو فترات حظر. جميع أيام التقويم متاحة!</p>
                    <?php else: ?>
                        <p class="text-muted" style="font-size: 0.82rem; margin-bottom: 12px;">العنصر محجوب وغير متاح للإعارة في التواريخ المحددة التالية:</p>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: rgba(0, 0, 0, 0.03);">
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($blocked_dates as $b_date): ?>
                                    <li style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                        <strong style="color: #1e293b;"><?php echo date('Y-m-d', strtotime($b_date['UnAvailableDate'])); ?></strong>
                                        <span style="font-size: 0.78rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.2);" title="<?php echo htmlspecialchars($b_date['UnAvailableReason']); ?>">
                                            <?php echo htmlspecialchars($b_date['UnAvailableReason']); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
