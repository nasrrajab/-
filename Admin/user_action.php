<?php
require_once __DIR__ . '\..\includes\auth.php';
// Ensure only Admin users can access this actions file
if (!is_admin()) {
    header('Location: ' . '\استعارة\index.php');
    exit;
}

// Determine action from request
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'add':
        // Expected POST fields: full_name, email, password, phone, user_role
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone    = trim($_POST['phone'] ?? '') ?: null;
        $role     = trim($_POST['user_role'] ?? 'EndUser');
        if (!in_array($role, ['Admin', 'EndUser'])) {
            $role = 'EndUser';
        }
        if ($fullName && $email && $password) {
            $stmt = $config->prepare('INSERT INTO users (FullName, Email, PhoneNumber, Password, UserRole, CreateDate) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param("sssss", $fullName, $email, $phone, $password, $role);
            $stmt->execute();
            $stmt->close();
        }
        break;
    case 'edit':
        // Expected POST fields: id, full_name, email, phone, password (optional), user_role
        $id       = intval($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '') ?: null;
        $role     = trim($_POST['user_role'] ?? 'EndUser');
        if (!in_array($role, ['Admin', 'EndUser'])) {
            $role = 'EndUser';
        }
        if ($id && $fullName && $email) {
            if (!empty($_POST['password'])) {
                $stmt = $config->prepare('UPDATE users SET FullName = ?, Email = ?, PhoneNumber = ?, Password = ?, UserRole = ? WHERE User_ID = ?');
                $stmt->bind_param("sssssi", $fullName, $email, $phone, $_POST['password'], $role, $id);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $config->prepare('UPDATE users SET FullName = ?, Email = ?, PhoneNumber = ?, UserRole = ? WHERE User_ID = ?');
                $stmt->bind_param("ssssi", $fullName, $email, $phone, $role, $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        break;
    // activate/deactivate cases removed – IsActive column no longer exists
    case 'delete':
        // Permanently delete a user and all their related data (except oneself)
        $id = intval($_GET['id'] ?? 0);
        if ($id && $id !== intval($_SESSION['User_ID'])) {
            try {
                $config->begin_transaction();

                // 1. Delete borrowing records that belong to this user
                $stmt1 = $config->prepare('DELETE FROM borrowing WHERE User_ID = ?');
                $stmt1->bind_param("i", $id);
                $stmt1->execute();
                $stmt1->close();

                // 2. Find all items owned by this user
                $itemStmt = $config->prepare('SELECT Item_ID FROM items WHERE User_ID = ?');
                $itemStmt->bind_param("i", $id);
                $itemStmt->execute();
                $res = $itemStmt->get_result();
                $itemIds = [];
                while ($row = $res->fetch_row()) {
                    $itemIds[] = intval($row[0]);
                }
                $itemStmt->close();

                if (!empty($itemIds)) {
                    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                    $types = str_repeat('i', count($itemIds));

                    // 3. Delete borrowing records for those items (other users may have borrowed them)
                    $stmt3 = $config->prepare("DELETE FROM borrowing WHERE Item_ID IN ($placeholders)");
                    $stmt3->bind_param($types, ...$itemIds);
                    $stmt3->execute();
                    $stmt3->close();

                    // 4. Delete item images
                    $stmt4 = $config->prepare("DELETE FROM item_images WHERE Item_ID IN ($placeholders)");
                    $stmt4->bind_param($types, ...$itemIds);
                    $stmt4->execute();
                    $stmt4->close();

                    // 5. Delete item availability records
                    $stmt5 = $config->prepare("DELETE FROM item_availability WHERE Item_ID IN ($placeholders)");
                    $stmt5->bind_param($types, ...$itemIds);
                    $stmt5->execute();
                    $stmt5->close();

                    // 6. Delete the items themselves
                    $stmt6 = $config->prepare("DELETE FROM items WHERE Item_ID IN ($placeholders)");
                    $stmt6->bind_param($types, ...$itemIds);
                    $stmt6->execute();
                    $stmt6->close();
                }

                // 7. Finally delete the user row
                $stmt7 = $config->prepare('DELETE FROM users WHERE User_ID = ?');
                $stmt7->bind_param("i", $id);
                $stmt7->execute();
                $stmt7->close();

                $config->commit();
            } catch (Exception $e) {
                $config->rollback();
                error_log('User delete failed: ' . $e->getMessage());
            }
        }
        break;
    default:
        // unknown action – do nothing
        break;
}

// Redirect back to the user management page
header('Location: ' . '\استعارة\Admin\manage_users.php');
exit;
?>
