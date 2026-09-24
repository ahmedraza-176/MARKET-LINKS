

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
// TOTAL ORDERS
// =============================

$query = "
    SELECT COUNT(*) AS total_orders

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$total_orders = mysqli_fetch_assoc($result)['total_orders'];


// =============================
// COMPLETED ORDERS
// =============================

$query = "
    SELECT COUNT(*) AS completed_orders

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
    AND o.status = 'Completed'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$completed_orders =
    mysqli_fetch_assoc($result)['completed_orders'];


// =============================
// CANCELLED ORDERS
// =============================

$query = "
    SELECT COUNT(*) AS cancelled_orders

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
    AND o.status = 'Cancelled'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$cancelled_orders =
    mysqli_fetch_assoc($result)['cancelled_orders'];


// =============================
// TOTAL SALES
// =============================

$query = "
    SELECT COALESCE(SUM(o.total_price), 0) AS total_sales

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
    AND o.status = 'Completed'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$total_sales =
    mysqli_fetch_assoc($result)['total_sales'];


// =============================
// PENDING ORDERS
// =============================

$query = "
    SELECT COUNT(*) AS pending_orders

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?

    AND o.status IN ('Placed', 'Accepted', 'Ready')
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pending_orders =
    mysqli_fetch_assoc($result)['pending_orders'];


// =============================
// PRODUCT SALES REPORT
// =============================

$product_query = "
    SELECT

        p.product_name,

        p.category,

        SUM(o.quantity) AS total_quantity,

        SUM(o.total_price) AS total_sales,

        COUNT(o.order_id) AS total_orders

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?

    AND o.status = 'Completed'

    GROUP BY
        p.product_id,
        p.product_name,
        p.category

    ORDER BY total_sales DESC
";

