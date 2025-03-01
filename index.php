<?php
require_once "init.php";

// Redirect to login page if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// If logged in, redirect based on user role
$redirect_path = match($_SESSION["role"]) {
    "admin" => "admin/dashboard.php",
    "staff" => "admin/dashboard.php",
    "customer" => "customer/dashboard.php",
    default => "login.php"
};
header("Location: $redirect_path");
exit();
