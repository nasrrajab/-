<?php
// Admin/items.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('Admin');

$success_msg = '';
$error_msg = '';

// Helper to handle image uploads
function handle_image_upload($file_field_name, &$error_msg) {
    if (!isset($_FILES[$file_field_name])) {
        return null;
    }
    
    $file_error = $_FILES[$file_field_name]['error'];
    
    if ($file_error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($file_error !== UPLOAD_ERR_OK) {
        $error_msg = "خطأ في رفع الملف: رمز الخطأ " . $file_error;
        return null;
    }
    
    $upload_dir = __DIR__ . '/../uploads';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $error_msg = "فشل إنشاء مجلد التحميلات.";
            return null;
        }
    }
    
    $file_tmp = $_FILES[$file_field_name]['tmp_name'];
    $file_name = $_FILES[$file_field_name]['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_ext, $allowed_exts)) {
        $error_msg = "نوع ملف غير صالح. الأنواع المسموح بها: " . implode(', ', $allowed_exts);
        return null;
    }
    
    $new_filename = uniqid('item_', true) . '.' . $file_ext;
    $target_path = $upload_dir . '/' . $new_filename;
    
    if (move_uploaded_file($file_tmp, $target_path)) {
        return '\استعارة\uploads\\' . $new_filename;
    } else {
        $error_msg = "فشل حفظ الملف المرفوع.";
        return null;
    }
}

