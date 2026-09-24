<?php

session_start();

require_once "../config/connection.php";


// =========================================
// ADMIN AUTH CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");
    exit();

}

if ($_SESSION['role'] !== 'Admin') {

    header("Location: ../auth/login.php");
    exit();

}


// =========================================
// DELETE PRODUCT
// =========================================

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $product_id = (int) $_GET['delete'];


    // Check if product is used in any order

    $check_query = "
        SELECT order_id
        FROM Orders
        WHERE product_id = ?
        LIMIT 1
    ";

    $check_stmt = mysqli_prepare(
        $conn,
        $check_query
    );

    mysqli_stmt_bind_param(
        $check_stmt,
        "i",
        $product_id
    );

    mysqli_stmt_execute($check_stmt);

    $check_result =
        mysqli_stmt_get_result($check_stmt);


    if (mysqli_num_rows($check_result) > 0) {

        header("Location: products.php?error=used");
        exit();

    }


    // Get image name before deleting

    $image_query = "
        SELECT image_name
        FROM Products
        WHERE product_id = ?
        LIMIT 1
    ";

    $image_stmt = mysqli_prepare(
        $conn,
        $image_query
    );

    mysqli_stmt_bind_param(
        $image_stmt,
        "i",
        $product_id
    );

    mysqli_stmt_execute($image_stmt);

    $image_result =
        mysqli_stmt_get_result($image_stmt);

    $image_data =
        mysqli_fetch_assoc($image_result);


    // Delete product

    $delete_query = "
        DELETE FROM Products
        WHERE product_id = ?
    ";

    $delete_stmt = mysqli_prepare(
        $conn,
        $delete_query
    );

    mysqli_stmt_bind_param(
        $delete_stmt,
        "i",
        $product_id
    );


    if (mysqli_stmt_execute($delete_stmt)) {


        // Delete image from folder

        if (
            $image_data &&
            !empty($image_data['image_name'])
        ) {

            $image_path =
                "../uploads/products/" .
                $image_data['image_name'];


            if (file_exists($image_path)) {

                unlink($image_path);

            }

        }


        header("Location: products.php?success=deleted");
        exit();

    } else {

        header("Location: products.php?error=delete");
        exit();

    }

}


// =========================================
// UPDATE PRODUCT STATUS
// =========================================

if (
    isset($_GET['status']) &&
    isset($_GET['id']) &&
    is_numeric($_GET['id'])
) {

    $product_id = (int) $_GET['id'];

    $status = $_GET['status'];


    if (
        $status === 'Available' ||
        $status === 'Sold Out'
    ) {


        $status_query = "
            UPDATE Products
            SET status = ?
            WHERE product_id = ?
        ";

        $status_stmt = mysqli_prepare(
            $conn,
            $status_query
        );

        mysqli_stmt_bind_param(
            $status_stmt,
            "si",
            $status,
            $product_id
        );

        mysqli_stmt_execute($status_stmt);

    }


    header("Location: products.php?success=status");
    exit();

}


// =========================================
// FETCH PRODUCTS
// =========================================

$query = "
    SELECT
        p.product_id,
        p.product_name,
        p.category,
        p.image_name,
        p.price,
        p.stock,
        p.status,

        u.name AS farmer_name,

        m.market_name

    FROM Products p

    INNER JOIN Users u
        ON p.farmer_id = u.user_id

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    ORDER BY p.product_id DESC
";


$result = mysqli_query(
    $conn,
    $query
);

?>

<?php require_once "partials/menu.php"; ?>

<link
    rel="stylesheet"
    href="../css/admin_style.css"
>


