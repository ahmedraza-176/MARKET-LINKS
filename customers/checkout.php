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
// CART CHECK
// =============================

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit();
}


$customer_id = $_SESSION['user_id'];

$error = "";


// =============================
// PLACE ORDER
// =============================

if (isset($_POST['place_order'])) {

    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];


    // =============================
    // VALIDATION
    // =============================

    if (empty($pickup_date) || empty($pickup_time)) {

        $error = "Please select pickup date and time.";

    } else {

        $selected_date = strtotime($pickup_date);

        $today = strtotime(date("Y-m-d"));


        if ($selected_date < $today) {

            $error = "Pickup date cannot be in the past.";

        } else {

            /*
             * Start transaction.
             * Either all cart items are ordered,
             * or none of them are.
             */

            mysqli_begin_transaction($conn);

            try {

                foreach ($_SESSION['cart'] as $item) {

                    $product_id = intval(
                        $item['product_id']
                    );

                    $quantity = intval(
                        $item['quantity']
                    );


                    // =============================
                    // GET CURRENT PRODUCT
                    // =============================

                    $product_query = "
                        SELECT
                            product_id,
                            price,
                            stock,
                            status
                        FROM Products
                        WHERE product_id = ?
                        LIMIT 1
                    ";

                    $product_stmt = mysqli_prepare(
                        $conn,
                        $product_query
                    );

                    mysqli_stmt_bind_param(
                        $product_stmt,
                        "i",
                        $product_id
                    );

                    mysqli_stmt_execute(
                        $product_stmt
                    );

                    $product_result =
                        mysqli_stmt_get_result(
                            $product_stmt
                        );


                    if (
                        mysqli_num_rows(
                            $product_result
                        ) !== 1
                    ) {

                        throw new Exception(
                            "A product in your cart no longer exists."
                        );

                    }


                    $product =
                        mysqli_fetch_assoc(
                            $product_result
                        );


                    // =============================
                    // CHECK PRODUCT STATUS
                    // =============================

                    if (
                        $product['status'] !== 'Available'
                    ) {

                        throw new Exception(
                            "One of the products is no longer available."
                        );

                    }


                    // =============================
                    // CHECK STOCK
                    // =============================

                    if (
                        $product['stock'] < $quantity
                    ) {

                        throw new Exception(
                            "Not enough stock available for one of your products."
                        );

                    }


                    // =============================
                    // CALCULATE TOTAL
                    // =============================

                    $total_price =
                        $product['price'] * $quantity;


                    // =============================
                    // INSERT ORDER
                    // =============================

                    $order_query = "
                        INSERT INTO Orders
                        (
                            customer_id,
                            product_id,
                            quantity,
                            total_price,
                            pickup_date,
                            pickup_time,
                            status
                        )
                        VALUES
                        (?, ?, ?, ?, ?, ?, 'Placed')
                    ";

                    $order_stmt = mysqli_prepare(
                        $conn,
                        $order_query
                    );

                    mysqli_stmt_bind_param(
                        $order_stmt,
                        "iiidss",
                        $customer_id,
                        $product_id,
                        $quantity,
                        $total_price,
                        $pickup_date,
                        $pickup_time
                    );


                    if (
                        !mysqli_stmt_execute(
                            $order_stmt
                        )
                    ) {

                        throw new Exception(
                            "Unable to place order."
                        );

                    }


                    // =============================
                    // UPDATE STOCK
                    // =============================

                    $new_stock =
                        $product['stock'] - $quantity;


                    $new_status =
                        $new_stock > 0
                            ? 'Available'
                            : 'Sold Out';


                    $stock_query = "
                        UPDATE Products
                        SET
                            stock = ?,
                            status = ?
                        WHERE product_id = ?
                    ";

                    $stock_stmt = mysqli_prepare(
                        $conn,
                        $stock_query
                    );

                    mysqli_stmt_bind_param(
                        $stock_stmt,
                        "isi",
                        $new_stock,
                        $new_status,
                        $product_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stock_stmt
                        )
                    ) {

                        throw new Exception(
                            "Unable to update product stock."
                        );

                    }

                }


                // =============================
                // EVERYTHING SUCCESSFUL
                // =============================

                mysqli_commit($conn);


                // Empty cart

                $_SESSION['cart'] = [];


                // Go to orders

                header(
                    "Location: orders.php?success=1"
                );

                exit();


            } catch (Exception $e) {

                // Undo database changes

                mysqli_rollback($conn);

                $error = $e->getMessage();

            }

        }

    }

}


