<?php
    require_once __DIR__ . '/../config/config_db.php';


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }


    function is_logged_in() {
        return isset($_SESSION['User_ID']);
    }


    function require_login() {
        if (!is_logged_in()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('location: استعارة\login.php');
            exit;
        }
    }

    /**
 * Enforces specific role limits.
 * @param string $required_role
 */
    function require_role($required_role) {
        require_login();
        if ($_SESSION['UserRole'] !== $required_role) {
            //Redirect to their default portal
            //إعادة التوجيه إلى بوابتهم الافتراضية
            if ($_SESSION['UserRole'] === 'Admin') {
                header('location: \استعارة\Admin\dashboard.php');
            }else {
                header('location: \استعارة\EndUser\dashboard.php');
            }
            exit;

        }
    }

    function verify_active_session() {
        global $config;
        if (!is_logged_in()) return false; 

        try {
            $stmt = $config->prepare("SELECT User_ID FROM users WHERE User_ID = ?");
            $stmt->bind_param("i",$_SESSION['User_ID']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                //User row deleted - force logout
                //تم حذف صف المستخدم - فرض تسجيل الخروج
                session_unset();
                session_destroy();
                return false;

            }
            return true;
        }catch (Exception $e) {
            return false;
        }

    }

    if (is_logged_in()) {
        verify_active_session();
    }
    function is_admin() {
        return isset($_SESSION['UserRole']) && $_SESSION['UserRole'] === 'Admin';
    }
?>