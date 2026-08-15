<?php
// EndUser/dashboard.php
require_once __DIR__ . '\..\includes\auth.php';

// Route guard
require_role('EndUser');

// Filter values
$search = trim($_GET['search'] ?? '');
$cat_id = intval($_GET['category'] ?? 0);
$city = trim($_GET['city'] ?? '');

// Build search query
$query_parts = [];
$params = [];

if (!empty($search)) {
    $query_parts[] = "(i.Title LIKE ? OR i.Description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($cat_id > 0) {
    $query_parts[] = "i.Category_ID = ?";
    $params[] = $cat_id;
}

if (!empty($city)) {
    $query_parts[] = "i.City = ?";
    $params[] = $city;
}

$where_clause = "";
if (!empty($query_parts)) {
    $where_clause = "WHERE " . implode(" AND ", $query_parts);
}

// Fetch items
$sql = "
    SELECT i.*, c.category_Name, u.FullName as OwnerName, img.ImageUrl
    FROM items i
    JOIN categories c ON i.Category_ID = c.Category_ID
    JOIN users u ON i.User_ID = u.User_ID
    LEFT JOIN item_images img ON i.Item_ID = img.Item_ID
    $where_clause
    GROUP BY i.Item_ID
    ORDER BY i.Status ASC, i.AddedDate DESC, i.Item_ID DESC
";

$types = "";
foreach ($params as $param) {
    if (is_int($param)) {
        $types .= "i";
    } else {
        $types .= "s";
    }
}

$items_stmt = $config->prepare($sql);
if (!empty($params)) {
    $items_stmt->bind_param($types, ...$params);
}
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// Fetch categories for search dropdown
$cat_result = $config->query("SELECT * FROM categories ORDER BY category_Name ASC");
$categories = $cat_result ? $cat_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch available cities for location dropdown
$city_result = $config->query("SELECT DISTINCT City FROM items WHERE City IS NOT NULL AND City != '' ORDER BY City ASC");
$cities = $city_result ? $city_result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "سوق الإعارة";
require_once __DIR__ . '\..\includes\header.php';
?>

<main>
    <div class="container" style="text-align: right;">
        <!-- Marketplace Welcome -->
        <div class="card-header" style="border: none; padding-bottom: 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="hero-badge" style="margin-bottom: 8px;">   استكشف العناصر والمعدات </span>
                <h2>سوق استعرها</h2>
            </div>
            <a href="my_borrows.php" class="btn btn-secondary" ><i class="fa-solid fa-clock-rotate-left"></i> سجل استعاراتي (الطلبات)</a>
        </div>

        <!-- Filter and Search controls -->
        <div class="card" style="margin-top: 25px; padding: 20px;">
            <form action="dashboard.php" method="GET" style="display: flex; flex-direction: column; gap: 15px;">
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
                            <a href="dashboard.php" class="btn btn-secondary" style="height: 46px; display: inline-flex; align-items: center;" title="إعادة تعيين الفلاتر"><i class="fa-solid fa-arrow-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Catalog Display Grid -->
        <div style="margin-top: 30px;">
            <?php if (empty($items)): ?>
                <div class="card" style="text-align: center; padding: 60px 0;">
                    <i class="fa-solid fa-box-open text-muted" style="font-size: 4rem; margin-bottom: 20px; display: block;"></i>
                    <h2>لم يتم العثور على نتائج مطابقة</h2>
                    <p class="text-muted" style="max-width: 500px; margin: 0 auto 20px;">حاول تعديل كلمات البحث، أو اختيار فئة مختلفة، أو إعادة تعيين معايير التصفية الخاصة بك.</p>
                    <a href="dashboard.php" class="btn btn-primary">تصفح كافة العناصر</a>
                </div>
            <?php else: ?>
                <div class="item-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card">
                            <div class="item-img-container">
                                <img src="<?php echo htmlspecialchars($item['ImageUrl'] ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800'); ?>" alt="<?php echo htmlspecialchars($item['Title']); ?>" class="item-img">
                                <?php 
                                    $status_class = strtolower($item['Status']);
                                    $status_ar = [
                                        'available' => 'متاح',
                                        'unavailable' => 'غير متاح',
                                        'borrowed' => 'معار',
                                        'pending' => 'معلق'
                                    ][$status_class] ?? $item['Status'];
                                ?>
                                <span class="item-badge badge-<?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($status_ar); ?>
                                </span>
                            </div>
                            
                            <div class="item-content">
                                <div class="item-meta">
                                    <span><i class="fa-solid fa-tag" style="margin-left: 4px;"></i> <?php echo htmlspecialchars($item['category_Name']); ?></span>
                                    <span><i class="fa-solid fa-location-dot" style="margin-left: 4px;"></i> <?php echo htmlspecialchars($item['City'] ?: 'الاستلام من الموقع'); ?></span>
                                </div>
                                
                                <h3 class="item-title"><?php echo htmlspecialchars($item['Title']); ?></h3>
                                <p class="item-desc"><?php echo htmlspecialchars($item['Description']); ?></p>
                                
                                <div class="item-footer">
                                    <div class="item-days">
                                        أقل استعارة: <strong><?php echo $item['MinBorrowDays']; ?> يوم/أيام</strong>
                                    </div>
                                    <a href="item_detail.php?id=<?php echo $item['Item_ID']; ?>" class="btn btn-secondary btn-sm" style="font-weight: 700;">عرض التفاصيل</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
