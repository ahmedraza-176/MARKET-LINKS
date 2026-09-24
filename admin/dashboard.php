<?php

session_start();

require_once "../config/connection.php";

// =========================================
// ADMIN LOGIN CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// =========================================
// TOTAL FARMERS
// =========================================

$query = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role = 'Farmer'
";

$result = mysqli_query($conn, $query);
$total_farmers = mysqli_fetch_assoc($result)['total'];

// =========================================
// TOTAL CUSTOMERS
// =========================================

$query = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role = 'Customer'
";

$result = mysqli_query($conn, $query);
$total_customers = mysqli_fetch_assoc($result)['total'];

// =========================================
// TOTAL PRODUCTS
// =========================================

$query = "
    SELECT COUNT(*) AS total
    FROM Products
";

$result = mysqli_query($conn, $query);
$total_products = mysqli_fetch_assoc($result)['total'];

// =========================================
// TOTAL ORDERS
// =========================================

$query = "
    SELECT COUNT(*) AS total
    FROM Orders
";

$result = mysqli_query($conn, $query);
$total_orders = mysqli_fetch_assoc($result)['total'];

// =========================================
// COMPLETED SALES
// =========================================

$query = "
    SELECT
        COALESCE(SUM(total_price), 0) AS total_sales
    FROM Orders
    WHERE status = 'Completed'
";

$result = mysqli_query($conn, $query);
$total_sales = mysqli_fetch_assoc($result)['total_sales'];

// =========================================
// ACTIVE FARMERS
// =========================================

$query = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role = 'Farmer'
    AND status = 'Active'
";

$result = mysqli_query($conn, $query);
$active_farmers = mysqli_fetch_assoc($result)['total'];

// =========================================
// ACTIVE CUSTOMERS
// =========================================

$query = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role = 'Customer'
    AND status = 'Active'
";

$result = mysqli_query($conn, $query);
$active_customers = mysqli_fetch_assoc($result)['total'];

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

        u.name AS customer_name,

        p.product_name

    FROM Orders o

    INNER JOIN Users u
        ON o.customer_id = u.user_id

    INNER JOIN Products p
        ON o.product_id = p.product_id

    ORDER BY o.order_date DESC

    LIMIT 8

";

$recent_orders = mysqli_query(
    $conn,
    $recent_orders_query
);

// =========================================
// RECENT FARMERS
// =========================================

$recent_farmers_query = "

    SELECT

        user_id,
        name,
        email,
        phone,
        status

    FROM Users

    WHERE role = 'Farmer'

    ORDER BY user_id DESC

    LIMIT 5

";

