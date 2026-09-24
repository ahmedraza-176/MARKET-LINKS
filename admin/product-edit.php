<?php
session_start();
require_once "../config/connection.php";

/* =========================
   ADMIN AUTH CHECK
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}


/* =========================
   GET PRODUCT ID
========================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET['id']);


/* =========================
   FETCH PRODUCT
========================= */
$product_query = "SELECT *
                  FROM Products
                  WHERE product_id = ?";

$stmt = mysqli_prepare($conn, $product_query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);

$product_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($product_result) === 0) {
    header("Location: products.php");
    exit();
}

$product = mysqli_fetch_assoc($product_result);


/* =========================
   FETCH FARMERS
========================= */
$farmer_query = "SELECT user_id, name
                 FROM Users
                 WHERE role = 'Farmer'
                 AND status = 'Active'
                 ORDER BY name ASC";

$farmer_result = mysqli_query($conn, $farmer_query);


/* =========================
   FETCH MARKETS
========================= */
$market_query = "SELECT market_id, market_name
                 FROM Markets
                 ORDER BY market_name ASC";

$market_result = mysqli_query($conn, $market_query);


/* =========================
   UPDATE PRODUCT
========================= */
$error = "";

if (isset($_POST['update_product'])) {

    $farmer_id = intval($_POST['farmer_id']);
    $market_id = intval($_POST['market_id']);

    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);

    $price = trim($_POST['price']);
    $stock = intval($_POST['stock']);

    $status = $_POST['status'];


    /* =========================
       VALIDATION
    ========================= */

    if (
        $farmer_id <= 0 ||
        $market_id <= 0 ||
        empty($product_name) ||
        empty($category) ||
        $price === '' ||
        $stock < 0
    ) {

        $error = "Please fill all required fields.";

    } elseif (!is_numeric($price) || $price < 0) {

        $error = "Please enter a valid price.";

    } elseif ($status !== 'Available' && $status !== 'Sold Out') {

        $error = "Invalid product status.";

    } else {

        /* =========================
           UPDATE QUERY
        ========================= */

        $update_query = "UPDATE Products
                         SET farmer_id = ?,
                             market_id = ?,
                             product_name = ?,
                             category = ?,
                             price = ?,
                             stock = ?,
                             status = ?
                         WHERE product_id = ?";

        $stmt = mysqli_prepare($conn, $update_query);

        mysqli_stmt_bind_param(
            $stmt,
            "iissdisi",
            $farmer_id,
            $market_id,
            $product_name,
            $category,
            $price,
            $stock,
            $status,
            $product_id
        );


        if (mysqli_stmt_execute($stmt)) {

            header("Location: products.php");
            exit();

        } else {

            $error = "Failed to update product. Please try again.";
        }
    }
}

?>

<?php require_once "partials/menu.php"; ?>
<link rel="stylesheet" href="../css/admin_style.css">

<div class="main-content">

    <!-- Form Header -->
    <div class="form-header">

        <div>
            <h2>Edit Product</h2>
        </div>

        <a href="products.php" class="back-btn">
            ← Back to Products
        </a>

    </div>


    <!-- Error -->
    <?php if (!empty($error)): ?>

        <div class="form-error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- Edit Form -->
    <form method="POST" class="admin-form">


        <!-- Farmer -->
        <div class="form-group">

            <label for="farmer_id">
                Farmer
            </label>

            <select name="farmer_id" id="farmer_id" required>

                <option value="">
                    Select Farmer
                </option>

                <?php while ($farmer = mysqli_fetch_assoc($farmer_result)): ?>

                    <option
                        value="<?php echo $farmer['user_id']; ?>"
                        <?php
                        if (
                            isset($_POST['farmer_id'])
                                ? $_POST['farmer_id'] == $farmer['user_id']
                                : $product['farmer_id'] == $farmer['user_id']
                        ) {
                            echo "selected";
                        }
                        ?>
                    >

                        <?php echo htmlspecialchars($farmer['name']); ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- Market -->
        <div class="form-group">

            <label for="market_id">
                Market
            </label>

            <select name="market_id" id="market_id" required>

                <option value="">
                    Select Market
                </option>

                <?php while ($market = mysqli_fetch_assoc($market_result)): ?>

                    <option
                        value="<?php echo $market['market_id']; ?>"
                        <?php
                        if (
                            isset($_POST['market_id'])
                                ? $_POST['market_id'] == $market['market_id']
                                : $product['market_id'] == $market['market_id']
                        ) {
                            echo "selected";
                        }
                        ?>
                    >

                        <?php echo htmlspecialchars($market['market_name']); ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- Product Name -->
        <div class="form-group">

            <label for="product_name">
                Product Name
            </label>

            <input
                type="text"
                name="product_name"
                id="product_name"
                placeholder="Enter product name"
                value="<?php
                    echo isset($_POST['product_name'])
                        ? htmlspecialchars($_POST['product_name'])
                        : htmlspecialchars($product['product_name']);
                ?>"
                required
            >

        </div>


        <!-- Category -->
        <div class="form-group">

            <label for="category">
                Category
            </label>

            <input
                type="text"
                name="category"
                id="category"
                placeholder="e.g. Vegetables, Fruits, Dairy"
                value="<?php
                    echo isset($_POST['category'])
                        ? htmlspecialchars($_POST['category'])
                        : htmlspecialchars($product['category']);
                ?>"
                required
            >

        </div>


        <!-- Price -->
        <div class="form-group">

            <label for="price">
                Price
            </label>

            <input
                type="number"
                name="price"
                id="price"
                placeholder="Enter price"
                step="0.01"
                min="0"
                value="<?php
                    echo isset($_POST['price'])
                        ? htmlspecialchars($_POST['price'])
                        : htmlspecialchars($product['price']);
                ?>"
                required
            >

            <small>
                Enter price per product/unit.
            </small>

        </div>


        <!-- Stock -->
        <div class="form-group">

            <label for="stock">
                Stock
            </label>

            <input
                type="number"
                name="stock"
                id="stock"
                placeholder="Enter available stock"
                min="0"
                value="<?php
                    echo isset($_POST['stock'])
                        ? htmlspecialchars($_POST['stock'])
                        : htmlspecialchars($product['stock']);
                ?>"
                required
            >

        </div>


        <!-- Status -->
        <div class="form-group">

            <label for="status">
                Status
            </label>

            <select name="status" id="status" required>

                <option
                    value="Available"
                    <?php
                    $current_status = isset($_POST['status'])
                        ? $_POST['status']
                        : $product['status'];

                    if ($current_status === 'Available') {
                        echo "selected";
                    }
                    ?>
                >
                    Available
                </option>

                <option
                    value="Sold Out"
                    <?php
                    if ($current_status === 'Sold Out') {
                        echo "selected";
                    }
                    ?>
                >
                    Sold Out
                </option>

            </select>

        </div>


        <!-- Buttons -->
        <div class="form-buttons">

            <a href="products.php" class="cancel-btn">
                Cancel
            </a>

            <button
                type="submit"
                name="update_product"
                class="save-btn"
            >
                Update Product
            </button>

        </div>

    </form>

</div>