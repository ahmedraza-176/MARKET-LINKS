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
// CHECK FARMER ID
// =========================================

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    header("Location: farmers.php");
    exit();

}

$farmer_id = (int) $_GET['id'];


// =========================================
// FETCH FARMER
// =========================================

$query = "
    SELECT
        user_id,
        name,
        email,
        phone,
        status

    FROM Users

    WHERE user_id = ?
    AND role = 'Farmer'

    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $farmer_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$farmer = mysqli_fetch_assoc($result);


// =========================================
// FARMER NOT FOUND
// =========================================

if (!$farmer) {

    header("Location: farmers.php");
    exit();

}


// =========================================
// VARIABLES
// =========================================

$error = "";


// =========================================
// UPDATE FARMER
// =========================================

if (isset($_POST['update_farmer'])) {


    $name = trim(
        $_POST['name'] ?? ''
    );


    $email = trim(
        $_POST['email'] ?? ''
    );


    $phone = trim(
        $_POST['phone'] ?? ''
    );


    $status = $_POST['status'] ?? '';


    // =====================================
    // VALIDATION
    // =====================================

    if ($name === "") {

        $error =
            "Farmer name is required.";

    }

    elseif ($email === "") {

        $error =
            "Email is required.";

    }

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }

    elseif (
        $status !== 'Active' &&
        $status !== 'Inactive'
    ) {

        $error =
            "Invalid farmer status.";

    }

    else {


        // =================================
        // CHECK DUPLICATE EMAIL
        // =================================

        $check_query = "
            SELECT user_id

            FROM Users

            WHERE email = ?
            AND user_id != ?

            LIMIT 1
        ";


        $check_stmt = mysqli_prepare(
            $conn,
            $check_query
        );


        mysqli_stmt_bind_param(
            $check_stmt,
            "si",
            $email,
            $farmer_id
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

            $error =
                "This email is already registered.";

        }

        else {


            // =================================
            // UPDATE FARMER
            // =================================

            $update_query = "

                UPDATE Users

                SET
                    name = ?,
                    email = ?,
                    phone = ?,
                    status = ?

                WHERE user_id = ?
                AND role = 'Farmer'

            ";


            $update_stmt = mysqli_prepare(
                $conn,
                $update_query
            );


            mysqli_stmt_bind_param(
                $update_stmt,
                "ssssi",
                $name,
                $email,
                $phone,
                $status,
                $farmer_id
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {

                header(
                    "Location: farmers.php?success=updated"
                );

                exit();

            }

            else {

                $error =
                    "Something went wrong. Please try again.";

            }

        }

    }


    // =====================================
    // KEEP ENTERED VALUES AFTER ERROR
    // =====================================

    $farmer['name'] =
        $name;

    $farmer['email'] =
        $email;

    $farmer['phone'] =
        $phone;

    $farmer['status'] =
        $status;

}

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
        Edit Farmer - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <style>

        /* =====================================
           FORM
        ===================================== */

        .admin-form {

            max-width: 750px;

            margin: 0 auto;

            background: #ffffff;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        .form-group {

            margin-bottom: 20px;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: 600;

            color: #333;

        }


        .form-group input,
        .form-group select {

            width: 100%;

            padding: 12px 13px;

            border: 1px solid #ddd;

            border-radius: 7px;

            font-size: 14px;

            font-family: inherit;

            outline: none;

            background: #fff;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #2e7d32;

            box-shadow:
                0 0 0 3px
                rgba(46, 125, 50, 0.08);

        }


        /* =====================================
           FORM HEADER
        ===================================== */

        .form-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

        }


        .form-header h2 {

            margin: 0;

            color: #163a24;

        }


        .back-btn {

            text-decoration: none;

            background: #f1f3f5;

            color: #333;

            padding: 10px 15px;

            border-radius: 7px;

            font-size: 13px;

            font-weight: 600;

        }


        .back-btn:hover {

            background: #e2e6e9;

        }


        /* =====================================
           ERROR
        ===================================== */

        .form-error {

            max-width: 750px;

            margin: 0 auto 20px;

            padding: 13px 15px;

            background: #ffebee;

            color: #c62828;

            border-left:
                4px solid #c62828;

            border-radius: 7px;

            font-size: 14px;

        }


        /* =====================================
           BUTTONS
        ===================================== */

        .form-buttons {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top:
                1px solid #eee;

        }


        .cancel-btn {

            text-decoration: none;

            background: #f1f3f5;

            color: #444;

            padding: 11px 20px;

            border-radius: 7px;

            font-size: 14px;

            font-weight: 600;

        }


        .cancel-btn:hover {

            background: #e2e6e9;

        }


        .save-btn {

            border: none;

            background: #198754;

            color: white;

            padding: 11px 20px;

            border-radius: 7px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

        }


        .save-btn:hover {

            background: #157347;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 600px) {

            .form-header {

                flex-direction: column;

                align-items: flex-start;

            }


            .admin-form {

                padding: 20px;

            }


            .form-buttons {

                flex-direction: column;

            }


            .cancel-btn,
            .save-btn {

                width: 100%;

                text-align: center;

                box-sizing: border-box;

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
                Edit Farmer
            </h1>


            <p>
                Update farmer account information.
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


        <div class="form-header">


            <div>

                <h2>
                    Farmer Information
                </h2>

            </div>


            <a
                href="farmers.php"
                class="back-btn"
            >

                ← Back to Farmers

            </a>


        </div>



        <!-- =====================================
             ERROR MESSAGE
        ===================================== -->

        <?php if ($error !== ""): ?>

            <div class="form-error">

                <?php

                echo htmlspecialchars(
                    $error
                );

                ?>

            </div>

        <?php endif; ?>



        <!-- =====================================
             FORM
        ===================================== -->

        <form
            method="POST"
            action=""
            class="admin-form"
        >


            <!-- NAME -->

            <div class="form-group">


                <label for="name">

                    Farmer Name

                </label>


                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter farmer name"
                    value="<?php

                        echo htmlspecialchars(
                            $farmer['name']
                        );

                    ?>"
                    required
                >


            </div>



            <!-- EMAIL -->

            <div class="form-group">


                <label for="email">

                    Email

                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter farmer email"
                    value="<?php

                        echo htmlspecialchars(
                            $farmer['email']
                        );

                    ?>"
                    required
                >


            </div>



            <!-- PHONE -->

            <div class="form-group">


                <label for="phone">

                    Phone

                </label>


                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter phone number"
                    value="<?php

                        echo htmlspecialchars(
                            $farmer['phone'] ?? ''
                        );

                    ?>"
                >


            </div>



            <!-- STATUS -->

            <div class="form-group">


                <label for="status">

                    Account Status

                </label>


                <select
                    id="status"
                    name="status"
                    required
                >


                    <option
                        value="Active"
                        <?php

                        echo (
                            $farmer['status']
                            === 'Active'
                        )
                        ? 'selected'
                        : '';

                        ?>
                    >

                        Active

                    </option>


                    <option
                        value="Inactive"
                        <?php

                        echo (
                            $farmer['status']
                            === 'Inactive'
                        )
                        ? 'selected'
                        : '';

                        ?>
                    >

                        Inactive

                    </option>


                </select>


            </div>



            <!-- BUTTONS -->

            <div class="form-buttons">


                <a
                    href="farmers.php"
                    class="cancel-btn"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    name="update_farmer"
                    class="save-btn"
                >

                    Update Farmer

                </button>


            </div>


        </form>


    </section>


</main>


</body>

</html>
