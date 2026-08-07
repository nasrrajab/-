<?php
// Admin/categories.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('Admin');

$success_msg = '';
$error_msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $cat_name = trim($_POST['category_name'] ?? '');
        
        if (empty($cat_name)) {
            $error_msg = "لا يمكن أن يكون اسم الفئة فارغاً.";
        } else {
            try {
                // Check unique
                $check_stmt = $config->prepare("SELECT Category_ID FROM categories WHERE category_Name = ?");
                $check_stmt->bind_param("s", $cat_name);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($exists) {
                    $error_msg = "توجد فئة بهذا الاسم بالفعل.";
                } else {
                    $stmt = $config->prepare("INSERT INTO categories (category_Name) VALUES (?)");
                    $stmt->bind_param("s", $cat_name);
                    $stmt->execute();
                    $stmt->close();
                    $success_msg = "تم إنشاء الفئة الجديدة '{$cat_name}' بنجاح!";
                }
            } catch (Exception $e) {
                $error_msg = "فشل إنشاء الفئة: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $cat_id = intval($_POST['category_id'] ?? 0);
        
        try {
            // Check if items belong to this category
            $check_stmt = $config->prepare("SELECT COUNT(*) FROM items WHERE Category_ID = ?");
            $check_stmt->bind_param("i", $cat_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result()->fetch_row();
            $count = $res ? intval($res[0]) : 0;
            $check_stmt->close();

            if ($count > 0) {
                $error_msg = "لا يمكن حذف هذه الفئة. هناك عناصر مدرجة تحتها في المخزون.";
            } else {
                $stmt = $config->prepare("DELETE FROM categories WHERE Category_ID = ?");
                $stmt->bind_param("i", $cat_id);
                $stmt->execute();
                $stmt->close();
                $success_msg = "تم حذف الفئة بنجاح.";
            }
        } catch (Exception $e) {
            $error_msg = "فشل حذف الفئة: " . $e->getMessage();
        }
    }
}

// Fetch categories with item counts
$cat_result = $config->query("
    SELECT c.*, COUNT(i.Item_ID) as ItemCount
    FROM categories c
    LEFT JOIN items i ON c.Category_ID = i.Category_ID
    GROUP BY c.Category_ID
    ORDER BY c.category_Name ASC
");
$categories = $cat_result ? $cat_result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "إدارة الفئات";
require_once __DIR__ . '\..\includes\header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <div class="card-header" style="border: none; padding-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="hero-badge" style="margin-bottom: 8px;">إدارة الفهارس</span>
                <h2>مدير الفئات</h2>
            </div>
            <button onclick="openModal('addCatModal')" class="btn btn-primary"><i class="fa-solid fa-folder-plus" style="margin-left: 5px;"></i> إضافة فئة</button>
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

        <!-- Categories grid -->
        <div class="stats-grid" style="margin-top: 30px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <?php foreach ($categories as $cat): ?>
                <div class="card" style="padding: 24px; position: relative;">
                    <div class="stat-icon" style="background: rgba(168, 85, 247, 0.1); color: #dd9344;">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    
                    <h3 style="margin-top: 15px; margin-bottom: 5px;"><?php echo htmlspecialchars($cat['category_Name']); ?></h3>
                    <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;"><?php echo $cat['ItemCount']; ?> عنصر/عناصر مدرجة حالياً</p>
                    
                    <div style="display: flex; justify-content: flex-end; width: 100%;">
                        <form action="categories.php" method="POST" data-confirm="هل تريد حذف هذه الفئة؟">
                            <input type="hidden" name="category_id" value="<?php echo $cat['Category_ID']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm" <?php echo $cat['ItemCount'] > 0 ? 'disabled style="opacity: 0.4; cursor: not-allowed;" title="الفئة تحتوي على عناصر"' : ''; ?>><i class="fa-solid fa-trash-can" style="margin-left: 5px;"></i> حذف</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Add Category Modal -->
<div class="modal-backdrop" id="addCatModal">
    <div class="modal-content" style="text-align: right;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>إضافة فئة</h3>
            <button class="btn btn-secondary btn-sm" data-close-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form action="categories.php" method="POST" autocomplete="off" style="margin-top: 15px;">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="category_name" class="form-label">اسم الفئة *</label>
                <input type="text" name="category_name" id="category_name" class="form-control" placeholder="مثال: التصوير والفيديو" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">إنشاء الفئة</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