// =============================
// CALCULATE CART TOTAL
// =============================

$grand_total = 0;

foreach ($_SESSION['cart'] as $item) {

    $grand_total +=
        $item['price'] * $item['quantity'];

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

    <title>Checkout - MarketLink</title>

    <link
        rel="stylesheet"
        href="../css/customers.css"
    >

    <style>

        .checkout-page {
            max-width: 1100px;
            margin: auto;
            padding: 40px 25px;
        }

        .checkout-header {
            margin-bottom: 30px;
        }

        .checkout-header h1 {
            color: #163a24;
            margin-bottom: 8px;
        }

        .checkout-header p {
            color: #777;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
        }

        .checkout-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
        }

        .checkout-box h2 {
            color: #163a24;
            margin-bottom: 25px;
        }

        .error {
            background: #ffe5e5;
            color: #c62828;
            border: 1px solid #f3b4b4;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.10);
        }

        .pickup-note {
            background: #f1f5f2;
            padding: 15px;
            border-radius: 7px;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .place-order-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #2e7d32;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .place-order-btn:hover {
            background: #245f27;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-name {
            color: #163a24;
            font-weight: bold;
        }

        .order-item-quantity {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .order-item-price {
            color: #2e7d32;
            font-weight: bold;
            white-space: nowrap;
        }

        .summary-total {
            border-top: 2px solid #eee;
            margin-top: 15px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            color: #163a24;
            font-size: 20px;
            font-weight: bold;
        }

        .back-cart {
            display: inline-block;
            margin-top: 20px;
            color: #2e7d32;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        @media (max-width: 800px) {

            .checkout-layout {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .checkout-page {
                padding: 25px 15px;
            }

            .checkout-box {
                padding: 20px;
            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =============================
     NAVBAR
============================= -->

<nav class="navbar">

    <div class="logo">
        MarketLink
    </div>

    <div class="nav-links">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="markets.php">
            Markets
        </a>

        <a href="cart.php">
            Cart
        </a>

        <a href="orders.php">
            My Orders
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="../auth/logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- =============================
     CHECKOUT
============================= -->

<main class="checkout-page">


    <div class="checkout-header">

        <h1>
            Pre-order Checkout
        </h1>

        <p>
            Select your preferred pickup date and time.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <div class="checkout-layout">


        <!-- =============================
             PICKUP FORM
        ============================= -->

        <div class="checkout-box">

            <h2>
                Pickup Details
            </h2>


            <div class="pickup-note">

                Your order will be picked up from the
                selected market. MarketLink does not provide
                delivery.

            </div>


            <form method="POST">


                <div class="form-group">

                    <label for="pickup_date">
                        Pickup Date
                    </label>

                    <input
                        type="date"
                        id="pickup_date"
                        name="pickup_date"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="pickup_time">
                        Pickup Time
                    </label>

                    <input
                        type="time"
                        id="pickup_time"
                        name="pickup_time"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="place_order"
                    class="place-order-btn"
                >
                    Place Pre-order
                </button>


            </form>


            <a
                href="cart.php"
                class="back-cart"
            >
                ← Back to Cart
            </a>

        </div>


        <!-- =============================
             ORDER SUMMARY
        ============================= -->

        <div class="checkout-box">

            <h2>
                Order Summary
            </h2>


            <?php foreach ($_SESSION['cart'] as $item): ?>


                <div class="order-item">


                    <div>

                        <div class="order-item-name">

                            <?php
                            echo htmlspecialchars(
                                $item['product_name']
                            );
                            ?>

                        </div>

                        <div class="order-item-quantity">

                            Quantity:
                            <?php
                            echo $item['quantity'];
                            ?>

                        </div>

                    </div>


                    <div class="order-item-price">

                        Rs.
                        <?php
                        echo number_format(
                            $item['price']
                            * $item['quantity'],
                            2
                        );
                        ?>

                    </div>


                </div>


            <?php endforeach; ?>


            <div class="summary-total">

                <span>
                    Total
                </span>

                <span>
                    Rs.
                    <?php
                    echo number_format(
                        $grand_total,
                        2
                    );
                    ?>
                </span>

            </div>


        </div>


    </div>


</main>


</body>

</html>
