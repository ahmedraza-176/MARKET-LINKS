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
// CHECK PRODUCT ID
// =========================================

if (
    !isset($_GET['product_id'])
    ||
    !is_numeric($_GET['product_id'])
) {

    header("Location: products.php");
    exit();

}


$product_id = (int) $_GET['product_id'];


// =========================================
// CHECK PRODUCT EXISTS
// =========================================

$check_product = "

    SELECT product_id

    FROM Products

    WHERE product_id = ?

    LIMIT 1

";


$stmt = mysqli_prepare(
    $conn,
    $check_product
);


if (!$stmt) {

    header("Location: products.php");
    exit();

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $product_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result(
    $stmt
);


if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    header("Location: products.php");
    exit();

}


mysqli_stmt_close($stmt);


// =========================================
// CHECK EXISTING FAVORITE
// =========================================

$check_favorite = "

    SELECT favorite_id

    FROM Favorites

    WHERE user_id = ?

    AND product_id = ?

    LIMIT 1

";


$stmt = mysqli_prepare(
    $conn,
    $check_favorite
);


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $user_id,
    $product_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result(
    $stmt
);


// =========================================
// REMOVE FAVORITE
// =========================================

if (mysqli_num_rows($result) > 0) {

    $favorite = mysqli_fetch_assoc(
        $result
    );


    $favorite_id =
        (int) $favorite['favorite_id'];


    mysqli_stmt_close($stmt);


    $delete_query = "

        DELETE FROM Favorites

        WHERE favorite_id = ?

        AND user_id = ?

    ";


    $delete_stmt = mysqli_prepare(
        $conn,
        $delete_query
    );


    mysqli_stmt_bind_param(
        $delete_stmt,
        "ii",
        $favorite_id,
        $user_id
    );


    mysqli_stmt_execute(
        $delete_stmt
    );


    mysqli_stmt_close(
        $delete_stmt
    );


// =========================================
// ADD FAVORITE
// =========================================

} else {

    mysqli_stmt_close($stmt);


    $insert_query = "

        INSERT INTO Favorites
        (
            user_id,
            product_id
        )

        VALUES
        (
            ?,
            ?
        )

    ";


    $insert_stmt = mysqli_prepare(
        $conn,
        $insert_query
    );


    mysqli_stmt_bind_param(
        $insert_stmt,
        "ii",
        $user_id,
        $product_id
    );


    mysqli_stmt_execute(
        $insert_stmt
    );


    mysqli_stmt_close(
        $insert_stmt
    );

}


// =========================================
// BACK TO PRODUCTS
// =========================================

header("Location: products.php");
exit();

?>