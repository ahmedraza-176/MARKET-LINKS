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

$user_id = (int) $_SESSION['user_id'];


// =============================
// ACTIVE SIDEBAR PAGE
// =============================

$current_page = basename($_SERVER['PHP_SELF']);


// =============================
// GET FILTER VALUES
// =============================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$category = isset($_GET['category'])
    ? trim($_GET['category'])
    : '';

$market_id = isset($_GET['market_id'])
    ? (int) $_GET['market_id']
    : 0;

$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== ''
    ? (float) $_GET['min_price']
    : null;

$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== ''
    ? (float) $_GET['max_price']
    : null;


// =============================
// FETCH CATEGORIES
// =============================

$category_query = "
    SELECT DISTINCT category
    FROM Products
    WHERE category IS NOT NULL
    AND category != ''
    ORDER BY category ASC
";

$category_result = mysqli_query($conn, $category_query);


// =============================
// FETCH MARKETS
// =============================

$market_query = "
    SELECT market_id, market_name
    FROM Markets
    ORDER BY market_name ASC
";

$market_result = mysqli_query($conn, $market_query);


// =============================
// PRODUCT QUERY
// =============================

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.category,
        p.price,
        p.stock,
        p.status,
        p.image_name,

        u.name AS farmer_name,

        m.market_id,
        m.market_name,
        m.location,

        CASE
            WHEN f.favorite_id IS NOT NULL THEN 1
            ELSE 0
        END AS is_favorite

    FROM Products p

    INNER JOIN Users u
        ON p.farmer_id = u.user_id

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    LEFT JOIN Favorites f
        ON f.product_id = p.product_id
        AND f.user_id = $user_id

    WHERE p.status = 'Available'
    AND p.stock > 0
";


// =============================
// SEARCH
// =============================

if ($search !== '') {

    $search_safe = mysqli_real_escape_string(
        $conn,
        $search
    );

    $sql .= "
        AND (
            p.product_name LIKE '%$search_safe%'
            OR p.category LIKE '%$search_safe%'
            OR u.name LIKE '%$search_safe%'
            OR m.market_name LIKE '%$search_safe%'
        )
    ";
}


// =============================
// CATEGORY FILTER
// =============================

if ($category !== '') {

    $category_safe = mysqli_real_escape_string(
        $conn,
        $category
    );

    $sql .= "
        AND p.category = '$category_safe'
    ";
}


// =============================
// MARKET FILTER
// =============================

if ($market_id > 0) {

    $sql .= "
        AND p.market_id = $market_id
    ";
}


// =============================
// MIN PRICE
// =============================

if ($min_price !== null) {

    $sql .= "
        AND p.price >= $min_price
    ";
}


// =============================
// MAX PRICE
// =============================

if ($max_price !== null) {

    $sql .= "
        AND p.price <= $max_price
    ";
}


// =============================
// ORDER
// =============================

$sql .= "
    ORDER BY p.product_id DESC
";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Product query error: " . mysqli_error($conn));
}

