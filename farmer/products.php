<?php

session_start();

require_once "../config/connection.php";


// ======================================================
// FARMER LOGIN CHECK
// ======================================================

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


// ======================================================
// DELETE PRODUCT
// ======================================================

if (isset($_GET['delete'])) {

    $product_id = (int) $_GET['delete'];


    // Get product image first
    $image_query = "
        SELECT image_name
        FROM Products
        WHERE product_id = ?
        AND farmer_id = ?
        LIMIT 1
    ";

    $image_stmt = mysqli_prepare(
        $conn,
        $image_query
    );

    mysqli_stmt_bind_param(
        $image_stmt,
        "ii",
        $product_id,
        $farmer_id
    );

    mysqli_stmt_execute($image_stmt);

    $image_result = mysqli_stmt_get_result(
        $image_stmt
    );

    $product_image = mysqli_fetch_assoc(
        $image_result
    );


    // Delete product
    $delete_query = "
        DELETE FROM Products
        WHERE product_id = ?
        AND farmer_id = ?
    ";

    $stmt = mysqli_prepare(
        $conn,
        $delete_query
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $product_id,
        $farmer_id
    );


    if (mysqli_stmt_execute($stmt)) {

        // Delete image file
        if (
            $product_image &&
            !empty($product_image['image_name'])
        ) {

            $image_path =
                "../uploads/products/"
                . $product_image['image_name'];

            if (file_exists($image_path)) {

                unlink($image_path);

            }

        }

        $success = "Product deleted successfully.";

    } else {

        $error = "Unable to delete product.";

    }

}


// ======================================================
// ADD PRODUCT
// ======================================================

if (isset($_POST['add_product'])) {

    $product_name = trim(
        $_POST['product_name'] ?? ''
    );

    $category = trim(
        $_POST['category'] ?? ''
    );

    $price = trim(
        $_POST['price'] ?? ''
    );

    $stock = trim(
        $_POST['stock'] ?? ''
    );

    $market_id = (int) (
        $_POST['market_id'] ?? 0
    );


    // --------------------------------------------------
    // VALIDATION
    // --------------------------------------------------

    if (
        $product_name === "" ||
        $category === "" ||
        $price === "" ||
        $stock === "" ||
        $market_id <= 0
    ) {

        $error = "Please fill all fields.";

    }

    elseif (
        !is_numeric($price) ||
        $price < 0
    ) {

        $error = "Please enter a valid price.";

    }

    elseif (
        !is_numeric($stock) ||
        $stock < 0
    ) {

        $error = "Please enter a valid stock quantity.";

    }

    else {

        $price = (float) $price;

        $stock = (int) $stock;

        $status = (
            $stock > 0
        )
        ? "Available"
        : "Sold Out";


        // --------------------------------------------------
        // IMAGE VARIABLES
        // --------------------------------------------------

        $image_name = "";


        // --------------------------------------------------
        // IMAGE UPLOAD
        // --------------------------------------------------

        if (
            isset($_FILES['product_image']) &&
            $_FILES['product_image']['error']
            !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['product_image']['error']
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    "There was an error uploading the image.";

            }

            else {

                $original_name =
                    $_FILES['product_image']['name'];

                $tmp_name =
                    $_FILES['product_image']['tmp_name'];

                $file_size =
                    $_FILES['product_image']['size'];


                $extension = strtolower(
                    pathinfo(
                        $original_name,
                        PATHINFO_EXTENSION
                    )
                );


                $allowed_extensions = [
                    "jpg",
                    "jpeg",
                    "png",
                    "webp"
                ];


                // Check extension
                if (
                    !in_array(
                        $extension,
                        $allowed_extensions
                    )
                ) {

                    $error =
                        "Only JPG, JPEG, PNG and WEBP images are allowed.";

                }

                // Check size
                elseif (
                    $file_size > 5 * 1024 * 1024
                ) {

                    $error =
                        "Image size must be less than 5MB.";

                }

                else {

                    // Generate unique name
                    $image_name =
                        time()
                        . "_"
                        . uniqid()
                        . "."
                        . $extension;


                    $upload_path =
                        "../uploads/products/"
                        . $image_name;


                    if (
                        !move_uploaded_file(
                            $tmp_name,
                            $upload_path
                        )
                    ) {

                        $error =
                            "Unable to upload image.";

                    }

                }

            }

        }


        // --------------------------------------------------
        // INSERT PRODUCT
        // --------------------------------------------------

        if ($error === "") {

            $insert_query = "
                INSERT INTO Products
                (
                    farmer_id,
                    market_id,
                    product_name,
                    category,
                    image_name,
                    price,
                    stock,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
            ";


            $stmt = mysqli_prepare(
                $conn,
                $insert_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "iisssdis",
                $farmer_id,
                $market_id,
                $product_name,
                $category,
                $image_name,
                $price,
                $stock,
                $status
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                $success =
                    "Product added successfully.";

            }

            else {

                // Delete uploaded image
                // if database insert fails
                if (
                    $image_name !== ""
                ) {

                    $image_path =
                        "../uploads/products/"
                        . $image_name;

                    if (
                        file_exists($image_path)
                    ) {

                        unlink($image_path);

                    }

                }


                $error =
                    "Unable to add product.";

            }

        }

    }

}


