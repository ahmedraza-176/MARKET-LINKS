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
// UPDATE ORDER STATUS
// =========================================

if (
    isset($_GET['status']) &&
    isset($_GET['id']) &&
    is_numeric($_GET['id'])
) {

    $order_id = (int) $_GET['id'];

    $status = $_GET['status'];


    $allowed_statuses = [
        'Placed',
        'Accepted',
        'Ready',
        'Completed',
        'Cancelled',
        'Declined'
    ];


    if (in_array($status, $allowed_statuses, true)) {


        // Get current order

        $order_query = "
            SELECT
                order_id,
                product_id,
                quantity,
                status

            FROM Orders

            WHERE order_id = ?

            LIMIT 1
        ";


        $order_stmt = mysqli_prepare(
            $conn,
            $order_query
        );


        mysqli_stmt_bind_param(
            $order_stmt,
            "i",
            $order_id
        );


        mysqli_stmt_execute($order_stmt);


        $order_result =
            mysqli_stmt_get_result($order_stmt);


        $order =
            mysqli_fetch_assoc($order_result);


        if ($order) {


            $current_status =
                $order['status'];


            /*
             * Admin can update status.
             *
             * If an order is cancelled or declined,
             * restore its quantity to product stock.
             */

            if (
                ($status === 'Cancelled' ||
                 $status === 'Declined') &&
                $current_status !== 'Cancelled' &&
                $current_status !== 'Declined'
            ) {


                mysqli_begin_transaction($conn);


                try {


                    // Restore product stock

                    $stock_query = "
                        UPDATE Products

                        SET
                            stock = stock + ?,
                            status = 'Available'

                        WHERE product_id = ?
                    ";


                    $stock_stmt = mysqli_prepare(
                        $conn,
                        $stock_query
                    );


                    mysqli_stmt_bind_param(
                        $stock_stmt,
                        "ii",
                        $order['quantity'],
                        $order['product_id']
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stock_stmt
                        )
                    ) {

                        throw new Exception(
                            "Unable to restore stock."
                        );

                    }


                    // Update order status

                    $update_query = "
                        UPDATE Orders

                        SET status = ?

                        WHERE order_id = ?
                    ";


                    $update_stmt = mysqli_prepare(
                        $conn,
                        $update_query
                    );


                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "si",
                        $status,
                        $order_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $update_stmt
                        )
                    ) {

                        throw new Exception(
                            "Unable to update order."
                        );

                    }


                    mysqli_commit($conn);


                } catch (Exception $e) {


                    mysqli_rollback($conn);

                    header(
                        "Location: orders.php?error=update"
                    );

                    exit();

                }


            } else {


                // Normal status update

                $update_query = "
                    UPDATE Orders

                    SET status = ?

                    WHERE order_id = ?
                ";


                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_query
                );


                mysqli_stmt_bind_param(
                    $update_stmt,
                    "si",
                    $status,
                    $order_id
                );


                mysqli_stmt_execute(
                    $update_stmt
                );

            }

        }

    }


    header(
        "Location: orders.php?success=status"
    );

    exit();

}


// =========================================
// FETCH ORDERS
// =========================================

