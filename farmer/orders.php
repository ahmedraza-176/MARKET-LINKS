<?php

session_start();

require_once "../config/connection.php";


// =============================
// FARMER LOGIN CHECK
// =============================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

$success = "";
$error = "";


// =============================
// UPDATE ORDER STATUS
// =============================

if (isset($_POST['update_status'])) {

    $order_id = (int) $_POST['order_id'];
    $new_status = trim($_POST['status']);


    // Allowed statuses

    $allowed_statuses = [
        'Accepted',
        'Ready',
        'Completed'
    ];


    if (!in_array($new_status, $allowed_statuses)) {

        $error = "Invalid order status.";

    } else {

        /*
        Make sure this order belongs
        to a product owned by this farmer.
        */

        $check_query = "
            SELECT o.order_id, o.status

            FROM Orders o

            INNER JOIN Products p
                ON o.product_id = p.product_id

            WHERE o.order_id = ?
            AND p.farmer_id = ?

            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $check_query
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $order_id,
            $farmer_id
        );

        mysqli_stmt_execute($stmt);

        $check_result = mysqli_stmt_get_result(
            $stmt
        );

        $order_check = mysqli_fetch_assoc(
            $check_result
        );


        if (!$order_check) {

            $error = "Order not found.";

        } else {

            $current_status =
                $order_check['status'];


            // =============================
            // STATUS FLOW CHECK
            // =============================

            $valid_transition = false;


            if (
                $current_status === 'Placed' &&
                $new_status === 'Accepted'
            ) {

                $valid_transition = true;

            } elseif (
                $current_status === 'Accepted' &&
                $new_status === 'Ready'
            ) {

                $valid_transition = true;

            } elseif (
                $current_status === 'Ready' &&
                $new_status === 'Completed'
            ) {

                $valid_transition = true;

            }


            if (!$valid_transition) {

                $error =
                    "This order cannot be moved to that status.";

            } else {

                // =============================
                // UPDATE STATUS
                // =============================

                $update_query = "
                    UPDATE Orders
                    SET status = ?
                    WHERE order_id = ?
                ";

                $stmt = mysqli_prepare(
                    $conn,
                    $update_query
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $new_status,
                    $order_id
                );


                if (mysqli_stmt_execute($stmt)) {

                    $success =
                        "Order #$order_id updated to $new_status.";

                } else {

                    $error =
                        "Unable to update order.";

                }

            }

        }

    }

}


// =============================
// FETCH FARMER ORDERS
// =============================

$orders_query = "
    SELECT

        o.order_id,
        o.quantity,
        o.total_price,
        o.pickup_date,
        o.pickup_time,
        o.status,
        o.order_date,

        p.product_name,
        p.category,

        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,

        m.market_name,
        m.location

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    INNER JOIN Users u
        ON o.customer_id = u.user_id

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    WHERE p.farmer_id = ?

    ORDER BY o.order_id DESC
";