// ======================================================
// UPDATE PRODUCT
// ======================================================

if (isset($_POST['update_product'])) {

    $product_id = (int) (
        $_POST['product_id'] ?? 0
    );

    $product_name = trim(
        $_POST['product_name'] ?? ''
    );

    $category = trim(
        $_POST['category'] ?? ''
    );

    $price = trim(
        $_POST['price'] ?? ''
    );

    $stock = trim(
        $_POST['stock'] ?? ''
    );

    $market_id = (int) (
        $_POST['market_id'] ?? 0
    );


    // --------------------------------------------------
    // VALIDATION
    // --------------------------------------------------

    if (
        $product_id <= 0 ||
        $product_name === "" ||
        $category === "" ||
        $price === "" ||
        $stock === "" ||
        $market_id <= 0
    ) {

        $error =
            "Please fill all fields.";

    }

    elseif (
        !is_numeric($price) ||
        $price < 0
    ) {

        $error =
            "Please enter a valid price.";

    }

    elseif (
        !is_numeric($stock) ||
        $stock < 0
    ) {

        $error =
            "Please enter a valid stock quantity.";

    }

    else {

        $price = (float) $price;

        $stock = (int) $stock;

        $status = (
            $stock > 0
        )
        ? "Available"
        : "Sold Out";


        // --------------------------------------------------
        // GET OLD PRODUCT DATA
        // --------------------------------------------------

        $old_product_query = "
            SELECT
                image_name,
                stock,
                status,
                product_name

            FROM Products

            WHERE product_id = ?
            AND farmer_id = ?

            LIMIT 1
        ";


        $old_stmt = mysqli_prepare(
            $conn,
            $old_product_query
        );


        mysqli_stmt_bind_param(
            $old_stmt,
            "ii",
            $product_id,
            $farmer_id
        );


        mysqli_stmt_execute(
            $old_stmt
        );


        $old_result =
            mysqli_stmt_get_result(
                $old_stmt
            );


        $old_product =
            mysqli_fetch_assoc(
                $old_result
            );


        // Product does not belong to farmer
        if (!$old_product) {

            $error =
                "Product not found.";

        }

        else {

            // --------------------------------------------------
            // SAVE OLD PRODUCT INFORMATION
            // --------------------------------------------------

            $old_image =
                $old_product['image_name'] ?? "";

            $old_stock =
                (int) $old_product['stock'];

            $old_status =
                $old_product['status'];

            $old_product_name =
                $old_product['product_name'];


            // Keep old image
            $image_name = $old_image;


            // --------------------------------------------------
            // NEW IMAGE UPLOAD
            // --------------------------------------------------

            if (
                isset($_FILES['product_image']) &&
                $_FILES['product_image']['error']
                !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['product_image']['error']
                    !== UPLOAD_ERR_OK
                ) {

                    $error =
                        "There was an error uploading the image.";

                }

                else {

                    $original_name =
                        $_FILES['product_image']['name'];

                    $tmp_name =
                        $_FILES['product_image']['tmp_name'];

                    $file_size =
                        $_FILES['product_image']['size'];


                    $extension = strtolower(
                        pathinfo(
                            $original_name,
                            PATHINFO_EXTENSION
                        )
                    );


                    $allowed_extensions = [
                        "jpg",
                        "jpeg",
                        "png",
                        "webp"
                    ];


                    // Check extension
                    if (
                        !in_array(
                            $extension,
                            $allowed_extensions
                        )
                    ) {

                        $error =
                            "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    }

                    // Check size
                    elseif (
                        $file_size >
                        5 * 1024 * 1024
                    ) {

                        $error =
                            "Image size must be less than 5MB.";

                    }

                    else {

                        $new_image_name =
                            time()
                            . "_"
                            . uniqid()
                            . "."
                            . $extension;


                        $upload_path =
                            "../uploads/products/"
                            . $new_image_name;


                        if (
                            move_uploaded_file(
                                $tmp_name,
                                $upload_path
                            )
                        ) {

                            $image_name =
                                $new_image_name;

                        }

                        else {

                            $error =
                                "Unable to upload new image.";

                        }

                    }

                }

            }


            // --------------------------------------------------
            // UPDATE DATABASE
            // --------------------------------------------------

            if ($error === "") {

                $update_query = "
                    UPDATE Products

                    SET
                        market_id = ?,
                        product_name = ?,
                        category = ?,
                        image_name = ?,
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
                    "isssdisii",
                    $market_id,
                    $product_name,
                    $category,
                    $image_name,
                    $price,
                    $stock,
                    $status,
                    $product_id,
                    $farmer_id
                );


                if (
                    mysqli_stmt_execute($stmt)
                ) {


                    // --------------------------------------------------
                    // DELETE OLD IMAGE
                    // --------------------------------------------------

                    if (
                        $image_name !== $old_image &&
                        $old_image !== ""
                    ) {

                        $old_image_path =
                            "../uploads/products/"
                            . $old_image;


                        if (
                            file_exists(
                                $old_image_path
                            )
                        ) {

                            unlink(
                                $old_image_path
                            );

                        }

                    }


                    // --------------------------------------------------
                    // RESTOCK NOTIFICATION
                    // --------------------------------------------------
                    //
                    // Notification will be created when:
                    //
                    // OLD STOCK = 0
                    // OR
                    // OLD STATUS = Sold Out
                    //
                    // AND
                    //
                    // NEW STOCK > 0
                    //
                    // --------------------------------------------------

                    if (
                        (
                            $old_stock <= 0 ||
                            $old_status === "Sold Out"
                        )
                        &&
                        $stock > 0
                    ) {


                        // ----------------------------------------------
                        // GET CUSTOMERS WHO FAVORITED THIS PRODUCT
                        // ----------------------------------------------

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


                        // ----------------------------------------------
                        // NOTIFICATION MESSAGE
                        // ----------------------------------------------

                        $notification_message =
                            $product_name
                            . " is back in stock! "
                            . "You can now place your order.";


                        // ----------------------------------------------
                        // CREATE NOTIFICATION
                        // FOR EACH CUSTOMER
                        // ----------------------------------------------

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
                        "Product updated successfully.";

                }

                else {

                    // --------------------------------------------------
                    // DELETE NEW IMAGE
                    // IF DATABASE UPDATE FAILS
                    // --------------------------------------------------

                    if (
                        $image_name !== $old_image &&
                        $image_name !== ""
                    ) {

                        $new_image_path =
                            "../uploads/products/"
                            . $image_name;


                        if (
                            file_exists(
                                $new_image_path
                            )
                        ) {

                            unlink(
                                $new_image_path
                            );

                        }

                    }


                    $error =
                        "Unable to update product.";

                }

            }

        }

    }

}


