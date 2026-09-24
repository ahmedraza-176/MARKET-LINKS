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
// DELETE MARKET
// =========================================

if (isset($_GET['delete'])) {

    $market_id = (int) $_GET['delete'];


    /*
        Check whether products are using
        this market before deleting.
    */

    $check_query = "
        SELECT product_id
        FROM Products
        WHERE market_id = ?
        LIMIT 1
    ";


    $check_stmt = mysqli_prepare(
        $conn,
        $check_query
    );


    mysqli_stmt_bind_param(
        $check_stmt,
        "i",
        $market_id
    );


    mysqli_stmt_execute(
        $check_stmt
    );


    $check_result =
        mysqli_stmt_get_result(
            $check_stmt
        );


    if (
        mysqli_num_rows(
            $check_result
        ) > 0
    ) {

        header(
            "Location: markets.php?error=used"
        );

        exit();

    }


    // =====================================
    // DELETE MARKET
    // =====================================

    $delete_query = "
        DELETE FROM Markets
        WHERE market_id = ?
    ";


    $stmt = mysqli_prepare(
        $conn,
        $delete_query
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $market_id
    );


    mysqli_stmt_execute(
        $stmt
    );


    header(
        "Location: markets.php?success=deleted"
    );

    exit();

}


// =========================================
// FETCH MARKETS
// =========================================

$query = "
    SELECT
        market_id,
        market_name,
        location,
        latitude,
        longitude,
        map_provider

    FROM Markets

    ORDER BY market_id DESC
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
        Manage Markets - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <style>

        /* =====================================
           BUTTONS
        ===================================== */

        .add-btn {

            display: inline-block;

            background: #198754;

            color: #fff;

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
        .btn-delete,
        .map-btn {

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


        .btn-delete {

            background: #f8d7da;

            color: #b02a37;

        }


        .map-btn {

            background: #d1e7dd;

            color: #146c43;

        }


        .table-actions {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-wrap: wrap;

        }


        .no-data {

            text-align: center;

            padding: 30px;

            color: #777;

        }


        .location-text {

            max-width: 250px;

            line-height: 1.5;

        }


        .coordinates {

            font-size: 12px;

            color: #777;

            line-height: 1.6;

        }


        .message {

            padding: 13px 15px;

            border-radius: 7px;

            margin-bottom: 20px;

            font-size: 14px;

        }


        .success-message {

            background: #d1e7dd;

            color: #146c43;

        }


        .error-message {

            background: #f8d7da;

            color: #b02a37;

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
                Markets
            </h1>


            <p>
                Manage MarketLink market locations.
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
                    All Markets
                </h2>


                <p>
                    Add and manage marketplace locations.
                </p>

            </div>


            <a
                href="market-add.php"
                class="add-btn"
            >

                + Add Market

            </a>


        </div>



        <!-- =====================================
             SUCCESS MESSAGE
        ===================================== -->

        <?php if (
            isset($_GET['success'])
        ): ?>


            <div
                class="message success-message"
            >

                <?php

                if (
                    $_GET['success']
                    === 'added'
                ) {

                    echo
                        "Market added successfully.";

                }

                elseif (
                    $_GET['success']
                    === 'updated'
                ) {

                    echo
                        "Market updated successfully.";

                }

                elseif (
                    $_GET['success']
                    === 'deleted'
                ) {

                    echo
                        "Market deleted successfully.";

                }

                ?>

            </div>


        <?php endif; ?>



        <!-- =====================================
             ERROR MESSAGE
        ===================================== -->

        <?php if (
            isset($_GET['error'])
        ): ?>


            <?php if (
                $_GET['error']
                === 'used'
            ): ?>


                <div
                    class="message error-message"
                >

                    This market cannot be deleted
                    because products are linked to it.

                </div>


            <?php endif; ?>


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
                            Market Name
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Coordinates
                        </th>

                        <th>
                            Map
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
                            $market =
                            mysqli_fetch_assoc($result)
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?php

                                    echo (int)
                                        $market['market_id'];

                                    ?>

                                </td>



                                <!-- MARKET NAME -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $market['market_name']
                                        );

                                        ?>

                                    </strong>

                                </td>



                                <!-- LOCATION -->

                                <td>

                                    <div
                                        class="location-text"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $market['location']
                                            ?? 'N/A'
                                        );

                                        ?>

                                    </div>

                                </td>



                                <!-- COORDINATES -->

                                <td>

                                    <div
                                        class="coordinates"
                                    >

                                        Lat:
                                        <?php

                                        echo htmlspecialchars(
                                            $market['latitude']
                                            ?? 'N/A'
                                        );

                                        ?>

                                        <br>

                                        Long:
                                        <?php

                                        echo htmlspecialchars(
                                            $market['longitude']
                                            ?? 'N/A'
                                        );

                                        ?>

                                    </div>

                                </td>



                                <!-- MAP -->

                                <td>


                                    <?php

                                    $lat =
                                        $market['latitude'];

                                    $lng =
                                        $market['longitude'];

                                    ?>


                                    <?php if (
                                        $lat !== null &&
                                        $lng !== null &&
                                        $lat !== '' &&
                                        $lng !== ''
                                    ): ?>


                                        <a
                                            href="https://www.google.com/maps?q=<?php
                                                echo urlencode(
                                                    $lat . ',' . $lng
                                                );
                                            ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="map-btn"
                                        >

                                            📍 View Map

                                        </a>


                                    <?php else: ?>


                                        <span
                                            style="color:#999;"
                                        >

                                            No coordinates

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- ACTIONS -->

                                <td>


                                    <div
                                        class="table-actions"
                                    >


                                        <a
                                            href="market-edit.php?id=<?php echo (int) $market['market_id']; ?>"
                                            class="btn-edit"
                                        >

                                            Edit

                                        </a>


                                        <a
                                            href="markets.php?delete=<?php echo (int) $market['market_id']; ?>"
                                            class="btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this market?');"
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

                                No markets added yet.

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
