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


// =============================
// GET FARMER NAME
// =============================

$user_query = "
    SELECT name, email
    FROM Users
    WHERE user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $user_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$user_result = mysqli_stmt_get_result($stmt);

$farmer = mysqli_fetch_assoc($user_result);


// =============================
// TOTAL PRODUCTS
// =============================

$product_query = "
    SELECT COUNT(*) AS total_products
    FROM Products
    WHERE farmer_id = ?
";

$stmt = mysqli_prepare($conn, $product_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$product_result = mysqli_stmt_get_result($stmt);

$product_data = mysqli_fetch_assoc($product_result);

$total_products = $product_data['total_products'];


// =============================
// AVAILABLE PRODUCTS
// =============================

$available_query = "
    SELECT COUNT(*) AS available_products
    FROM Products
    WHERE farmer_id = ?
    AND status = 'Available'
";

$stmt = mysqli_prepare($conn, $available_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$available_result = mysqli_stmt_get_result($stmt);

$available_data = mysqli_fetch_assoc($available_result);

$available_products = $available_data['available_products'];


// =============================
// TOTAL ORDERS
// =============================

$order_query = "
    SELECT COUNT(*) AS total_orders
    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
";

$stmt = mysqli_prepare($conn, $order_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$order_result = mysqli_stmt_get_result($stmt);

$order_data = mysqli_fetch_assoc($order_result);

$total_orders = $order_data['total_orders'];


// =============================
// PENDING ORDERS
// =============================

$pending_query = "
    SELECT COUNT(*) AS pending_orders
    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?

    AND o.status IN ('Placed', 'Accepted')
";

$stmt = mysqli_prepare($conn, $pending_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$pending_result = mysqli_stmt_get_result($stmt);

$pending_data = mysqli_fetch_assoc($pending_result);

$pending_orders = $pending_data['pending_orders'];


// =============================
// TOTAL SALES
// =============================

$sales_query = "
    SELECT
        COALESCE(
            SUM(o.total_price),
            0
        ) AS total_sales

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?

    AND o.status != 'Cancelled'
";

$stmt = mysqli_prepare($conn, $sales_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$sales_result = mysqli_stmt_get_result($stmt);

$sales_data = mysqli_fetch_assoc($sales_result);

$total_sales = $sales_data['total_sales'];


// =============================
// RECENT ORDERS
// =============================

$recent_query = "
    SELECT

        o.order_id,
        o.quantity,
        o.total_price,
        o.pickup_date,
        o.pickup_time,
        o.status,
        o.order_date,

        p.product_name,

        u.name AS customer_name

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    INNER JOIN Users u
        ON o.customer_id = u.user_id

    WHERE p.farmer_id = ?

    ORDER BY o.order_id DESC

    LIMIT 5
";

$stmt = mysqli_prepare($conn, $recent_query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$recent_result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Farmer Dashboard - MarketLink</title>


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


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .topbar h1 {

            color: #163a24;

            margin-bottom: 5px;

        }


        .topbar p {

            color: #777;

            font-size: 14px;

        }


        .farmer-name {

            background: white;

            padding: 10px 15px;

            border-radius: 8px;

            color: #2e7d32;

            font-weight: bold;

        }


        /* =============================
           STAT CARDS
        ============================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 30px;

        }


        .stat-card {

            background: white;

            padding: 22px;

            border-radius: 10px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.05);

        }


        .stat-card h3 {

            font-size: 14px;

            color: #777;

            margin-bottom: 10px;

        }


        .stat-card .number {

            font-size: 28px;

            font-weight: bold;

            color: #163a24;

        }


        .stat-card .description {

            font-size: 12px;

            color: #999;

            margin-top: 7px;

        }


        /* =============================
           DASHBOARD GRID
        ============================= */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                2fr 1fr;

            gap: 25px;

        }


        .card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.05);

        }


        .card h2 {

            color: #163a24;

            font-size: 20px;

            margin-bottom: 20px;

        }


        /* =============================
           TABLE
        ============================= */

        .table-container {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        th {

            background: #f4f6f8;

            color: #555;

            font-size: 13px;

            text-align: left;

            padding: 12px;

        }


        td {

            padding: 12px;

            border-bottom: 1px solid #eee;

            font-size: 13px;

        }


        .status {

            display: inline-block;

            padding: 5px 9px;

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
           QUICK ACTIONS
        ============================= */

        .quick-actions {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


        .quick-action {

            display: block;

            text-decoration: none;

            padding: 14px;

            border-radius: 7px;

            background: #e8f5e9;

            color: #2e7d32;

            font-weight: bold;

            font-size: 14px;

        }


        .quick-action:hover {

            background: #2e7d32;

            color: white;

        }


        .no-orders {

            text-align: center;

            padding: 30px;

            color: #888;

        }


        /* =============================
           RESPONSIVE
        ============================= */

        @media (max-width: 1000px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .dashboard-grid {

                grid-template-columns: 1fr;

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


            .topbar {

                align-items: flex-start;

                flex-direction: column;

                gap: 15px;

            }

        }


        @media (max-width: 500px) {

            .stats-grid {

                grid-template-columns: 1fr;

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


        <a
            href="dashboard.php"
            class="active"
        >
            Dashboard
        </a>


        <a href="products.php">
            My Products
        </a>


        <a href="orders.php">
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


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h1>
                Farmer Dashboard
            </h1>

            <p>
                Manage your products and orders.
            </p>

        </div>


        <div class="farmer-name">

            <?php
            echo htmlspecialchars(
                $farmer['name']
            );
            ?>

        </div>

    </div>


    <!-- =============================
         STAT CARDS
    ============================= -->

    <div class="stats-grid">


        <div class="stat-card">

            <h3>
                Total Products
            </h3>

            <div class="number">
                <?php echo $total_products; ?>
            </div>

            <div class="description">
                Products you have added
            </div>

        </div>


        <div class="stat-card">

            <h3>
                Available Products
            </h3>

            <div class="number">
                <?php echo $available_products; ?>
            </div>

            <div class="description">
                Currently available
            </div>

        </div>


        <div class="stat-card">

            <h3>
                Total Orders
            </h3>

            <div class="number">
                <?php echo $total_orders; ?>
            </div>

            <div class="description">
                Orders received
            </div>

        </div>


        <div class="stat-card">

            <h3>
                Total Sales
            </h3>

            <div class="number">

                Rs.
                <?php
                echo number_format(
                    $total_sales,
                    2
                );
                ?>

            </div>

            <div class="description">
                Excluding cancelled orders
            </div>

        </div>


    </div>


    <!-- =============================
         DASHBOARD CONTENT
    ============================= -->

    <div class="dashboard-grid">


        <!-- RECENT ORDERS -->

        <div class="card">

            <h2>
                Recent Orders
            </h2>


            <?php if (mysqli_num_rows($recent_result) > 0): ?>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Qty
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($order = mysqli_fetch_assoc($recent_result)): ?>


                                <tr>

                                    <td>
                                        #<?php echo $order['order_id']; ?>
                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['customer_name']
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $order['product_name']
                                        );
                                        ?>

                                    </td>


                                    <td>
                                        <?php echo $order['quantity']; ?>
                                    </td>


                                    <td>

                                        Rs.
                                        <?php
                                        echo number_format(
                                            $order['total_price'],
                                            2
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status <?php echo strtolower($order['status']); ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $order['status']
                                            );
                                            ?>

                                        </span>

                                    </td>

                                </tr>


                            <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="no-orders">

                    No orders received yet.

                </div>


            <?php endif; ?>


        </div>


        <!-- QUICK ACTIONS -->

        <div class="card">

            <h2>
                Quick Actions
            </h2>


            <div class="quick-actions">


                <a
                    href="products.php"
                    class="quick-action"
                >
                    + Add / Manage Products
                </a>


                <a
                    href="orders.php"
                    class="quick-action"
                >
                    View Orders
                </a>


                <a
                    href="profile.php"
                    class="quick-action"
                >
                    Update Profile
                </a>


                <a
                    href="reports.php"
                    class="quick-action"
                >
                    View Reports
                </a>


            </div>


            <br>


            <h2>
                Pending Orders
            </h2>


            <div class="stat-card">

                <div class="number">
                    <?php echo $pending_orders; ?>
                </div>

                <div class="description">
                    Orders waiting for action
                </div>

            </div>


        </div>


    </div>


</main>


</body>

</html>