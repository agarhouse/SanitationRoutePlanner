<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garbage Route Planner</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Garbage Route Planner</a>
  </div>
</nav>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7 text-center">
            <img src="https://api.dicebear.com/7.x/identicon/svg?seed=garbage" alt="Logo" width="80" class="mb-4">
            <h1 class="mb-3">Welcome to Garbage Route Planner</h1>
            <p class="lead mb-4">Plan, assign, and manage garbage collection routes with ease. Please login or register to get started.</p>
            <a href="auth/login.php" class="btn btn-primary btn-lg m-2">Login</a>
            <a href="auth/register.php" class="btn btn-outline-secondary btn-lg m-2">Register</a>
        </div>
    </div>
</div>
</body>
</html> 