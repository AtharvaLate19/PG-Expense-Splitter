<?php
require('db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Session variables
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$pg_code = $_SESSION['pg_code'];

// Fetch data
$group = fetchGroup($conn, $pg_code);
$members = fetchMembers($conn, $pg_code);
$current_user = fetchUser($conn, $user_id);
$admin_name = fetchAdminName($conn, $pg_code);

// Profile pictures are stored as files, so no database schema change is required.
$profile_picture = '';
$profile_upload_dir = __DIR__ . '/uploads/profiles';
foreach (['jpg', 'jpeg', 'png', 'webp'] as $profile_ext) {
    $candidate = $profile_upload_dir . '/user_' . intval($user_id) . '.' . $profile_ext;
    if (is_file($candidate)) {
        $profile_picture = 'uploads/profiles/user_' . intval($user_id) . '.' . $profile_ext;
        break;
    }
}

// Handle POST requests
$delete_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete_error = handlePostRequest($conn, $role, $pg_code, $group, $user_id);
}

// Helper Functions
function fetchGroup($conn, $pg_code) {
    $result = mysqli_query($conn, "SELECT * FROM groupp WHERE pg_code='$pg_code'");
    return $result ? mysqli_fetch_assoc($result) : null;
}

function fetchMembers($conn, $pg_code) {
    return mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code'");
}

function fetchUser($conn, $user_id) {
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id' LIMIT 1");
    return $result ? mysqli_fetch_assoc($result) : null;
}

function fetchAdminName($conn, $pg_code) {
    $result = mysqli_query($conn, "SELECT name FROM users WHERE pg_code='$pg_code' AND role='Admin' LIMIT 1");
    return ($result && $row = mysqli_fetch_assoc($result)) ? $row['name'] : "";
}

function handlePostRequest($conn, $role, $pg_code, $group, $user_id) {
    if (isset($_POST['split_expense']) && $role == 'Admin') {
        handleSplitExpense($conn, $pg_code, $group);
    } elseif (isset($_POST['add_expense']) && $role == 'Admin') {
        handleAddExpense($conn, $group);
    } elseif (isset($_POST['edit_expense']) && $role == 'Admin') {
        handleEditExpense($conn);
    } elseif (isset($_POST['add_member']) && $role == 'Admin') {
        handleAddMember($conn, $pg_code);
    } elseif (isset($_POST['remove_member']) && $role == 'Admin') {
        handleRemoveMember($conn, $pg_code);
    } elseif (isset($_POST['mark_paid']) && $role == 'Admin') {
        handleMarkPaid($conn);
    } elseif (isset($_POST['update_profile'])) {
        handleUpdateProfile($conn, $user_id);
    } elseif (isset($_POST['delete_account'])) {
        return handleDeleteAccount($conn, $user_id);
    }
    return null;
}

function handleSplitExpense($conn, $pg_code, $group) {
    $desc = mysqli_real_escape_string($conn, $_POST['split_description']);
    $amt = floatval($_POST['split_amount']);
    $gid = $group['id'];
    
    $countRes = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE pg_code='$pg_code' AND role!='Admin'");
    $count = mysqli_fetch_assoc($countRes)['total'] ?? 0;
    
    if ($count > 0) {
        $perPerson = round($amt / $count, 2);
        $allMembers = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code' AND role!='Admin'");
        
        while ($mem = mysqli_fetch_assoc($allMembers)) {
            $uid = $mem['id'];
            $existingExp = mysqli_query($conn, "SELECT * FROM expenses WHERE group_id='$gid' AND user_id='$uid'");
            
            if (mysqli_num_rows($existingExp) > 0) {
                $expRow = mysqli_fetch_assoc($existingExp);
                $newAmount = floatval($expRow['amount']) + $perPerson;
                mysqli_query($conn, "UPDATE expenses SET amount='$newAmount', description=CONCAT(description, ', $desc') WHERE group_id='$gid' AND user_id='$uid'");
            } else {
                mysqli_query($conn, "INSERT INTO expenses (group_id,user_id,description,amount,status) VALUES ('$gid','$uid','$desc','$perPerson','Pending')");
            }
        }
    }
    header("Location: dashboard.php");
    exit();
}

function handleAddExpense($conn, $group) {
    $desc = mysqli_real_escape_string($conn, $_POST['expense_description']);
    $amt = floatval($_POST['expense_amount']);
    $uid = intval($_POST['expense_user_id']);
    $gid = $group['id'];
    mysqli_query($conn, "INSERT INTO expenses (group_id,user_id,description,amount,status) VALUES ('$gid','$uid','$desc','$amt','Pending')");
    header("Location: dashboard.php");
    exit();
}