// ======================================================
// EDIT PRODUCT DATA
// ======================================================

$edit_product = null;


if (isset($_GET['edit'])) {

    $edit_id =
        (int) $_GET['edit'];


    $edit_query = "
        SELECT
            product_id,
            market_id,
            product_name,
            category,
            image_name,
            price,
            stock,
            status

        FROM Products

        WHERE product_id = ?
        AND farmer_id = ?

        LIMIT 1
    ";


    $stmt = mysqli_prepare(
        $conn,
        $edit_query
    );


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $edit_id,
        $farmer_id
    );


    mysqli_stmt_execute($stmt);


    $edit_result =
        mysqli_stmt_get_result(
            $stmt
        );


    $edit_product =
        mysqli_fetch_assoc(
            $edit_result
        );

}


// ======================================================
// FETCH MARKETS
// ======================================================

$markets_query = "
    SELECT
        market_id,
        market_name

    FROM Markets

    ORDER BY market_name ASC
";


$markets_result =
    mysqli_query(
        $conn,
        $markets_query
    );


// ======================================================
// FETCH FARMER PRODUCTS
// ======================================================

$products_query = "
    SELECT

        p.product_id,
        p.product_name,
        p.category,
        p.image_name,
        p.price,
        p.stock,
        p.status,

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
    $products_query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);


