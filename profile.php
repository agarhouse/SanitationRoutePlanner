<?php
session_start();
require_once 'auth/user.php';
require_login();
$db = get_db();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$avatar_path = "profile_pics/{$user_id}.jpg";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Password change
    if (!empty($_POST['password']) || !empty($_POST['confirm'])) {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $user_id]);
            $success = 'Password updated.';
        }
    }
    // Avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['avatar']['tmp_name'];
        $type = mime_content_type($tmp);
        if (in_array($type, ['image/jpeg', 'image/png'])) {
            $ext = $type === 'image/png' ? 'png' : 'jpg';
            $dest = "profile_pics/{$user_id}.{$ext}";
            move_uploaded_file($tmp, $dest);
            // Remove old avatar if type changed
            if ($ext === 'jpg' && file_exists("profile_pics/{$user_id}.png")) unlink("profile_pics/{$user_id}.png");
            if ($ext === 'png' && file_exists("profile_pics/{$user_id}.jpg")) unlink("profile_pics/{$user_id}.jpg");
            $avatar_path = $dest;
            $success = ($success ? $success . ' ' : '') . 'Avatar updated.';
        } else {
            $error = 'Only JPG or PNG images allowed.';
        }
    }
}
if (!file_exists($avatar_path)) {
    $avatar_path = 'https://api.dicebear.com/7.x/identicon/svg?seed=' . urlencode($username);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Garbage Route Planner</a>
    <div class="ms-auto">
      <a href="auth/logout.php" class="btn btn-outline-light">Logout</a>
    </div>
  </div>
</nav>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">My Profile</div>
                <div class="card-body text-center">
                    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;">
                    <h4><?php echo htmlspecialchars($username); ?></h4>
                    <form method="POST" enctype="multipart/form-data" class="mt-4 text-start">
                        <div class="mb-3">
                            <label class="form-label">Change Password</label>
                            <input type="password" name="password" class="form-control" placeholder="New password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm" class="form-control" placeholder="Confirm new password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Change Avatar (JPG/PNG)</label>
                            <input type="file" name="avatar" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 