$recent_farmers = mysqli_query(
    $conn,
    $recent_farmers_query
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

    <title>
        Admin Dashboard - MarketLink
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f4f6f8;

            color: #333;

            transition: background 0.25s ease, color 0.25s ease;

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

        }


        .sidebar-logo {

            padding: 0 10px 25px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.1);

        }


        .sidebar-logo h2 {

            margin: 0 0 5px;

            color: white;

        }


        .sidebar-logo p {

            margin: 0;

            color: #b8cdbd;

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

            background:
                rgba(255,255,255,0.08);

        }


        .sidebar-menu a.active {

            background: #2e7d32;

            color: white;

        }


        .sidebar-bottom {

            margin-top: auto;

        }


        /* =====================================
           MAIN
        ===================================== */

        .admin-main {

            margin-left: 240px;

            min-height: 100vh;

            padding: 35px;

        }


        .dashboard-container {

            max-width: 1250px;

            margin: auto;

        }


        /* =====================================
           HEADER
        ===================================== */

        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            margin: 0 0 7px;

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

            gap: 20px;

            margin-bottom: 30px;

        }


        .stat-card {

            background: white;

            border-radius: 12px;

            padding: 22px;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,0.06);

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

            margin: 0;

            color: #777;

            font-size: 13px;

            font-weight: normal;

        }


        .stat-card .number {

            margin-top: 7px;

            font-size: 27px;

            font-weight: bold;

            color: #163a24;

        }


        /* =====================================
           EXTRA STATS
        ===================================== */

        .extra-stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 30px;

        }


        .extra-card {

            background: white;

            padding: 20px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,0.06);

        }


        .extra-card h3 {

            margin: 0 0 10px;

            color: #555;

            font-size: 14px;

        }


        .extra-card strong {

            font-size: 24px;

            color: #198754;

        }


        /* =====================================
           CONTENT GRID
        ===================================== */

        .content-grid {

            display: grid;

            grid-template-columns:
                2fr 1fr;

            gap: 20px;

        }


        .panel {

            background: white;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,0.06);

        }


        .panel-header {

            padding: 18px 20px;

            border-bottom:
                1px solid #eee;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .panel-header h2 {

            margin: 0;

            font-size: 17px;

            color: #163a24;

        }


        .view-link {

            text-decoration: none;

            color: #198754;

            font-size: 12px;

            font-weight: bold;

        }


        /* =====================================
           TABLE
        ===================================== */

        .table-wrapper {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 600px;

        }


        th {

            background: #f8f9fa;

            color: #555;

            padding: 12px 15px;

            text-align: left;

            font-size: 12px;

        }


        td {

            padding: 13px 15px;

            border-top:
                1px solid #eee;

            font-size: 12px;

            color: #666;

        }


        .customer {

            color: #163a24;

            font-weight: bold;

        }


        .price {

            color: #198754;

            font-weight: bold;

        }


        /* =====================================
           STATUS
        ===================================== */

        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;

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

            background: #e0f2fe;

            color: #0369a1;

        }


        .completed {

            background: #dcfce7;

            color: #166534;

        }


        .cancelled,
        .declined {

            background: #fee2e2;

            color: #991b1b;

        }


        /* =====================================
           FARMERS
        ===================================== */

        .farmer-list {

            padding: 5px 20px 15px;

        }


        .farmer-item {

            padding: 15px 0;

            border-bottom:
                1px solid #eee;

        }


        .farmer-item:last-child {

            border-bottom: none;

        }


        .farmer-name {

            color: #163a24;

            font-weight: bold;

            margin-bottom: 4px;

        }


        .farmer-email {

            color: #888;

            font-size: 11px;

        }


        .farmer-status {

            margin-top: 7px;

            font-size: 10px;

            font-weight: bold;

        }


        .active {

            color: #198754;

        }


        .inactive {

            color: #dc3545;

        }


        /* =====================================
           DARK MODE
        ===================================== */

        body.dark-mode {

            background: #111714;

            color: #e8eee9;

        }


        body.dark-mode .admin-main {

            background: #111714;

        }


        body.dark-mode .page-header h1,
        body.dark-mode .panel-header h2,
        body.dark-mode .customer,
        body.dark-mode .farmer-name {

            color: #e8eee9;

        }


        body.dark-mode .page-header p,
        body.dark-mode .stat-card h3,
        body.dark-mode td,
        body.dark-mode .farmer-email {

            color: #aebbb2;

        }


        body.dark-mode .stat-card,
        body.dark-mode .extra-card,
        body.dark-mode .panel {

            background: #1b241f;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,0.25);

        }


        body.dark-mode .panel-header {

            border-bottom-color: #344139;

        }


        body.dark-mode th {

            background: #243229;

            color: #e8eee9;

        }


        body.dark-mode td {

            border-top-color: #344139;

        }


        body.dark-mode .farmer-item {

            border-bottom-color: #344139;

        }


        body.dark-mode .stat-icon {

            background: #243b2b;

        }


        body.dark-mode .view-link {

            color: #72d572;

        }


        /* =====================================
           DARK MODE BUTTON
        ===================================== */

        .dark-mode-toggle {

            position: fixed;

            right: 25px;

            bottom: 25px;

            width: 48px;

            height: 48px;

            border: none;

            border-radius: 50%;

            background: #198754;

            color: white;

            font-size: 20px;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.25);

            z-index: 9999;

            transition: 0.2s ease;

        }


        .dark-mode-toggle:hover {

            transform: scale(1.08);

            background: #157347;

        }


        body.dark-mode .dark-mode-toggle {

            background: #f4c542;

            color: #111;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 1050px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .content-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 750px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }


            .admin-main {

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


            .extra-stats {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 450px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }


            .sidebar-menu {

                grid-template-columns: 1fr;

            }


            .dark-mode-toggle {

                width: 44px;

                height: 44px;

                right: 15px;

                bottom: 15px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================
     SIDEBAR
===================================== -->

<aside class="sidebar">

    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Admin Panel
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
            href="farmers.php"
            class="<?php echo $current_page === 'farmers.php' ? 'active' : ''; ?>"
        >

            <span>👨‍🌾</span>

            Farmers

        </a>


        <a
            href="customers.php"
            class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>"
        >

            <span>👥</span>

            Customers

        </a>


        <a
            href="markets.php"
            class="<?php echo $current_page === 'markets.php' ? 'active' : ''; ?>"
        >

            <span>🏪</span>

            Markets

        </a>


        <a
            href="products.php"
            class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>"
        >

            <span>🥕</span>

            Products

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
            href="reports.php"
            class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>"
        >

            <span>📊</span>

            Reports

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

<main class="admin-main">

    <div class="dashboard-container">


        <div class="page-header">

            <h1>
                Admin Dashboard
            </h1>

            <p>
                Welcome to your MarketLink administration panel.
            </p>

        </div>


        <!-- =====================================
             MAIN STATS
        ===================================== -->

        <div class="stats-grid">


            <div class="stat-card">

                <div class="stat-icon">
                    👨‍🌾
                </div>

                <h3>
                    Total Farmers
                </h3>

                <div class="number">
                    <?php echo $total_farmers; ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👥
                </div>

                <h3>
                    Total Customers
                </h3>

                <div class="number">
                    <?php echo $total_customers; ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🥕
                </div>

                <h3>
                    Total Products
                </h3>

                <div class="number">
                    <?php echo $total_products; ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📦
                </div>

                <h3>
                    Total Orders
                </h3>

                <div class="number">
                    <?php echo $total_orders; ?>
                </div>

            </div>


        </div>


        <!-- =====================================
             EXTRA STATS
        ===================================== -->

        <div class="extra-stats">


            <div class="extra-card">

                <h3>
                    Completed Sales
                </h3>

                <strong>

                    Rs.
                    <?php

                    echo number_format(
                        (float) $total_sales,
                        2
                    );

                    ?>

                </strong>

            </div>


            <div class="extra-card">

                <h3>
                    Active Farmers
                </h3>

                <strong>
                    <?php echo $active_farmers; ?>
                </strong>

            </div>


            <div class="extra-card">

                <h3>
                    Active Customers
                </h3>

                <strong>
                    <?php echo $active_customers; ?>
                </strong>

            </div>


        </div>


        <!-- =====================================
             CONTENT
        ===================================== -->

        <div class="content-grid">


            <!-- RECENT ORDERS -->

            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Recent Orders
                    </h2>

                    <a
                        href="orders.php"
                        class="view-link"
                    >
                        View All
                    </a>

                </div>


                <div class="table-wrapper">


                    <?php if (
                        mysqli_num_rows(
                            $recent_orders
                        ) > 0
                    ): ?>


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
                                        Total
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php while (
                                    $order =
                                    mysqli_fetch_assoc(
                                        $recent_orders
                                    )
                                ): ?>


                                    <?php

                                    $status_class =
                                        strtolower(
                                            $order['status']
                                        );

                                    ?>


                                    <tr>

                                        <td>

                                            #<?php

                                            echo (int)
                                                $order['order_id'];

                                            ?>

                                        </td>


                                        <td>

                                            <span class="customer">

                                                <?php

                                                echo htmlspecialchars(
                                                    $order['customer_name']
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $order['product_name']
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <span class="price">

                                                Rs.
                                                <?php

                                                echo number_format(
                                                    (float)
                                                    $order['total_price'],
                                                    2
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="status <?php
                                                    echo $status_class;
                                                ?>"
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


                    <?php else: ?>


                        <div
                            style="
                                padding:40px;
                                text-align:center;
                                color:#888;
                            "
                        >

                            No orders yet.

                        </div>


                    <?php endif; ?>


                </div>


            </div>


            <!-- RECENT FARMERS -->

            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Recent Farmers
                    </h2>

                    <a
                        href="farmers.php"
                        class="view-link"
                    >
                        View All
                    </a>

                </div>


                <div class="farmer-list">


                    <?php if (
                        mysqli_num_rows(
                            $recent_farmers
                        ) > 0
                    ): ?>


                        <?php while (
                            $farmer =
                            mysqli_fetch_assoc(
                                $recent_farmers
                            )
                        ): ?>


                            <div class="farmer-item">


                                <div class="farmer-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $farmer['name']
                                    );

                                    ?>

                                </div>


                                <div class="farmer-email">

                                    <?php

                                    echo htmlspecialchars(
                                        $farmer['email']
                                    );

                                    ?>

                                </div>


                                <div
                                    class="farmer-status <?php
                                        echo $farmer['status']
                                        === 'Active'
                                        ? 'active'
                                        : 'inactive';
                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $farmer['status']
                                    );

                                    ?>

                                </div>


                            </div>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <div
                            style="
                                padding:30px 0;
                                text-align:center;
                                color:#888;
                            "
                        >

                            No farmers yet.

                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </div>


</main>


<!-- =====================================
     DARK MODE BUTTON
===================================== -->

<button
    type="button"
    id="darkModeToggle"
    class="dark-mode-toggle"
    title="Dark Mode"
    aria-label="Toggle Dark Mode"
>
    🌙
</button>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const toggle = document.getElementById("darkModeToggle");

    // Check saved theme
    const savedTheme =
        localStorage.getItem("marketlink-theme");


    if (savedTheme === "dark") {

        document.body.classList.add("dark-mode");

        toggle.innerHTML = "☀️";

        toggle.title = "Light Mode";

    }


    // Toggle dark mode

    toggle.addEventListener("click", function () {

        document.body.classList.toggle("dark-mode");


        if (
            document.body.classList.contains("dark-mode")
        ) {

            localStorage.setItem(
                "marketlink-theme",
                "dark"
            );

            toggle.innerHTML = "☀️";

            toggle.title = "Light Mode";

        } else {

            localStorage.setItem(
                "marketlink-theme",
                "light"
            );

            toggle.innerHTML = "🌙";

            toggle.title = "Dark Mode";

        }

    });

});

</script>


</body>

</html>
