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

$success = "";
$error = "";


// =========================================
// ACTIVE PAGE
// =========================================

$current_page = basename($_SERVER['PHP_SELF']);


// =========================================
// UPDATE WEEKLY STOCK / PRICE
// =========================================

if (isset($_POST['update_product'])) {

    $product_id = (int) ($_POST['product_id'] ?? 0);

    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');


    // =====================================
    // VALIDATION
    // =====================================

    if ($product_id <= 0) {

        $error = "Invalid product.";

    }

    elseif ($price === "" || $stock === "") {

        $error = "Price and stock are required.";

    }

    elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    }

    elseif (!is_numeric($stock) || $stock < 0) {

        $error = "Please enter a valid stock quantity.";

    }

    else {

        $price = (float) $price;
        $stock = (int) $stock;


        // =====================================
        // CHECK PRODUCT BELONGS TO FARMER
        // =====================================

        $check_query = "
            SELECT
                product_id,
                product_name,
                stock,
                status
            FROM Products
            WHERE product_id = ?
            AND farmer_id = ?
            LIMIT 1
        ";


        $stmt = mysqli_prepare(
            $conn,
            $check_query
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $product_id,
            $farmer_id
        );


        mysqli_stmt_execute($stmt);


        $result = mysqli_stmt_get_result($stmt);


        $product = mysqli_fetch_assoc($result);


        if (!$product) {

            $error = "Product not found.";

        }

        else {


            // =====================================
            // AUTOMATIC STATUS
            // =====================================

            if ($stock > 0) {

                $status = "Available";

            } else {

                $status = "Sold Out";

            }


            // =====================================
            // UPDATE PRODUCT
            // =====================================

            $update_query = "
                UPDATE Products
                SET
                    price = ?,
                    stock = ?,
                    status = ?
                WHERE product_id = ?
                AND farmer_id = ?
            ";


            $stmt = mysqli_prepare(
                $conn,
                $update_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "disii",
                $price,
                $stock,
                $status,
                $product_id,
                $farmer_id
            );


            if (mysqli_stmt_execute($stmt)) {

                // =================================
                // RESTOCK NOTIFICATION
                // =================================

                $old_stock = (int) $product['stock'];
                $old_status = $product['status'];


                if (
                    (
                        $old_stock <= 0 ||
                        $old_status === "Sold Out"
                    )
                    &&
                    $stock > 0
                ) {


                    // Find customers who favorited
                    // this product

                    $favorite_query = "
                        SELECT DISTINCT user_id
                        FROM Favorites
                        WHERE product_id = ?
                    ";


                    $favorite_stmt =
                        mysqli_prepare(
                            $conn,
                            $favorite_query
                        );


                    mysqli_stmt_bind_param(
                        $favorite_stmt,
                        "i",
                        $product_id
                    );


                    mysqli_stmt_execute(
                        $favorite_stmt
                    );


                    $favorite_result =
                        mysqli_stmt_get_result(
                            $favorite_stmt
                        );


                    $notification_message =
                        $product['product_name']
                        . " is back in stock! "
                        . "You can now place your order.";


                    while (
                        $favorite =
                        mysqli_fetch_assoc(
                            $favorite_result
                        )
                    ) {

                        $customer_id =
                            (int) $favorite['user_id'];


                        $notification_query = "
                            INSERT INTO Notifications
                            (
                                user_id,
                                product_id,
                                message,
                                is_read
                            )
                            VALUES
                            (?, ?, ?, 0)
                        ";


                        $notification_stmt =
                            mysqli_prepare(
                                $conn,
                                $notification_query
                            );


                        mysqli_stmt_bind_param(
                            $notification_stmt,
                            "iis",
                            $customer_id,
                            $product_id,
                            $notification_message
                        );


                        mysqli_stmt_execute(
                            $notification_stmt
                        );

                    }

                }


                $success =
                    "Product stock and price updated successfully.";

            }

            else {

                $error =
                    "Unable to update product. Please try again.";

            }

        }

    }

}


// =========================================
// FETCH FARMER PRODUCTS
// =========================================

$query = "
    SELECT
        p.product_id,
        p.product_name,
        p.category,
        p.price,
        p.stock,
        p.status,
        p.image_name,

        m.market_name,
        m.location

    FROM Products p

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    WHERE p.farmer_id = ?

    ORDER BY p.product_id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);


