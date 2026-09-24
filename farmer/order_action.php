<?php

session_start();

require_once "../config/connection.php";


// =========================================
// FARMER LOGIN CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php");
    exit();
}


$farmer_id = (int) $_SESSION['user_id'];

$order_id = (int) ($_GET['order_id'] ?? 0);

$action = $_GET['action'] ?? '';


// =========================================
// VALIDATE INPUT
// =========================================

if ($order_id <= 0) {

    header("Location: orders.php?error=invalid");
    exit();

}


$allowed_actions = [
    'accept',
    'decline',
    'ready',
    'complete'
];


if (!in_array($action, $allowed_actions, true)) {

    header("Location: orders.php?error=action");
    exit();

}


// =========================================
// GET ORDER
// =========================================

$query = "
    SELECT
        o.order_id,
        o.product_id,
        o.quantity,
        o.status,

        p.product_name,
        p.farmer_id

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE o.order_id = ?
    AND p.farmer_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $order_id,
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) !== 1) {

    header("Location: orders.php?error=notfound");
    exit();

}


$order = mysqli_fetch_assoc($result);

$current_status = $order['status'];

$product_id = (int) $order['product_id'];

$quantity = (int) $order['quantity'];


// =========================================
// ACCEPT ORDER
// =========================================

if ($action === 'accept') {

    if ($current_status !== 'Placed') {

        header("Location: orders.php?error=status");
        exit();

    }


    $update = "
        UPDATE Orders

        SET status = 'Accepted'

        WHERE order_id = ?
        AND status = 'Placed'
    ";


    $stmt = mysqli_prepare(
        $conn,
        $update
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $order_id
    );

    mysqli_stmt_execute($stmt);


    header("Location: orders.php?success=accepted");
    exit();
}


// =========================================
// DECLINE ORDER
// =========================================

if ($action === 'decline') {


    // Only Placed orders can be declined

    if ($current_status !== 'Placed') {

        header("Location: orders.php?error=status");
        exit();

    }


    // Start transaction

    mysqli_begin_transaction($conn);


    try {


        // -------------------------------------
        // Restore stock
        // -------------------------------------

        $stock_query = "
            UPDATE Products

            SET
                stock = stock + ?,
                status = 'Available'

            WHERE product_id = ?
            AND farmer_id = ?
        ";


        $stmt = mysqli_prepare(
            $conn,
            $stock_query
        );


        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $quantity,
            $product_id,
            $farmer_id
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                "Unable to restore stock."
            );

        }


        // -------------------------------------
        // Change order status
        // -------------------------------------

        $order_query = "
            UPDATE Orders

            SET status = 'Declined'

            WHERE order_id = ?
            AND status = 'Placed'
        ";


        $stmt = mysqli_prepare(
            $conn,
            $order_query
        );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $order_id
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                "Unable to decline order."
            );

        }


        // -------------------------------------
        // Commit
        // -------------------------------------

        mysqli_commit($conn);


        header(
            "Location: orders.php?success=declined"
        );

        exit();


    } catch (Exception $e) {


        mysqli_rollback($conn);


        header(
            "Location: orders.php?error=decline"
        );

        exit();

    }

}


// =========================================
// MARK READY
// =========================================

if ($action === 'ready') {

    if ($current_status !== 'Accepted') {

        header("Location: orders.php?error=status");
        exit();

    }


    $update = "
        UPDATE Orders

        SET status = 'Ready'

        WHERE order_id = ?
        AND status = 'Accepted'
    ";


    $stmt = mysqli_prepare(
        $conn,
        $update
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $order_id
    );


    mysqli_stmt_execute($stmt);


    header(
        "Location: orders.php?success=ready"
    );

    exit();
}


// =========================================
// COMPLETE ORDER
// =========================================

if ($action === 'complete') {

    if ($current_status !== 'Ready') {

        header("Location: orders.php?error=status");
        exit();

    }


    $update = "
        UPDATE Orders

        SET status = 'Completed'

        WHERE order_id = ?
        AND status = 'Ready'
    ";


    $stmt = mysqli_prepare(
        $conn,
        $update
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $order_id
    );


    mysqli_stmt_execute($stmt);


    header(
        "Location: orders.php?success=completed"
    );

    exit();
}

?>