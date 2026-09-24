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


// =============================
// ACTIVE SIDEBAR PAGE
// =============================

$current_page = basename($_SERVER['PHP_SELF']);


// =============================
// CREATE CART SESSION
// =============================

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// =============================
// ADD TO CART
// =============================

if (isset($_GET['add'])) {

    $product_id = (int) $_GET['add'];

    if ($product_id > 0) {

        $query = "
            SELECT
                product_id,
                product_name,
                price,
                stock
            FROM Products
            WHERE product_id = ?
            AND status = 'Available'
            AND stock > 0
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $query);

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $product_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {

            $product = mysqli_fetch_assoc($result);

            if (isset($_SESSION['cart'][$product_id])) {

                if (
                    $_SESSION['cart'][$product_id]['quantity']
                    < (int) $product['stock']
                ) {

                    $_SESSION['cart'][$product_id]['quantity']++;

                }

            } else {

                $_SESSION['cart'][$product_id] = [

                    'product_id' => (int) $product['product_id'],

                    'product_name' => $product['product_name'],

                    'price' => (float) $product['price'],

                    'quantity' => 1,

                    'stock' => (int) $product['stock']

                ];

            }

        }

    }

    header("Location: cart.php");
    exit();
}


// =============================
// INCREASE QUANTITY
// =============================

if (isset($_GET['increase'])) {

    $product_id = (int) $_GET['increase'];

    if (isset($_SESSION['cart'][$product_id])) {

        $query = "
            SELECT stock
            FROM Products
            WHERE product_id = ?
            AND status = 'Available'
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $query);

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $product_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {

            $product = mysqli_fetch_assoc($result);

            $current_quantity =
                (int) $_SESSION['cart'][$product_id]['quantity'];

            $current_stock =
                (int) $product['stock'];

            if ($current_quantity < $current_stock) {

                $_SESSION['cart'][$product_id]['quantity']++;

            }

        }

    }

    header("Location: cart.php");
    exit();
}


// =============================
// DECREASE QUANTITY
// =============================

if (isset($_GET['decrease'])) {

    $product_id = (int) $_GET['decrease'];

    if (isset($_SESSION['cart'][$product_id])) {

        if (
            $_SESSION['cart'][$product_id]['quantity'] > 1
        ) {

            $_SESSION['cart'][$product_id]['quantity']--;

        } else {

            unset($_SESSION['cart'][$product_id]);

        }

    }

    header("Location: cart.php");
    exit();
}


// =============================
// REMOVE PRODUCT
// =============================

if (isset($_GET['remove'])) {

    $product_id = (int) $_GET['remove'];

    if (isset($_SESSION['cart'][$product_id])) {

        unset($_SESSION['cart'][$product_id]);

    }

    header("Location: cart.php");
    exit();
}


// =============================
// CLEAR CART
// =============================

if (isset($_GET['clear'])) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit();
}


// =============================
// CALCULATE GRAND TOTAL
// =============================

$grand_total = 0;

