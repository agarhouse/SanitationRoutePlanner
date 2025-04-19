<?php
session_start();
require_once 'auth/user.php';
require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = $_POST['day'] ?? '';
    if (!in_array($day, ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"])) {
        $error = 'Invalid day selected.';
    } elseif (!isset($_FILES['routeFile']) || $_FILES['routeFile']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No file uploaded or upload error.';
    } else {
        $fileTmpPath = $_FILES['routeFile']['tmp_name'];
        $fileName = $_FILES['routeFile']['name'];
        $fileNameCmps = explode('.', $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        if ($fileExtension !== 'json') {
            $error = 'Only JSON files are allowed.';
        } else {
            $newFileName = uniqid('route_', true) . '.json';
            $uploadFileDir = 'uploads/';
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                header('Location: workspace.php?day=' . urlencode($day) . '&file=' . urlencode($newFileName));
                exit;
            } else {
                $error = 'Error moving the uploaded file.';
            }
        }
    }
} else {
    $error = 'Invalid request.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Error</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <a href="workspace.php" class="btn btn-primary">Back to Workspace</a>
</div>
</body>
</html> 