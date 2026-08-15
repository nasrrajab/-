<?php 
require_once __DIR__ . '/auth.php';

//لتحديد الصفحه النشطه
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0 ">
    <link rel="stylesheet" href="\استعارة\assets\css\style.css">
    <link rel="icon" type="image/png" href="\استعارة\assets\images\customer-service.png" />
    <title><?php echo isset($page_title) ? $page_title . " | استعرها" : "منصة مشاركة الأغراض | p2p استعرها"; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    
    
</head>
<body>

<header>
    <div class="container nav-container">
        <a href="" class="logo" style="display: flex; align-items:center; gap: 10px" >
           <img src="\استعارة\assets\images\customer-service.png" alt="شعار استعرها" style="width: 60px; height: 60px; display: block; object-fit: contain; background: transparent; padding: 3px;"> 
           <span class="logo-text">استعرها</span>
        </a>

        <nav>
            <ul class="nav-links">
                <?php if (!is_logged_in()): ?>
                    <li><a href="\استعارة\login.php" class="btn-nav">تسجيل الدخول</a></li>
                    <li><a href="\استعارة\register.php">إنشاء حساب</a></li>
                    <?php else: ?>

                    <!-- Admin Links-->
                        <?php if ($_SESSION['UserRole'] === 'Admin'): ?>
                             <li class="<?php echo ($current_page == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/Admin/') !== false) ? 'active' : ''; ?>">
                            <a href="\استعارة\Admin\dashboard.php"><i class="fa-solid fa-chart-line"></i> لوحة التحكم</a>
                        </li>
                        <li class="<?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>">
                            <a href="\استعارة\Admin\manage_users.php"><i class="fa-solid fa-users"></i> المستخدمين</a>
                        </li>
                        <li class="<?php echo $current_page == 'items.php' ? 'active' : ''; ?>">
                            <a href="\استعارة\Admin\items.php"><i class="fa-solid fa-boxes-stacked"></i> المخزون</a>
                        </li>
                        <li class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                            <a href="\استعارة\Admin\categories.php"><i class="fa-solid fa-tags"></i> الفئات</a>
                        </li>
                        <li class="<?php echo $current_page == 'availability.php' ? 'active' : ''; ?>">
                            <a href="\استعارة\Admin\availability.php"><i class="fa-solid fa-calendar-xmark"></i> مواعيد الحجب</a>
                        </li>


                        <!-- EndUser Links -->
                    <?php else: ?>
                        <li>
                            <a href="\استعارة\EndUser\dashboard.php"><i class="fa-solid fa-shop">السوق</i></a>
                        </li>
                        <li class="<?php echo $current_page == 'my_borrows.php' ? 'active' : ''; ?>">
                            <a href="\استعارة\EndUser\my_borrows.php"><i class="fa-solid fa-clock-history"></i> استعاراتي</a>
                        </li>
                    <?php endif; ?>


                      <!-- Authenticated User Profile Badge -->
                    <li class="nav-item user-dropdown" style="position:relative; display:inline-block;">
                        <button id="userDropdown" class="btn btn-secondary d-flex align-items-center" style="background:transparent; border:none; color:#1e293b; font-weight:600; cursor:pointer; gap:8px; padding:8px 4px; border:1px solid #e2e8f0; border-radius:25px;">
                            <i class="fa-solid fa-circle-user" style="font-size: 1.2rem; color: #4281a4;"></i>
                            <?php echo htmlspecialchars($_SESSION['FullName']); ?>
                            <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem; opacity: 0.6;"></i>
                        </button>
                        <ul class="dropdown-menu" style="display: none; position: absolute; left: 0; top: calc(100% + 8px); min-width: 180px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10); border-radius: 8px; z-index: 1000; padding: 6px 0; margin: 0; list-style: none !important; list-style-type: none !important;">
                            <li style="border-bottom: 1px solid #e2e8f0; padding: 8px 16px; margin-bottom: 4px; list-style: none !important; list-style-type: none !important;">
                                <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700;">نوع الحساب</div>
                                <div class="role-badge role-<?php echo $_SESSION['UserRole']; ?>" style="display: inline-block; margin-top: 4px; padding: 2px 8px; font-size: 0.65rem; border-radius: 12px; font-weight: 700; text-transform: uppercase; line-height: 1.2;"><?php echo $_SESSION['UserRole'] === 'Admin' ? 'مسؤول (Admin)' : 'مستخدم نهائي (EndUser)'; ?></div>
                            </li>
                            <li style="list-style: none !important; list-style-type: none !important;">
                                <a class="dropdown-item" href="<?php echo ($_SESSION['UserRole'] === 'Admin' ? '\استعارة\Admin\profile.php' : '\استعارة\EndUser\profile.php'); ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #1e293b; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.25s ease;">
                                    <i class="fa-solid fa-user-gear" style="color: #4281a4; width: 16px;"></i> الملف الشخصي
                                </a>
                            </li>
                            <li style="list-style: none !important; list-style-type: none !important;">
                                <a class="dropdown-item" href="\استعارة/logout.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #1e293b; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.25s ease;">
                                    <i class="fa-solid fa-right-from-bracket" style="color: #ef4444; width: 16px;"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                    <script>
                        document.getElementById('userDropdown').addEventListener('click', function(e){
                            e.stopPropagation();
                            var menu = this.parentElement.querySelector('.dropdown-menu');
                            var isShown = menu.style.display === 'block';
                            // hide any other open menus
                            document.querySelectorAll('.user-dropdown .dropdown-menu').forEach(function(m){m.style.display='none';});
                            menu.style.display = isShown ? 'none' : 'block';
                        });
                        // close when clicking outside
                        document.addEventListener('click', function(){
                            document.querySelectorAll('.user-dropdown .dropdown-menu').forEach(function(m){m.style.display='none'; });

                        });
                    </script>              
                   <?php endif; ?>

            </ul>
        </nav>

    </div>

</header>
    