// Handle actions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $owner_id = intval($_POST['owner_id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $condition = $_POST['condition'] ?? 'Good';
        $min_days = intval($_POST['min_days'] ?? 1);
        $city = trim($_POST['city'] ?? '') ?: null;
        $status = $_POST['status'] ?? 'Available';
        $added_by = $_SESSION['User_ID'];
        $added_date = date('Y-m-d');
        
        $uploaded_url = handle_image_upload('item_image', $error_msg);
        $image_url = $uploaded_url ?? '';
        
        if (empty($error_msg)) {
            if (empty($title) || $owner_id === 0 || $category_id === 0) {
                $error_msg = "يرجى ملء جميع الحقول المطلوبة.";
            } else {
                try {
                    $config->begin_transaction();
                    
                    $stmt = $config->prepare("INSERT INTO items (User_ID, Category_ID, Title, Description, ItemCondition, MinBorrowDays, City, Status, AddedBy, AddedDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisssissss", $owner_id, $category_id, $title, $description, $condition, $min_days, $city, $status, $added_by, $added_date);
                    $stmt->execute();
                    $item_id = $config->insert_id;
                    $stmt->close();
                    
                    $final_image = !empty($image_url) ? $image_url : 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800';
                    $img_stmt = $config->prepare("INSERT INTO item_images (Item_ID, ImageUrl) VALUES (?, ?)");
                    $img_stmt->bind_param("is", $item_id, $final_image);
                    $img_stmt->execute();
                    $img_stmt->close();
                    
                    $config->commit();
                    $success_msg = "تم إنشاء إدراج العنصر الجديد بنجاح!";
                } catch (Exception $e) {
                    $config->rollback();
                    $error_msg = "فشل إنشاء الإدراج: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'edit') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $owner_id = intval($_POST['owner_id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $condition = $_POST['condition'] ?? 'Good';
        $min_days = intval($_POST['min_days'] ?? 1);
        $city = trim($_POST['city'] ?? '') ?: null;
        $status = $_POST['status'] ?? 'Available';
        
        $uploaded_url = handle_image_upload('item_image', $error_msg);
        $image_url = $uploaded_url ?? '';
        
        if (empty($error_msg)) {
            if (empty($title) || $owner_id === 0 || $category_id === 0) {
                $error_msg = "يرجى ملء جميع الحقول المطلوبة.";
            } else {
                try {
                    $config->begin_transaction();
                    
                    $stmt = $config->prepare("UPDATE items SET User_ID = ?, Category_ID = ?, Title = ?, Description = ?, ItemCondition = ?, MinBorrowDays = ?, City = ?, Status = ? WHERE Item_ID = ?");
                    $stmt->bind_param("iisssissi", $owner_id, $category_id, $title, $description, $condition, $min_days, $city, $status, $item_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    if (!empty($image_url)) {
                        $check_img = $config->prepare("SELECT Image_ID FROM item_images WHERE Item_ID = ? LIMIT 1");
                        $check_img->bind_param("i", $item_id);
                        $check_img->execute();
                        $existing_img = $check_img->get_result()->fetch_assoc();
                        $check_img->close();
                        
                        if ($existing_img) {
                            $img_stmt = $config->prepare("UPDATE item_images SET ImageUrl = ? WHERE Image_ID = ?");
                            $img_stmt->bind_param("si", $image_url, $existing_img['Image_ID']);
                        } else {
                            $img_stmt = $config->prepare("INSERT INTO item_images (Item_ID, ImageUrl) VALUES (?, ?)");
                            $img_stmt->bind_param("is", $item_id, $image_url);
                        }
                        $img_stmt->execute();
                        $img_stmt->close();
                    }
                    
                    $config->commit();
                    $success_msg = "تم تحديث إدراج العنصر بنجاح!";
                } catch (Exception $e) {
                    $config->rollback();
                    $error_msg = "فشل تحديث الإدراج: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete') {
        $item_id = intval($_POST['item_id'] ?? 0);
        
        try {
            $config->begin_transaction();
            
            $check_borrow = $config->prepare("SELECT COUNT(*) FROM borrowing WHERE Item_ID = ? AND BorrowingStatus IN ('Requested', 'Approved')");
            $check_borrow->bind_param("i", $item_id);
            $check_borrow->execute();
            $borrow_res = $check_borrow->get_result()->fetch_row();
            $borrow_count = $borrow_res ? intval($borrow_res[0]) : 0;
            $check_borrow->close();

            if ($borrow_count > 0) {
                $error_msg = "لا يمكن حذف هذا العنصر لأنه مرتبط بمعاملات استعارة نشطة أو معلقة حالياً.";
                $config->rollback();
            } else {
                foreach (['item_images', 'item_availability', 'borrowing', 'items'] as $table) {
                    $col = ($table === 'items') ? 'Item_ID' : 'Item_ID';
                    $sql="DELETE FROM `$table` WHERE Item_ID = ?";
                    $del = $config->prepare($sql);
                    $del->bind_param("i", $item_id);
                    $del->execute();
                    $del->close();
                }
                $config->commit();
                $success_msg = "تم حذف قائمة العنصر بنجاح.";
            }
        } catch (Exception $e) {
            $config->rollback();
            $error_msg = "فشل حذف العنصر: " . $e->getMessage();
        }
    }
}

// Fetch lists for forms
$cat_res = $config->query("SELECT * FROM categories ORDER BY category_Name ASC");
$categories = $cat_res ? $cat_res->fetch_all(MYSQLI_ASSOC) : [];

$own_res = $config->query("SELECT User_ID, FullName, Email FROM users WHERE UserRole = 'EndUser' ORDER BY FullName ASC");
$owners = $own_res ? $own_res->fetch_all(MYSQLI_ASSOC) : [];

$city_res = $config->query("SELECT DISTINCT City FROM items WHERE City IS NOT NULL AND City != '' ORDER BY City ASC");
$cities = $city_res ? $city_res->fetch_all(MYSQLI_ASSOC) : [];

// Filter parameters from GET request
$search = trim($_GET['search'] ?? '');
$cat_id = intval($_GET['category'] ?? 0);
$city = trim($_GET['city'] ?? '');

$query_parts = [];
$params = [];
$types = '';

if (!empty($search)) {
    $query_parts[] = "(i.Title LIKE ? OR i.Description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($cat_id > 0) {
    $query_parts[] = "i.Category_ID = ?";
    $params[] = $cat_id;
    $types .= 'i';
}

if (!empty($city)) {
    $query_parts[] = "i.City = ?";
    $params[] = $city;
    $types .= 's';
}

$where_clause = !empty($query_parts) ? "WHERE " . implode(" AND ", $query_parts) : "";

// Fetch items
$items_stmt = $config->prepare("
    SELECT i.*, c.category_Name, u.FullName as OwnerName, img.ImageUrl
    FROM items i
    JOIN categories c ON i.Category_ID = c.Category_ID
    JOIN users u ON i.User_ID = u.User_ID
    LEFT JOIN item_images img ON i.Item_ID = img.Item_ID
    $where_clause
    GROUP BY i.Item_ID
    ORDER BY i.AddedDate DESC, i.Item_ID DESC
");
if (!empty($params)) {
    $items_stmt->bind_param($types, ...$params);
}
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

$page_title = "إدارة المخزون";
require_once __DIR__ . '/../includes/header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <div class="card-header" style="border: none; padding-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="hero-badge" style="margin-bottom: 8px;">التحكم في دليل المخزون</span>
                <h2>دليل الأصول المادية</h2>
            </div>
            <button onclick="openModal('addItemModal')" class="btn btn-primary"><i class="fa-solid fa-plus" style="margin-left: 5px;"></i> إضافة عنصر جديد</button>
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

        <!-- Filter and Search controls -->
        <div class="card" style="margin-top: 25px; padding: 20px;">
            <form action="items.php" method="GET" style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label for="search" class="form-label">الكلمات المفتاحية للبحث</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="مثال: مثقاب لاسلكي، خيمة مقاومة للماء..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label for="category" class="form-label">الفئة</label>
                        <select name="category" id="category" class="form-control">
                            <option value="">جميع الفئات</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['Category_ID']; ?>" <?php echo $cat_id === intval($cat['Category_ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label for="city" class="form-label">الموقع (المدينة)</label>
                        <select name="city" id="city" class="form-control">
                            <option value="">جميع المواقع</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['City']); ?>" <?php echo $city === $c['City'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['City']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="height: 46px;"><i class="fa-solid fa-magnifying-glass"></i> تصفية</button>
                        <?php if (!empty($search) || $cat_id > 0 || !empty($city)): ?>
                            <a href="items.php" class="btn btn-secondary" style="height: 46px; display: inline-flex; align-items: center;" title="إعادة تعيين الفلاتر"><i class="fa-solid fa-arrow-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Inventory List Card -->
        <div class="card" style="margin-top: 25px;">
            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="fa-solid fa-boxes-packing text-muted" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p class="text-muted">لا توجد عناصر مدرجة حالياً في الدليل.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>العنوان</th>
                                <th>المالك / الموقع</th>
                                <th>الفئة</th>
                                <th>الحالة</th>
                                <th>أقل مدة</th>
                                <th>الوضعية</th>
                                <th style="text-align: left;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($item['ImageUrl'] ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800'); ?>" alt="صورة العنصر" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['Title']); ?></strong>
                                        <div style="font-size: 0.8rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="text-muted"><?php echo htmlspecialchars($item['Description']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($item['OwnerName']); ?></div>
                                        <div style="font-size: 0.78rem; font-weight: 600;" class="text-muted"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($item['City'] ?: 'عام'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category_Name']); ?></td>
                                    <td>
                                        <?php 
                                            $cond_ar = [
                                                'New' => 'جديد',
                                                'Good' => 'جيد',
                                                'Fair' => 'مقبول',
                                                'Poor' => 'رديء'
                                            ][$item['ItemCondition']] ?? $item['ItemCondition'];
                                        ?>
                                        <span style="font-size: 0.8rem; background: rgba(255, 255, 255, 0.05); padding: 4px 8px; border-radius: 4px;">
                                            <?php echo htmlspecialchars($cond_ar); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $item['MinBorrowDays']; ?> يوم/أيام</strong></td>
                                    <td>
                                        <?php 
                                            $status_class = strtolower($item['Status']);
                                            $status_ar = [
                                                'available' => 'متاح',
                                                'unavailable' => 'غير متاح',
                                                'borrowed' => 'معار',
                                                'pending' => 'معلق'
                                            ][$status_class] ?? $item['Status'];
                                        ?>
                                        <span class="item-badge badge-<?php echo $status_class; ?>" style="position: static;">
                                            <?php echo htmlspecialchars($status_ar); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: left; white-space: nowrap;">
                                        <button onclick='editItem(<?php echo json_encode($item); ?>)' class="btn btn-secondary btn-sm" style="margin-left: 6px;"><i class="fa-solid fa-pen"></i></button>
                                        
                                        <form action="items.php" method="POST" style="display: inline;" data-confirm="هل تريد حذف هذا العنصر؟ سيتم إزالة صور الدليل والتواريخ المرتبطة والسجلات غير النشطة بالكامل.">
                                            <input type="hidden" name="item_id" value="<?php echo $item['Item_ID']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i></button>
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

<!-- Add Item Modal -->
<div class="modal-backdrop" id="addItemModal">
    <div class="modal-content" style="max-width: 600px; text-align: right;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>إضافة قائمة جديدة</h3>
            <button class="btn btn-secondary btn-sm" data-close-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form action="items.php" method="POST" enctype="multipart/form-data" autocomplete="off" style="margin-top: 15px;">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="add_title" class="form-label">عنوان العنصر *</label>
                <input type="text" name="title" id="add_title" class="form-control" placeholder="مثال: مثقاب ديفولت لاسلكي" required>
            </div>
            
            <div class="form-group">
                <label for="add_desc" class="form-label">الوصف</label>
                <textarea name="description" id="add_desc" class="form-control" rows="3" placeholder="أخبر المستعيرين عن حالة العنصر، وما هو متضمن معها، وما إلى ذلك."></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add_owner" class="form-label">مالك العنصر (فرد) *</label>
                    <select name="owner_id" id="add_owner" class="form-control" required>
                        <option value="">-- اختر المالك --</option>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['User_ID']; ?>"><?php echo htmlspecialchars($owner['FullName']); ?> (<?php echo htmlspecialchars($owner['Email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_category" class="form-label">الفئة *</label>
                    <select name="category_id" id="add_category" class="form-control" required>
                        <option value="">-- اختر الفئة --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['Category_ID']; ?>"><?php echo htmlspecialchars($cat['category_Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add_condition" class="form-label">حالة العنصر *</label>
                    <select name="condition" id="add_condition" class="form-control" required>
                        <option value="New">جديد</option>
                        <option value="Good" selected>جيد</option>
                        <option value="Fair">مقبول</option>
                        <option value="Poor">رديء</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_min_days" class="form-label">الحد الأدنى للاستعارة (بالأيام) *</label>
                    <input type="number" name="min_days" id="add_min_days" class="form-control" min="1" value="1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add_city" class="form-label">الموقع (المدينة)</label>
                    <input type="text" name="city" id="add_city" class="form-control" placeholder="مثال: طولكرم">
                </div>
                
                <div class="form-group">
                    <label for="add_status" class="form-label">الحالة *</label>
                    <select name="status" id="add_status" class="form-control" required>
                        <option value="Available" selected>متاح</option>
                        <option value="Unavailable">غير متاح</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="add_item_image" class="form-label">صورة العنصر</label>
                <input type="file" name="item_image" id="add_item_image" class="form-control" accept="image/*" onchange="previewImage(this, 'add_img_preview')">
                <div style="position: relative;">
                    <img id="add_img_preview" src="" alt="معاينة" style="display: none; margin-top: 12px; width: 100%; height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <button type="button" id="add_remove_img" onclick="clearImagePreview('add')" style="display: none; position: absolute; top: 20px; left: 8px; background: rgba(239, 68, 68, 0.85); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; z-index: 10;">
                        <i class="fa-solid fa-trash-can" style="font-size: 0.85rem;"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">حفظ القائمة</button>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal-backdrop" id="editItemModal">
    <div class="modal-content" style="max-width: 600px; text-align: right;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>تعديل تفاصيل القائمة</h3>
            <button class="btn btn-secondary btn-sm" data-close-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form action="items.php" method="POST" enctype="multipart/form-data" autocomplete="off" style="margin-top: 15px;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="item_id" id="edit_item_id">
            
            <div class="form-group">
                <label for="edit_title" class="form-label">عنوان العنصر *</label>
                <input type="text" name="title" id="edit_title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_desc" class="form-label">الوصف</label>
                <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_owner" class="form-label">مالك العنصر *</label>
                    <select name="owner_id" id="edit_owner" class="form-control" required>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['User_ID']; ?>"><?php echo htmlspecialchars($owner['FullName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_category" class="form-label">الفئة *</label>
                    <select name="category_id" id="edit_category" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['Category_ID']; ?>"><?php echo htmlspecialchars($cat['category_Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_condition" class="form-label">حالة العنصر *</label>
                    <select name="condition" id="edit_condition" class="form-control" required>
                        <option value="New">جديد</option>
                        <option value="Good">جيد</option>
                        <option value="Fair">مقبول</option>
                        <option value="Poor">رديء</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_min_days" class="form-label">أقل مدة للاستعارة (بالأيام) *</label>
                    <input type="number" name="min_days" id="edit_min_days" class="form-control" min="1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_city" class="form-label">الموقع (المدينة)</label>
                    <input type="text" name="city" id="edit_city" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_status" class="form-label">الحالة *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="Available">متاح</option>
                        <option value="Unavailable">غير متاح</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_item_image" class="form-label">صورة العنصر (اترك فارغاً للإبقاء على الصورة الحالية)</label>
                <input type="file" name="item_image" id="edit_item_image" class="form-control" accept="image/*" onchange="previewImage(this, 'edit_img_preview')">
                <div style="position: relative;">
                    <img id="edit_img_preview" src="" alt="معاينة" style="display: none; margin-top: 12px; width: 100%; height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <button type="button" id="edit_remove_img" onclick="clearImagePreview('edit')" style="display: none; position: absolute; top: 20px; left: 8px; background: rgba(239, 68, 68, 0.85); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; z-index: 10;">
                        <i class="fa-solid fa-trash-can" style="font-size: 0.85rem;"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">تحديث القائمة</button>
        </form>
    </div>
</div>

<script>
function clearImagePreview(prefix) {
    const fileInput = document.getElementById(prefix + '_item_image');
    const preview  = document.getElementById(prefix + '_img_preview');
    const removeBtn = document.getElementById(prefix + '_remove_img');

    if (fileInput) fileInput.value = '';
    if (preview)  { preview.src = ''; preview.style.display = 'none'; }
    if (removeBtn) removeBtn.style.display = 'none';
}

// Extend global previewImage to show/hide the remove button
if (typeof previewImage === 'function') {
    const _origPreview = previewImage;
    previewImage = function(inputElement, previewImgId) {
        _origPreview(inputElement, previewImgId);
        const prefix = previewImgId.replace('_img_preview', '');
        const removeBtn = document.getElementById(prefix + '_remove_img');
        setTimeout(() => {
            const img = document.getElementById(previewImgId);
            if (img && img.style.display === 'block' && img.getAttribute('src')) {
                if (removeBtn) removeBtn.style.display = 'flex';
            } else {
                if (removeBtn) removeBtn.style.display = 'none';
            }
        }, 100);
    };
}

function editItem(item) {
    document.getElementById('edit_item_id').value    = item.Item_ID;
    document.getElementById('edit_title').value      = item.Title;
    document.getElementById('edit_desc').value       = item.Description || '';
    document.getElementById('edit_owner').value      = item.User_ID;
    document.getElementById('edit_category').value  = item.Category_ID;
    document.getElementById('edit_condition').value = item.ItemCondition;
    document.getElementById('edit_min_days').value  = item.MinBorrowDays;
    document.getElementById('edit_city').value       = item.City || '';
    document.getElementById('edit_status').value     = item.Status;

    // Clear previous file selection
    const fileInput = document.getElementById('edit_item_image');
    if (fileInput) fileInput.value = '';

    // Show existing image as preview (read-only — user can replace by uploading a new file)
    const imgUrl     = item.ImageUrl || '';
    const imgPreview = document.getElementById('edit_img_preview');
    const removeBtn  = document.getElementById('edit_remove_img');

    if (imgUrl) {
        imgPreview.src = imgUrl;
        imgPreview.style.display = 'block';
        if (removeBtn) removeBtn.style.display = 'flex';
    } else {
        imgPreview.style.display = 'none';
        if (removeBtn) removeBtn.style.display = 'none';
    }

    openModal('editItemModal');
}
</script>

<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
