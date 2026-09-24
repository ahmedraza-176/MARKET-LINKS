<?php

session_start();

require_once "../config/connection.php";


// =========================================
// CUSTOMER LOGIN CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");
    exit();

}

if ($_SESSION['role'] !== 'Customer') {

    header("Location: ../auth/login.php");
    exit();

}

$user_id = (int) $_SESSION['user_id'];


// =========================================
// ACTIVE PAGE
// =========================================

$current_page = basename($_SERVER['PHP_SELF']);


// =========================================
// GET NOTIFICATIONS
// =========================================

$sql = "
    SELECT 
        n.notification_id,
        n.message,
        n.is_read,
        n.created_at,

        p.product_id,
        p.product_name,
        p.image_name

    FROM Notifications n

    LEFT JOIN Products p
        ON n.product_id = p.product_id

    WHERE n.user_id = ?

    ORDER BY n.created_at DESC
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die("Database error: " . mysqli_error($conn));

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// =========================================
// UNREAD COUNT
// =========================================

$count_sql = "
    SELECT COUNT(*) AS unread_count

    FROM Notifications

    WHERE user_id = ?

    AND is_read = 0
";


$count_stmt = mysqli_prepare(
    $conn,
    $count_sql
);


mysqli_stmt_bind_param(
    $count_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($count_stmt);


$count_result = mysqli_stmt_get_result(
    $count_stmt
);


$count_data = mysqli_fetch_assoc(
    $count_result
);


$unread_count =
    (int) $count_data['unread_count'];

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
        Notifications - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/customers.css"
    >


    <style>

        /* =====================================
           NOTIFICATIONS PAGE
        ===================================== */

        .notifications-container {

            max-width: 1100px;

            margin: 0 auto;

            padding: 40px 25px;

        }


        /* =====================================
           HEADER
        ===================================== */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

        }


        .page-header h1 {

            color: #163a24;

            font-size: 28px;

        }


        .unread-badge {

            background: #2e7d32;

            color: white;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 14px;

            font-weight: bold;

        }


        /* =====================================
           NOTIFICATIONS LIST
        ===================================== */

        .notifications-list {

            display: flex;

            flex-direction: column;

            gap: 15px;

        }


        /* =====================================
           NOTIFICATION CARD
        ===================================== */

        .notification {

            background: white;

            border-radius: 10px;

            padding: 18px;

            display: flex;

            align-items: center;

            gap: 18px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.07);

            border-left: 4px solid transparent;

        }


        /* UNREAD */

        .notification.unread {

            border-left-color: #2e7d32;

            background: #f0f8f1;

        }


        /* =====================================
           NOTIFICATION ICON
        ===================================== */

        .notification-icon {

            width: 48px;

            height: 48px;

            min-width: 48px;

            border-radius: 50%;

            background: #e8f5e9;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 23px;

        }


        /* =====================================
           CONTENT
        ===================================== */

        .notification-content {

            flex: 1;

        }


        .notification-content h3 {

            color: #163a24;

            font-size: 17px;

            margin-bottom: 6px;

        }


        .notification-content p {

            color: #555;

            font-size: 14px;

            line-height: 1.5;

            margin-bottom: 6px;

        }


        .notification-time {

            color: #888;

            font-size: 12px;

        }


        /* =====================================
           ACTION
        ===================================== */

        .notification-action {

            flex-shrink: 0;

        }


        .read-btn {

            display: inline-block;

            background: #198754;

            color: white;

            text-decoration: none;

            padding: 8px 13px;

            border-radius: 6px;

            font-size: 13px;

        }


        .read-btn:hover {

            background: #146c43;

        }


        .read-label {

            color: #777;

            font-size: 13px;

        }


        /* =====================================
           PRODUCT LINK
        ===================================== */

        .product-link {

            color: #2e7d32;

            font-weight: bold;

            text-decoration: none;

        }


        .product-link:hover {

            text-decoration: underline;

        }


        /* =====================================
           EMPTY STATE
        ===================================== */

        .empty-box {

            background: white;

            padding: 60px 20px;

            text-align: center;

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.07);

        }


        .empty-icon {

            font-size: 50px;

            margin-bottom: 15px;

        }


        .empty-box h2 {

            color: #163a24;

            margin-bottom: 8px;

        }


        .empty-box p {

            color: #777;

        }


        .back-btn {

            display: inline-block;

            margin-top: 20px;

            background: #198754;

            color: white;

            text-decoration: none;

            padding: 10px 18px;

            border-radius: 6px;

        }


        .back-btn:hover {

            background: #146c43;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 700px) {

            .notifications-container {

                padding: 25px 15px;

            }


            .page-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .notification {

                align-items: flex-start;

                flex-direction: column;

            }


            .notification-action {

                width: 100%;

            }


            .read-btn {

                display: block;

                text-align: center;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =====================================
     CUSTOMER SIDEBAR
===================================== -->

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

            <?php if ($unread_count > 0): ?>

                <small class="notification-count">

                    <?php echo $unread_count; ?>

                </small>

            <?php endif; ?>

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


<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="customer-main">


    <div class="notifications-container">


        <!-- =====================================
             PAGE HEADER
        ====================================== -->

        <div class="page-header">


            <h1>

                 Notifications

            </h1>


            <?php if ($unread_count > 0): ?>

                <span class="unread-badge">

                    <?php echo $unread_count; ?>

                    Unread

                </span>

            <?php endif; ?>


        </div>


        <!-- =====================================
             NOTIFICATIONS
        ====================================== -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="notifications-list">


                <?php while (
                    $notification =
                    mysqli_fetch_assoc($result)
                ): ?>


                    <div
                        class="notification
                        <?php
                        echo $notification['is_read'] == 0
                            ? 'unread'
                            : '';
                        ?>"
                    >


                        <!-- ICON -->

                        <div class="notification-icon">

                            

                        </div>


                        <!-- CONTENT -->

                        <div class="notification-content">


                            <h3>

                                MarketLink Notification

                            </h3>


                            <p>


                                <?php

                                echo htmlspecialchars(
                                    $notification['message']
                                );

                                ?>


                                <?php if (
                                    !empty(
                                        $notification['product_id']
                                    )
                                ): ?>


                                    <br>


                                    <a
                                        href="products.php"
                                        class="product-link"
                                    >

                                        View Product

                                    </a>


                                <?php endif; ?>


                            </p>


                            <span
                                class="notification-time"
                            >

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $notification['created_at']
                                    )
                                );

                                ?>

                            </span>


                        </div>


                        <!-- ACTION -->

                        <div class="notification-action">


                            <?php if (
                                $notification['is_read'] == 0
                            ): ?>


                                <a
                                    href="mark_notification_read.php?id=<?php echo (int) $notification['notification_id']; ?>"
                                    class="read-btn"
                                >

                                    Mark as Read

                                </a>


                            <?php else: ?>


                                <span class="read-label">

                                     Read

                                </span>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- =====================================
                 EMPTY STATE
            ====================================== -->

            <div class="empty-box">


                <div class="empty-icon">

                    

                </div>


                <h2>

                    No Notifications

                </h2>


                <p>

                    You don't have any notifications yet.

                </p>


                <a
                    href="products.php"
                    class="back-btn"
                >

                    Browse Products

                </a>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

mysqli_stmt_close($count_stmt);

?>