function handleEditExpense($conn) {
    $exp_id = intval($_POST['expense_id']);
    $desc = mysqli_real_escape_string($conn, $_POST['edit_expense_description']);
    $amt = floatval($_POST['edit_expense_amount']);
    mysqli_query($conn, "UPDATE expenses SET description='$desc', amount='$amt' WHERE id='$exp_id'");
    header("Location: dashboard.php");
    exit();
}

function handleAddMember($conn, $pg_code) {
    $name = mysqli_real_escape_string($conn, $_POST['new_member_name']);
    $email = mysqli_real_escape_string($conn, $_POST['new_member_email']);
    $phone = mysqli_real_escape_string($conn, $_POST['new_member_phone']);
    $password = password_hash($_POST['new_member_password'], PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (name,email,password,pg_code,role,phone) VALUES ('$name','$email','$password','$pg_code','User','$phone')");
    header("Location: dashboard.php");
    exit();
}

function handleRemoveMember($conn, $pg_code) {
    $remove_id = intval($_POST['remove_member_id']);
    mysqli_query($conn, "DELETE FROM users WHERE id='$remove_id' AND pg_code='$pg_code' AND role!='Admin'");
    header("Location: dashboard.php");
    exit();
}

function handleMarkPaid($conn) {
    $expense_id = intval($_POST['expense_id']);
    $new_status = $_POST['paid_pending_status'] == "Paid" ? "Paid" : "Pending";
    mysqli_query($conn, "UPDATE expenses SET status='$new_status' WHERE id='$expense_id'");
    header("Location: dashboard.php");
    exit();
}

function handleUpdateProfile($conn, $user_id) {
    $email = mysqli_real_escape_string($conn, trim($_POST['profile_email'] ?? ''));
    $password = $_POST['profile_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: dashboard.php?profile_error=invalid_email");
        exit();
    }

    if (!empty($_FILES['profile_picture']['name'])) {
        $upload = $_FILES['profile_picture'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 3 * 1024 * 1024) {
            header("Location: dashboard.php?profile_error=upload");
            exit();
        }

        $imageInfo = @getimagesize($upload['tmp_name']);
        $mime = $imageInfo['mime'] ?? '';
        if (!$imageInfo || !isset($allowed[$mime])) {
            header("Location: dashboard.php?profile_error=type");
            exit();
        }

        $uploadDir = __DIR__ . '/uploads/profiles';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            header("Location: dashboard.php?profile_error=folder");
            exit();
        }

        // Remove any older avatar belonging to this user.
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $oldFile = $uploadDir . '/user_' . intval($user_id) . '.' . $ext;
            if (is_file($oldFile)) @unlink($oldFile);
        }

        $extension = $allowed[$mime];
        $destination = $uploadDir . '/user_' . intval($user_id) . '.' . $extension;
        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            header("Location: dashboard.php?profile_error=upload");
            exit();
        }
    }

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET email = ?, password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $email, $hashed, $user_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET email = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    }
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: dashboard.php?profile_saved=1");
    exit();
}

function handleDeleteAccount($conn, $user_id) {
    $entered_password = $_POST['delete_password'] ?? '';
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return "Could not verify your account. Please try again.";
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$user_data || !password_verify($entered_password, $user_data['password'])) {
        return "Incorrect password. Your account was not deleted.";
    }

    mysqli_begin_transaction($conn);
    try {
        // Delete only this user's expense records. Preserve the PG group and other members.
        $expenseStmt = mysqli_prepare($conn, "DELETE FROM expenses WHERE user_id = ?");
        if (!$expenseStmt) throw new Exception("Could not prepare expense cleanup.");
        mysqli_stmt_bind_param($expenseStmt, "i", $user_id);
        if (!mysqli_stmt_execute($expenseStmt)) throw new Exception("Could not remove the user's expenses.");
        mysqli_stmt_close($expenseStmt);

        $userStmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        if (!$userStmt) throw new Exception("Could not prepare account deletion.");
        mysqli_stmt_bind_param($userStmt, "i", $user_id);
        if (!mysqli_stmt_execute($userStmt)) throw new Exception("Could not delete the account.");
        mysqli_stmt_close($userStmt);

        mysqli_commit($conn);
        session_unset();
        session_destroy();
        header('Location: login.php?account_deleted=1');
        exit();
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return "Account could not be deleted. No changes were saved. Check whether related database records prevent deletion.";
    }
}

