<?php
session_start();

// If already logged in redirect to their dashboard
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["user_role"] == "admin") {
        header("Location: pages/admin-dashboard.php");
    } elseif ($_SESSION["user_role"] == "donor") {
        header("Location: pages/donor-dashboard.php");
    } elseif ($_SESSION["user_role"] == "recipient") {
        header("Location: pages/recipient-dashboard.php");
    } elseif ($_SESSION["user_role"] == "rider") {
        header("Location: pages/rider-dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Redistribution & Hunger Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="text-center">
        <h1 class="mb-3">Food Redistribution & Hunger Relief System</h1>
        <p class="text-muted mb-4">Connecting food donors with verified NGOs and community shelters.</p>
        <a href="pages/login.php" class="btn btn-success btn-lg me-3">Login</a>
        <a href="pages/register.php" class="btn btn-outline-success btn-lg">Register</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>