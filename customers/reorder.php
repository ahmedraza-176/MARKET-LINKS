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

$product_id = (int) ($_GET['product_id'] ?? 0);


// =============================
// PRODUCT ID CHECK
// =============================

if ($product_id <= 0) {

    header("Location: orders.php");
    exit();

}


// =============================
// CHECK PRODUCT
// =============================

$product_query = "

    SELECT
        product_id,
        product_name,
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


$product_result = mysqli_stmt_get_result(
    $product_stmt
);


// =============================
// PRODUCT NOT FOUND
// =============================

if (mysqli_num_rows($product_result) === 0) {

    header("Location: orders.php?reorder=notfound");
    exit();

}


$product = mysqli_fetch_assoc(
    $product_result
);


// =============================
// CHECK PRODUCT STOCK
// =============================

if (
    $product['status'] !== 'Available'
    ||
    (int)$product['stock'] <= 0
) {

    header("Location: orders.php?reorder=unavailable");
    exit();

}


// =============================
// CREATE CART IF NOT EXISTS
// =============================

if (!isset($_SESSION['cart'])) {

    $_SESSION['cart'] = [];

}


// =============================
// ADD PRODUCT TO CART
// =============================

if (isset($_SESSION['cart'][$product_id])) {

    $current_quantity =
        (int) $_SESSION['cart'][$product_id]['quantity'];

    $new_quantity =
        $current_quantity + 1;


    // Don't exceed available stock

    if (
        $new_quantity >
        (int)$product['stock']
    ) {

        $new_quantity =
            (int)$product['stock'];

    }


    $_SESSION['cart'][$product_id]['quantity'] =
        $new_quantity;


    // Update latest stock

    $_SESSION['cart'][$product_id]['stock'] =
        (int)$product['stock'];


    // Update latest price

    $_SESSION['cart'][$product_id]['price'] =
        (float)$product['price'];


} else {

    $_SESSION['cart'][$product_id] = [

        'product_id' =>
            (int)$product['product_id'],

        'product_name' =>
            $product['product_name'],

        'price' =>
            (float)$product['price'],

        'quantity' =>
            1,

        'stock' =>
            (int)$product['stock']

    ];

}


// =============================
// REDIRECT TO CART
// =============================

header("Location: cart.php?reorder=success");
exit();

?>