$product_count = mysqli_num_rows($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products - MarketLink</title>

    <link
        rel="stylesheet"
        href="../css/customers.css"
    >

    <style>

        /* =====================================
           PRODUCTS PAGE
        ===================================== */

        .products-page {
            max-width: 1250px;
            margin: 0 auto;
        }


        /* =====================================
           PAGE HEADER
        ===================================== */

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #163a24;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #777;
            font-size: 15px;
        }


        /* =====================================
           FILTER BOX
        ===================================== */

        .filter-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);
        }

        .filter-box h2 {
            color: #163a24;
            font-size: 19px;
            margin-bottom: 20px;
        }

        .filter-form {
            display: grid;

            grid-template-columns:
                2fr
                1fr
                1fr
                1fr
                1fr;

            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: #555;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            height: 42px;
            padding: 0 12px;

            border: 1px solid #ddd;
            border-radius: 6px;

            outline: none;
            font-size: 14px;

            background: #fff;
            box-sizing: border-box;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #2e7d32;
        }


        /* =====================================
           FILTER BUTTONS
        ===================================== */

        .filter-buttons {
            display: flex;
            gap: 8px;
        }

        .filter-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 42px;
            padding: 0 16px;

            border: none;
            border-radius: 6px;

            background: #2e7d32;
            color: #ffffff;

            text-decoration: none;
            font-size: 14px;
            font-weight: bold;

            cursor: pointer;
            box-sizing: border-box;
        }

        .filter-btn:hover {
            background: #245f27;
        }

        .clear-btn {
            background: #777;
        }

        .clear-btn:hover {
            background: #555;
        }


        /* =====================================
           RESULT INFO
        ===================================== */

        .result-info {
            margin-bottom: 18px;

            color: #666;
            font-size: 14px;
        }


        /* =====================================
           PRODUCTS GRID
        ===================================== */

        .products-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 25px;
        }


        /* =====================================
           PRODUCT CARD
        ===================================== */

        .product-card {
            background: #ffffff;

            border-radius: 12px;
            padding: 20px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 8px 22px rgba(0, 0, 0, 0.10);
        }


        /* =====================================
           PRODUCT IMAGE
        ===================================== */

        .product-image {
            width: 100%;
            height: 200px;

            object-fit: cover;

            border-radius: 9px;
            margin-bottom: 15px;

            display: block;
        }

        .no-product-image {
            width: 100%;
            height: 200px;

            background: #f4f6f8;

            border-radius: 9px;
            margin-bottom: 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #999;
            font-size: 14px;
        }


        /* =====================================
           CATEGORY
        ===================================== */

        .product-category {
            display: inline-block;

            background: #e8f5e9;
            color: #2e7d32;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;
            font-weight: bold;

            margin-bottom: 10px;
        }


        /* =====================================
           PRODUCT TITLE
        ===================================== */

        .product-card h2 {
            color: #163a24;

            font-size: 20px;
            line-height: 1.3;

            margin-bottom: 10px;
        }


        /* =====================================
           PRICE
        ===================================== */

        .product-price {
            color: #198754;

            font-size: 22px;
            font-weight: bold;

            margin-bottom: 14px;
        }


        /* =====================================
           PRODUCT INFO
        ===================================== */

        .product-info {
            color: #666;

            font-size: 14px;
            line-height: 1.7;

            margin-bottom: 15px;
        }

        .product-info strong {
            color: #444;
        }


        /* =====================================
           STOCK
        ===================================== */

        .stock {
            background: #f4f6f8;

            padding: 9px 10px;

            border-radius: 6px;

            color: #555;
            font-size: 13px;

            margin-bottom: 15px;
        }

        .stock strong {
            color: #163a24;
        }


        /* =====================================
           FAVORITE BUTTON
        ===================================== */

        .favorite-btn {
            display: block;

            width: 100%;

            padding: 11px;

            margin-bottom: 10px;

            border: 1px solid #2e7d32;
            border-radius: 6px;

            background: #ffffff;
            color: #2e7d32;

            text-align: center;
            text-decoration: none;

            font-size: 14px;
            font-weight: bold;

            box-sizing: border-box;

            transition: 0.2s ease;
        }

        .favorite-btn:hover {
            background: #e8f5e9;
        }

        .favorite-btn.favorited {
            background: #ffebee;
            color: #c62828;
            border-color: #c62828;
        }

        .favorite-btn.favorited:hover {
            background: #ffcdd2;
        }


        /* =====================================
           ADD TO CART
        ===================================== */

        .add-cart-btn {
            display: block;

            width: 100%;

            padding: 12px;

            background: #2e7d32;
            color: #ffffff;

            border-radius: 6px;

            text-align: center;
            text-decoration: none;

            font-size: 14px;
            font-weight: bold;

            box-sizing: border-box;

            transition: 0.2s ease;
        }

        .add-cart-btn:hover {
            background: #245f27;
        }


        /* =====================================
           NO PRODUCTS
        ===================================== */

        .no-products {
            background: #ffffff;

            padding: 60px 20px;

            border-radius: 12px;

            text-align: center;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);
        }

        .no-products h2 {
            color: #163a24;
            margin-bottom: 8px;
        }

        .no-products p {
            color: #777;
        }


        /* =====================================
           SIDEBAR MOBILE
        ===================================== */

        @media (max-width: 700px) {

            .products-page {
                max-width: 100%;
            }

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 500px) {

            .filter-form {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                width: 100%;
            }

            .filter-btn {
                flex: 1;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-image,
            .no-product-image {
                height: 190px;
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

        <h2>MarketLink</h2>

        <p>Customer Panel</p>

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
     MAIN
===================================== -->

<main class="customer-main">


    <div class="products-page">


        <!-- =================================
             PAGE HEADER
        ================================= -->

        <div class="page-header">

            <h1>
                Browse Products
            </h1>

            <p>
                Find fresh products from local farmers.
            </p>

        </div>


        <!-- =================================
             FILTER BOX
        ================================= -->

        <div class="filter-box">

            <h2>
                Search & Filter
            </h2>


            <form
                method="GET"
                action="products.php"
                class="filter-form"
            >


                <!-- SEARCH -->

                <div class="filter-group">

                    <label>
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search product, farmer..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <!-- CATEGORY -->

                <div class="filter-group">

                    <label>
                        Category
                    </label>

                    <select name="category">

                        <option value="">
                            All Categories
                        </option>

                        <?php while ($cat = mysqli_fetch_assoc($category_result)): ?>

                            <option
                                value="<?php echo htmlspecialchars($cat['category']); ?>"
                                <?php
                                echo $category === $cat['category']
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php
                                echo htmlspecialchars($cat['category']);
                                ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- MARKET -->

                <div class="filter-group">

                    <label>
                        Market
                    </label>

                    <select name="market_id">

                        <option value="0">
                            All Markets
                        </option>

                        <?php while ($market = mysqli_fetch_assoc($market_result)): ?>

                            <option
                                value="<?php echo (int) $market['market_id']; ?>"
                                <?php
                                echo $market_id == $market['market_id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php
                                echo htmlspecialchars($market['market_name']);
                                ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- MIN PRICE -->

                <div class="filter-group">

                    <label>
                        Min Price
                    </label>

                    <input
                        type="number"
                        name="min_price"
                        min="0"
                        step="0.01"
                        placeholder="0"
                        value="<?php
                            echo $min_price !== null
                                ? htmlspecialchars($min_price)
                                : '';
                        ?>"
                    >

                </div>


                <!-- MAX PRICE -->

                <div class="filter-group">

                    <label>
                        Max Price
                    </label>

                    <input
                        type="number"
                        name="max_price"
                        min="0"
                        step="0.01"
                        placeholder="10000"
                        value="<?php
                            echo $max_price !== null
                                ? htmlspecialchars($max_price)
                                : '';
                        ?>"
                    >

                </div>


                <!-- BUTTONS -->

                <div class="filter-buttons">

                    <button
                        type="submit"
                        class="filter-btn"
                    >
                        Apply
                    </button>


                    <a
                        href="products.php"
                        class="filter-btn clear-btn"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <!-- =================================
             RESULT COUNT
        ================================= -->

        <div class="result-info">

            <?php

            echo $product_count . " product";

            if ($product_count != 1) {
                echo "s";
            }

            echo " found.";

            ?>

        </div>


        <!-- =================================
             PRODUCTS
        ================================= -->

        <?php if ($product_count > 0): ?>

            <div class="products-grid">


                <?php while ($product = mysqli_fetch_assoc($result)): ?>


                    <div class="product-card">


                        <!-- PRODUCT IMAGE -->

                        <?php

                        $image_path =
                            "../uploads/products/" .
                            $product['image_name'];

                        ?>

                        <?php if (
                            !empty($product['image_name']) &&
                            file_exists($image_path)
                        ): ?>

                            <img
                                src="<?php echo htmlspecialchars($image_path); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                class="product-image"
                            >

                        <?php else: ?>

                            <div class="no-product-image">
                                No Image Available
                            </div>

                        <?php endif; ?>


                        <!-- CATEGORY -->

                        <?php if (!empty($product['category'])): ?>

                            <span class="product-category">

                                <?php
                                echo htmlspecialchars(
                                    $product['category']
                                );
                                ?>

                            </span>

                        <?php endif; ?>


                        <!-- PRODUCT NAME -->

                        <h2>

                            <?php
                            echo htmlspecialchars(
                                $product['product_name']
                            );
                            ?>

                        </h2>


                        <!-- PRICE -->

                        <div class="product-price">

                            Rs.
                            <?php
                            echo number_format(
                                $product['price'],
                                2
                            );
                            ?>

                        </div>


                        <!-- PRODUCT INFO -->

                        <div class="product-info">

                            <strong>
                                Farmer:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $product['farmer_name']
                            );
                            ?>

                            <br>


                            <strong>
                                Market:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $product['market_name']
                            );
                            ?>

                            <br>


                            <strong>
                                Location:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $product['location']
                            );
                            ?>

                        </div>


                        <!-- STOCK -->

                        <div class="stock">

                            Available Stock:

                            <strong>
                                <?php
                                echo (int) $product['stock'];
                                ?>
                            </strong>

                        </div>


                        <!-- FAVORITE -->

                        <a
                            href="favorite_action.php?product_id=<?php echo (int) $product['product_id']; ?>"
                            class="favorite-btn <?php echo $product['is_favorite'] ? 'favorited' : ''; ?>"
                        >

                            <?php

                            echo $product['is_favorite']
                                ? ' Favorited'
                                : '♡ Add to Favorites';

                            ?>

                        </a>


                        <!-- ADD TO CART -->

                        <a
                            href="cart.php?add=<?php echo (int) $product['product_id']; ?>"
                            class="add-cart-btn"
                        >
                            Add to Cart
                        </a>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- NO PRODUCTS -->

            <div class="no-products">

                <h2>
                    No Products Found
                </h2>

                <p>
                    Try changing your search or filter options.
                </p>

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>