$stmt = mysqli_prepare(
    $conn,
    $product_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$product_result =
    mysqli_stmt_get_result($stmt);


// =============================
// RECENT COMPLETED SALES
// =============================

$recent_query = "
    SELECT

        o.order_id,
        o.quantity,
        o.total_price,
        o.pickup_date,

        p.product_name

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?

    AND o.status = 'Completed'

    ORDER BY o.order_id DESC

    LIMIT 10
";

$stmt = mysqli_prepare(
    $conn,
    $recent_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$recent_result =
    mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports - MarketLink</title>


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

            border-bottom:
                1px solid
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

            border-top:
                1px solid
                rgba(255,255,255,0.1);

        }


        .logout:hover {

            background: #b3261e;

            color: white;

        }


        /* =============================
           MAIN
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


        .stat-card span {

            display: block;

            color: #777;

            font-size: 13px;

            margin-bottom: 10px;

        }


        .stat-card h2 {

            color: #163a24;

            font-size: 28px;

        }


        .sales-card h2 {

            color: #2e7d32;

        }


        /* =============================
           SECTION
        ============================= */

        .section {

            background: white;

            border-radius: 10px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.05);

        }


        .section-header {

            margin-bottom: 20px;

        }


        .section-header h2 {

            color: #163a24;

            font-size: 19px;

            margin-bottom: 5px;

        }


        .section-header p {

            color: #888;

            font-size: 13px;

        }


        /* =============================
           TABLE
        ============================= */

        .table-wrapper {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 650px;

        }


        th {

            background: #f1f5f2;

            color: #163a24;

            padding: 13px;

            text-align: left;

            font-size: 13px;

        }


        td {

            padding: 13px;

            border-bottom: 1px solid #eee;

            font-size: 13px;

        }


        tr:hover {

            background: #fafafa;

        }


        .price {

            color: #2e7d32;

            font-weight: bold;

        }


        .empty {

            text-align: center;

            color: #888;

            padding: 30px;

        }


        /* =============================
           SUMMARY
        ============================= */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px;

        }


        .summary-box {

            background: #f8f9fa;

            padding: 20px;

            border-radius: 8px;

        }


        .summary-box h3 {

            color: #163a24;

            margin-bottom: 10px;

            font-size: 15px;

        }


        .summary-box p {

            color: #777;

            font-size: 13px;

        }


        /* =============================
           RESPONSIVE
        ============================= */

        @media (max-width: 1050px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 800px) {

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


        @media (max-width: 600px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }


            .summary-grid {

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

        <a href="dashboard.php">
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


        <a
            href="reports.php"
            class="active"
        >
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
     MAIN CONTENT
============================= -->

<main class="main-content">


    <div class="page-header">

        <h1>
            Reports & Insights
        </h1>

        <p>
            View your orders, sales and product performance.
        </p>

    </div>


    <!-- =============================
         STATISTICS
    ============================= -->

    <div class="stats-grid">


        <div class="stat-card">

            <span>
                Total Orders
            </span>

            <h2>
                <?php
                echo number_format($total_orders);
                ?>
            </h2>

        </div>


        <div class="stat-card">

            <span>
                Completed Orders
            </span>

            <h2>
                <?php
                echo number_format($completed_orders);
                ?>
            </h2>

        </div>


        <div class="stat-card">

            <span>
                Pending Orders
            </span>

            <h2>
                <?php
                echo number_format($pending_orders);
                ?>
            </h2>

        </div>


        <div class="stat-card sales-card">

            <span>
                Total Sales
            </span>

            <h2>

                Rs.

                <?php
                echo number_format(
                    $total_sales,
                    2
                );
                ?>

            </h2>

        </div>


    </div>


    <!-- =============================
         PRODUCT SALES
    ============================= -->

    <div class="section">


        <div class="section-header">

            <h2>
                Product Sales Report
            </h2>

            <p>
                Performance of your completed product orders.
            </p>

        </div>


        <div class="table-wrapper">


            <table>

                <thead>

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Orders
                        </th>

                        <th>
                            Quantity Sold
                        </th>

                        <th>
                            Total Sales
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        mysqli_num_rows(
                            $product_result
                        ) > 0
                    ): ?>


                        <?php while (
                            $product =
                            mysqli_fetch_assoc(
                                $product_result
                            )
                        ): ?>


                            <tr>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $product['category']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo number_format(
                                        $product['total_orders']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo number_format(
                                        $product['total_quantity']
                                    );
                                    ?>

                                </td>


                                <td class="price">

                                    Rs.

                                    <?php
                                    echo number_format(
                                        $product['total_sales'],
                                        2
                                    );
                                    ?>

                                </td>

                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="5"
                                class="empty"
                            >

                                No completed sales yet.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>


        </div>


    </div>


    <!-- =============================
         RECENT SALES
    ============================= -->

    <div class="section">


        <div class="section-header">

            <h2>
                Recent Completed Sales
            </h2>

            <p>
                Your latest completed customer orders.
            </p>

        </div>


        <div class="table-wrapper">


            <table>

                <thead>

                    <tr>

                        <th>
                            Order ID
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Quantity
                        </th>

                        <th>
                            Pickup Date
                        </th>

                        <th>
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        mysqli_num_rows(
                            $recent_result
                        ) > 0
                    ): ?>


                        <?php while (
                            $sale =
                            mysqli_fetch_assoc(
                                $recent_result
                            )
                        ): ?>


                            <tr>

                                <td>

                                    #

                                    <?php
                                    echo $sale['order_id'];
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $sale['product_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $sale['quantity'];
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo $sale['pickup_date']
                                        ? date(
                                            "d M Y",
                                            strtotime(
                                                $sale['pickup_date']
                                            )
                                        )
                                        : "N/A";

                                    ?>

                                </td>


                                <td class="price">

                                    Rs.

                                    <?php
                                    echo number_format(
                                        $sale['total_price'],
                                        2
                                    );
                                    ?>

                                </td>

                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="5"
                                class="empty"
                            >

                                No completed sales available.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>


        </div>


    </div>


    <!-- =============================
         ORDER SUMMARY
    ============================= -->

    <div class="section">


        <div class="section-header">

            <h2>
                Order Summary
            </h2>

        </div>


        <div class="summary-grid">


            <div class="summary-box">

                <h3>
                    Completed Orders
                </h3>

                <p>

                    <?php
                    echo number_format(
                        $completed_orders
                    );
                    ?>

                    orders have been completed successfully.

                </p>

            </div>


            <div class="summary-box">

                <h3>
                    Cancelled Orders
                </h3>

                <p>

                    <?php
                    echo number_format(
                        $cancelled_orders
                    );
                    ?>

                    orders have been cancelled.

                </p>

            </div>


        </div>


    </div>


</main>


</body>

</html>