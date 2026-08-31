<?php
require('db.php');
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Authentication check
if(!isset($_SESSION['user_id'])) header('Location: login.html');

// Session variables
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$pg_code = $_SESSION['pg_code'];

// Fetch data
$group = fetchGroup($conn, $pg_code);
$members = fetchMembers($conn, $pg_code);
$current_user = fetchUser($conn, $user_id);
$admin_name = fetchAdminName($conn, $pg_code);

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
    $email = mysqli_real_escape_string($conn, $_POST['profile_email']);
    $password = $_POST['profile_password'];
    
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET email='$email', password='$hashed' WHERE id='$user_id'");
    } else {
        mysqli_query($conn, "UPDATE users SET email='$email' WHERE id='$user_id'");
    }
    header("Location: dashboard.php");
    exit();
}

function handleDeleteAccount($conn, $user_id) {
    $entered_password = $_POST['delete_password'];
    $user_query = mysqli_query($conn, "SELECT password FROM users WHERE id='$user_id' LIMIT 1");
    
    if ($user_query && $user_data = mysqli_fetch_assoc($user_query)) {
        if (password_verify($entered_password, $user_data['password'])) {
            mysqli_query($conn, "DELETE FROM users WHERE id='$user_id'");
            session_destroy();
            header('Location: login.php');
            exit();
        } else {
            return "Incorrect password. Account not deleted.";
        }
    }
    return null;
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
                <select name="paid_pending_status" onchange="this.form.submit()" class="form-select form-select-sm" style="width:auto;display:inline;">
                    <option value="Pending" ' . ($exp['status'] == "Pending" ? 'selected' : '') . '>Pending</option>
                    <option value="Paid" ' . ($exp['status'] == "Paid" ? 'selected' : '') . '>Paid</option>
                </select>
                <input type="hidden" name="mark_paid" value="1">
            </form>';
        } else {
            echo $exp && $exp['status'] == "Paid" ? "<span class='text-success'>Paid</span>" : "<span class='text-danger'>Pending</span>";
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
    $membersQuery = mysqli_query($conn, "SELECT * FROM users WHERE pg_code='$pg_code'");
    while ($member = mysqli_fetch_assoc($membersQuery)) {
        echo "<li class='list-group-item d-flex justify-content-between align-items-center'>" 
            . htmlspecialchars($member['name']) 
            . ($member['role'] == 'Admin' ? "<span class='admin-label'>Admin</span>" : "") 
            . "</li>";
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
  <style>
    body { background: #f5faff; font-family: "Segoe UI", sans-serif; }
    .side-nav { min-width: 220px; background: #0d6efd; color: #fff; min-height: 100vh; padding-top: 2rem; position: fixed; left: 0; top: 0; bottom: 0; }
    .side-nav .nav-link { color: #fff; font-size: 1.1rem; margin-bottom: 1rem; transition: background 0.2s; }
    .side-nav .nav-link.active, .side-nav .nav-link:hover { background: #2563eb; border-radius: 0.25rem; }
    .profile-btn { position: absolute; right: 2rem; top: 1.2rem; border: none; background: transparent; cursor: pointer; }
    .profile-btn img { width: 38px; height: 38px; border-radius: 50%; border: 2px solid #0d6efd; transition: transform 0.2s; }
    .profile-btn img:hover { transform: scale(1.08); }
    .main-content { margin-left: 220px; padding: 2rem; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px #0001; margin-bottom: 2rem; }
    .admin-label { font-size: 0.9rem; padding: 2px 8px; background: #eaffea; color: #198754; border-radius: 6px; margin-left: 10px; }
    .admin-name-highlight { color: #0d6efd; font-weight: bold; }
  </style>
</head>
<body>
  <?php if($delete_error): ?>
  <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
    <?php echo htmlspecialchars($delete_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="side-nav d-flex flex-column align-items-start px-3">
    <a href="#" class="nav-link active" data-page="welcome">Welcome</a>
    <a href="#" class="nav-link" data-page="group">Group</a>
    <a href="dashboard.php?logout=true" class="nav-link">Logout</a>

  </div>
  
  <button class="profile-btn" id="profileBtn">
    <img src="https://e7.pngegg.com/pngimages/343/677/png-clipart-computer-icons-user-profile-login-my-account-icon-heroes-black-thumbnail.png" alt="Profile">
  </button>
  
  <div class="main-content">
    <!-- Welcome Page -->
    <div id="welcomePage">
      <h2>Welcome to Your PG Group</h2>
      <div class="card p-3 mt-3">
        <p><b>Group Code:</b> <span><?php echo htmlspecialchars($group['pg_code'] ?? 'No group'); ?></span>
          &nbsp;&nbsp;<span class="admin-name-highlight"><?php echo htmlspecialchars($admin_name); ?> (Admin)</span>
        </p>
        <h5 class="mt-4 mb-2">Expense Status</h5>
        <table class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Name</th><th>Status</th><th>Amount</th><th>Description</th>
              <?php if($role == 'Admin') echo "<th>Action</th>"; ?>
            </tr>
          </thead>
          <tbody><?php renderExpenseTable($conn, $group, $members, $role); ?></tbody>
        </table>
      </div>
      
      <!-- Split Calculator -->
      <div class="card p-4">
        <h4 class="mb-3">Expense Splitter Calculator</h4>
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
      <h2>Group Details</h2>
      <div class="card p-3">
        <div class="d-flex justify-content-between mb-2">
          <div>
            <h4><?php echo htmlspecialchars($group['name'] ?? ''); ?></h4>
            <?php if($role == 'Admin') echo '<span class="admin-label">Admin</span>'; ?>
          </div>
        </div>
        <p><?php echo htmlspecialchars($group['description'] ?? ''); ?></p>
        <h5 class="mt-4 mb-3">Members</h5>
        <ul class="list-group mb-3"><?php renderMembersList($conn, $pg_code); ?></ul>
        <?php if($role == 'Admin'){ ?>
        <div>
          <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addExpenseModal">Add Expense</button>
          <button class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#removeMemberModal">Remove Member</button>
          <button class="btn btn-success mb-2" data-bs-toggle="modal" data-bs-target="#addMemberModal">Add Member</button>
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
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
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
      document.getElementById('profileEmail').value = '<?php echo htmlspecialchars($current_user['email']); ?>';
      profileModal.show();
    };
    
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