function renderExpenseTable($conn, $group, $members, $role) {
    if (!$group || !$members) {
        echo "<tr><td colspan='5'>No group or members found.</td></tr>";
        return;
    }
    
    mysqli_data_seek($members, 0);
    while ($member = mysqli_fetch_assoc($members)) {
        if ($member['role'] == 'Admin') continue;
        
        echo "<tr><td>" . htmlspecialchars($member['name']) . "</td><td>";
        
        $exp = null;
        if (isset($group['id']) && isset($member['id'])) {
            $expRes = mysqli_query($conn, "SELECT * FROM expenses WHERE group_id='" . intval($group['id']) . "' AND user_id='" . intval($member['id']) . "'");
            if ($expRes) $exp = mysqli_fetch_assoc($expRes);
        }
        
        if ($role == 'Admin' && $exp) {
            echo '<form method="post" style="display:inline-block;">
                <input type="hidden" name="expense_id" value="' . $exp['id'] . '">
                <select name="paid_pending_status" onchange="this.form.submit()" class="form-select form-select-sm status-select ' . ($exp['status'] == "Paid" ? 'status-paid' : 'status-pending') . '" style="width:auto;display:inline;">
                    <option value="Pending" ' . ($exp['status'] == "Pending" ? 'selected' : '') . '>Pending</option>
                    <option value="Paid" ' . ($exp['status'] == "Paid" ? 'selected' : '') . '>Paid</option>
                </select>
                <input type="hidden" name="mark_paid" value="1">
            </form>';
        } else {
            echo $exp && $exp['status'] == "Paid" ? "<span class='status-badge status-paid'><i class=\"fa-solid fa-circle-check\"></i> Paid</span>" : "<span class='status-badge status-pending'><i class=\"fa-solid fa-clock\"></i> Pending</span>";
        }
        
        echo "</td><td>" . ($exp ? htmlspecialchars($exp['amount']) : "--") . "</td>";
        echo "<td>" . ($exp ? htmlspecialchars($exp['description']) : "--") . "</td>";
        
        if ($role == 'Admin' && $exp) {
            echo "<td><button type='button' class='btn btn-sm btn-outline-primary edit-expense-btn' data-id='" . $exp['id'] . "' data-desc='" . htmlspecialchars($exp['description']) . "' data-amt='" . htmlspecialchars($exp['amount']) . "'>Edit</button></td>";
        }
        echo "</tr>";
    }
}

