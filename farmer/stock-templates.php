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
// DELETE TEMPLATE
// =========================================

if (isset($_GET['delete'])) {

    $template_id = (int) $_GET['delete'];


    if ($template_id > 0) {

        $delete_query = "
            DELETE FROM Stock_Templates
            WHERE template_id = ?
            AND farmer_id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $delete_query
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $template_id,
            $farmer_id
        );

        mysqli_stmt_execute($stmt);
    }


    header("Location: stock-templates.php");
    exit();

}


// =========================================
// APPLY TEMPLATE
// =========================================

if (isset($_GET['apply'])) {

    $template_id = (int) $_GET['apply'];


    if ($template_id > 0) {

        // Get template

        $template_query = "
            SELECT
                template_id,
                product_id,
                price,
                stock
            FROM Stock_Templates
            WHERE template_id = ?
            AND farmer_id = ?
            LIMIT 1
        ";


        $stmt = mysqli_prepare(
            $conn,
            $template_query
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $template_id,
            $farmer_id
        );


        mysqli_stmt_execute($stmt);


        $template_result =
            mysqli_stmt_get_result($stmt);


        $template =
            mysqli_fetch_assoc($template_result);


        if (!$template) {

            $error = "Template not found.";

        }

        else {

            $product_id =
                (int) $template['product_id'];

            $price =
                (float) $template['price'];

            $stock =
                (int) $template['stock'];


            // =============================
            // CHECK PRODUCT
            // =============================

            $product_query = "
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
                $product_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $product_id,
                $farmer_id
            );


            mysqli_stmt_execute($stmt);


            $product_result =
                mysqli_stmt_get_result($stmt);


            $product =
                mysqli_fetch_assoc(
                    $product_result
                );


            if (!$product) {

                $error = "Product not found.";

            }

            else {


                // =============================
                // STATUS
                // =============================

                if ($stock > 0) {

                    $status = "Available";

                } else {

                    $status = "Sold Out";

                }


                // =============================
                // UPDATE PRODUCT
                // =============================

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

                    // =========================
                    // RESTOCK NOTIFICATION
                    // =========================

                    $old_stock =
                        (int) $product['stock'];

                    $old_status =
                        $product['status'];


                    if (
                        (
                            $old_stock <= 0 ||
                            $old_status === "Sold Out"
                        )
                        &&
                        $stock > 0
                    ) {


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
                                (int)
                                $favorite['user_id'];


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
                        "Template applied successfully.";

                }

                else {

                    $error =
                        "Unable to apply template.";

                }

            }

        }

    }

}


// =========================================
// CREATE TEMPLATE
// =========================================

if (isset($_POST['create_template'])) {

    $product_id =
        (int) ($_POST['product_id'] ?? 0);

    $day_of_week =
        trim($_POST['day_of_week'] ?? '');

    $price =
        trim($_POST['price'] ?? '');

    $stock =
        trim($_POST['stock'] ?? '');


    // =====================================
    // VALIDATION
    // =====================================

    $valid_days = [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday"
    ];


    if ($product_id <= 0) {

        $error = "Please select a product.";

    }

    elseif (!in_array(
        $day_of_week,
        $valid_days,
        true
    )) {

        $error = "Please select a valid day.";

    }

    elseif ($price === "" || $stock === "") {

        $error = "Price and stock are required.";

    }

    elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    }

    elseif (!is_numeric($stock) || $stock < 0) {

        $error = "Please enter a valid stock.";

    }

    else {

        $price = (float) $price;
        $stock = (int) $stock;


        // =====================================
        // CHECK PRODUCT BELONGS TO FARMER
        // =====================================

        $check_product = "
            SELECT product_id
            FROM Products
            WHERE product_id = ?
            AND farmer_id = ?
            LIMIT 1
        ";


        $stmt = mysqli_prepare(
            $conn,
            $check_product
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $product_id,
            $farmer_id
        );


        mysqli_stmt_execute($stmt);


        $product_result =
            mysqli_stmt_get_result($stmt);


        if (
            mysqli_num_rows(
                $product_result
            ) === 0
        ) {

            $error =
                "Invalid product.";

        }

        else {


            // =====================================
            // CREATE TEMPLATE
            // =====================================

            $insert_query = "
                INSERT INTO Stock_Templates
                (
                    farmer_id,
                    product_id,
                    day_of_week,
                    price,
                    stock
                )
                VALUES
                (?, ?, ?, ?, ?)
            ";


            $stmt = mysqli_prepare(
                $conn,
                $insert_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "iisd i",
                $farmer_id,
                $product_id,
                $day_of_week,
                $price,
                $stock
            );


            /*
             * mysqli type string ko
             * spaces ke baghair hona chahiye.
             */

            mysqli_stmt_close($stmt);


            $stmt = mysqli_prepare(
                $conn,
                $insert_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "iisd i",
                $farmer_id,
                $product_id,
                $day_of_week,
                $price,
                $stock
            );

        }

    }

}


