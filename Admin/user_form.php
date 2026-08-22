<?php
require_once __DIR__ . '\..\includes\auth.php';
// Ensure only Admin users can access
if (!is_admin()) {
    header('Location: \استعارة\index.php');
    exit;
}

$editing = false;
$user = ['FullName' => '', 'Email' => '', 'PhoneNumber' => '', 'UserRole' => 'EndUser'];
if (isset($_GET['id'])) {
    $editing = true;
    $stmt = $config->prepare("SELECT User_ID, FullName, Email, PhoneNumber, UserRole FROM users WHERE User_ID = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        die('المستخدم غير موجود');
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? 'تعديل' : 'إضافة' ?> مستخدم | استعرها</title>
    <link rel="stylesheet" href="\استعارة\assets\css\style.css">
</head>
<body>
<?php require_once __DIR__ . '\..\includes\header.php'; ?>
<main class="container" style="margin-top: 40px; text-align: right;">
    <h2><?= $editing ? 'تعديل' : 'إضافة' ?> مستخدم</h2>
    <form action="user_action.php" method="post" style="max-width: 500px; margin-top: 20px;">
        <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['User_ID']) ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="full_name" class="form-label">الاسم الكامل</label>
            <input type="text" id="full_name" name="full_name" class="form-control" required value="<?= htmlspecialchars($user['FullName'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['Email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="phone" class="form-label">رقم الهاتف</label>
            <input type="text" id="phone" name="phone" class="form-control" placeholder="مثال: 0599000000" value="<?= htmlspecialchars($user['PhoneNumber'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="user_role" class="form-label">دور وصلاحية المستخدم</label>
            <select id="user_role" name="user_role" class="form-control" required>
                <option value="EndUser" <?= ($user['UserRole'] ?? '') === 'EndUser' ? 'selected' : '' ?>>مستخدم نهائي (EndUser)</option>
                <option value="Admin" <?= ($user['UserRole'] ?? '') === 'Admin' ? 'selected' : '' ?>>مسؤول (Admin)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="password" class="form-label">كلمة المرور <?= $editing ? '(اتركه فارغاً للاحتفاظ بكلمة المرور الحالية دون تغيير)' : '' ?></label>
            <input type="password" id="password" name="password" class="form-control" <?= $editing ? '' : 'required' ?>>
        </div>
        

        
        <div style="display: flex; gap: 10px; margin-top: 25px;">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'تحديث' : 'إنشاء' ?> مستخدم</button>
            <a href="manage_users.php" class="btn btn-secondary">إلغاء</a>
        </div>
    </form>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>


