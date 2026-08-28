<?php
// index.php
require_once __DIR__ . '\includes\auth.php';

// Redirect to dashboard if logged in
if (is_logged_in()) {
    if ($_SESSION['UserRole'] === 'Admin') {
        header('Location: Admin\dashboard.php');
    } else {
        header('Location: EndUser\dashboard.php');
    }
    exit;
}
 
$page_title = "مرحباً بك في استعرها";

// Fetch available items for the showcase
$search = trim($_GET['search'] ?? '');
$filter_category = intval($_GET['category'] ?? 0);
$filter_city = trim($_GET['city'] ?? '');

$page = intval($_GET['page'] ?? 0);
$items_per_page = 6;
$offset = $page * $items_per_page;
$showcase_items = [];
$categories = [];
$cities = [];

try {
    // Build dynamic WHERE clause
    $where = "i.Status = 'Available'";
    $types = "";
    $params = [];
    if ($search !== '') {
        $where .= " AND (i.Title LIKE ? OR i.Description LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    if ($filter_category > 0) {
        $where .= " AND c.Category_ID = ?";
        $params[] = $filter_category;
        $types .= "i";
    }
    if ($filter_city !== '') {
        $where .= " AND i.City = ?";
        $params[] = $filter_city;
        $types .= "s";
    }
    $sql = "SELECT i.*, c.Category_Name, u.FullName as OwnerName, i.City, img.ImageUrl
    FROM items i
    JOIN categories c ON i.Category_ID = c.Category_ID
    JOIN users u ON i.User_ID = u.User_ID
    LEFT JOIN item_images img ON i.Item_ID = img.Item_ID
    WHERE {$where}
    GROUP BY i.Item_ID
    ORDER BY i.Item_ID DESC
    LIMIT ? OFFSET ?";
    
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $item_stmt = $config->prepare($sql);
    if (!empty($params)) {
        $item_stmt->bind_param($types, ...$params);
    }
    
    $item_stmt->execute();
    $showcase_items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cat_result = $config->query("SELECT Category_ID, Category_Name FROM categories ORDER BY Category_Name ASC");
    $categories = $cat_result ? $cat_result->fetch_all(MYSQLI_ASSOC) : [];
    
    $city_result = $config->query("SELECT DISTINCT City FROM items WHERE City IS NOT NULL AND City != '' ORDER BY City ASC");
    $cities = $city_result ? $city_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    // Fail silently
    error_log($e->getMessage());
}

require_once __DIR__ . '\includes\header.php';
?>

<main>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1>استعر وأعر أي شيء، محلياً وبكل أمان</h1>
            <p>يربط "استعرها" بين المستخدمين النهائيين الذين يتطلعون إلى استعارة معدات وأدوات وإلكترونيات وأجهزة عالية الجودة، بدعم من إدارة المنصة (Admin) المحلية لضمان التنسيق المثالي، والتحقق من القوائم، والثقة المتبادلة.</p>
        </div>

        <!-- Available Items Showcase Section -->
        <div style="margin-top: 20px; margin-bottom: 20px;">
            <div style="text-align: right; margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; margin-top: 5px; text-align: right;">نظرة عامة على العناصر المتاحة للاستعارة</h2>
            </div>

            <div class="card" style="margin-top: 25px; padding: 20px; text-align: right;">
                <form action="\استعارة\index.php" method="GET" style="display: flex; flex-direction: column; gap: 15px;">
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
                                    <option value="<?php echo $cat['Category_ID']; ?>" <?php echo $filter_category === intval($cat['Category_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['Category_Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label for="city" class="form-label">الموقع (المدينة)</label>
                            <select name="city" id="city" class="form-control">
                                <option value="">جميع المواقع</option>
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['City']); ?>" <?php echo $filter_city === $c['City'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['City']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="height: 46px;"><i class="fa-solid fa-magnifying-glass"></i> تصفية</button>
                            <?php if (!empty($search) || $filter_category > 0 || !empty($filter_city)): ?>
                                <a href="\استعارة\index.php" class="btn btn-secondary" style="height: 46px; display: inline-flex; align-items: center;" title="إعادة تعيين الفلاتر"><i class="fa-solid fa-arrow-rotate-left"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($showcase_items)): ?>
                <div class="card" style="text-align: center; padding: 40px 0;">
                    <i class="fa-solid fa-hourglass-empty" style="font-size: 3rem; margin-bottom: 15px; display: block; color: #64748b;"></i>
                    <p class="text-muted">لا توجد عناصر معروضة حالياً. تفضل بزيارتنا لاحقاً!</p>
                </div>
            <?php else: ?>
                <div class="item-grid" style="margin-top: 30px;">
                    <?php foreach ($showcase_items as $item): ?>
                        <div class="item-card">
                            <div class="item-img-container">
                                <img src="<?php echo htmlspecialchars($item['ImageUrl'] ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800'); ?>" alt="<?php echo htmlspecialchars($item['Title']); ?>" class="item-img">
                                <span class="item-badge badge-available">
                                    <?php echo htmlspecialchars($item['Status'] === 'Available' ? 'متاح' : $item['Status']); ?>
                                </span>
                            </div>
                            
                            <div class="item-content">
                                <div class="item-meta">
                                    <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($item['Category_Name']); ?></span>
                                    <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($item['City'] ?: 'الاستلام فقط'); ?></span>
                                </div>
                                
                                <h3 class="item-title"><?php echo htmlspecialchars($item['Title']); ?></h3>
                                <p class="item-desc"><?php echo htmlspecialchars($item['Description']); ?></p>
                                
                                <div class="item-footer">
                                    <div class="item-days">
                                        الحد الأدنى للاستعارة: <strong><?php echo $item['MinBorrowDays']; ?> يوم/أيام</strong>
                                    </div>
                                    <a href="EndUser\item_detail.php?id=<?php echo $item['Item_ID']; ?>" class="btn btn-secondary btn-sm" style="font-weight: 700;">استعر الآن</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($showcase_items) == $items_per_page): ?>
                    <?php
                        $next_page = $page + 1;
                        $query_params = [];
                        if ($search !== '') $query_params['search'] = $search;
                        if ($filter_category !== '') $query_params['category'] = $filter_category;
                        $query_params['page'] = $next_page;
                        $query_string = http_build_query($query_params);
                        $load_more_url = '\استعارة\index.php?' . $query_string;
                    ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo $load_more_url; ?>" class="btn btn-primary">تحميل المزيد</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '\includes\footer.php'; ?>
