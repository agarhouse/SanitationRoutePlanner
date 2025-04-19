<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['routeFile']) && $_FILES['routeFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['routeFile']['tmp_name'];
        $fileName = $_FILES['routeFile']['name'];
        $fileSize = $_FILES['routeFile']['size'];
        $fileType = $_FILES['routeFile']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if ($fileExtension === 'json') {
            $newFileName = uniqid('route_', true) . '.json';
            $uploadFileDir = 'uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                header("Location: workspace.php?file=" . urlencode($newFileName));
                exit;
            } else {
                $error = 'Error moving the uploaded file.';
            }
        } else {
            $error = 'Only JSON files are allowed.';
        }
    } else {
        $error = 'No file uploaded or upload error.';
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
<body>
<div class="container mt-5">
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <a href="index.php" class="btn btn-primary">Back to Upload</a>
</div>
</body>
</html> 