$stmt = mysqli_prepare(
    $conn,
    $orders_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$orders_result = mysqli_stmt_get_result(
    $stmt
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Orders - MarketLink</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f4f6f8;

            color: #333;

        }


        /* =============================
           SIDEBAR
        ============================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 240px;

            height: 100vh;

            background: #163a24;

            color: white;

            display: flex;

            flex-direction: column;

        }


        .sidebar-logo {

            padding: 25px 20px;

            border-bottom: 1px solid
                rgba(255,255,255,0.1);

        }


        .sidebar-logo h2 {

            margin-bottom: 5px;

        }


        .sidebar-logo p {

            font-size: 13px;

            color: #b8d1bd;

        }


        .sidebar-menu {

            padding: 20px 12px;

            flex: 1;

        }


        .sidebar-menu a,
        .sidebar-bottom a {

            display: block;

            color: #d9e8dc;

            text-decoration: none;

            padding: 12px 15px;

            margin-bottom: 5px;

            border-radius: 6px;

            font-size: 14px;

        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {

            background: #2e7d32;

            color: white;

        }


        .sidebar-bottom {

            padding: 15px 12px;

            border-top: 1px solid
                rgba(255,255,255,0.1);

        }


        .logout:hover {

            background: #b3261e;

            color: white;

        }


        /* =============================
           MAIN CONTENT
        ============================= */

        .main-content {

            margin-left: 240px;

            padding: 30px;

        }


        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            color: #163a24;

            margin-bottom: 7px;

        }


        .page-header p {

            color: #777;

            font-size: 14px;

        }


        /* =============================
           ALERTS
        ============================= */

        .success {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 13px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .error {

            background: #ffebee;

            color: #c62828;

            padding: 13px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        /* =============================
           ORDER CARD
        ============================= */

        .orders-container {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        .order-card {

            background: white;

            border-radius: 10px;

            padding: 25px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.05);

        }


        .order-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding-bottom: 18px;

            margin-bottom: 20px;

            border-bottom: 1px solid #eee;

        }


        .order-header h2 {

            color: #163a24;

            font-size: 18px;

        }


        .order-date {

            color: #888;

            font-size: 12px;

        }


        /* =============================
           ORDER DETAILS
        ============================= */

        .order-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

        }


        .detail-box {

            background: #f8f9fa;

            padding: 15px;

            border-radius: 7px;

        }


        .detail-box label {

            display: block;

            color: #888;

            font-size: 11px;

            margin-bottom: 6px;

            text-transform: uppercase;

        }


        .detail-box strong {

            color: #333;

            font-size: 14px;

        }


        .customer-section {

            margin-top: 20px;

            padding-top: 20px;

            border-top: 1px solid #eee;

        }


        .customer-section h3 {

            color: #163a24;

            font-size: 15px;

            margin-bottom: 12px;

        }


        .customer-info {

            color: #666;

            font-size: 13px;

            line-height: 1.8;

        }


        /* =============================
           STATUS
        ============================= */

        .status {

            display: inline-block;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

        }


        .placed {

            background: #fff3cd;

            color: #856404;

        }


        .accepted {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .ready {

            background: #e8f5e9;

            color: #2e7d32;

        }


        .completed {

            background: #d1fae5;

            color: #047857;

        }


        .cancelled {

            background: #ffebee;

            color: #c62828;

        }


        /* =============================
           STATUS ACTION
        ============================= */

        .order-action {

            margin-top: 20px;

            padding-top: 20px;

            border-top: 1px solid #eee;

        }


        .status-form {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .status-form select {

            padding: 10px;

            border: 1px solid #ddd;

            border-radius: 6px;

            outline: none;

        }


        .update-btn {

            border: none;

            background: #2e7d32;

            color: white;

            padding: 10px 16px;

            border-radius: 6px;

            cursor: pointer;

            font-weight: bold;

        }


        .update-btn:hover {

            background: #245f27;

        }


        .no-orders {

            background: white;

            padding: 50px 20px;

            border-radius: 10px;

            text-align: center;

            color: #888;

        }


        /* =============================
           RESPONSIVE
        ============================= */

        @media (max-width: 1000px) {

            .order-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }


            .sidebar-menu {

                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .main-content {

                margin-left: 0;

                padding: 20px;

            }

        }


        @media (max-width: 550px) {

            .order-grid {

                grid-template-columns: 1fr;

            }


            .order-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 8px;

            }


            .status-form {

                flex-direction: column;

                align-items: stretch;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =============================
     SIDEBAR
============================= -->

<aside class="sidebar">


    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Farmer Panel
        </p>

    </div>


    <nav class="sidebar-menu">

        <a href="dashboard.php">
            Dashboard
        </a>


        <a href="products.php">
            My Products
        </a>


        <a
            href="orders.php"
            class="active"
        >
            Orders
        </a>


        <a href="profile.php">
            Profile
        </a>


        <a href="reports.php">
            Reports
        </a>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="../auth/logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>


</aside>


<!-- =============================
     MAIN
============================= -->

<main class="main-content">


    <div class="page-header">

        <h1>
            Customer Orders
        </h1>

        <p>
            Manage orders for your products.
        </p>

    </div>


    <!-- =============================
         MESSAGES
    ============================= -->

    <?php if ($success !== ""): ?>

        <div class="success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =============================
         ORDERS
    ============================= -->

    <?php if (mysqli_num_rows($orders_result) > 0): ?>


        <div class="orders-container">


            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>


                <div class="order-card">


                    <!-- ORDER HEADER -->

                    <div class="order-header">

                        <div>

                            <h2>

                                Order
                                #<?php
                                echo $order['order_id'];
                                ?>

                            </h2>

                        </div>


                        <div>

                            <span
                                class="status <?php echo strtolower($order['status']); ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $order['status']
                                );
                                ?>

                            </span>

                            <div class="order-date">

                                <?php
                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $order['order_date']
                                    )
                                );
                                ?>

                            </div>

                        </div>

                    </div>


                    <!-- ORDER DETAILS -->

                    <div class="order-grid">


                        <div class="detail-box">

                            <label>
                                Product
                            </label>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $order['product_name']
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Quantity
                            </label>

                            <strong>

                                <?php
                                echo $order['quantity'];
                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Total Price
                            </label>

                            <strong>

                                Rs.

                                <?php
                                echo number_format(
                                    $order['total_price'],
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Category
                            </label>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $order['category']
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Pickup Date
                            </label>

                            <strong>

                                <?php

                                echo $order['pickup_date']
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $order['pickup_date']
                                        )
                                    )
                                    : "Not set";

                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Pickup Time
                            </label>

                            <strong>

                                <?php

                                echo $order['pickup_time']
                                    ? date(
                                        "h:i A",
                                        strtotime(
                                            $order['pickup_time']
                                        )
                                    )
                                    : "Not set";

                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Market
                            </label>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $order['market_name']
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <label>
                                Location
                            </label>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $order['location']
                                );
                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- CUSTOMER -->

                    <div class="customer-section">

                        <h3>
                            Customer Information
                        </h3>


                        <div class="customer-info">

                            <strong>
                                Name:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $order['customer_name']
                            );
                            ?>

                            <br>


                            <strong>
                                Email:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $order['customer_email']
                            );
                            ?>


                            <br>


                            <strong>
                                Phone:
                            </strong>

                            <?php

                            echo !empty(
                                $order['customer_phone']
                            )
                                ? htmlspecialchars(
                                    $order['customer_phone']
                                )
                                : "Not provided";

                            ?>

                        </div>

                    </div>


                    <!-- STATUS ACTION -->

                    <?php if (
                        $order['status'] === 'Placed' ||
                        $order['status'] === 'Accepted' ||
                        $order['status'] === 'Ready'
                    ): ?>


                        <div class="order-action">

                            <form
                                method="POST"
                                class="status-form"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?php
                                        echo $order['order_id'];
                                    ?>"
                                >


                                <select
                                    name="status"
                                    required
                                >

                                    <?php if ($order['status'] === 'Placed'): ?>

                                        <option value="">
                                            Select Action
                                        </option>

                                        <option value="Accepted">
                                            Accept Order
                                        </option>

                                    <?php elseif ($order['status'] === 'Accepted'): ?>

                                        <option value="">
                                            Select Action
                                        </option>

                                        <option value="Ready">
                                            Mark as Ready
                                        </option>

                                    <?php elseif ($order['status'] === 'Ready'): ?>

                                        <option value="">
                                            Select Action
                                        </option>

                                        <option value="Completed">
                                            Mark as Completed
                                        </option>

                                    <?php endif; ?>

                                </select>


                                <button
                                    type="submit"
                                    name="update_status"
                                    class="update-btn"
                                >
                                    Update Status
                                </button>

                            </form>

                        </div>


                    <?php endif; ?>


                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="no-orders">

            <h2>
                No Orders Yet
            </h2>

            <p>
                You have not received any customer orders.
            </p>

        </div>


    <?php endif; ?>


</main>


</body>

</html>