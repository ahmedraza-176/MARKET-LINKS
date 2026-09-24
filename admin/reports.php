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
// TOTAL FARMERS
// =========================================

$farmers_query = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role = 'Farmer'
";

$farmers_result = mysqli_query(
    $conn,
    $farmers_query
);

$farmers_data =
    mysqli_fetch_assoc($farmers_result);

$total_farmers =
    (int) $farmers_data['total'];


// =========================================
// TOTAL CUSTOMERS
// =========================================

$customers_query = "
    SELECT COUNT(*) AS total
    FROM Users
    WHERE role = 'Customer'
";

$customers_result = mysqli_query(
    $conn,
    $customers_query
);

$customers_data =
    mysqli_fetch_assoc($customers_result);

$total_customers =
    (int) $customers_data['total'];


// =========================================
// TOTAL PRODUCTS
// =========================================

$products_query = "
    SELECT COUNT(*) AS total
    FROM Products
";

$products_result = mysqli_query(
    $conn,
    $products_query
);

$products_data =
    mysqli_fetch_assoc($products_result);

$total_products =
    (int) $products_data['total'];


// =========================================
// TOTAL ORDERS
// =========================================

$orders_query = "
    SELECT COUNT(*) AS total
    FROM Orders
";

$orders_result = mysqli_query(
    $conn,
    $orders_query
);

$orders_data =
    mysqli_fetch_assoc($orders_result);

$total_orders =
    (int) $orders_data['total'];


// =========================================
// COMPLETED ORDERS
// =========================================

$completed_query = "
    SELECT COUNT(*) AS total
    FROM Orders
    WHERE status = 'Completed'
";

$completed_result = mysqli_query(
    $conn,
    $completed_query
);

$completed_data =
    mysqli_fetch_assoc($completed_result);

$completed_orders =
    (int) $completed_data['total'];


// =========================================
// TOTAL SALES
// =========================================

$sales_query = "
    SELECT
        COALESCE(
            SUM(total_price),
            0
        ) AS total_sales

    FROM Orders

    WHERE status = 'Completed'
";

$sales_result = mysqli_query(
    $conn,
    $sales_query
);

$sales_data =
    mysqli_fetch_assoc($sales_result);

$total_sales =
    (float) $sales_data['total_sales'];


// =========================================
// ACTIVE ORDERS
// =========================================

$active_query = "
    SELECT COUNT(*) AS total

    FROM Orders

    WHERE status IN (
        'Placed',
        'Accepted',
        'Ready'
    )
";

$active_result = mysqli_query(
    $conn,
    $active_query
);

$active_data =
    mysqli_fetch_assoc($active_result);

$active_orders =
    (int) $active_data['total'];


// =========================================
// CANCELLED ORDERS
// =========================================

$cancelled_query = "
    SELECT COUNT(*) AS total

    FROM Orders

    WHERE status = 'Cancelled'
";

$cancelled_result = mysqli_query(
    $conn,
    $cancelled_query
);

$cancelled_data =
    mysqli_fetch_assoc($cancelled_result);

$cancelled_orders =
    (int) $cancelled_data['total'];


// =========================================
// DECLINED ORDERS
// =========================================

$declined_query = "
    SELECT COUNT(*) AS total

    FROM Orders

    WHERE status = 'Declined'
";

$declined_result = mysqli_query(
    $conn,
    $declined_query
);

$declined_data =
    mysqli_fetch_assoc($declined_result);

$declined_orders =
    (int) $declined_data['total'];


// =========================================
// BEST SELLING PRODUCT
// =========================================

$best_seller_query = "
    SELECT

        p.product_name,

        SUM(o.quantity) AS total_quantity

    FROM Orders o

    INNER JOIN Products p
        ON o.product_id = p.product_id

    WHERE o.status = 'Completed'

    GROUP BY
        p.product_id,
        p.product_name

    ORDER BY
        total_quantity DESC

    LIMIT 1
";


$best_seller_result = mysqli_query(
    $conn,
    $best_seller_query
);


$best_seller =
    mysqli_fetch_assoc(
        $best_seller_result
    );


// =========================================
// FARMER REPORT
// =========================================

