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
// GET CUSTOMER FAVORITES
// =========================================

$sql = "
    SELECT
        f.favorite_id,
        p.product_id,
        p.product_name,
        p.category,
        p.price,
        p.stock,
        p.status,
        p.image_name,

        u.name AS farmer_name,

        m.market_name,
        m.location AS market_location

    FROM Favorites f

    INNER JOIN Products p
        ON f.product_id = p.product_id

    INNER JOIN Users u
        ON p.farmer_id = u.user_id

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    WHERE f.user_id = ?

    ORDER BY f.created_at DESC
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
        My Favorites - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/customers.css"
    >


    <style>

        /* =====================================
           FAVORITES PAGE
        ===================================== */

        .favorites-container {

            max-width: 1200px;

            margin: 0 auto;

            padding: 40px 25px;

        }


        /* =====================================
           HEADER
        ===================================== */

        .favorites-header {

            margin-bottom: 25px;

        }


        .favorites-header h1 {

            color: #163a24;

            margin-bottom: 6px;

        }


        .favorites-header p {

            color: #6b7280;

        }


        /* =====================================
           FAVORITES GRID
        ===================================== */

        .favorites-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(260px, 1fr));

            gap: 22px;

        }


        /* =====================================
           FAVORITE CARD
        ===================================== */

        .favorite-card {

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.06);

            transition: 0.2s ease;

        }


        .favorite-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.10);

        }


        /* =====================================
           PRODUCT IMAGE
        ===================================== */

        .favorite-image {

            width: 100%;

            height: 200px;

            object-fit: cover;

            display: block;

            background: #f3f4f6;

        }


        .no-image {

            height: 200px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3f4f6;

            color: #9ca3af;

            font-size: 14px;

        }


        /* =====================================
           CARD CONTENT
        ===================================== */

        .favorite-content {

            padding: 18px;

        }


        .favorite-content h3 {

            color: #1f2937;

            font-size: 19px;

            margin-bottom: 8px;

        }


        .category {

            display: inline-block;

            background: #e8f5e9;

            color: #198754;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 12px;

            margin-bottom: 12px;

        }


        .price {

            color: #198754;

            font-size: 20px;

            font-weight: 700;

            margin-bottom: 8px;

        }


        .favorite-info {

            color: #6b7280;

            font-size: 14px;

            line-height: 1.7;

            margin-bottom: 15px;

        }


        /* =====================================
           STATUS
        ===================================== */

        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            margin-bottom: 15px;

        }


        .available {

            background: #dcfce7;

            color: #166534;

        }


        .sold-out {

            background: #fee2e2;

            color: #b91c1c;

        }


        /* =====================================
           BUTTONS
        ===================================== */

        .favorite-actions {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .view-btn,
        .remove-btn {

            flex: 1;

            text-align: center;

            padding: 10px 12px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

        }


        .view-btn {

            background: #198754;

            color: #ffffff;

        }


        .view-btn:hover {

            background: #157347;

        }


        .remove-btn {

            background: #fee2e2;

            color: #b91c1c;

        }


        .remove-btn:hover {

            background: #fecaca;

        }


        /* =====================================
           EMPTY FAVORITES
        ===================================== */

        .empty-favorites {

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 60px 20px;

            text-align: center;

        }


        .empty-favorites h2 {

            color: #163a24;

            margin-bottom: 10px;

        }


        .empty-favorites p {

            color: #6b7280;

            margin-bottom: 20px;

        }


        .browse-btn {

            display: inline-block;

            background: #198754;

            color: #ffffff;

            text-decoration: none;

            padding: 11px 20px;

            border-radius: 7px;

            font-weight: 600;

            font-size: 14px;

        }


        .browse-btn:hover {

            background: #157347;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 700px) {

            .favorites-container {

                padding: 25px 15px;

            }

        }


        @media (max-width: 600px) {

            .favorites-header h1 {

                font-size: 25px;

            }


            .favorite-image,
            .no-image {

                height: 180px;

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


    <div class="favorites-container">


        <!-- =====================================
             HEADER
        ====================================== -->

        <div class="favorites-header">

            <h1>
                 My Favorites
            </h1>

            <p>
                Products you have saved for later.
            </p>

        </div>


        <!-- =====================================
             FAVORITES
        ====================================== -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="favorites-grid">


                <?php while (
                    $favorite = mysqli_fetch_assoc($result)
                ): ?>


                    <div class="favorite-card">


                        <!-- PRODUCT IMAGE -->

                        <?php if (
                            !empty($favorite['image_name']) &&
                            file_exists(
                                "../uploads/products/" .
                                $favorite['image_name']
                            )
                        ): ?>


                            <img
                                src="../uploads/products/<?php
                                    echo htmlspecialchars(
                                        $favorite['image_name']
                                    );
                                ?>"
                                alt="<?php
                                    echo htmlspecialchars(
                                        $favorite['product_name']
                                    );
                                ?>"
                                class="favorite-image"
                            >


                        <?php else: ?>


                            <div class="no-image">

                                No Image Available

                            </div>


                        <?php endif; ?>


                        <!-- CARD CONTENT -->

                        <div class="favorite-content">


                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $favorite['product_name']
                                );

                                ?>

                            </h3>


                            <!-- CATEGORY -->

                            <?php if (
                                !empty($favorite['category'])
                            ): ?>


                                <span class="category">

                                    <?php

                                    echo htmlspecialchars(
                                        $favorite['category']
                                    );

                                    ?>

                                </span>


                            <?php endif; ?>


                            <!-- PRICE -->

                            <div class="price">

                                Rs.

                                <?php

                                echo number_format(
                                    $favorite['price'],
                                    2
                                );

                                ?>

                            </div>


                            <!-- INFO -->

                            <div class="favorite-info">


                                <strong>
                                    Farmer:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $favorite['farmer_name']
                                );

                                ?>


                                <br>


                                <strong>
                                    Market:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $favorite['market_name']
                                );

                                ?>


                                <br>


                                <strong>
                                    Location:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $favorite['market_location']
                                );

                                ?>


                                <br>


                                <strong>
                                    Stock:
                                </strong>

                                <?php

                                echo (int)
                                    $favorite['stock'];

                                ?>


                            </div>


                            <!-- STATUS -->

                            <?php if (
                                $favorite['status'] === 'Available' &&
                                $favorite['stock'] > 0
                            ): ?>


                                <span class="status available">

                                    Available

                                </span>


                            <?php else: ?>


                                <span class="status sold-out">

                                    Sold Out

                                </span>


                            <?php endif; ?>


                            <!-- ACTIONS -->

                            <div class="favorite-actions">


                                <a
                                    href="products.php"
                                    class="view-btn"
                                >

                                    Browse Products

                                </a>


                                <a
                                    href="favorite_action.php?product_id=<?php
                                        echo (int)
                                            $favorite['product_id'];
                                    ?>"
                                    class="remove-btn"
                                >

                                     Remove

                                </a>


                            </div>


                        </div>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- =====================================
                 EMPTY STATE
            ====================================== -->

            <div class="empty-favorites">


                <h2>

                    No Favorites Yet

                </h2>


                <p>

                    You haven't added any products
                    to your favorites.

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

<?php

mysqli_stmt_close($stmt);

?>