$query = "
    SELECT

        o.order_id,
        o.quantity,
        o.total_price,
        o.pickup_date,
        o.pickup_time,
        o.status,
        o.order_date,

        u.name AS customer_name,
        u.phone AS customer_phone,

        p.product_name,
        p.category,

        f.name AS farmer_name,

        m.market_name

    FROM Orders o

    INNER JOIN Users u
        ON o.customer_id = u.user_id

    INNER JOIN Products p
        ON o.product_id = p.product_id

    INNER JOIN Users f
        ON p.farmer_id = f.user_id

    INNER JOIN Markets m
        ON p.market_id = m.market_id

    ORDER BY o.order_id DESC
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
                All Orders
            </h2>

            <p>
                View and manage customer orders.
            </p>

        </div>

    </div>



    <!-- =====================================
         SUCCESS MESSAGE
    ===================================== -->

    <?php if (
        isset($_GET['success']) &&
        $_GET['success'] === 'status'
    ): ?>

        <div class="success-message">

            Order status updated successfully.

        </div>

    <?php endif; ?>



    <!-- =====================================
         ERROR MESSAGE
    ===================================== -->

    <?php if (
        isset($_GET['error']) &&
        $_GET['error'] === 'update'
    ): ?>

        <div class="error-message">

            Unable to update order. Please try again.

        </div>

    <?php endif; ?>



    <!-- =====================================
         ORDERS TABLE
    ===================================== -->

    <div class="table-wrapper">

        <table class="admin-table">


            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Customer
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
                        Qty
                    </th>

                    <th>
                        Total
                    </th>

                    <th>
                        Pickup
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Order Date
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
                        $order =
                        mysqli_fetch_assoc($result)
                    ): ?>


                        <tr>


                            <!-- ORDER ID -->

                            <td>

                                #<?php

                                echo (int)
                                    $order['order_id'];

                                ?>

                            </td>



                            <!-- CUSTOMER -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['customer_name']
                                    );

                                    ?>

                                </strong>

                                <small class="table-subtext">

                                    <?php

                                    echo htmlspecialchars(
                                        $order['customer_phone']
                                        ?? ''
                                    );

                                    ?>

                                </small>

                            </td>



                            <!-- PRODUCT -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['product_name']
                                    );

                                    ?>

                                </strong>

                                <small class="table-subtext">

                                    <?php

                                    echo htmlspecialchars(
                                        $order['category']
                                    );

                                    ?>

                                </small>

                            </td>



                            <!-- FARMER -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $order['farmer_name']
                                );

                                ?>

                            </td>



                            <!-- MARKET -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $order['market_name']
                                );

                                ?>

                            </td>



                            <!-- QUANTITY -->

                            <td>

                                <?php

                                echo (int)
                                    $order['quantity'];

                                ?>

                            </td>



                            <!-- TOTAL -->

                            <td>

                                <strong>

                                    Rs.

                                    <?php

                                    echo number_format(
                                        $order['total_price'],
                                        2
                                    );

                                    ?>

                                </strong>

                            </td>



                            <!-- PICKUP -->

                            <td>

                                <?php if (
                                    !empty(
                                        $order['pickup_date']
                                    )
                                ): ?>

                                    <strong>

                                        <?php

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $order['pickup_date']
                                            )
                                        );

                                        ?>

                                    </strong>

                                    <?php if (
                                        !empty(
                                            $order['pickup_time']
                                        )
                                    ): ?>

                                        <small class="table-subtext">

                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $order['pickup_time']
                                                )
                                            );

                                            ?>

                                        </small>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="table-subtext">

                                        Not set

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php

                                $status_class =
                                    'status-pending';


                                if (
                                    $order['status']
                                    === 'Accepted'
                                ) {

                                    $status_class =
                                        'status-accepted';

                                } elseif (
                                    $order['status']
                                    === 'Ready'
                                ) {

                                    $status_class =
                                        'status-ready';

                                } elseif (
                                    $order['status']
                                    === 'Completed'
                                ) {

                                    $status_class =
                                        'status-completed';

                                } elseif (
                                    $order['status']
                                    === 'Cancelled'
                                ) {

                                    $status_class =
                                        'status-cancelled';

                                } elseif (
                                    $order['status']
                                    === 'Declined'
                                ) {

                                    $status_class =
                                        'status-declined';

                                }

                                ?>


                                <span
                                    class="order-status <?php
                                        echo $status_class;
                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $order['status']
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- ORDER DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $order['order_date']
                                    )
                                );

                                ?>

                                <small class="table-subtext">

                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $order['order_date']
                                        )
                                    );

                                    ?>

                                </small>

                            </td>



                            <!-- ACTIONS -->

                            <td>

                                <div class="table-actions">


                                    <?php if (
                                        $order['status']
                                        === 'Placed'
                                    ): ?>


                                        <a
                                            href="orders.php?id=<?php
                                                echo (int)
                                                    $order['order_id'];
                                            ?>&status=Accepted"
                                            class="btn-success"
                                            onclick="return confirm('Accept this order?');"
                                        >

                                            Accept

                                        </a>


                                        <a
                                            href="orders.php?id=<?php
                                                echo (int)
                                                    $order['order_id'];
                                            ?>&status=Declined"
                                            class="btn-warning"
                                            onclick="return confirm('Decline this order? The product stock will be restored.');"
                                        >

                                            Decline

                                        </a>


                                    <?php elseif (
                                        $order['status']
                                        === 'Accepted'
                                    ): ?>


                                        <a
                                            href="orders.php?id=<?php
                                                echo (int)
                                                    $order['order_id'];
                                            ?>&status=Ready"
                                            class="btn-success"
                                            onclick="return confirm('Mark this order as Ready?');"
                                        >

                                            Ready

                                        </a>


                                    <?php elseif (
                                        $order['status']
                                        === 'Ready'
                                    ): ?>


                                        <a
                                            href="orders.php?id=<?php
                                                echo (int)
                                                    $order['order_id'];
                                            ?>&status=Completed"
                                            class="btn-success"
                                            onclick="return confirm('Mark this order as Completed?');"
                                        >

                                            Complete

                                        </a>


                                    <?php else: ?>


                                        <span class="no-action">

                                            No Action

                                        </span>


                                    <?php endif; ?>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="11"
                            class="no-data"
                        >

                            No orders found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>


        </table>

    </div>


</div>
