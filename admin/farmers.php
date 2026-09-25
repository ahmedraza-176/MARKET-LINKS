<?php

session_start();

require_once "../config/connection.php";

/* =====================================================
   ADMIN LOGIN CHECK
===================================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");
    exit();

}


/* =====================================================
   ADMIN ROLE CHECK
===================================================== */

if ($_SESSION['role'] !== 'Admin') {

    header("Location: ../auth/login.php");
    exit();

}


/* =====================================================
   DELETE FARMER
===================================================== */

if (isset($_GET['delete'])) {

    $farmer_id = (int) $_GET['delete'];

    $delete_query = "
        DELETE FROM Users
        WHERE user_id = ?
        AND role = 'Farmer'
    ";

    $stmt = mysqli_prepare($conn, $delete_query);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $farmer_id
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);
    }

    header("Location: farmers.php");
    exit();

}


/* =====================================================
   CHANGE FARMER STATUS
===================================================== */

if (
    isset($_GET['status']) &&
    isset($_GET['id'])
) {

    $farmer_id = (int) $_GET['id'];

    $status = $_GET['status'];

    if (
        $status === 'Active' ||
        $status === 'Inactive'
    ) {

        $status_query = "
            UPDATE Users
            SET status = ?
            WHERE user_id = ?
            AND role = 'Farmer'
        ";

        $stmt = mysqli_prepare(
            $conn,
            $status_query
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $status,
                $farmer_id
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);
        }
    }

    header("Location: farmers.php");
    exit();

}


/* =====================================================
   FETCH FARMERS + MARKET NAME
===================================================== */

$query = "

    SELECT

        u.user_id,
        u.name,
        u.email,
        u.phone,
        u.status,

        GROUP_CONCAT(
            DISTINCT m.market_name
            SEPARATOR ', '
        ) AS market_names

    FROM Users u

    LEFT JOIN Products p
        ON u.user_id = p.farmer_id

    LEFT JOIN Markets m
        ON p.market_id = m.market_id

    WHERE u.role = 'Farmer'

    GROUP BY

        u.user_id,
        u.name,
        u.email,
        u.phone,
        u.status

    ORDER BY u.user_id DESC

";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Farmers - MarketLink</title>


    <!-- ADMIN CSS -->

    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <!-- FARMERS PAGE CSS -->

    <style>

        /* =========================================
           FARMERS PAGE
        ========================================= */

        .farmers-content {

            width: 100%;

            max-width: none;

        }


        /* =========================================
           MARKET NAME
        ========================================= */

        .market-name {

            color: #333;

            font-size: 13px;

            line-height: 1.5;

        }


        /* =========================================
           DARK MODE
        ========================================= */

        body.dark-mode .market-name {

            color: #dce5df;

        }


        body.dark-mode .table-subtext {

            color: #aebbb2;

        }


        /* =========================================
           FARMER TABLE
        ========================================= */

        .farmers-table {

            width: 100%;

            min-width: 850px;

        }


        /* =========================================
           ACTION BUTTONS
        ========================================= */

        .table-actions {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 7px;

        }


        .table-actions a {

            white-space: nowrap;

        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1100px) {

            .farmers-table {

                min-width: 950px;

            }

        }


        @media (max-width: 650px) {

            .farmers-table {

                min-width: 950px;

            }

        }

    </style>

</head>


<body>


<?php

require_once "partials/menu.php";

?>


<!-- =================================================
     MAIN CONTENT
================================================= -->

