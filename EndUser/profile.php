<?php
require_once __DIR__ . '\..\includes\auth.php'; // ensure logged in
require_once __DIR__ . '\..\includes\header.php';

// Fetch user data from session
$fullName = htmlspecialchars($_SESSION['FullName'] ?? '');
$userRole = htmlspecialchars($_SESSION['UserRole'] ?? 'EndUser');
$email = htmlspecialchars($_SESSION['Email'] ?? '');
$city = htmlspecialchars($_SESSION['City'] ?? '');
$phone = htmlspecialchars($_SESSION['Phone'] ?? '');

$alert_message = '';
$alert_type = ''; // 'success' or 'danger'

// Handle profile form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $_SESSION['FullName'] = $_POST['full_name'] ?? $_SESSION['FullName'];
    $_SESSION['Email'] = $_POST['email'] ?? $_SESSION['Email'];
    $_SESSION['City'] = $_POST['city'] ?? $_SESSION['City'];
    $_SESSION['Phone'] = $_POST['phone'] ?? $_SESSION['Phone'];
    
    // Refresh local variables
    $fullName = htmlspecialchars($_SESSION['FullName']);
    $email = htmlspecialchars($_SESSION['Email']);
    $city = htmlspecialchars($_SESSION['City']);
    $phone = htmlspecialchars($_SESSION['Phone']);
    
    $alert_message = 'تم تحديث الملف الشخصي بنجاح!';
    $alert_type = 'success';
}

// Handle password change submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Fetch the stored plain-text password from the database
    global $config;
    $pwStmt = $config->prepare("SELECT Password FROM users WHERE User_ID = ?");
    $pwStmt->bind_param("i", $_SESSION['User_ID']);
    $pwStmt->execute();
    $res = $pwStmt->get_result()->fetch_row();
    $storedPassword = $res ? $res[0] : '';

    if ($currentPass !== $storedPassword) {
        $alert_message = 'كلمة المرور الحالية غير صحيحة.';
        $alert_type = 'danger';
    } elseif ($newPass !== $confirmPass) {
        $alert_message = 'كلمات المرور الجديدة غير متطابقة.';
        $alert_type = 'danger';
    } else {
        // Save new plain-text password to database
        $upStmt = $config->prepare("UPDATE users SET Password = ? WHERE User_ID = ?");
        $upStmt->bind_param("si", $newPass, $_SESSION['User_ID']);
        $upStmt->execute();
        $alert_message = 'تم تغيير كلمة المرور بنجاح!';
        $alert_type = 'success';
    }
}
?>
<main>
    <div class="container" style="max-width: 800px; margin-top: 40px; margin-bottom: 60px; text-align: right;">
        <h2 class="mb-4"><i class="fa-solid fa-user-gear text-primary" style="margin-left: 8px;"></i> الملف الشخصي للمستخدم</h2>

        <!-- Alert messages handled automatically by SweetAlert2 progressively! -->
        <?php if (!empty($alert_message)): ?>
            <div class="alert alert-<?php echo $alert_type; ?>" style="display: none;">
                <?php echo $alert_message; ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Profile Info Form -->
            <form method="POST" class="card" style="padding: 30px;">
                <input type="hidden" name="action" value="update_profile">
                <h3 class="mb-4" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                    <i class="fa-solid fa-id-card text-accent" style="margin-left: 8px;"></i> المعلومات الشخصية
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="full_name">الاسم الكامل</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo $fullName; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="city">المدينة</label>
                    <input type="text" id="city" name="city" class="form-control" value="<?php echo $city; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">رقم الهاتف</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $phone; ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">الصلاحية</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userRole === 'Admin' ? 'حساب مسؤول (Admin)' : 'حساب مستخدم نهائي (EndUser)'); ?>" disabled style="opacity: 0.6; background: rgba(255, 255, 255, 0.02); cursor: not-allowed;">
                </div>
                <div class="mt-4" style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save" style="margin-left: 5px;"></i> حفظ الملف الشخصي</button>
                </div>
            </form>

            <!-- Password Change Form -->
            <form method="POST" class="card" style="padding: 30px;" data-confirm="هل أنت متأكد من رغبتك في تغيير كلمة المرور؟">
                <input type="hidden" name="action" value="change_password">
                <h3 class="mb-4" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                    <i class="fa-solid fa-key text-accent" style="margin-left: 8px;"></i> الأمان وكلمة المرور
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="current_password">كلمة المرور الحالية</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" placeholder="أدخل كلمة المرور الحالية" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">كلمة المرور الجديدة</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="أدخل كلمة المرور الجديدة" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="أدخل تأكيد كلمة المرور الجديدة" required>
                </div>
                <div class="mt-4" style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-accent"><i class="fa-solid fa-shield-halved" style="margin-left: 5px;"></i> تحديث كلمة المرور</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '\..\includes\footer.php';
?>