$farmer_report_query = "
    SELECT

        u.user_id,
        u.name AS farmer_name,

        COUNT(
            CASE
                WHEN o.order_id IS NOT NULL
                THEN 1
            END
        ) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN o.status = 'Completed'
                    THEN o.total_price
                    ELSE 0
                END
            ),
            0
        ) AS total_sales,

        (
            SELECT p2.product_name

            FROM Orders o2

            INNER JOIN Products p2
                ON o2.product_id = p2.product_id

            WHERE
                p2.farmer_id = u.user_id
                AND o2.status = 'Completed'

            GROUP BY
                p2.product_id,
                p2.product_name

            ORDER BY
                SUM(o2.quantity) DESC

            LIMIT 1
        ) AS best_seller

    FROM Users u

    LEFT JOIN Products p
        ON p.farmer_id = u.user_id

    LEFT JOIN Orders o
        ON o.product_id = p.product_id

    WHERE u.role = 'Farmer'

    GROUP BY
        u.user_id,
        u.name

    ORDER BY
        total_sales DESC
";


$farmer_report_result =
    mysqli_query(
        $conn,
        $farmer_report_query
    );

?>

<?php require_once "partials/menu.php"; ?>

<link
    rel="stylesheet"
    href="../css/admin_style.css"
>
<style>
/* =========================================
   REPORTS
========================================= */

.report-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.report-card {
    background: #ffffff;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
}

.report-card-label {
    display: block;
    color: #777;
    font-size: 13px;
    margin-bottom: 8px;
}

.report-card h3 {
    margin: 0;
    color: #163a24;
    font-size: 25px;
}


/* =========================================
   REPORT SECTION
========================================= */

.report-section {
    background: #ffffff;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
}

.section-heading {
    margin-bottom: 20px;
}

.section-heading h3 {
    margin: 0 0 5px;
    color: #163a24;
}

.section-heading p {
    margin: 0;
    color: #777;
    font-size: 13px;
}


/* =========================================
   BEST SELLER
========================================= */

.best-seller-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 22px;
    background: #f4f8f5;
    border-radius: 10px;
}

.report-small-label {
    display: block;
    color: #777;
    font-size: 12px;
    margin-bottom: 5px;
}

.best-seller-box h2 {
    margin: 0;
    color: #163a24;
    font-size: 22px;
}

.best-seller-number {
    color: #198754;
    font-size: 24px;
    font-weight: 700;
    text-align: right;
}

.best-seller-number span {
    display: block;
    color: #777;
    font-size: 12px;
    font-weight: 400;
}


/* =========================================
   STATUS SUMMARY
========================================= */

.status-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.status-summary-item {
    padding: 18px;
    background: #f7f7f7;
    border-radius: 9px;
}

.status-summary-item span {
    display: block;
    color: #666;
    font-size: 13px;
    margin-bottom: 8px;
}

.status-summary-item strong {
    color: #163a24;
    font-size: 22px;
}


/* =========================================
   REPORT RESPONSIVE
========================================= */

@media (max-width: 1000px) {

    .report-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .status-summary {
        grid-template-columns: repeat(2, 1fr);
    }

}


@media (max-width: 600px) {

    .report-cards {
        grid-template-columns: 1fr;
    }

    .status-summary {
        grid-template-columns: 1fr;
    }

    .report-section {
        padding: 18px;
    }

    .best-seller-box {
        flex-direction: column;
        align-items: flex-start;
    }

    .best-seller-number {
        text-align: left;
    }

}


</style>


