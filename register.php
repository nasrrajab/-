<?php
require_once __DIR__ . '\includes\auth.php';

if (is_logged_in()){
    if ($_SESSION['UserRole'] === 'Admin'){
        header('location: Admin\dashboard.php');
    }else {
        header('location: EndUser\dashboard.php');
    }
    exit;
}

$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'EndUser';

    if(empty ($fullname) || empty($email) || empty($password)){
        $error_msg = 'يرجى ملء جميع الحقول المطلوبة.';
    }elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'يرجى إدخال بريد إلكتروني صالح.';
    }elseif (strlen($password) < 6){
        $error_msg = 'يجب أن تتكون كلمة المرور من 6 أحرف على.';
    }else {
        try {
            $check_stmt = $config->prepare("SELECT User_ID FROM users WHERE Email = ?");
            $check_stmt->bind_param("s",$email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->fetch_assoc()) {
                $error_msg = 'البريد الإلكتروني هذا مسجل بالفعل.';
            }else {
                $insert_stmt = $config->prepare("INSERT INTO users (FullName, Email, PhoneNumber, Password, UserRole, CreateDate) VALUES (?,?,?,?,?,?)");
                $phone_val = !empty($phone) ? $phone : null;
                $date_val = date('Y-m-d');
                $insert_stmt->bind_param("ssssss", $fullname,$email,$phone_val,$password,$role,$date_val);
                $insert_stmt->execute();

                $_SESSION['registrtion-success'] = 'تم التسجيل بنجاح! يمكنك الا تسجيل الدخول.';
                header('location: login.pho');
                exit;
            }

        }catch (Exception $e) {
            $error_msg = 'فشل التسجيل يرجى المحاولة لاحقاً التفاصيل: ' . $e->getMessage();
        }
    }
}

$page_title = "أنشاء حساب";
require_once __DIR__ . '\includes\header.php';
?>

<main>
    <div class="container">
        <div class="auth-container card">
            <div class="card-header" style="justify-content: center; flex-direction: column; gap: 8px">
                <h2>البدء الان</h2>
                <p style="margin: 0; font-size: 0.9rem;">انضم إلى استعرها لبدء استعارة او إدارة</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" autocomplete="off">
            <div class ="form-group" >
                <label for="fullname" class="form-label font-medium">الاسم الكامل</label>
                <input type="text" name="fullname" id="fullname" class="form-control" placeholder="مثال : أحمد محمد" required value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
            </div>

            <div class ="form-group" >
                <label for="email" class="form-label ">البريد الإلكتروني</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nasr@domain.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class ="form-group" >
                <label for="phone" class="form-label ">رقم الهاتف</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="+966500000000" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div class ="form-group" style="margin-bottom: 24px;">
                <label for="password" class="form-label ">كلمة المرور  </label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required >
            
                 <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px">تسجيل الدخول</button>
            </form>

            <div style="text-align: center; margin-top: 25px; font-size: 0.9rem">
                <span class="text-muted">لديك حساب بالفعل؟</span>
                <a href="login.php" style="margin-right: 5px; font-weight: 600;">سجل دخولك بدلا من ذلك</a>

            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '\includes\footer.php'; ?>