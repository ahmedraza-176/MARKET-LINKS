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

$current_page = basename($_SERVER['PHP_SELF']);


// =========================================
// TOTAL ORDERS
// =========================================

$total_orders_query = "
    SELECT COUNT(*) AS total_orders
    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
";


$stmt = mysqli_prepare(
    $conn,
    $total_orders_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$total_orders_data =
    mysqli_fetch_assoc($result);

$total_orders =
    (int) $total_orders_data['total_orders'];


// =========================================
// COMPLETED ORDERS
// =========================================

$completed_orders_query = "
    SELECT COUNT(*) AS completed_orders
    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
    AND o.status = 'Completed'
";


$stmt = mysqli_prepare(
    $conn,
    $completed_orders_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$completed_data =
    mysqli_fetch_assoc($result);

$completed_orders =
    (int) $completed_data['completed_orders'];


// =========================================
// TOTAL SALES
// =========================================

$total_sales_query = "
    SELECT
        COALESCE(
            SUM(o.total_price),
            0
        ) AS total_sales

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
    AND o.status = 'Completed'
";


$stmt = mysqli_prepare(
    $conn,
    $total_sales_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$total_sales_data =
    mysqli_fetch_assoc($result);

$total_sales =
    (float) $total_sales_data['total_sales'];


// =========================================
// ACTIVE ORDERS
// =========================================

$active_orders_query = "
    SELECT COUNT(*) AS active_orders

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?

    AND o.status IN (
        'Placed',
        'Accepted',
        'Ready'
    )
";


$stmt = mysqli_prepare(
    $conn,
    $active_orders_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$active_data =
    mysqli_fetch_assoc($result);

$active_orders =
    (int) $active_data['active_orders'];


// =========================================
// BEST SELLING PRODUCT
// =========================================

$best_product_query = "
    SELECT
        p.product_name,
        SUM(o.quantity) AS total_quantity,
        SUM(o.total_price) AS total_revenue

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE p.farmer_id = ?
    AND o.status = 'Completed'

    GROUP BY
        p.product_id,
        p.product_name

    ORDER BY total_quantity DESC

    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $best_product_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$best_product =
    mysqli_fetch_assoc($result);


// =========================================
// PRODUCT PERFORMANCE
// =========================================

$product_performance_query = "
    SELECT
        p.product_name,
        p.category,

        COUNT(o.order_id) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN o.status = 'Completed'
                    THEN o.quantity
                    ELSE 0
                END
            ),
            0
        ) AS units_sold,

        COALESCE(
            SUM(
                CASE
                    WHEN o.status = 'Completed'
                    THEN o.total_price
                    ELSE 0
                END
            ),
            0
        ) AS revenue

    FROM Products p

    LEFT JOIN Orders o
        ON p.product_id = o.product_id

    WHERE p.farmer_id = ?

    GROUP BY
        p.product_id,
        p.product_name,
        p.category

    ORDER BY revenue DESC
";


$stmt = mysqli_prepare(
    $conn,
    $product_performance_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$product_performance_result =
    mysqli_stmt_get_result($stmt);


// =========================================
// RECENT ORDERS
// =========================================

$recent_orders_query = "
    SELECT
        o.order_id,
        o.quantity,
        o.total_price,
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

    ORDER BY o.order_date DESC

    LIMIT 8
";


$stmt = mysqli_prepare(
    $conn,
    $recent_orders_query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);

mysqli_stmt_execute($stmt);

$recent_orders_result =
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

    <title>
        Farmer Insights - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/farmer.css"
    >


    <style>

        /* =====================================
           MAIN
        ===================================== */

        .farmer-main {

            margin-left: 240px;

            min-height: 100vh;

            padding: 35px;

            background: #f4f6f8;

        }


        .insights-page {

            max-width: 1200px;

            margin: 0 auto;

        }


        /* =====================================
           HEADER
        ===================================== */

        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin: 0 0 8px;

            color: #163a24;

            font-size: 30px;

        }


        .page-header p {

            margin: 0;

            color: #777;

        }


        /* =====================================
           STAT CARDS
        ===================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;

        }


        .stat-card {

            background: white;

            padding: 22px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        .stat-icon {

            width: 45px;

            height: 45px;

            border-radius: 10px;

            background: #e8f5e9;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

            margin-bottom: 15px;

        }


        .stat-card h3 {

            margin: 0 0 6px;

            font-size: 28px;

            color: #163a24;

        }


        .stat-card p {

            margin: 0;

            color: #777;

            font-size: 13px;

        }


        /* =====================================
           BEST PRODUCT
        ===================================== */

        .best-product {

            background: #163a24;

            color: white;

            padding: 25px;

            border-radius: 12px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.08);

        }


        .best-product h2 {

            margin: 0 0 8px;

            font-size: 20px;

        }


        .best-product p {

            margin: 0 0 18px;

            color: #c9d8cd;

        }


        .best-product-details {

            display: flex;

            gap: 35px;

            flex-wrap: wrap;

        }


        .best-detail strong {

            display: block;

            font-size: 22px;

        }


        .best-detail span {

            font-size: 12px;

            color: #b8cdbd;

        }


        /* =====================================
           SECTION CARD
        ===================================== */

        .section-card {

            background: white;

            border-radius: 12px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        .section-card h2 {

            margin: 0 0 20px;

            color: #163a24;

            font-size: 21px;

        }


        /* =====================================
           TABLE
        ===================================== */

        .table-wrapper {

            overflow-x: auto;

        }


        .insights-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 650px;

        }


        .insights-table th {

            text-align: left;

            background: #f7f9f8;

            color: #555;

            padding: 13px;

            font-size: 13px;

        }


        .insights-table td {

            padding: 14px 13px;

            border-bottom: 1px solid #eee;

            color: #444;

            font-size: 14px;

        }


        .insights-table tr:last-child td {

            border-bottom: none;

        }


        .product-name {

            font-weight: bold;

            color: #163a24;

        }


        /* =====================================
           STATUS
        ===================================== */

        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

        }


        .status.placed {

            background: #fff3cd;

            color: #856404;

        }


        .status.accepted {

            background: #e3f2fd;

            color: #1565c0;

        }


        .status.ready {

            background: #e8f5e9;

            color: #2e7d32;

        }


        .status.completed {

            background: #d1e7dd;

            color: #146c43;

        }


        .status.cancelled,
        .status.declined {

            background: #ffebee;

            color: #c62828;

        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty-box {

            padding: 35px;

            text-align: center;

            color: #777;

        }


        .empty-box h3 {

            color: #163a24;

            margin-bottom: 7px;

        }


        /* =====================================
           SIDEBAR
        ===================================== */

        .sidebar {

            position: fixed;

            top: 0;

            left: 0;

            width: 240px;

            height: 100vh;

            background: #163a24;

            padding: 25px 15px;

            display: flex;

            flex-direction: column;

            box-shadow:
                3px 0 15px
                rgba(0, 0, 0, 0.08);

            z-index: 1000;

            box-sizing: border-box;

        }


        .sidebar-logo {

            padding: 0 10px 25px;

            border-bottom: 1px solid
                rgba(255, 255, 255, 0.1);

        }


        .sidebar-logo h2 {

            color: white;

            margin: 0 0 5px;

        }


        .sidebar-logo p {

            color: #b8cdbd;

            margin: 0;

            font-size: 13px;

        }


        .sidebar-menu {

            margin-top: 25px;

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .sidebar-menu a,
        .sidebar-bottom a {

            text-decoration: none;

            color: #dce9df;

            padding: 12px;

            border-radius: 7px;

            display: flex;

            align-items: center;

            gap: 10px;

            font-size: 14px;

        }


        .sidebar-menu a:hover,
        .sidebar-bottom a:hover {

            background: rgba(
                255,
                255,
                255,
                0.08
            );

        }


        .sidebar-menu a.active {

            background: #2e7d32;

            color: white;

        }


        .sidebar-bottom {

            margin-top: auto;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 1000px) {

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


            .farmer-main {

                margin-left: 0;

                padding: 25px 15px;

            }


            .sidebar-menu {

                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .sidebar-bottom {

                margin-top: 15px;

            }

        }


        @media (max-width: 550px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }


            .sidebar-menu {

                grid-template-columns: 1fr;

            }


            .section-card {

                padding: 18px;

            }


            .page-header h1 {

                font-size: 25px;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =====================================
     FARMER SIDEBAR
===================================== -->

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
            class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
        >

            <span>🏠</span>
            Dashboard

        </a>


        <a
            href="products.php"
            class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>"
        >

            <span>🥕</span>
            Products

        </a>


        <a
            href="weekly-stock.php"
            class="<?php echo $current_page === 'weekly-stock.php' ? 'active' : ''; ?>"
        >

            <span>📅</span>
            Weekly Stock

        </a>


        <a
            href="stock-templates.php"
            class="<?php echo $current_page === 'stock-templates.php' ? 'active' : ''; ?>"
        >

            <span>🔄</span>
            Templates

        </a>


        <a
            href="orders.php"
            class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
        >

            <span>📦</span>
            Orders

        </a>


        <a
            href="reviews.php"
            class="<?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>"
        >

            <span>⭐</span>
            Reviews

        </a>


        <a
            href="insights.php"
            class="<?php echo $current_page === 'insights.php' ? 'active' : ''; ?>"
        >

            <span>📊</span>
            Insights

        </a>


        <a
            href="profile.php"
            class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>"
        >

            <span>👤</span>
            Profile

        </a>


    </nav>


    <div class="sidebar-bottom">

        <a href="../auth/logout.php">

            <span>🚪</span>
            Logout

        </a>

    </div>


</aside>


<!-- =====================================
     MAIN
===================================== -->

<main class="farmer-main">


    <div class="insights-page">


        <!-- =====================================
             HEADER
        ===================================== -->

        <div class="page-header">

            <h1>
                Farmer Insights
            </h1>

            <p>
                View your orders, sales and product performance.
            </p>

        </div>


        <!-- =====================================
             STAT CARDS
        ===================================== -->

        <div class="stats-grid">


            <div class="stat-card">

                <div class="stat-icon">
                    📦
                </div>

                <h3>
                    <?php
                    echo $total_orders;
                    ?>
                </h3>

                <p>
                    Total Orders
                </p>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    💰
                </div>

                <h3>

                    Rs.
                    <?php

                    echo number_format(
                        $total_sales,
                        2
                    );

                    ?>

                </h3>

                <p>
                    Completed Sales
                </p>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <h3>
                    <?php
                    echo $completed_orders;
                    ?>
                </h3>

                <p>
                    Completed Orders
                </p>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🔄
                </div>

                <h3>
                    <?php
                    echo $active_orders;
                    ?>
                </h3>

                <p>
                    Active Orders
                </p>

            </div>


        </div>


        <!-- =====================================
             BEST SELLER
        ===================================== -->

        <div class="best-product">


            <h2>
                🏆 Best Selling Product
            </h2>


            <?php if ($best_product): ?>


                <p>

                    <?php

                    echo htmlspecialchars(
                        $best_product['product_name']
                    );

                    ?>

                </p>


                <div class="best-product-details">


                    <div class="best-detail">

                        <strong>

                            <?php

                            echo (int)
                                $best_product['total_quantity'];

                            ?>

                        </strong>

                        <span>
                            Units Sold
                        </span>

                    </div>


                    <div class="best-detail">

                        <strong>

                            Rs.
                            <?php

                            echo number_format(
                                (float)
                                $best_product['total_revenue'],
                                2
                            );

                            ?>

                        </strong>

                        <span>
                            Revenue
                        </span>

                    </div>


                </div>


            <?php else: ?>


                <p>
                    No completed sales yet.
                </p>


            <?php endif; ?>


        </div>


        <!-- =====================================
             PRODUCT PERFORMANCE
        ===================================== -->

        <div class="section-card">


            <h2>
                Product Performance
            </h2>


            <?php if (
                mysqli_num_rows(
                    $product_performance_result
                ) > 0
            ): ?>


                <div class="table-wrapper">


                    <table class="insights-table">


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
                                    Units Sold
                                </th>

                                <th>
                                    Revenue
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while (
                                $product =
                                mysqli_fetch_assoc(
                                    $product_performance_result
                                )
                            ): ?>


                                <tr>

                                    <td class="product-name">

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

                                        echo (int)
                                            $product['total_orders'];

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo (int)
                                            $product['units_sold'];

                                        ?>

                                    </td>


                                    <td>

                                        Rs.
                                        <?php

                                        echo number_format(
                                            (float)
                                            $product['revenue'],
                                            2
                                        );

                                        ?>

                                    </td>

                                </tr>


                            <?php endwhile; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-box">

                    <h3>
                        No Product Data
                    </h3>

                    <p>
                        Product performance will appear
                        after customers place orders.
                    </p>

                </div>


            <?php endif; ?>


        </div>


        <!-- =====================================
             RECENT ORDERS
        ===================================== -->

        <div class="section-card">


            <h2>
                Recent Orders
            </h2>


            <?php if (
                mysqli_num_rows(
                    $recent_orders_result
                ) > 0
            ): ?>


                <div class="table-wrapper">


                    <table class="insights-table">


                        <thead>

                            <tr>

                                <th>
                                    Order ID
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while (
                                $order =
                                mysqli_fetch_assoc(
                                    $recent_orders_result
                                )
                            ): ?>


                                <tr>


                                    <td>

                                        #
                                        <?php

                                        echo (int)
                                            $order['order_id'];

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $order['customer_name']
                                        );

                                        ?>

                                    </td>


                                    <td class="product-name">

                                        <?php

                                        echo htmlspecialchars(
                                            $order['product_name']
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo (int)
                                            $order['quantity'];

                                        ?>

                                    </td>


                                    <td>

                                        Rs.
                                        <?php

                                        echo number_format(
                                            (float)
                                            $order['total_price'],
                                            2
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        $status_class =
                                            strtolower(
                                                $order['status']
                                            );

                                        ?>

                                        <span
                                            class="status <?php
                                                echo htmlspecialchars(
                                                    $status_class
                                                );
                                            ?>"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $order['status']
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $order['order_date']
                                            )
                                        );

                                        ?>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-box">

                    <h3>
                        No Orders Yet
                    </h3>

                    <p>
                        Your recent orders will appear here.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</main>


</body>

</html>