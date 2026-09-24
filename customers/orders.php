<?php

session_start();

require_once "../config/connection.php";


// =============================
// CUSTOMER LOGIN CHECK
// =============================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Customer') {
    header("Location: ../auth/login.php");
    exit();
}

$customer_id = (int) $_SESSION['user_id'];


// =============================
// ACTIVE PAGE
// =============================

$current_page = basename($_SERVER['PHP_SELF']);


// =============================
// CANCEL ORDER
// =============================

if (isset($_GET['cancel'])) {

    $order_id = (int) $_GET['cancel'];

    if ($order_id > 0) {

        $cancel_query = "
            SELECT
                order_id,
                product_id,
                quantity
            FROM Orders
            WHERE order_id = ?
            AND customer_id = ?
            AND status = 'Placed'
            LIMIT 1
        ";

        $cancel_stmt = mysqli_prepare(
            $conn,
            $cancel_query
        );

        mysqli_stmt_bind_param(
            $cancel_stmt,
            "ii",
            $order_id,
            $customer_id
        );

        mysqli_stmt_execute($cancel_stmt);

        $cancel_result = mysqli_stmt_get_result(
            $cancel_stmt
        );


        if (mysqli_num_rows($cancel_result) == 1) {

            $order = mysqli_fetch_assoc(
                $cancel_result
            );


            // =============================
            // UPDATE ORDER STATUS
            // =============================

            $update_query = "
                UPDATE Orders
                SET status = 'Cancelled'
                WHERE order_id = ?
                AND customer_id = ?
                AND status = 'Placed'
            ";

            $update_stmt = mysqli_prepare(
                $conn,
                $update_query
            );

            mysqli_stmt_bind_param(
                $update_stmt,
                "ii",
                $order_id,
                $customer_id
            );

            mysqli_stmt_execute(
                $update_stmt
            );


            // =============================
            // RESTORE STOCK
            // =============================

            $product_id = (int) $order['product_id'];
            $quantity = (int) $order['quantity'];


            $stock_query = "
                SELECT stock
                FROM Products
                WHERE product_id = ?
                LIMIT 1
            ";

            $stock_stmt = mysqli_prepare(
                $conn,
                $stock_query
            );

            mysqli_stmt_bind_param(
                $stock_stmt,
                "i",
                $product_id
            );

            mysqli_stmt_execute(
                $stock_stmt
            );

            $stock_result = mysqli_stmt_get_result(
                $stock_stmt
            );


            if (mysqli_num_rows($stock_result) == 1) {

                $product = mysqli_fetch_assoc(
                    $stock_result
                );


                $new_stock =
                    (int) $product['stock'] + $quantity;


                $new_status =
                    $new_stock > 0
                        ? 'Available'
                        : 'Sold Out';


                $restore_query = "
                    UPDATE Products
                    SET
                        stock = ?,
                        status = ?
                    WHERE product_id = ?
                ";

                $restore_stmt = mysqli_prepare(
                    $conn,
                    $restore_query
                );

                mysqli_stmt_bind_param(
                    $restore_stmt,
                    "isi",
                    $new_stock,
                    $new_status,
                    $product_id
                );

                mysqli_stmt_execute(
                    $restore_stmt
                );
            }
        }
    }


    header("Location: orders.php?cancelled=1");
    exit();
}


// =============================
// FETCH CUSTOMER ORDERS
// =============================