// =========================================
// FETCH FARMER PRODUCTS
// =========================================

$product_query = "
    SELECT
        product_id,
        product_name,
        category

    FROM Products

    WHERE farmer_id = ?

    ORDER BY product_name ASC
";


$stmt = mysqli_prepare(
    $conn,
    $product_query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);


mysqli_stmt_execute($stmt);


$products_result =
    mysqli_stmt_get_result($stmt);


// =========================================
// FETCH TEMPLATES
// =========================================

$template_query = "
    SELECT
        st.template_id,
        st.day_of_week,
        st.price,
        st.stock,

        p.product_name,
        p.category

    FROM Stock_Templates st

    INNER JOIN Products p
        ON st.product_id = p.product_id

    WHERE st.farmer_id = ?

    ORDER BY
        FIELD(
            st.day_of_week,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        ),
        st.template_id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $template_query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);


mysqli_stmt_execute($stmt);


$templates_result =
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
        Stock Templates - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/farmer.css"
    >


    <style>

        /* =====================================
           MAIN
        ===================================== */

        .farmer-main {

            margin-left: 240px;

            min-height: 100vh;

            padding: 35px;

            background: #f4f6f8;

        }


        .templates-page {

            max-width: 1200px;

            margin: 0 auto;

        }


        /* =====================================
           HEADER
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

        }


        .error-message {

            background: #ffebee;

            color: #c62828;

            padding: 13px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

        }


        /* =====================================
           CREATE CARD
        ===================================== */

        .create-card {

            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

            margin-bottom: 25px;

        }


        .create-card h2 {

            margin: 0 0 7px;

            color: #163a24;

            font-size: 21px;

        }


        .create-card p {

            margin: 0 0 20px;

            color: #777;

            font-size: 14px;

        }


        .template-form {

            display: grid;

            grid-template-columns:
                1.3fr
                1fr
                1fr
                1fr
                auto;

            gap: 12px;

            align-items: end;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: bold;

            color: #444;

        }


        .form-group input,
        .form-group select {

            width: 100%;

            box-sizing: border-box;

            padding: 11px 12px;

            border: 1px solid #ddd;

            border-radius: 7px;

            outline: none;

            background: white;

            font-size: 14px;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #2e7d32;

        }


        .save-btn {

            border: none;

            background: #2e7d32;

            color: white;

            padding: 11px 18px;

            border-radius: 7px;

            font-weight: bold;

            cursor: pointer;

            white-space: nowrap;

        }


        .save-btn:hover {

            background: #245f27;

        }


        /* =====================================
           TEMPLATE LIST
        ===================================== */

        .templates-card {

            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        .templates-card h2 {

            margin: 0 0 20px;

            color: #163a24;

            font-size: 21px;

        }


        .templates-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(300px, 1fr));

            gap: 18px;

        }


        .template-item {

            border: 1px solid #e5e9e6;

            border-radius: 10px;

            padding: 18px;

        }


        .template-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 10px;

            margin-bottom: 15px;

        }


        .template-top h3 {

            margin: 0 0 5px;

            color: #163a24;

        }


        .template-category {

            color: #888;

            font-size: 12px;

        }


        .day-badge {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

        }


        .template-details {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;

            margin-bottom: 18px;

        }


        .detail-box {

            background: #f7f9f8;

            padding: 12px;

            border-radius: 7px;

        }


        .detail-box small {

            display: block;

            color: #888;

            margin-bottom: 5px;

        }


        .detail-box strong {

            color: #163a24;

        }


        .template-actions {

            display: flex;

            gap: 8px;

        }


        .apply-btn,
        .delete-btn {

            flex: 1;

            text-align: center;

            text-decoration: none;

            padding: 10px;

            border-radius: 7px;

            font-size: 13px;

            font-weight: bold;

        }


        .apply-btn {

            background: #2e7d32;

            color: white;

        }


        .apply-btn:hover {

            background: #245f27;

        }


        .delete-btn {

            background: #ffebee;

            color: #c62828;

        }


        .delete-btn:hover {

            background: #ffcdd2;

        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty-box {

            text-align: center;

            padding: 40px 20px;

            color: #777;

        }


        .empty-box h3 {

            color: #163a24;

            margin-bottom: 7px;

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
                rgba(255,255,255,0.1);

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

        @media (max-width: 950px) {

            .template-form {

                grid-template-columns:
                    1fr 1fr;

            }

        }


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


        @media (max-width: 550px) {

            .template-form {

                grid-template-columns: 1fr;

            }


            .templates-grid {

                grid-template-columns: 1fr;

            }


            .sidebar-menu {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


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
            href="stock-templates.php"
            class="<?php echo $current_page === 'stock-templates.php' ? 'active' : ''; ?>"
        >

            <span>🔄</span>

            Templates

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


    <div class="templates-page">


        <div class="page-header">

            <h1>
                Recurring Stock Templates
            </h1>

            <p>
                Save your regular weekly stock and pricing
                so you can apply it quickly.
            </p>

        </div>


        <!-- =====================================
             MESSAGES
        ===================================== -->

        <?php if ($success !== ""): ?>

            <div class="success-message">

                <?php
                echo htmlspecialchars($success);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="error-message">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =====================================
             CREATE TEMPLATE
        ===================================== -->

        <div class="create-card">

            <h2>
                Create New Template
            </h2>

            <p>
                Save a product's regular weekly price
                and stock quantity.
            </p>


            <form
                method="POST"
                action=""
                class="template-form"
            >


                <!-- PRODUCT -->

                <div class="form-group">

                    <label for="product_id">

                        Product

                    </label>


                    <select
                        name="product_id"
                        id="product_id"
                        required
                    >

                        <option value="">
                            Select Product
                        </option>


                        <?php while (
                            $product =
                            mysqli_fetch_assoc(
                                $products_result
                            )
                        ): ?>

                            <option
                                value="<?php
                                    echo (int)
                                        $product['product_id'];
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $product['product_name']
                                );

                                ?>

                            </option>

                        <?php endwhile; ?>


                    </select>

                </div>


                <!-- DAY -->

                <div class="form-group">

                    <label for="day_of_week">

                        Day

                    </label>


                    <select
                        name="day_of_week"
                        id="day_of_week"
                        required
                    >

                        <option value="">
                            Select Day
                        </option>

                        <option value="Monday">
                            Monday
                        </option>

                        <option value="Tuesday">
                            Tuesday
                        </option>

                        <option value="Wednesday">
                            Wednesday
                        </option>

                        <option value="Thursday">
                            Thursday
                        </option>

                        <option value="Friday">
                            Friday
                        </option>

                        <option value="Saturday">
                            Saturday
                        </option>

                        <option value="Sunday">
                            Sunday
                        </option>

                    </select>

                </div>


                <!-- PRICE -->

                <div class="form-group">

                    <label for="price">

                        Price

                    </label>


                    <input
                        type="number"
                        name="price"
                        id="price"
                        min="0"
                        step="0.01"
                        placeholder="200"
                        required
                    >

                </div>


                <!-- STOCK -->

                <div class="form-group">

                    <label for="stock">

                        Stock

                    </label>


                    <input
                        type="number"
                        name="stock"
                        id="stock"
                        min="0"
                        placeholder="20"
                        required
                    >

                </div>


                <!-- SAVE -->

                <button
                    type="submit"
                    name="create_template"
                    class="save-btn"
                >

                    + Save Template

                </button>


            </form>

        </div>


        <!-- =====================================
             SAVED TEMPLATES
        ===================================== -->

        <div class="templates-card">

            <h2>
                Saved Templates
            </h2>


            <?php if (
                mysqli_num_rows(
                    $templates_result
                ) > 0
            ): ?>


                <div class="templates-grid">


                    <?php while (
                        $template =
                        mysqli_fetch_assoc(
                            $templates_result
                        )
                    ): ?>


                        <div class="template-item">


                            <div class="template-top">


                                <div>

                                    <h3>

                                        <?php

                                        echo htmlspecialchars(
                                            $template['product_name']
                                        );

                                        ?>

                                    </h3>


                                    <div
                                        class="template-category"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $template['category']
                                        );

                                        ?>

                                    </div>

                                </div>


                                <span class="day-badge">

                                    <?php

                                    echo htmlspecialchars(
                                        $template['day_of_week']
                                    );

                                    ?>

                                </span>


                            </div>


                            <div class="template-details">


                                <div class="detail-box">

                                    <small>
                                        Price
                                    </small>

                                    <strong>

                                        Rs.
                                        <?php

                                        echo number_format(
                                            (float)
                                            $template['price'],
                                            2
                                        );

                                        ?>

                                    </strong>

                                </div>


                                <div class="detail-box">

                                    <small>
                                        Stock
                                    </small>

                                    <strong>

                                        <?php

                                        echo (int)
                                            $template['stock'];

                                        ?>

                                    </strong>

                                </div>


                            </div>


                            <div class="template-actions">


                                <a
                                    href="stock-templates.php?apply=<?php
                                        echo (int)
                                            $template['template_id'];
                                    ?>"
                                    class="apply-btn"
                                >

                                    Apply

                                </a>


                                <a
                                    href="stock-templates.php?delete=<?php
                                        echo (int)
                                            $template['template_id'];
                                    ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this template?');"
                                >

                                    Delete

                                </a>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="empty-box">

                    <h3>
                        No Templates Yet
                    </h3>

                    <p>
                        Create your first weekly stock
                        template using the form above.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</main>


</body>

</html>