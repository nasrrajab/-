<?php
require_once __DIR__ . '\..\includes\auth.php';
// Ensure only Admin users can access
if (!is_admin()) {
    header('Location: \استعارة\index.php');
    exit;
}

// Fetch all users
global $config;
$stmt = $config->prepare("SELECT User_ID, FullName, Email, PhoneNumber, UserRole FROM users");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$page_title = "إدارة المستخدمين";
require_once __DIR__ . '\..\includes\header.php';
?>
<main class="container" style="margin-top: 40px;">
    <h2>إدارة مستخدمي المنصة</h2>
    <a href="user_form.php" class="btn btn-primary" style="margin-bottom: 20px;">إضافة مستخدم جديد</a>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>الرقم التعريفي</th>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>رقم الهاتف</th>
                    <th>الصلاحية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['User_ID']; ?></td>
                        <td><?php echo htmlspecialchars($u['FullName']); ?></td>
                        <td><?php echo htmlspecialchars($u['Email']); ?></td>
                        <td><?php echo htmlspecialchars($u['PhoneNumber'] ?? 'غير متوفر'); ?></td>
                        <td>
                            <?php 
                                $role_badge_style = $u['UserRole'] === 'Admin' ? 
                                    'background: rgba(66, 129, 164, 0.15); color: #4281a4;' : 
                                    'background: rgba(86, 194, 166, 0.15); color: #56c2a6;';
                            ?>
                            <span style="display: inline-block; padding: 4px 10px; font-size: 0.75rem; border-radius: 12px; font-weight: 700; <?php echo $role_badge_style; ?>">
                                <?php echo $u['UserRole'] === 'Admin' ? 'مسؤول' : 'مستخدم نهائي'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="user_form.php?id=<?php echo $u['User_ID']; ?>" class="btn btn-secondary btn-sm">تعديل</a>
                            <?php if ((int)$u['User_ID'] !== (int)$_SESSION['User_ID']): ?>
                                <a href="user_action.php?action=delete&id=<?php echo $u['User_ID']; ?>" class="btn btn-danger btn-sm" data-confirm="تحذير: سيتم حذف المستخدم '<?php echo htmlspecialchars($u['FullName']); ?>' نهائياً مع جميع سجلات الاستعارة والعناصر الخاصة به. هذا الإجراء لا يمكن التراجع عنه.">حذف نهائي</a>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.85rem; font-weight: 600; padding-right: 5px;">(حسابك الحالي)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require_once __DIR__ . '\..\includes\footer.php'; ?>