<div class="main-content">


    <!-- =====================================
         PAGE HEADER
    ===================================== -->

    <div class="page-header">

        <div>

            <h2>
                All Products
            </h2>

            <p>
                View and manage farmer products.
            </p>

        </div>


        <a
            href="product-add.php"
            class="add-btn"
        >

            + Add Product

        </a>

    </div>



    <!-- =====================================
         SUCCESS MESSAGES
    ===================================== -->

    <?php if (isset($_GET['success'])): ?>

        <div class="success-message">

            <?php

            if ($_GET['success'] === 'deleted') {

                echo "Product deleted successfully.";

            } elseif ($_GET['success'] === 'status') {

                echo "Product status updated successfully.";

            }

            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================
         ERROR MESSAGES
    ===================================== -->

    <?php if (isset($_GET['error'])): ?>

        <div class="error-message">

            <?php

            if ($_GET['error'] === 'used') {

                echo "This product cannot be deleted because it is already used in an order.";

            } elseif ($_GET['error'] === 'delete') {

                echo "Unable to delete product. Please try again.";

            }

            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================
         PRODUCTS TABLE
    ===================================== -->

    <div class="table-wrapper">


        <table class="admin-table">


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
                        Farmer
                    </th>

                    <th>
                        Market
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
                        Status
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>



            <tbody>


                <?php if (
                    $result &&
                    mysqli_num_rows($result) > 0
                ): ?>


                    <?php while (
                        $product =
                        mysqli_fetch_assoc($result)
                    ): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php

                                echo (int)
                                    $product['product_id'];

                                ?>

                            </td>



                            <!-- IMAGE -->

                            <td>


                                <?php if (
                                    !empty(
                                        $product['image_name']
                                    )
                                ): ?>


                                    <img
                                        src="../uploads/products/<?php
                                            echo htmlspecialchars(
                                                $product['image_name']
                                            );
                                        ?>"
                                        alt="<?php
                                            echo htmlspecialchars(
                                                $product['product_name']
                                            );
                                        ?>"
                                        class="product-thumb"
                                    >


                                <?php else: ?>


                                    <div class="no-image">

                                        No Image

                                    </div>


                                <?php endif; ?>


                            </td>



                            <!-- PRODUCT -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );

                                    ?>

                                </strong>

                            </td>



                            <!-- FARMER -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $product['farmer_name']
                                );

                                ?>

                            </td>



                            <!-- MARKET -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $product['market_name']
                                );

                                ?>

                            </td>



                            <!-- CATEGORY -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $product['category']
                                );

                                ?>

                            </td>



                            <!-- PRICE -->

                            <td>

                                Rs.

                                <?php

                                echo number_format(
                                    $product['price'],
                                    2
                                );

                                ?>

                            </td>



                            <!-- STOCK -->

                            <td>

                                <?php

                                echo (int)
                                    $product['stock'];

                                ?>

                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php if (
                                    $product['status']
                                    === 'Available'
                                ): ?>


                                    <span
                                        class="status active"
                                    >

                                        Available

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="status inactive"
                                    >

                                        Sold Out

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- ACTIONS -->

                            <td>


                                <div class="table-actions">


                                    <!-- EDIT -->

                                    <a
                                        href="product-edit.php?id=<?php
                                            echo (int)
                                                $product['product_id'];
                                        ?>"
                                        class="btn-edit"
                                    >

                                        Edit

                                    </a>



                                    <!-- STATUS -->

                                    <?php if (
                                        $product['status']
                                        === 'Available'
                                    ): ?>


                                        <a
                                            href="products.php?id=<?php
                                                echo (int)
                                                    $product['product_id'];
                                            ?>&status=Sold%20Out"
                                            class="btn-warning"
                                            onclick="return confirm('Mark this product as Sold Out?');"
                                        >

                                            Sold Out

                                        </a>


                                    <?php else: ?>


                                        <a
                                            href="products.php?id=<?php
                                                echo (int)
                                                    $product['product_id'];
                                            ?>&status=Available"
                                            class="btn-success"
                                            onclick="return confirm('Mark this product as Available?');"
                                        >

                                            Available

                                        </a>


                                    <?php endif; ?>



                                    <!-- DELETE -->

                                    <a
                                        href="products.php?delete=<?php
                                            echo (int)
                                                $product['product_id'];
                                        ?>"
                                        class="btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this product?');"
                                    >

                                        Delete

                                    </a>


                                </div>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="10"
                            class="no-data"
                        >

                            No products found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>


        </table>


    </div>


</div>



<style>


/* =========================================
   PRODUCT IMAGE
========================================= */

.product-thumb {

    width: 55px;

    height: 55px;

    object-fit: cover;

    border-radius: 8px;

    border: 1px solid #e5e5e5;

    display: block;

}


.no-image {

    width: 55px;

    height: 55px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f1f3f5;

    color: #777;

    border-radius: 8px;

    font-size: 11px;

    text-align: center;

}


/* =========================================
   MESSAGES
========================================= */

.success-message {

    margin-bottom: 20px;

    padding: 13px 16px;

    background: #e8f5e9;

    color: #2e7d32;

    border-left: 4px solid #2e7d32;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;

}


.error-message {

    margin-bottom: 20px;

    padding: 13px 16px;

    background: #ffebee;

    color: #c62828;

    border-left: 4px solid #c62828;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;

}


/* =========================================
   ACTIONS
========================================= */

.table-actions {

    display: flex;

    align-items: center;

    gap: 7px;

    flex-wrap: wrap;

}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 900px) {

    .table-wrapper {

        overflow-x: auto;

    }

    .admin-table {

        min-width: 1100px;

    }

}

</style>