<main class="main-content">


    <!-- =================================================
         TOPBAR
    ================================================= -->

    <div class="topbar">

        <div>

            <h1>
                Farmers
            </h1>

            <p>
                Manage MarketLink farmers
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



    <!-- =================================================
         CONTENT BOX
    ================================================= -->

    <section class="content-box farmers-content">


        <!-- =================================================
             PAGE HEADER
        ================================================= -->

        <div class="page-header">


            <div>

                <h2>
                    All Farmers
                </h2>

                <p>
                    View and manage registered farmers.
                </p>

            </div>


            <!-- ADD FARMER -->

            <a
                href="farmer-add.php"
                class="add-btn"
            >
                + Add Farmer
            </a>


        </div>



        <!-- =================================================
             FARMERS TABLE
        ================================================= -->

        <div class="table-wrapper">


            <table class="admin-table farmers-table">


                <!-- TABLE HEADER -->

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
                            Market
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>



                <!-- TABLE BODY -->

                <tbody>


                    <?php if (
                        $result &&
                        mysqli_num_rows($result) > 0
                    ): ?>


                        <?php while (
                            $farmer =
                            mysqli_fetch_assoc($result)
                        ): ?>


                            <tr>


                                <!-- =========================
                                     ID
                                ========================== -->

                                <td>

                                    <?php

                                    echo (int)
                                        $farmer['user_id'];

                                    ?>

                                </td>



                                <!-- =========================
                                     NAME
                                ========================== -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $farmer['name']
                                    );

                                    ?>

                                </td>



                                <!-- =========================
                                     EMAIL
                                ========================== -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $farmer['email']
                                    );

                                    ?>

                                </td>



                                <!-- =========================
                                     PHONE
                                ========================== -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $farmer['phone']
                                        ?? 'N/A'
                                    );

                                    ?>

                                </td>



                                <!-- =========================
                                     MARKET
                                ========================== -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $farmer['market_names']
                                        )
                                    ):

                                    ?>

                                        <span class="market-name">

                                            <?php

                                            echo htmlspecialchars(
                                                $farmer['market_names']
                                            );

                                            ?>

                                        </span>

                                    <?php

                                    else:

                                    ?>

                                        <span class="table-subtext">

                                            No Market Assigned

                                        </span>

                                    <?php

                                    endif;

                                    ?>

                                </td>



                                <!-- =========================
                                     STATUS
                                ========================== -->

                                <td>

                                    <?php

                                    if (
                                        $farmer['status']
                                        === 'Active'
                                    ):

                                    ?>

                                        <span class="status active">

                                            Active

                                        </span>

                                    <?php

                                    else:

                                    ?>

                                        <span class="status inactive">

                                            Inactive

                                        </span>

                                    <?php

                                    endif;

                                    ?>

                                </td>



                                <!-- =========================
                                     ACTIONS
                                ========================== -->

                                <td>

                                    <div class="table-actions">


                                        <!-- EDIT -->

                                        <a
                                            href="farmer-edit.php?id=<?php echo (int) $farmer['user_id']; ?>"
                                            class="btn-edit"
                                        >
                                            Edit
                                        </a>



                                        <!-- STATUS -->

                                        <?php

                                        if (
                                            $farmer['status']
                                            === 'Active'
                                        ):

                                        ?>

                                            <a
                                                href="farmers.php?id=<?php echo (int) $farmer['user_id']; ?>&status=Inactive"
                                                class="btn-warning"
                                                onclick="return confirm('Are you sure you want to suspend this farmer?');"
                                            >
                                                Suspend
                                            </a>

                                        <?php

                                        else:

                                        ?>

                                            <a
                                                href="farmers.php?id=<?php echo (int) $farmer['user_id']; ?>&status=Active"
                                                class="btn-success"
                                                onclick="return confirm('Activate this farmer?');"
                                            >
                                                Activate
                                            </a>

                                        <?php

                                        endif;

                                        ?>



                                        <!-- DELETE -->

                                        <a
                                            href="farmers.php?delete=<?php echo (int) $farmer['user_id']; ?>"
                                            class="btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this farmer?');"
                                        >
                                            Delete
                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- NO FARMERS -->

                        <tr>

                            <td
                                colspan="7"
                                class="no-data"
                            >

                                No farmers registered yet.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


</main>



<!-- =================================================
     DARK MODE BUTTON
================================================= -->

<button
    type="button"
    id="darkModeToggle"
    class="dark-mode-toggle"
    title="Dark Mode"
    aria-label="Toggle Dark Mode"
>
    🌙
</button>



</body>

</html>