<div class="main-content">


    <!-- =====================================
         PAGE HEADER
    ===================================== -->

    <div class="page-header">

        <div>

            <h2>
                Reports
            </h2>

            <p>
                View MarketLink performance and sales reports.
            </p>

        </div>

    </div>



    <!-- =====================================
         SUMMARY CARDS
    ===================================== -->

    <div class="report-cards">


        <!-- FARMERS -->

        <div class="report-card">

            <span class="report-card-label">
                Total Farmers
            </span>

            <h3>

                <?php

                echo $total_farmers;

                ?>

            </h3>

        </div>



        <!-- CUSTOMERS -->

        <div class="report-card">

            <span class="report-card-label">
                Total Customers
            </span>

            <h3>

                <?php

                echo $total_customers;

                ?>

            </h3>

        </div>



        <!-- PRODUCTS -->

        <div class="report-card">

            <span class="report-card-label">
                Total Products
            </span>

            <h3>

                <?php

                echo $total_products;

                ?>

            </h3>

        </div>



        <!-- ORDERS -->

        <div class="report-card">

            <span class="report-card-label">
                Total Orders
            </span>

            <h3>

                <?php

                echo $total_orders;

                ?>

            </h3>

        </div>



        <!-- COMPLETED -->

        <div class="report-card">

            <span class="report-card-label">
                Completed Orders
            </span>

            <h3>

                <?php

                echo $completed_orders;

                ?>

            </h3>

        </div>



        <!-- SALES -->

        <div class="report-card">

            <span class="report-card-label">
                Total Sales
            </span>

            <h3>

                Rs.

                <?php

                echo number_format(
                    $total_sales,
                    2
                );

                ?>

            </h3>

        </div>



        <!-- ACTIVE -->

        <div class="report-card">

            <span class="report-card-label">
                Active Orders
            </span>

            <h3>

                <?php

                echo $active_orders;

                ?>

            </h3>

        </div>



        <!-- CANCELLED -->

        <div class="report-card">

            <span class="report-card-label">
                Cancelled Orders
            </span>

            <h3>

                <?php

                echo $cancelled_orders;

                ?>

            </h3>

        </div>



    </div>



    <!-- =====================================
         BEST SELLER
    ===================================== -->

    <div class="report-section">


        <div class="section-heading">

            <div>

                <h3>
                    Best Selling Product
                </h3>

                <p>
                    Product with the highest completed order quantity.
                </p>

            </div>

        </div>


        <div class="best-seller-box">


            <?php if ($best_seller): ?>


                <div>

                    <span class="report-small-label">
                        Best Seller
                    </span>

                    <h2>

                        <?php

                        echo htmlspecialchars(
                            $best_seller['product_name']
                        );

                        ?>

                    </h2>

                </div>


                <div class="best-seller-number">

                    <?php

                    echo (int)
                        $best_seller['total_quantity'];

                    ?>

                    <span>
                        units sold
                    </span>

                </div>


            <?php else: ?>


                <div>

                    <h2>
                        No completed sales yet.
                    </h2>

                    <p>
                        Best seller will appear after completed orders.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>



    <!-- =====================================
         ORDER STATUS SUMMARY
    ===================================== -->

    <div class="report-section">


        <div class="section-heading">

            <div>

                <h3>
                    Order Status Summary
                </h3>

                <p>
                    Current order status overview.
                </p>

            </div>

        </div>


        <div class="status-summary">


            <div class="status-summary-item">

                <span>
                    Placed / Accepted / Ready
                </span>

                <strong>
                    <?php

                    echo $active_orders;

                    ?>
                </strong>

            </div>


            <div class="status-summary-item">

                <span>
                    Completed
                </span>

                <strong>
                    <?php

                    echo $completed_orders;

                    ?>
                </strong>

            </div>


            <div class="status-summary-item">

                <span>
                    Cancelled
                </span>

                <strong>
                    <?php

                    echo $cancelled_orders;

                    ?>
                </strong>

            </div>


            <div class="status-summary-item">

                <span>
                    Declined
                </span>

                <strong>
                    <?php

                    echo $declined_orders;

                    ?>
                </strong>

            </div>


        </div>


    </div>



    <!-- =====================================
         FARMER REPORT
    ===================================== -->

    <div class="report-section">


        <div class="section-heading">

            <div>

                <h3>
                    Farmer Performance
                </h3>

                <p>
                    Orders and completed sales for each farmer.
                </p>

            </div>

        </div>



        <div class="table-wrapper">


            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Farmer
                        </th>

                        <th>
                            Total Orders
                        </th>

                        <th>
                            Total Sales
                        </th>

                        <th>
                            Best Seller
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        $farmer_report_result &&
                        mysqli_num_rows(
                            $farmer_report_result
                        ) > 0
                    ): ?>


                        <?php while (
                            $farmer =
                            mysqli_fetch_assoc(
                                $farmer_report_result
                            )
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    #<?php

                                    echo (int)
                                        $farmer['user_id'];

                                    ?>

                                </td>



                                <!-- FARMER -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $farmer['farmer_name']
                                        );

                                        ?>

                                    </strong>

                                </td>



                                <!-- ORDERS -->

                                <td>

                                    <?php

                                    echo (int)
                                        $farmer['total_orders'];

                                    ?>

                                </td>



                                <!-- SALES -->

                                <td>

                                    <strong>

                                        Rs.

                                        <?php

                                        echo number_format(
                                            $farmer['total_sales'],
                                            2
                                        );

                                        ?>

                                    </strong>

                                </td>



                                <!-- BEST SELLER -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $farmer['best_seller']
                                        )
                                    ): ?>

                                        <?php

                                        echo htmlspecialchars(
                                            $farmer['best_seller']
                                        );

                                        ?>

                                    <?php else: ?>

                                        <span class="table-subtext">

                                            No completed sales

                                        </span>

                                    <?php endif; ?>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="5"
                                class="no-data"
                            >

                                No farmer data found.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>



</div>
