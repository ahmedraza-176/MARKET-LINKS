<?php

session_start();

require_once "../config/connection.php";

// Customer login check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Customer role check
if ($_SESSION['role'] !== 'Customer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$notification_id = (int) ($_GET['id'] ?? 0);

if ($notification_id > 0) {

    $sql = "
        UPDATE Notifications
        SET is_read = 1
        WHERE notification_id = ?
        AND user_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $notification_id,
        $user_id
    );

    mysqli_stmt_execute($stmt);
}

header("Location: notifications.php");
exit();
?>