$query = "
    SELECT

        o.order_id,
        o.product_id,
        o.quantity,
        o.total_price,
        o.pickup_date,
        o.pickup_time,
        o.status,
        o.order_date,

        p.product_name,
        p.category,

        u.name AS farmer_name,

        m.market_name,
        m.location

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    INNER JOIN Users u
        ON p.farmer_id = u.user_id

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    WHERE o.customer_id = ?

    ORDER BY o.order_id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $customer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result(
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

    <title>My Orders - MarketLink</title>

    <link
        rel="stylesheet"
        href="../css/customers.css"
    >


    <style>

        /* =============================
           ORDERS PAGE
        ============================= */

        .orders-page {

            max-width: 1200px;

            margin: auto;

            padding: 40px 25px;

        }


        /* =============================
           HEADER
        ============================= */

        .orders-header {

            margin-bottom: 25px;

        }


        .orders-header h1 {

            color: #163a24;

            margin-bottom: 8px;

        }


        .orders-header p {

            color: #777;

        }


        /* =============================
           MESSAGES
        ============================= */

        .success-message {

            background: #e8f5e9;

            color: #2e7d32;

            border: 1px solid #b7dfb9;

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .error-message {

            background: #ffe5e5;

            color: #c62828;

            border: 1px solid #f1b5b5;

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        /* =============================
           ORDERS CONTAINER
        ============================= */

        .orders-container {

            display: grid;

            gap: 20px;

        }


        /* =============================
           ORDER CARD
        ============================= */

        .order-card {

            background: white;

            border-radius: 12px;

            padding: 25px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        /* =============================
           ORDER TOP
        ============================= */

        .order-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

        }


        .order-id {

            color: #163a24;

            font-size: 18px;

            font-weight: bold;

        }


        .order-date {

            color: #888;

            font-size: 13px;

            margin-top: 5px;

        }


        /* =============================
           STATUS
        ============================= */

        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

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


        .status.cancelled {

            background: #ffe5e5;

            color: #c62828;

        }


        /* =============================
           ORDER DETAILS
        ============================= */

        .order-details {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            border-top: 1px solid #eee;

            border-bottom: 1px solid #eee;

            padding: 20px 0;

        }


        .detail-box span {

            display: block;

            color: #888;

            font-size: 12px;

            margin-bottom: 6px;

        }


        .detail-box strong {

            color: #333;

            font-size: 14px;

            word-break: break-word;

        }


        /* =============================
           ORDER BOTTOM
        ============================= */

        .order-bottom {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding-top: 20px;

        }


        .order-total {

            color: #2e7d32;

            font-size: 20px;

            font-weight: bold;

        }


        /* =============================
           ACTIONS
        ============================= */

        .order-actions {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        /* =============================
           REORDER
        ============================= */

        .reorder-btn {

            display: inline-block;

            background: #2e7d32;

            color: white;

            padding: 9px 15px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

            border: 1px solid #2e7d32;

        }


        .reorder-btn:hover {

            background: #1b5e20;

            border-color: #1b5e20;

        }


        /* =============================
           CANCEL
        ============================= */

        .cancel-btn {

            display: inline-block;

            background: white;

            border: 1px solid #c62828;

            color: #c62828;

            padding: 9px 15px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;

        }


        .cancel-btn:hover {

            background: #c62828;

            color: white;

        }


        /* =============================
           NO ORDERS
        ============================= */

        .no-orders {

            background: white;

            border-radius: 12px;

            padding: 60px 20px;

            text-align: center;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        .no-orders h2 {

            color: #163a24;

            margin-bottom: 10px;

        }


        .no-orders p {

            color: #777;

            margin-bottom: 20px;

        }


        .browse-btn {

            display: inline-block;

            background: #2e7d32;

            color: white;

            text-decoration: none;

            padding: 12px 20px;

            border-radius: 6px;

            font-weight: bold;

        }


        .browse-btn:hover {

            background: #1b5e20;

        }


        /* =============================
           MOBILE
        ============================= */

        @media (max-width: 900px) {

            .order-details {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .orders-page {

                padding: 25px 15px;

            }

        }


        @media (max-width: 600px) {

            .order-top {

                align-items: flex-start;

                flex-direction: column;

            }


            .order-details {

                grid-template-columns: 1fr;

            }


            .order-bottom {

                align-items: flex-start;

                flex-direction: column;

            }


            .order-actions {

                width: 100%;

                flex-direction: column;

                align-items: stretch;

            }


            .reorder-btn,
            .cancel-btn {

                width: 100%;

                text-align: center;

                box-sizing: border-box;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =============================
     CUSTOMER SIDEBAR
============================= -->

<aside class="sidebar">


    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Customer Panel
        </p>

    </div>


    <nav class="sidebar-menu">


        <a
            href="dashboard.php"
            class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Dashboard

        </a>


        <a
            href="products.php"
            class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Products

        </a>


        <a
            href="markets.php"
            class="<?php echo $current_page === 'markets.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Markets

        </a>


        <a
            href="cart.php"
            class="<?php echo $current_page === 'cart.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Cart

        </a>


        <a
            href="orders.php"
            class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
        >

            <span></span>

            My Orders

        </a>


        <a
            href="favorites.php"
            class="<?php echo $current_page === 'favorites.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Favorites

        </a>


        <a
            href="notifications.php"
            class="<?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Notifications

        </a>


        <a
            href="profile.php"
            class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Profile

        </a>


    </nav>


    <div class="sidebar-bottom">


        <a href="../auth/logout.php">

            <span></span>

            Logout

        </a>


    </div>


</aside>


<!-- =============================
     MAIN CONTENT
============================= -->

<main class="customer-main">


    <div class="orders-page">


        <div class="orders-header">

            <h1>
                My Orders
            </h1>

            <p>
                Track your pre-orders and pickup status.
            </p>

        </div>


        <!-- =============================
             SUCCESS
        ============================= -->

        <?php if (isset($_GET['success'])): ?>

            <div class="success-message">

                Your order has been placed successfully.

            </div>

        <?php endif; ?>


        <!-- =============================
             CANCELLED
        ============================= -->

        <?php if (isset($_GET['cancelled'])): ?>

            <div class="success-message">

                Your order has been cancelled successfully.

            </div>

        <?php endif; ?>


        <!-- =============================
             REORDER UNAVAILABLE
        ============================= -->

        <?php if (
            isset($_GET['reorder']) &&
            $_GET['reorder'] === 'unavailable'
        ): ?>

            <div class="error-message">

                This product is currently sold out
                or unavailable.

            </div>

        <?php endif; ?>


        <!-- =============================
             REORDER NOT FOUND
        ============================= -->

        <?php if (
            isset($_GET['reorder']) &&
            $_GET['reorder'] === 'notfound'
        ): ?>

            <div class="error-message">

                This product could not be found.

            </div>

        <?php endif; ?>


        <!-- =============================
             ORDERS
        ============================= -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="orders-container">


                <?php while (
                    $order = mysqli_fetch_assoc($result)
                ): ?>


                    <div class="order-card">


                        <!-- ORDER TOP -->

                        <div class="order-top">


                            <div>

                                <div class="order-id">

                                    Order #

                                    <?php
                                    echo (int)
                                        $order['order_id'];
                                    ?>

                                </div>


                                <div class="order-date">

                                    Placed on:

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


                            <!-- STATUS -->

                            <?php

                            $status_class =
                                strtolower(
                                    $order['status']
                                );

                            ?>


                            <span
                                class="status <?php echo htmlspecialchars($status_class); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $order['status']
                                );

                                ?>

                            </span>


                        </div>


                        <!-- ORDER DETAILS -->

                        <div class="order-details">


                            <div class="detail-box">

                                <span>
                                    Product
                                </span>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['product_name']
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Farmer
                                </span>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['farmer_name']
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Market
                                </span>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['market_name']
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Quantity
                                </span>

                                <strong>

                                    <?php

                                    echo (int)
                                        $order['quantity'];

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Pickup Date
                                </span>

                                <strong>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $order['pickup_date']
                                        )
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Pickup Time
                                </span>

                                <strong>

                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $order['pickup_time']
                                        )
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Location
                                </span>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['location']
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Category
                                </span>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['category']
                                    );

                                    ?>

                                </strong>

                            </div>


                        </div>


                        <!-- ORDER BOTTOM -->

                        <div class="order-bottom">


                            <div class="order-total">

                                Total:

                                Rs.

                                <?php

                                echo number_format(
                                    $order['total_price'],
                                    2
                                );

                                ?>

                            </div>


                            <div class="order-actions">


                                <!-- REORDER -->

                                <?php if (
                                    $order['status'] === 'Completed'
                                ): ?>

                                    <a
                                        href="reorder.php?product_id=<?php echo (int) $order['product_id']; ?>"
                                        class="reorder-btn"
                                    >

                                         Reorder

                                    </a>

                                <?php endif; ?>


                                <!-- CANCEL -->

                                <?php if (
                                    $order['status'] === 'Placed'
                                ): ?>

                                    <a
                                        href="orders.php?cancel=<?php echo (int) $order['order_id']; ?>"
                                        class="cancel-btn"
                                        onclick="return confirm('Are you sure you want to cancel this order?');"
                                    >

                                        Cancel Order

                                    </a>

                                <?php endif; ?>


                            </div>


                        </div>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- NO ORDERS -->

            <div class="no-orders">


                <h2>
                    No Orders Yet
                </h2>


                <p>
                    You have not placed any orders yet.
                </p>


                <a
                    href="products.php"
                    class="browse-btn"
                >

                    Browse Products

                </a>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>