mysqli_stmt_execute($stmt);


$products_result =
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
        Weekly Stock - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/farmer.css"
    >


    <style>

        /* =====================================
           PAGE
        ===================================== */

        .farmer-main {

            margin-left: 240px;

            min-height: 100vh;

            padding: 35px;

            background: #f4f6f8;

        }


        .weekly-page {

            max-width: 1200px;

            margin: 0 auto;

        }


        /* =====================================
           PAGE HEADER
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
           MESSAGES
        ===================================== */

        .success-message {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 13px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            border-left: 4px solid #2e7d32;

        }


        .error-message {

            background: #ffebee;

            color: #c62828;

            padding: 13px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            border-left: 4px solid #c62828;

        }


        /* =====================================
           INFO BOX
        ===================================== */

        .info-box {

            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.05);

        }


        .info-box h3 {

            margin: 0 0 8px;

            color: #163a24;

        }


        .info-box p {

            margin: 0;

            color: #777;

            line-height: 1.6;

        }


        /* =====================================
           PRODUCTS GRID
        ===================================== */

        .products-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(330px, 1fr));

            gap: 20px;

        }


        /* =====================================
           PRODUCT CARD
        ===================================== */

        .product-card {

            background: white;

            border-radius: 12px;

            padding: 22px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        .product-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 18px;

        }


        .product-name {

            margin: 0 0 6px;

            color: #163a24;

            font-size: 20px;

        }


        .product-category {

            color: #777;

            font-size: 13px;

        }


        /* =====================================
           STATUS
        ===================================== */

        .status {

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            white-space: nowrap;

        }


        .status.available {

            background: #e8f5e9;

            color: #2e7d32;

        }


        .status.sold-out {

            background: #ffebee;

            color: #c62828;

        }


        /* =====================================
           PRODUCT DETAILS
        ===================================== */

        .product-details {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-bottom: 20px;

        }


        .detail-box {

            background: #f7f9f8;

            padding: 12px;

            border-radius: 8px;

        }


        .detail-box small {

            display: block;

            color: #888;

            margin-bottom: 5px;

        }


        .detail-box strong {

            color: #163a24;

        }


        .market-info {

            margin-bottom: 20px;

            padding: 12px;

            background: #f7f9f8;

            border-radius: 8px;

            font-size: 13px;

            color: #555;

        }


        /* =====================================
           FORM
        ===================================== */

        .update-form {

            border-top: 1px solid #eee;

            padding-top: 20px;

        }


        .form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-bottom: 15px;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: bold;

            color: #444;

        }


        .form-group input {

            width: 100%;

            box-sizing: border-box;

            padding: 11px 12px;

            border: 1px solid #ddd;

            border-radius: 7px;

            outline: none;

            font-size: 14px;

        }


        .form-group input:focus {

            border-color: #2e7d32;

        }


        .update-btn {

            width: 100%;

            border: none;

            background: #2e7d32;

            color: white;

            padding: 12px;

            border-radius: 7px;

            font-size: 14px;

            font-weight: bold;

            cursor: pointer;

        }


        .update-btn:hover {

            background: #245f27;

        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty-box {

            background: white;

            padding: 50px 20px;

            text-align: center;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.05);

        }


        .empty-box h3 {

            color: #163a24;

            margin-bottom: 8px;

        }


        .empty-box p {

            color: #777;

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

            transition: 0.2s;

        }


        .sidebar-menu a:hover,
        .sidebar-bottom a:hover {

            background: rgba(255,255,255,0.08);

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


        @media (max-width: 500px) {

            .products-grid {

                grid-template-columns: 1fr;

            }


            .form-row {

                grid-template-columns: 1fr;

            }


            .product-details {

                grid-template-columns: 1fr;

            }


            .sidebar-menu {

                grid-template-columns: 1fr;

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


    <div class="weekly-page">


        <!-- =====================================
             HEADER
        ===================================== -->

        <div class="page-header">

            <h1>
                Weekly Stock & Pricing
            </h1>

            <p>
                Manage your product prices and available stock.
            </p>

        </div>


        <!-- =====================================
             SUCCESS
        ===================================== -->

        <?php if ($success !== ""): ?>

            <div class="success-message">

                <?php

                echo htmlspecialchars(
                    $success
                );

                ?>

            </div>

        <?php endif; ?>


        <!-- =====================================
             ERROR
        ===================================== -->

        <?php if ($error !== ""): ?>

            <div class="error-message">

                <?php

                echo htmlspecialchars(
                    $error
                );

                ?>

            </div>

        <?php endif; ?>


        <!-- =====================================
             INFO
        ===================================== -->

        <div class="info-box">

            <h3>
                Manage This Week's Stock
            </h3>

            <p>
                Update the current price and stock
                quantity for each product. If stock is
                set to 0, the product will automatically
                become Sold Out. If stock is greater than
                0, it will become Available.
            </p>

        </div>


        <!-- =====================================
             PRODUCTS
        ===================================== -->

        <?php if (mysqli_num_rows($products_result) > 0): ?>


            <div class="products-grid">


                <?php while (
                    $product =
                    mysqli_fetch_assoc($products_result)
                ): ?>


                    <div class="product-card">


                        <!-- PRODUCT TOP -->

                        <div class="product-top">


                            <div>

                                <h2 class="product-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );

                                    ?>

                                </h2>


                                <div class="product-category">

                                    <?php

                                    echo htmlspecialchars(
                                        $product['category']
                                    );

                                    ?>

                                </div>

                            </div>


                            <?php if (
                                $product['status']
                                === "Available"
                            ): ?>

                                <span class="status available">

                                    Available

                                </span>

                            <?php else: ?>

                                <span class="status sold-out">

                                    Sold Out

                                </span>

                            <?php endif; ?>


                        </div>


                        <!-- =================================
                             CURRENT DETAILS
                        ================================= -->

                        <div class="product-details">


                            <div class="detail-box">

                                <small>
                                    Current Price
                                </small>

                                <strong>

                                    Rs.
                                    <?php

                                    echo number_format(
                                        (float)
                                        $product['price'],
                                        2
                                    );

                                    ?>

                                </strong>

                            </div>


                            <div class="detail-box">

                                <small>
                                    Current Stock
                                </small>

                                <strong>

                                    <?php

                                    echo (int)
                                        $product['stock'];

                                    ?>

                                </strong>

                            </div>


                        </div>


                        <!-- =================================
                             MARKET
                        ================================= -->

                        <div class="market-info">

                            <strong>
                                Market:
                            </strong>

                            <?php

                            echo htmlspecialchars(
                                $product['market_name']
                            );

                            ?>


                            <?php if (
                                !empty($product['location'])
                            ): ?>

                                <br>

                                <strong>
                                    Location:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $product['location']
                                );

                                ?>

                            <?php endif; ?>

                        </div>


                        <!-- =================================
                             UPDATE FORM
                        ================================= -->

                        <form
                            method="POST"
                            action=""
                            class="update-form"
                        >


                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php
                                    echo (int)
                                        $product['product_id'];
                                ?>"
                            >


                            <div class="form-row">


                                <!-- PRICE -->

                                <div class="form-group">

                                    <label
                                        for="price_<?php
                                            echo (int)
                                                $product['product_id'];
                                        ?>"
                                    >

                                        Price

                                    </label>


                                    <input
                                        type="number"
                                        id="price_<?php
                                            echo (int)
                                                $product['product_id'];
                                        ?>"
                                        name="price"
                                        value="<?php
                                            echo htmlspecialchars(
                                                $product['price']
                                            );
                                        ?>"
                                        min="0"
                                        step="0.01"
                                        required
                                    >

                                </div>


                                <!-- STOCK -->

                                <div class="form-group">

                                    <label
                                        for="stock_<?php
                                            echo (int)
                                                $product['product_id'];
                                        ?>"
                                    >

                                        Stock

                                    </label>


                                    <input
                                        type="number"
                                        id="stock_<?php
                                            echo (int)
                                                $product['product_id'];
                                        ?>"
                                        name="stock"
                                        value="<?php
                                            echo (int)
                                                $product['stock'];
                                        ?>"
                                        min="0"
                                        required
                                    >

                                </div>


                            </div>


                            <button
                                type="submit"
                                name="update_product"
                                class="update-btn"
                            >

                                Update Stock & Price

                            </button>


                        </form>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- =================================
                 EMPTY STATE
            ================================= -->

            <div class="empty-box">

                <h3>
                    No Products Found
                </h3>

                <p>
                    You don't have any products yet.
                    Add a product first from the Products page.
                </p>

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>