function renderMembersList($conn, $pg_code) {
    $membersQuery = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code' ORDER BY CASE WHEN role='Admin' THEN 0 ELSE 1 END, name ASC");
    while ($member = mysqli_fetch_assoc($membersQuery)) {
        $memberName = htmlspecialchars($member['name'] ?? 'Member', ENT_QUOTES, 'UTF-8');
        $initial = htmlspecialchars(strtoupper(substr(trim($member['name'] ?? 'M'), 0, 1)), ENT_QUOTES, 'UTF-8');
        $isAdmin = (($member['role'] ?? '') === 'Admin');
        echo "<li class='list-group-item member-row'>
                <span class='member-avatar " . ($isAdmin ? "member-avatar-admin" : "") . "'>" . $initial . "</span>
                <span class='member-row-copy'><strong>" . $memberName . "</strong><small>PG group member</small></span>
                <span class='member-role " . ($isAdmin ? "member-role-admin" : "member-role-user") . "'><i class='fa-solid " . ($isAdmin ? "fa-shield-halved" : "fa-user") . "'></i> " . ($isAdmin ? "Admin" : "User") . "</span>
              </li>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PG Expense Splitter</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root{--ink:#17213b;--muted:#77819a;--primary:#4f46e5;--primary-dark:#3730a3;--line:#e7eaf3;--surface:#fff;--page:#f5f7fc}
    *{box-sizing:border-box} body{margin:0;background:var(--page);color:var(--ink);font-family:"Segoe UI",Inter,system-ui,-apple-system,sans-serif;font-size:15px}
    .side-nav{width:250px;min-width:250px;background:#171b35;color:#fff;min-height:100vh;padding:25px 16px 18px;position:fixed;inset:0 auto 0 0;z-index:1020;box-shadow:8px 0 28px #11152a0a}
    .sidebar-brand{display:flex;align-items:center;gap:11px;color:#fff;text-decoration:none;font-size:20px;font-weight:800;letter-spacing:-.5px;padding:3px 10px 30px}.sidebar-brand:hover{color:#fff}
    .brand-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg,#818cf8,#4f46e5);color:white;font-size:18px;box-shadow:0 7px 18px #4f46e54a}
    .sidebar-brand small{display:block;font-size:11px;font-weight:500;letter-spacing:.3px;color:#aeb5d1;margin-top:2px}.sidebar-caption{font-size:10px;font-weight:800;letter-spacing:1.6px;color:#858eaf;padding:0 12px 10px}
    .side-nav .nav-link{display:flex;align-items:center;gap:13px;width:100%;color:#bfc5df;font-size:14px;font-weight:600;text-decoration:none;border:0;background:transparent;padding:12px 13px;margin:3px 0;border-radius:11px;transition:background .2s,color .2s;text-align:left}
    .side-nav .nav-link i{width:19px;text-align:center;font-size:15px}.side-nav .nav-link.active,.side-nav .nav-link:hover{background:linear-gradient(100deg,#4f46e5,#625cf0);color:#fff}
    .sidebar-spacer{flex:1;min-height:28px}.sidebar-user{display:flex;align-items:center;gap:10px;padding:15px 8px;border-top:1px solid #ffffff1a;margin-top:14px;min-width:0}
    .user-avatar{display:grid;place-items:center;width:36px;height:36px;flex:0 0 36px;border-radius:50%;background:#343a5d;color:#dce0ff}.sidebar-user-copy{min-width:0}
    .sidebar-user-copy strong,.sidebar-user-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.sidebar-user-copy strong{font-size:12px;color:#fff;max-width:155px}.sidebar-user-copy small{font-size:11px;color:#aeb5d1;margin-top:2px}
    .logout-link{margin-top:4px!important;color:#ffb4bc!important}.logout-link:hover{background:#9f30401f!important;color:#fff!important}
    .profile-stack{position:fixed;right:32px;top:18px;z-index:1030;display:flex;flex-direction:column;align-items:flex-end;gap:6px}
    .profile-btn{position:static;display:flex;align-items:center;gap:10px;border:1px solid #e5e8f2;background:rgba(255,255,255,.96);cursor:pointer;padding:6px 13px 6px 7px;border-radius:16px;box-shadow:0 8px 26px #25325a12;color:var(--ink);text-align:left;max-width:280px;backdrop-filter:blur(10px);transition:box-shadow .2s,border-color .2s,transform .2s}
    .profile-btn:hover{border-color:#c7c9ff;box-shadow:0 10px 28px #4f46e51a;transform:translateY(-1px)}
    .profile-btn img{width:42px;height:42px;object-fit:cover;border-radius:13px;border:2px solid #e2e5ff;transition:transform .2s}.profile-btn:hover img{transform:scale(1.03)}
    .profile-button-copy{display:flex;flex-direction:column;min-width:0;line-height:1.35}.profile-button-copy small{font-size:10px;color:var(--muted);font-weight:700}.profile-button-copy strong{font-size:12px;max-width:165px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .profile-role{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 11px;font-size:10px;font-weight:800;letter-spacing:.35px;margin-right:5px;border:1px solid}
    .profile-role-admin{color:#5143c5;background:#eeedff;border-color:#dcd9ff}.profile-role-user{color:#19744a;background:#e8f8ef;border-color:#c6eed7}
    .status-select{font-weight:800!important;border-radius:999px!important;padding:7px 30px 7px 12px!important;border-width:1px!important;cursor:pointer}
    .status-paid{background:#e7f8ee!important;color:#137a43!important;border-color:#a8e5bf!important}
    .status-pending{background:#fff0f0!important;color:#c52e43!important;border-color:#ffc4cb!important}
    .status-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:7px 11px;font-size:12px;font-weight:800;white-space:nowrap}
    .status-badge.status-paid{background:#e7f8ee!important;color:#137a43!important}
    .status-badge.status-pending{background:#fff0f0!important;color:#c52e43!important}
    .main-content{margin-left:250px;padding:42px clamp(20px,4vw,56px);max-width:1700px;min-height:100vh}
    .page-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:0 280px 18px 0}.page-heading h2{font-size:clamp(24px,2.5vw,32px);font-weight:800;letter-spacing:-.8px;margin:5px 0 8px;color:#1b2340}
    .page-heading p,.section-subtitle{margin:0;color:var(--muted);font-size:14px;line-height:1.6}.heading-kicker{font-size:10px;font-weight:800;letter-spacing:1.7px;color:var(--primary)}
    .card{background:var(--surface);border:1px solid #e9ecf4;border-radius:18px;box-shadow:0 8px 28px #24345b08;margin-bottom:24px;overflow:hidden}.card h4,.card h5{color:#202944;font-weight:750}
    .section-title{font-size:19px;font-weight:800!important;letter-spacing:-.3px}.section-title i{color:var(--primary);margin-right:8px}
    .admin-label{font-size:11px;font-weight:700;padding:5px 9px;background:#e8fbf0;color:#16834b;border-radius:999px;margin-left:10px}
    .expense-card-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:8px 2px 15px}
    .expense-table{border-collapse:separate;border-spacing:0;font-size:13px}.expense-table thead th{background:#f5f6fc;color:#737d97;font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:800;padding:15px 16px;border-bottom:1px solid var(--line);white-space:nowrap}
    .expense-table tbody td{padding:16px;border-bottom:1px solid #edf0f6;color:#3f4962;font-size:13px;font-weight:500;background:#fff}.expense-table tbody tr{transition:background .18s}.expense-table tbody tr:hover td{background:#fafaff}.expense-table tbody tr:last-child td{border-bottom:0}.expense-table tbody td:first-child{font-weight:750;color:#202944}.table-responsive{border:1px solid var(--line);border-radius:15px;overflow-x:auto;background:#fff}
    .group-hero{position:relative;padding:26px!important;background:linear-gradient(125deg,#fff 0%,#f5f5ff 100%);border:1px solid #e6e7fb;border-radius:20px;overflow:hidden}
    .group-hero:after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;right:-60px;top:-85px;background:linear-gradient(135deg,#dcdcff80,#b8b5ff20);pointer-events:none}
    .group-hero-top{position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap}
    .group-icon{width:54px;height:54px;border-radius:17px;display:grid;place-items:center;background:linear-gradient(135deg,#7774f7,#4f46e5);color:white;font-size:21px;box-shadow:0 9px 22px #4f46e52c;flex:0 0 54px}
    .group-code-pill{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid #e4e6f5;border-radius:11px;padding:9px 12px;color:#4f46e5;font-size:12px;font-weight:800;letter-spacing:.4px}
    .group-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:23px;position:relative;z-index:1}
    .group-stat{background:#ffffffd9;border:1px solid #e8eaf6;border-radius:14px;padding:14px 16px}.group-stat small{display:block;color:var(--muted);font-size:11px;font-weight:700;margin-bottom:5px}.group-stat strong{display:block;color:#202944;font-size:16px;font-weight:800;overflow-wrap:anywhere}
    .members-panel{padding:22px!important}.member-row{display:flex;align-items:center;gap:12px;padding:13px 15px!important;border-bottom:1px solid #eef0f7!important}.member-row:last-child{border-bottom:0!important}.member-avatar{width:40px;height:40px;border-radius:13px;background:#eef0ff;color:#4f46e5;display:grid;place-items:center;font-size:14px;font-weight:850;flex:0 0 40px}.member-avatar-admin{background:#e9e7ff;color:#5143c5}.member-row-copy{display:flex;flex-direction:column;min-width:0;flex:1}.member-row-copy strong{font-size:13px;color:#202944;font-weight:750;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.member-row-copy small{font-size:11px;color:var(--muted);margin-top:3px}.member-role{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:6px 9px;font-size:10px;font-weight:800;white-space:nowrap}.member-role-admin{background:#eeedff;color:#5143c5}.member-role-user{background:#edf8f1;color:#24784e}
    .group-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}
    .form-control,.form-select{border:1px solid #dfe4ef;border-radius:10px;padding:11px 13px;color:#28324d;box-shadow:none!important}.form-control:focus,.form-select:focus{border-color:#818cf8;box-shadow:0 0 0 3px #6366f11a!important}
    .form-label{font-size:13px;color:#4a5470}.btn{border-radius:10px;font-weight:650;padding:9px 15px;transition:transform .15s,box-shadow .15s}
    .btn-primary{background:var(--primary);border-color:var(--primary)}.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark);transform:translateY(-1px);box-shadow:0 5px 14px #4f46e52b}
    .btn-danger{background:#e5485d;border-color:#e5485d}.list-group{border-radius:12px;overflow:hidden}.list-group-item{padding:14px 16px;border-color:#edf0f6;color:#35405b}
    .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 22px 70px #121a3533}.modal-header,.modal-footer{padding:18px 22px}.modal-body{padding:22px}
    .confirm-modal{padding-top:24px}.confirm-icon{display:grid;place-items:center;margin:0 auto 12px;width:54px;height:54px;border-radius:17px;background:#eef0ff;color:#4f46e5;font-size:20px}
    .profile-preview{width:92px;height:92px;object-fit:cover;border-radius:50%;border:4px solid #eef0ff;box-shadow:0 5px 20px #25325a12}.confirm-modal .modal-title{font-weight:800;color:#202944}.dashboard-alert{z-index:1090;max-width:min(92vw,560px);width:max-content;box-shadow:0 12px 32px #18213c22;border:0;border-radius:12px}
    .text-success,.text-danger{font-weight:700}
    @media(max-width:900px){.side-nav{width:210px;min-width:210px}.main-content{margin-left:210px;padding:32px 22px}.page-heading{align-items:flex-start;flex-direction:column}}
    @media(max-width:640px){.side-nav{width:68px;min-width:68px;padding:18px 8px}.sidebar-brand{padding:0 4px 25px;justify-content:center}.sidebar-brand>span:last-child,.sidebar-caption,.side-nav .nav-link span,.sidebar-user-copy{display:none}.brand-icon{width:42px;height:42px;flex:0 0 42px}.side-nav .nav-link{justify-content:center;padding:13px 8px;gap:0}.side-nav .nav-link i{font-size:17px}.sidebar-user{justify-content:center;padding:13px 0}.user-avatar{width:35px;height:35px;flex-basis:35px}.main-content{margin-left:68px;padding:105px 14px 28px}.page-heading{padding-right:0}.page-heading h2{font-size:23px}.page-heading p{font-size:13px}.profile-stack{right:12px;top:12px;gap:4px}.profile-btn{padding:4px 8px 4px 5px;gap:7px;border-radius:13px}.profile-btn img{width:33px;height:33px;border-radius:10px}.profile-button-copy small{display:none}.profile-button-copy strong{max-width:90px;font-size:11px}.profile-role{padding:4px 8px;font-size:9px}.card{border-radius:14px}.card.p-3,.card.p-4{padding:16px!important}.expense-table thead th,.expense-table tbody td{padding:12px 11px}.modal-dialog{margin:12px}.group-hero{padding:18px!important}.group-stats{gap:9px}.group-stat{padding:12px}.group-stat strong{font-size:14px}.members-panel{padding:16px!important}.member-row{gap:9px;padding:12px 10px!important}.member-avatar{width:35px;height:35px;flex-basis:35px;border-radius:11px}.member-role{padding:5px 7px;font-size:9px}}
  </style>
</head>
<body>
  <?php if (isset($_GET['profile_saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show dashboard-alert position-fixed top-0 start-50 translate-middle-x mt-3" role="alert">
      Profile updated successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php elseif (isset($_GET['profile_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show dashboard-alert position-fixed top-0 start-50 translate-middle-x mt-3" role="alert">
      Profile update failed. Check the image type (JPG/PNG/WEBP), size (max 3 MB), and that the uploads folder is writable.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if($delete_error): ?>
  <div class="alert alert-danger alert-dismissible fade show dashboard-alert position-fixed top-0 start-50 translate-middle-x mt-3" role="alert">
    <?php echo htmlspecialchars($delete_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <aside class="side-nav d-flex flex-column px-3">
    <a href="dashboard.php" class="sidebar-brand"><span class="brand-icon"><i class="fa-solid fa-wallet"></i></span><span>PG Split<small>Expense Manager</small></span></a>
    <div class="sidebar-caption">WORKSPACE</div>
    <a href="#welcome" class="nav-link active" data-page="welcome"><i class="fa-solid fa-house"></i><span>Overview</span></a>
    <a href="#group" class="nav-link" data-page="group"><i class="fa-solid fa-people-group"></i><span>My PG Group</span></a>
    <div class="sidebar-spacer"></div>
    <div class="sidebar-user"><span class="user-avatar"><i class="fa-solid fa-user"></i></span><span class="sidebar-user-copy"><strong><?php echo htmlspecialchars($current_user['name'] ?? 'Member'); ?></strong><small>PG Split account</small></span></div>
    <button type="button" class="nav-link logout-link" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Log out</span></button>
  </aside>
  
  <div class="profile-stack">
    <button class="profile-btn" id="profileBtn" type="button" aria-label="Open profile settings" title="Profile settings">
      <img src="<?php echo htmlspecialchars($profile_picture ?: 'https://cdn-icons-png.flaticon.com/512/149/149071.png'); ?>" alt="Profile picture">
      <span class="profile-button-copy"><small>Hello,</small><strong><?php echo htmlspecialchars($current_user['name'] ?? 'User'); ?></strong></span>
      <i class="fa-solid fa-chevron-down text-muted small ms-1"></i>
    </button>
    <span class="profile-role <?php echo strtolower($role) === 'admin' ? 'profile-role-admin' : 'profile-role-user'; ?>"><i class="fa-solid <?php echo strtolower($role) === 'admin' ? 'fa-shield-halved' : 'fa-user'; ?>"></i> <?php echo htmlspecialchars($role); ?> ACCOUNT</span>
  </div>
  
  <div class="main-content">
    <!-- Welcome Page -->
    <div id="welcomePage">
      <div class="page-heading"><div><span class="heading-kicker">YOUR DASHBOARD</span><h2>Hello, <?php echo htmlspecialchars($current_user['name'] ?? 'there'); ?> 👋</h2><p>Keep track of shared expenses and stay in sync with your housemates.</p></div></div>
      <div class="card p-3 mt-3">
        <div class="expense-card-heading"><div><h4 class="section-title mb-1"><i class="fa-solid fa-receipt"></i> Expense status</h4><p class="section-subtitle">A quick look at what everyone owes.</p></div><span class="group-code-pill"><i class="fa-solid fa-key"></i> <?php echo htmlspecialchars($group['pg_code'] ?? 'No group'); ?></span></div>
        <div class="table-responsive mt-3">
        <table class="table expense-table align-middle">
          <thead>
            <tr>
              <th>Name</th><th>Status</th><th>Amount</th><th>Description</th>
              <?php if($role == 'Admin') echo "<th>Action</th>"; ?>
            </tr>
          </thead>
          <tbody><?php renderExpenseTable($conn, $group, $members, $role); ?></tbody>
        </table>
        </div>
      </div>
      
      <!-- Split Calculator -->
      <div class="card p-4">
        <h4 class="section-title mb-1"><i class="fa-solid fa-calculator"></i> Expense splitter calculator</h4><p class="section-subtitle mb-4">Divide a shared cost fairly among your PG members.</p>
        <?php if($role == 'Admin'){ ?>
        <form method="post" id="splitForm">
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <input type="text" name="split_description" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Amount</label>
            <input type="number" step="0.01" name="split_amount" id="splitAmt" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Members</label>
            <div class="border rounded p-3" style="background: #f8f9fa;">
              <?php
              $splitMembers = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code' AND role!='Admin'");
              $count = mysqli_num_rows($splitMembers);
              while($m = mysqli_fetch_assoc($splitMembers)){
                echo "<div class='d-flex justify-content-between p-2 mb-2 bg-white rounded'>
                  <span>".htmlspecialchars($m['name'])."</span>
                  <span class='text-muted split-display'>₹0.00</span>
                </div>";
              }
              ?>
            </div>
            <small class="text-muted">Total Members: <span id="memCount"><?php echo $count; ?></span></small>
          </div>
          <button type="submit" name="split_expense" class="btn btn-primary btn-lg w-100">Split Amount</button>
        </form>
        <?php } else { echo "<div class='alert alert-info'>Only Admin can split expenses.</div>"; } ?>
      </div>
    </div>
    
    <!-- Group Page -->
    <div id="groupPage" style="display:none;">
      <div class="page-heading"><div><span class="heading-kicker">YOUR SPACE</span><h2>My PG group</h2><p>Your housemates, group information, and shared living space in one place.</p></div></div>
      <div class="card group-hero">
        <div class="group-hero-top">
          <div class="d-flex align-items-center gap-3">
            <span class="group-icon"><i class="fa-solid fa-house-chimney-user"></i></span>
            <div><span class="heading-kicker">YOUR GROUP</span><h3 class="mb-1 mt-1" style="font-size:24px;font-weight:850;letter-spacing:-.5px"><?php echo htmlspecialchars($group['name'] ?? 'My PG Group'); ?></h3><p class="section-subtitle mb-0"><?php echo htmlspecialchars($group['description'] ?? 'Your shared space for managing PG expenses.'); ?></p></div>
          </div>
          <span class="group-code-pill"><i class="fa-solid fa-key"></i> <?php echo htmlspecialchars($group['pg_code'] ?? 'No group'); ?></span>
        </div>
        <?php
          $memberCountRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE pg_code='" . mysqli_real_escape_string($conn, $pg_code) . "'");
          $memberCount = $memberCountRes ? (int)(mysqli_fetch_assoc($memberCountRes)['total'] ?? 0) : 0;
          $groupAdminLabel = $admin_name !== '' ? $admin_name : 'Not assigned';
        ?>
        <div class="group-stats">
          <div class="group-stat"><small><i class="fa-solid fa-users me-1"></i> GROUP MEMBERS</small><strong><?php echo $memberCount; ?> people</strong></div>
          <div class="group-stat"><small><i class="fa-solid fa-shield-halved me-1"></i> GROUP ADMIN</small><strong><?php echo htmlspecialchars($groupAdminLabel); ?></strong></div>
        </div>
      </div>
      <div class="card members-panel">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3"><div><h4 class="section-title mb-1"><i class="fa-solid fa-people-group"></i> Group members</h4><p class="section-subtitle">Everyone sharing expenses in your PG.</p></div><span class="group-code-pill"><i class="fa-solid fa-user-group"></i> <?php echo $memberCount; ?> total</span></div>
        <ul class="list-group mb-0"><?php renderMembersList($conn, $pg_code); ?></ul>
        <?php if($role == 'Admin'){ ?>
        <div class="group-actions">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal"><i class="fa-solid fa-user-plus me-2"></i>Add member</button>
          <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="fa-solid fa-receipt me-2"></i>Add expense</button>
          <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#removeMemberModal"><i class="fa-solid fa-user-minus me-2"></i>Remove member</button>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
  
  <!-- Add Expense Modal -->
  <div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control mb-2" name="expense_description" placeholder="Description" required>
          <input type="number" class="form-control mb-2" step="0.01" name="expense_amount" placeholder="Amount" required>
          <label>Select Member</label>
          <select name="expense_user_id" class="form-control" required>
            <?php
            $mList = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code'");
            while($m = mysqli_fetch_assoc($mList)) {
              echo "<option value='".$m['id']."'>".htmlspecialchars($m['name'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="add_expense" class="btn btn-primary">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Expense Modal -->
  <div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="expense_id" id="editExpenseId">
          <input type="text" class="form-control mb-2" name="edit_expense_description" id="editExpenseDesc" required>
          <input type="number" class="form-control mb-2" step="0.01" name="edit_expense_amount" id="editExpenseAmt" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="edit_expense" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Member Modal -->
  <div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="new_member_name" placeholder="Name" required class="form-control mb-2">
          <input type="email" name="new_member_email" placeholder="Email" required class="form-control mb-2">
          <input type="text" name="new_member_phone" placeholder="Phone" required class="form-control mb-2">
          <input type="password" name="new_member_password" placeholder="Password" required class="form-control mb-2">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="add_member" class="btn btn-success">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Remove Member Modal -->
  <div class="modal fade" id="removeMemberModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Remove Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label>Select Member to Remove</label>
          <select name="remove_member_id" class="form-control" required>
            <?php
            $mList2 = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code' AND role!='Admin'");
            while($m = mysqli_fetch_assoc($mList2)) {
              echo "<option value='".$m['id']."'>".htmlspecialchars($m['name'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="remove_member" class="btn btn-danger">Remove</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Profile Modal -->
  <div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title">Your Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="text-center mb-4">
              <img src="<?php echo htmlspecialchars($profile_picture ?: 'https://cdn-icons-png.flaticon.com/512/149/149071.png'); ?>" alt="Current profile picture" class="profile-preview mb-2" id="profilePreview">
              <label for="profilePictureInput" class="btn btn-outline-primary d-block mx-auto" style="width:max-content"><i class="fa-solid fa-camera me-2"></i>Choose profile picture</label>
              <input type="file" name="profile_picture" id="profilePictureInput" class="form-control mt-2" accept="image/jpeg,image/png,image/webp">
              <small class="text-muted d-block mt-1">JPG, PNG or WEBP · Maximum 3 MB</small>
            </div>
            <label class="form-label">Email</label>
            <input type="email" name="profile_email" id="profileEmail" class="form-control mb-3" required>
            <label class="form-label">New Password (leave blank to keep current)</label>
            <input type="password" name="profile_password" class="form-control mb-3">
            <hr class="my-4">
            <div class="alert alert-danger">
              <h6 class="alert-heading">Delete Account</h6>
              <p class="mb-2">This action cannot be undone.</p>
            </div>
            <button type="button" class="btn btn-danger w-100" id="deleteAccountBtn">Delete My Account</button>
          </div>
          <div class="modal-footer">
            <button type="submit" name="update_profile" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Logout confirmation modal -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content confirm-modal">
        <div class="confirm-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
        <div class="modal-body text-center px-4 pt-2">
          <h5 class="modal-title mb-2" id="logoutModalLabel">Log out of PG Split?</h5>
          <p class="text-muted mb-0">Are you sure you want to log out? You will need to sign in again to access your group.</p>
        </div>
        <div class="modal-footer border-0 justify-content-center gap-2 px-4 pb-4">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <form method="post" class="m-0">
            <button type="submit" name="confirm_logout" value="1" class="btn btn-primary">Yes, log out</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Account Modal -->
  <div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Confirm Account Deletion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning"><strong>Warning!</strong> This cannot be undone.</div>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($current_user['email']); ?></p>
          <label class="form-label">Enter your password to confirm:</label>
          <input type="password" name="delete_password" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="delete_account" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Page navigation
    document.querySelectorAll('.side-nav .nav-link[data-page]').forEach(link => {
      link.onclick = e => {
        e.preventDefault();
        document.querySelectorAll('[id$="Page"]').forEach(p => p.style.display = 'none');
        document.getElementById(link.dataset.page + 'Page').style.display = 'block';
        document.querySelectorAll('.side-nav .nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
      };
    });
    
    // Profile modal
    const profileModal = new bootstrap.Modal(document.getElementById('profileModal'));
    document.getElementById('profileBtn').onclick = () => {
      document.getElementById('profileEmail').value = <?php echo json_encode($current_user['email'] ?? ''); ?>;
      profileModal.show();
    };
    document.getElementById('profilePictureInput')?.addEventListener('change', function () {
      const file = this.files && this.files[0];
      if (!file) return;
      if (file.size > 3 * 1024 * 1024) {
        alert('Please choose an image smaller than 3 MB.');
        this.value = '';
        return;
      }
      document.getElementById('profilePreview').src = URL.createObjectURL(file);
    });
    
    // Delete account
    document.getElementById('deleteAccountBtn')?.addEventListener('click', () => {
      profileModal.hide();
      new bootstrap.Modal(document.getElementById('deleteAccountModal')).show();
    });
    
    // Edit expense
    document.querySelectorAll('.edit-expense-btn').forEach(btn => {
      btn.onclick = function() {
        document.getElementById('editExpenseId').value = this.dataset.id;
        document.getElementById('editExpenseDesc').value = this.dataset.desc;
        document.getElementById('editExpenseAmt').value = this.dataset.amt;
        new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
      };
    });
    
    // Split calculator
    document.getElementById('splitAmt')?.addEventListener('input', function() {
      const total = parseFloat(this.value) || 0;
      const count = parseInt(document.getElementById('memCount').innerText) || 1;
      const perPerson = (total / count).toFixed(2);
      document.querySelectorAll('.split-display').forEach(d => d.innerText = '₹' + perPerson);
    });
  </script>
</body>
</html>