foreach ($_SESSION['cart'] as $item) {

    $grand_total +=
        (float) $item['price']
        * (int) $item['quantity'];

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Cart - MarketLink</title>

    <link
        rel="stylesheet"
        href="../css/customers.css"
    >

    <style>

        /* =====================================
           CART PAGE
        ===================================== */

        .cart-page {
            max-width: 1150px;
            margin: 0 auto;
        }


        /* =====================================
           CART HEADER
        ===================================== */

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 25px;
        }

        .cart-header h1 {
            color: #163a24;
            font-size: 30px;
        }

        .continue-shopping {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .continue-shopping:hover {
            text-decoration: underline;
        }


        /* =====================================
           CART BOX
        ===================================== */

        .cart-box {
            background: #ffffff;

            border-radius: 12px;

            padding: 25px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);
        }


        /* =====================================
           CART ITEM
        ===================================== */

        .cart-item {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                150px
                130px
                80px;

            gap: 20px;

            align-items: center;

            padding: 20px 0;

            border-bottom: 1px solid #eeeeee;
        }

        .cart-item:last-of-type {
            border-bottom: none;
        }


        /* =====================================
           ITEM NAME
        ===================================== */

        .item-name {
            color: #163a24;

            font-size: 17px;

            font-weight: bold;

            line-height: 1.4;
        }

        .item-price {
            color: #777;

            margin-top: 5px;

            font-size: 13px;
        }


        /* =====================================
           QUANTITY
        ===================================== */

        .quantity {
            display: flex;

            align-items: center;

            justify-content: center;

            gap: 10px;
        }

        .quantity a {
            width: 32px;
            height: 32px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #e8f5e9;

            color: #2e7d32;

            text-decoration: none;

            border-radius: 5px;

            font-size: 18px;

            font-weight: bold;

            transition: 0.2s ease;
        }

        .quantity a:hover {
            background: #c8e6c9;
        }

        .quantity span {
            min-width: 25px;

            text-align: center;

            color: #333;

            font-weight: bold;
        }


        /* =====================================
           ITEM TOTAL
        ===================================== */

        .item-total {
            color: #2e7d32;

            font-size: 15px;

            font-weight: bold;

            text-align: right;
        }


        /* =====================================
           REMOVE BUTTON
        ===================================== */

        .remove-btn {
            color: #c62828;

            text-decoration: none;

            font-size: 13px;

            font-weight: bold;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }


        /* =====================================
           CART SUMMARY
        ===================================== */

        .cart-summary {
            border-top: 2px solid #eeeeee;

            margin-top: 20px;

            padding-top: 25px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .total-text {
            color: #163a24;

            font-size: 22px;

            font-weight: bold;
        }


        /* =====================================
           CHECKOUT BUTTON
        ===================================== */

        .checkout-btn {
            display: inline-block;

            background: #2e7d32;

            color: #ffffff;

            text-decoration: none;

            padding: 13px 24px;

            border-radius: 6px;

            font-size: 14px;

            font-weight: bold;

            transition: 0.2s ease;
        }

        .checkout-btn:hover {
            background: #245f27;
        }


        /* =====================================
           CLEAR CART
        ===================================== */

        .clear-btn {
            display: inline-block;

            margin-top: 20px;

            color: #c62828;

            text-decoration: none;

            font-size: 14px;

            font-weight: bold;
        }

        .clear-btn:hover {
            text-decoration: underline;
        }


        /* =====================================
           EMPTY CART
        ===================================== */

        .empty-cart {
            text-align: center;

            padding: 60px 20px;
        }

        .empty-cart-icon {
            font-size: 50px;

            margin-bottom: 15px;
        }

        .empty-cart h2 {
            color: #163a24;

            margin-bottom: 10px;

            font-size: 24px;
        }

        .empty-cart p {
            color: #777;

            margin-bottom: 25px;

            font-size: 14px;
        }


        /* =====================================
           BROWSE BUTTON
        ===================================== */

        .browse-btn {
            display: inline-block;

            background: #2e7d32;

            color: #ffffff;

            text-decoration: none;

            padding: 12px 22px;

            border-radius: 6px;

            font-weight: bold;

            font-size: 14px;
        }

        .browse-btn:hover {
            background: #245f27;
        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 800px) {

            .cart-item {
                grid-template-columns:
                    1fr
                    auto
                    auto
                    auto;

                gap: 15px;
            }

            .item-total {
                text-align: left;
            }

            .quantity {
                justify-content: flex-start;
            }

        }


        @media (max-width: 600px) {

            .cart-header {
                flex-direction: column;

                align-items: flex-start;

                gap: 10px;
            }

            .cart-header h1 {
                font-size: 26px;
            }

            .cart-box {
                padding: 18px;
            }

            .cart-item {
                display: block;

                padding: 20px 0;
            }

            .item-name {
                margin-bottom: 5px;
            }

            .quantity {
                justify-content: flex-start;

                margin: 15px 0;
            }

            .item-total {
                text-align: left;

                margin-bottom: 10px;
            }

            .cart-summary {
                flex-direction: column;

                align-items: flex-start;

                gap: 20px;
            }

            .checkout-btn {
                width: 100%;

                text-align: center;

                box-sizing: border-box;
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


    <!-- LOGO -->

    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Customer Panel
        </p>

    </div>


    <!-- MENU -->

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


    <!-- LOGOUT -->

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


    <div class="cart-page">


        <!-- =================================
             CART HEADER
        ================================= -->

        <div class="cart-header">

            <h1>
                My Cart
            </h1>

            <a
                href="products.php"
                class="continue-shopping"
            >
                ← Continue Shopping
            </a>

        </div>


        <!-- =================================
             CART BOX
        ================================= -->

        <div class="cart-box">


            <?php if (empty($_SESSION['cart'])): ?>


                <!-- =================================
                     EMPTY CART
                ================================= -->

                <div class="empty-cart">

                    <div class="empty-cart-icon">
                        
                    </div>

                    <h2>
                        Your cart is empty
                    </h2>

                    <p>
                        Browse products and add something to your cart.
                    </p>

                    <a
                        href="products.php"
                        class="browse-btn"
                    >
                        Browse Products
                    </a>

                </div>


            <?php else: ?>


                <!-- =================================
                     CART ITEMS
                ================================= -->

                <?php foreach ($_SESSION['cart'] as $item): ?>


                    <div class="cart-item">


                        <!-- PRODUCT -->

                        <div>

                            <div class="item-name">

                                <?php
                                echo htmlspecialchars(
                                    $item['product_name']
                                );
                                ?>

                            </div>

                            <div class="item-price">

                                Rs.
                                <?php
                                echo number_format(
                                    $item['price'],
                                    2
                                );
                                ?>

                                per unit

                            </div>

                        </div>


                        <!-- QUANTITY -->

                        <div class="quantity">

                            <a
                                href="cart.php?decrease=<?php echo (int) $item['product_id']; ?>"
                                title="Decrease quantity"
                            >
                                −
                            </a>


                            <span>

                                <?php
                                echo (int) $item['quantity'];
                                ?>

                            </span>


                            <a
                                href="cart.php?increase=<?php echo (int) $item['product_id']; ?>"
                                title="Increase quantity"
                            >
                                +
                            </a>

                        </div>


                        <!-- ITEM TOTAL -->

                        <div class="item-total">

                            Rs.
                            <?php
                            echo number_format(
                                $item['price']
                                * $item['quantity'],
                                2
                            );
                            ?>

                        </div>


                        <!-- REMOVE -->

                        <div>

                            <a
                                href="cart.php?remove=<?php echo (int) $item['product_id']; ?>"
                                class="remove-btn"
                            >
                                Remove
                            </a>

                        </div>


                    </div>


                <?php endforeach; ?>


                <!-- =================================
                     CART SUMMARY
                ================================= -->

                <div class="cart-summary">


                    <div class="total-text">

                        Total:

                        Rs.
                        <?php
                        echo number_format(
                            $grand_total,
                            2
                        );
                        ?>

                    </div>


                    <a
                        href="checkout.php"
                        class="checkout-btn"
                    >
                        Proceed to Pre-order
                    </a>


                </div>


                <!-- CLEAR CART -->

                <a
                    href="cart.php?clear=1"
                    class="clear-btn"
                    onclick="return confirm('Are you sure you want to clear your cart?');"
                >
                    Clear Cart
                </a>


            <?php endif; ?>


        </div>


    </div>


</main>


</body>

</html>