mysqli_stmt_execute($stmt);


$products_result =
    mysqli_stmt_get_result(
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

    <title>
        My Products - MarketLink
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f4f6f8;

            color: #333;

        }


        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 240px;

            height: 100vh;

            background: #163a24;

            color: white;

            display: flex;

            flex-direction: column;

        }


        .sidebar-logo {

            padding: 25px 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.1);

        }


        .sidebar-logo h2 {

            margin-bottom: 5px;

        }


        .sidebar-logo p {

            font-size: 13px;

            color: #b8d1bd;

        }


        .sidebar-menu {

            padding: 20px 12px;

            flex: 1;

        }


        .sidebar-menu a,
        .sidebar-bottom a {

            display: block;

            color: #d9e8dc;

            text-decoration: none;

            padding: 12px 15px;

            margin-bottom: 5px;

            border-radius: 6px;

            font-size: 14px;

        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {

            background: #2e7d32;

            color: white;

        }


        .sidebar-bottom {

            padding: 15px 12px;

            border-top:
                1px solid
                rgba(255,255,255,0.1);

        }


        .logout:hover {

            background: #b3261e;

            color: white;

        }


        /* =========================================
           MAIN CONTENT
        ========================================= */

        .main-content {

            margin-left: 240px;

            padding: 30px;

        }


        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            color: #163a24;

            margin-bottom: 7px;

        }


        .page-header p {

            color: #777;

            font-size: 14px;

        }


        /* =========================================
           ALERTS
        ========================================= */

        .success {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 13px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .error {

            background: #ffebee;

            color: #c62828;

            padding: 13px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        /* =========================================
           FORM CARD
        ========================================= */

        .form-card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.05);

            margin-bottom: 30px;

        }


        .form-card h2 {

            color: #163a24;

            margin-bottom: 20px;

            font-size: 20px;

        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group label {

            font-size: 13px;

            font-weight: bold;

            color: #555;

            margin-bottom: 7px;

        }


        .form-group input,
        .form-group select {

            padding: 11px;

            border: 1px solid #ddd;

            border-radius: 6px;

            outline: none;

            font-size: 14px;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #2e7d32;

        }


        .current-image {

            margin-top: 10px;

            width: 80px;

            height: 80px;

            object-fit: cover;

            border-radius: 8px;

            border: 1px solid #ddd;

        }


        .image-help {

            margin-top: 6px;

            color: #888;

            font-size: 12px;

        }


        /* =========================================
           BUTTONS
        ========================================= */

        .form-actions {

            margin-top: 20px;

            display: flex;

            gap: 10px;

        }


        .btn {

            display: inline-block;

            border: none;

            padding: 11px 18px;

            border-radius: 6px;

            cursor: pointer;

            text-decoration: none;

            font-size: 14px;

            font-weight: bold;

        }


        .btn-primary {

            background: #2e7d32;

            color: white;

        }


        .btn-primary:hover {

            background: #245f27;

        }


        .btn-secondary {

            background: #777;

            color: white;

        }


        .btn-secondary:hover {

            background: #555;

        }


        /* =========================================
           TABLE
        ========================================= */

        .table-card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.05);

        }


        .table-card h2 {

            color: #163a24;

            margin-bottom: 20px;

            font-size: 20px;

        }


        .table-container {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;

        }


        th {

            background: #f4f6f8;

            color: #555;

            text-align: left;

            padding: 13px;

            font-size: 13px;

        }


        td {

            padding: 13px;

            border-bottom:
                1px solid #eee;

            font-size: 13px;

            vertical-align: middle;

        }


        /* =========================================
           PRODUCT IMAGE
        ========================================= */

        .product-image {

            width: 65px;

            height: 65px;

            object-fit: cover;

            border-radius: 8px;

            border:
                1px solid #ddd;

            display: block;

        }


        .no-image {

            width: 65px;

            height: 65px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f4f6f8;

            color: #999;

            border-radius: 8px;

            font-size: 11px;

            text-align: center;

        }


        /* =========================================
           STATUS
        ========================================= */

        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

        }


        .available {

            background: #e8f5e9;

            color: #2e7d32;

        }


        .sold-out {

            background: #ffebee;

            color: #c62828;

        }


        /* =========================================
           ACTIONS
        ========================================= */

        .edit-btn {

            color: #1565c0;

            text-decoration: none;

            font-weight: bold;

            margin-right: 10px;

        }


        .delete-btn {

            color: #c62828;

            text-decoration: none;

            font-weight: bold;

        }


        .edit-btn:hover,
        .delete-btn:hover {

            text-decoration: underline;

        }


        /* =========================================
           EMPTY
        ========================================= */

        .empty {

            text-align: center;

            color: #888;

            padding: 30px;

        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 700px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }


            .sidebar-menu {

                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .main-content {

                margin-left: 0;

                padding: 20px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 500px) {

            .form-actions {

                flex-direction: column;

            }


            .btn {

                text-align: center;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

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

        <a href="dashboard.php">
            Dashboard
        </a>


        <a
            href="products.php"
            class="active"
        >
            My Products
        </a>


        <a href="orders.php">
            Orders
        </a>


        <a href="profile.php">
            Profile
        </a>


        <a href="reports.php">
            Reports
        </a>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="../auth/logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>


</aside>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<main class="main-content">


    <div class="page-header">

        <h1>
            My Products
        </h1>

        <p>
            Add and manage your products, prices,
            stock and images.
        </p>

    </div>


    <!-- =========================================
         ALERTS
    ========================================= -->

    <?php if ($success !== ""): ?>

        <div class="success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================================
         ADD / EDIT FORM
    ========================================= -->

    <div class="form-card">


        <h2>

            <?php

            echo $edit_product
                ? "Edit Product"
                : "Add New Product";

            ?>

        </h2>


        <form
            method="POST"
            action=""
            enctype="multipart/form-data"
        >


            <?php if ($edit_product): ?>

                <input
                    type="hidden"
                    name="product_id"
                    value="<?php
                        echo $edit_product['product_id'];
                    ?>"
                >

            <?php endif; ?>


            <div class="form-grid">


                <!-- PRODUCT NAME -->

                <div class="form-group">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        placeholder="e.g. Fresh Tomatoes"
                        value="<?php

                        echo $edit_product
                            ? htmlspecialchars(
                                $edit_product[
                                    'product_name'
                                ]
                            )
                            : '';

                        ?>"
                        required
                    >

                </div>


                <!-- CATEGORY -->

                <div class="form-group">

                    <label>
                        Category
                    </label>

                    <input
                        type="text"
                        name="category"
                        placeholder="e.g. Vegetables"
                        value="<?php

                        echo $edit_product
                            ? htmlspecialchars(
                                $edit_product[
                                    'category'
                                ]
                            )
                            : '';

                        ?>"
                        required
                    >

                </div>


                <!-- PRICE -->

                <div class="form-group">

                    <label>
                        Price
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        placeholder="Enter price"
                        value="<?php

                        echo $edit_product
                            ? htmlspecialchars(
                                $edit_product[
                                    'price'
                                ]
                            )
                            : '';

                        ?>"
                        required
                    >

                </div>


                <!-- STOCK -->

                <div class="form-group">

                    <label>
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        name="stock"
                        min="0"
                        placeholder="Enter stock"
                        value="<?php

                        echo $edit_product
                            ? htmlspecialchars(
                                $edit_product[
                                    'stock'
                                ]
                            )
                            : '';

                        ?>"
                        required
                    >

                </div>


                <!-- MARKET -->

                <div class="form-group">

                    <label>
                        Market
                    </label>

                    <select
                        name="market_id"
                        required
                    >

                        <option value="">
                            Select Market
                        </option>


                        <?php while (
                            $market =
                            mysqli_fetch_assoc(
                                $markets_result
                            )
                        ): ?>

                            <option
                                value="<?php
                                    echo $market[
                                        'market_id'
                                    ];
                                ?>"

                                <?php

                                if (
                                    $edit_product &&
                                    $edit_product[
                                        'market_id'
                                    ]
                                    ==
                                    $market[
                                        'market_id'
                                    ]
                                ) {

                                    echo "selected";

                                }

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $market[
                                        'market_name'
                                    ]
                                );

                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- PRODUCT IMAGE -->

                <div class="form-group">

                    <label>
                        Product Image
                    </label>


                    <input
                        type="file"
                        name="product_image"
                        accept=".jpg,.jpeg,.png,.webp,image/*"
                    >


                    <div class="image-help">

                        JPG, JPEG, PNG or WEBP.
                        Maximum 5MB.

                    </div>


                    <?php if (
                        $edit_product &&
                        !empty(
                            $edit_product[
                                'image_name'
                            ]
                        )
                    ): ?>


                        <img
                            src="../uploads/products/<?php
                                echo htmlspecialchars(
                                    $edit_product[
                                        'image_name'
                                    ]
                                );
                            ?>"
                            alt="Current Product Image"
                            class="current-image"
                        >


                        <small
                            style="
                                margin-top:6px;
                                color:#777;
                            "
                        >

                            Current Image

                        </small>


                    <?php endif; ?>

                </div>


            </div>


            <!-- =====================================
                 BUTTONS
            ====================================== -->

            <div class="form-actions">


                <?php if ($edit_product): ?>


                    <button
                        type="submit"
                        name="update_product"
                        class="btn btn-primary"
                    >

                        Update Product

                    </button>


                    <a
                        href="products.php"
                        class="btn btn-secondary"
                    >

                        Cancel

                    </a>


                <?php else: ?>


                    <button
                        type="submit"
                        name="add_product"
                        class="btn btn-primary"
                    >

                        Add Product

                    </button>


                <?php endif; ?>


            </div>


        </form>


    </div>


    <!-- =========================================
         PRODUCTS TABLE
    ========================================= -->

    <div class="table-card">


        <h2>
            My Products
        </h2>


        <?php if (
            mysqli_num_rows(
                $products_result
            ) > 0
        ): ?>


            <div class="table-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>


                            <th>
                                Image
                            </th>


                            <th>
                                Product
                            </th>


                            <th>
                                Category
                            </th>


                            <th>
                                Price
                            </th>


                            <th>
                                Stock
                            </th>


                            <th>
                                Market
                            </th>


                            <th>
                                Status
                            </th>


                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while (
                            $product =
                            mysqli_fetch_assoc(
                                $products_result
                            )
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    #

                                    <?php

                                    echo $product[
                                        'product_id'
                                    ];

                                    ?>

                                </td>


                                <!-- IMAGE -->

                                <td>


                                    <?php if (
                                        !empty(
                                            $product[
                                                'image_name'
                                            ]
                                        )
                                    ): ?>


                                        <img
                                            src="../uploads/products/<?php
                                                echo htmlspecialchars(
                                                    $product[
                                                        'image_name'
                                                    ]
                                                );
                                            ?>"
                                            alt="<?php
                                                echo htmlspecialchars(
                                                    $product[
                                                        'product_name'
                                                    ]
                                                );
                                            ?>"
                                            class="product-image"
                                        >


                                    <?php else: ?>


                                        <div
                                            class="no-image"
                                        >

                                            No Image

                                        </div>


                                    <?php endif; ?>


                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $product[
                                                'product_name'
                                            ]
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $product[
                                            'category'
                                        ]
                                    );

                                    ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    Rs.

                                    <?php

                                    echo number_format(
                                        $product[
                                            'price'
                                        ],
                                        2
                                    );

                                    ?>

                                </td>


                                <!-- STOCK -->

                                <td>

                                    <?php

                                    echo $product[
                                        'stock'
                                    ];

                                    ?>

                                </td>


                                <!-- MARKET -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $product[
                                            'market_name'
                                        ]
                                    );

                                    ?>


                                    <br>


                                    <small>

                                        <?php

                                        echo htmlspecialchars(
                                            $product[
                                                'location'
                                            ]
                                        );

                                        ?>

                                    </small>

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <span
                                        class="status <?php

                                        echo
                                            $product[
                                                'status'
                                            ]
                                            ===
                                            'Available'

                                            ? 'available'

                                            : 'sold-out';

                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $product[
                                                'status'
                                            ]
                                        );

                                        ?>

                                    </span>


                                </td>


                                <!-- ACTION -->

                                <td>


                                    <a
                                        href="products.php?edit=<?php
                                            echo $product[
                                                'product_id'
                                            ];
                                        ?>"
                                        class="edit-btn"
                                    >

                                        Edit

                                    </a>


                                    <a
                                        href="products.php?delete=<?php
                                            echo $product[
                                                'product_id'
                                            ];
                                        ?>"
                                        class="delete-btn"

                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this product?'
                                            );
                                        "
                                    >

                                        Delete

                                    </a>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">

                You have not added any products yet.

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>