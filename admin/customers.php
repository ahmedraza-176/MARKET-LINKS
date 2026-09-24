<?php

session_start();

require_once "../config/connection.php";


// =========================================
// ADMIN LOGIN CHECK
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
// DELETE CUSTOMER
// =========================================

if (isset($_GET['delete'])) {

    $customer_id = (int) $_GET['delete'];


    $delete_query = "
        DELETE FROM Users
        WHERE user_id = ?
        AND role = 'Customer'
    ";


    $stmt = mysqli_prepare(
        $conn,
        $delete_query
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $customer_id
    );


    mysqli_stmt_execute($stmt);


    header("Location: customers.php");
    exit();

}


// =========================================
// CHANGE CUSTOMER STATUS
// =========================================

if (
    isset($_GET['status']) &&
    isset($_GET['id'])
) {

    $customer_id = (int) $_GET['id'];

    $status = $_GET['status'];


    if (
        $status === 'Active' ||
        $status === 'Inactive'
    ) {


        $status_query = "
            UPDATE Users
            SET status = ?
            WHERE user_id = ?
            AND role = 'Customer'
        ";


        $stmt = mysqli_prepare(
            $conn,
            $status_query
        );


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $status,
            $customer_id
        );


        mysqli_stmt_execute($stmt);

    }


    header("Location: customers.php");
    exit();

}


// =========================================
// FETCH CUSTOMERS
// =========================================

$query = "
    SELECT
        user_id,
        name,
        email,
        phone,
        status

    FROM Users

    WHERE role = 'Customer'

    ORDER BY user_id DESC
";


$result = mysqli_query(
    $conn,
    $query
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
        Manage Customers - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <style>

        .add-btn {

            display: inline-block;

            background: #198754;

            color: #ffffff;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 7px;

            font-size: 14px;

            font-weight: 600;

        }


        .add-btn:hover {

            background: #157347;

        }


        .btn-edit,
        .btn-warning,
        .btn-success,
        .btn-delete {

            display: inline-block;

            text-decoration: none;

            padding: 7px 11px;

            border-radius: 5px;

            font-size: 12px;

            font-weight: 600;

        }


        .btn-edit {

            background: #e8f1ff;

            color: #1769aa;

        }


        .btn-warning {

            background: #fff3cd;

            color: #856404;

        }


        .btn-success {

            background: #d1e7dd;

            color: #146c43;

        }


        .btn-delete {

            background: #f8d7da;

            color: #b02a37;

        }


        .table-actions {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: wrap;

        }


        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status.active {

            background: #d1e7dd;

            color: #146c43;

        }


        .status.inactive {

            background: #f8d7da;

            color: #b02a37;

        }


        .no-data {

            text-align: center;

            padding: 30px;

            color: #777;

        }


        @media (max-width: 700px) {

            .page-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .table-wrapper {

                overflow-x: auto;

            }

        }

    </style>

</head>


<body>


<?php require_once "partials/menu.php"; ?>


<main class="main-content">


    <!-- =====================================
         TOPBAR
    ===================================== -->

    <div class="topbar">


        <div>

            <h1>
                Customers
            </h1>


            <p>
                Manage MarketLink customers.
            </p>

        </div>


        <div class="admin-info">


            <strong>

                <?php

                echo htmlspecialchars(
                    $_SESSION['name']
                );

                ?>

            </strong>


            <span>
                Admin
            </span>


        </div>


    </div>



    <!-- =====================================
         CONTENT
    ===================================== -->

    <section class="content-box">


        <div class="page-header">


            <div>

                <h2>
                    All Customers
                </h2>


                <p>
                    View and manage registered customers.
                </p>

            </div>


            <a
                href="customer-add.php"
                class="add-btn"
            >

                + Add Customer

            </a>


        </div>



        <!-- =====================================
             SUCCESS MESSAGE
        ===================================== -->

        <?php if (
            isset($_GET['success'])
        ): ?>


            <div
                style="
                    background:#d1e7dd;
                    color:#146c43;
                    padding:12px 15px;
                    border-radius:7px;
                    margin-bottom:20px;
                "
            >

                <?php

                if (
                    $_GET['success']
                    === 'added'
                ) {

                    echo "Customer added successfully.";

                }

                elseif (
                    $_GET['success']
                    === 'updated'
                ) {

                    echo "Customer updated successfully.";

                }

                ?>

            </div>


        <?php endif; ?>



        <!-- =====================================
             TABLE
        ===================================== -->

        <div class="table-wrapper">


            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Phone
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
                        mysqli_num_rows($result) > 0
                    ): ?>


                        <?php while (
                            $customer =
                            mysqli_fetch_assoc($result)
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?php

                                    echo (int)
                                        $customer['user_id'];

                                    ?>

                                </td>



                                <!-- NAME -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $customer['name']
                                    );

                                    ?>

                                </td>



                                <!-- EMAIL -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $customer['email']
                                    );

                                    ?>

                                </td>



                                <!-- PHONE -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $customer['phone']
                                        ?? 'N/A'
                                    );

                                    ?>

                                </td>



                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $customer['status']
                                        === 'Active'
                                    ): ?>


                                        <span
                                            class="status active"
                                        >

                                            Active

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="status inactive"
                                        >

                                            Inactive

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- ACTIONS -->

                                <td>


                                    <div
                                        class="table-actions"
                                    >


                                        <!-- EDIT -->

                                        <a
                                            href="customer-edit.php?id=<?php echo (int) $customer['user_id']; ?>"
                                            class="btn-edit"
                                        >

                                            Edit

                                        </a>



                                        <!-- STATUS -->

                                        <?php if (
                                            $customer['status']
                                            === 'Active'
                                        ): ?>


                                            <a
                                                href="customers.php?id=<?php echo (int) $customer['user_id']; ?>&status=Inactive"
                                                class="btn-warning"
                                                onclick="return confirm('Are you sure you want to suspend this customer?');"
                                            >

                                                Suspend

                                            </a>


                                        <?php else: ?>


                                            <a
                                                href="customers.php?id=<?php echo (int) $customer['user_id']; ?>&status=Active"
                                                class="btn-success"
                                            >

                                                Activate

                                            </a>


                                        <?php endif; ?>



                                        <!-- DELETE -->

                                        <a
                                            href="customers.php?delete=<?php echo (int) $customer['user_id']; ?>"
                                            class="btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this customer?');"
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
                                colspan="6"
                                class="no-data"
                            >

                                No customers registered yet.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


</main>


</body>

</html>
