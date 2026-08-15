<?php
require_once __DIR__ . '\includes\auth.php';


//redirect if already logged in
//إعادة التوجيه في حال تسجيل الدخول مسبقاً
if (is_logged_in()) {
    if($_SESSION['UserRole'] === 'Admin') {
        header('location: \استعارة\Admin\dashboard.php');
    } else {
        header('location: \استعارة\EndUser\dashboard.php');
    }
    exit;
}

$error_msg = '';
$success_msg = '';

if (isset($_SESSION['registration_success'])){
    $success_msg = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if(isset($_GET['timeout']) ) {
    $error_msg = 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
   
    if(empty($email) || empty($password)) {
        $error_msg = 'يرجى ملء جميع الحقول';
    }else{
        try{
            $stmt = mysqli_query($config,"SELECT * FROM users where Email = '$email';");
            $user = mysqli_fetch_assoc($stmt);

            if ($user && $password === $user['Password']) {
                // Populate Session
                $_SESSION['User_ID'] = $user['User_ID'];
                $_SESSION['FullName'] = $user['FullName'];
                $_SESSION['UserRole'] = $user['UserRole'];
                $_SESSION['last_activity'] = time(); // Update last activity time

                //Redirect
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("location: " . $redirect);
                } elseif ($user['UserRole'] === 'Admin') {
                    header('location: Admin/dashboard.php');
                }else{
                    header('location: EndUser/dashboard.php');
                }
                exit;
            } else {
                $error_msg = 'البريد الإلكتروني أو كلمة المرور غير صحيحه.';
            }
        } catch (Exception $e) {
            $error_msg = 'حدث خطأ ما . يرجى المحاولة مرة أخرى لاحقا.';
        }
    }
}
$page_title = "تسجيل الدخول";

// include "includes\header.php";
require_once __DIR__ . '\includes\header.php';
?>
<main>
    <div class="container">
        <div class="auth-container card ">
            <div class="img-login">
                <img src="assets/images/customer-service.png" alt="شعار صفحة تسجيل الدخول">
            </div>
            <div class="card-header" style="justify-content: center; flex-direction: column; gap:8px">
                <h2>مرحباً بعودتك</h2>
                <p style="margin: 0; font-size:0.9rem">سجل دخولك لادارة العناصر أو تصفح منصةالاستعاة</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="Email" class="form-label">البريد الالكتروني</label>
                    <input type="Email" name="email" id="Email" class="form-control" placeholder="name@domain.com" required>
                </div>
                <div class="form-group">
                    <label for="Password" class="form-label">كلمة المرور</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div>
                    <button type="submit" class="btn-primary btn" style="width: 100% ; padding: 14px;">تسجيل الدخول</button>
                </div>
            </form>
            <div class="register-here">
                <span style="color: #64748b";>ليس لديك حساب؟</span>
                <a href="register.php" style="margin-right: 5px; font-weight: 600;">سجل هنا</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '\includes\footer.php'; ?>