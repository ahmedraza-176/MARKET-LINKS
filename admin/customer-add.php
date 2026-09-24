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


$error = "";


// =========================================
// ADD CUSTOMER
// =========================================

if (isset($_POST['add_customer'])) {


    $name = trim(
        $_POST['name'] ?? ''
    );


    $email = trim(
        $_POST['email'] ?? ''
    );


    $phone = trim(
        $_POST['phone'] ?? ''
    );


    $password = $_POST['password'] ?? '';



    // =====================================
    // VALIDATION
    // =====================================

    if ($name === "") {

        $error =
            "Customer name is required.";

    }

    elseif ($email === "") {

        $error =
            "Customer email is required.";

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

    elseif ($password === "") {

        $error =
            "Password is required.";

    }

    elseif (strlen($password) < 6) {

        $error =
            "Password must be at least 6 characters.";

    }

    else {


        // =================================
        // CHECK EMAIL
        // =================================

        $check_query = "
            SELECT user_id
            FROM Users
            WHERE email = ?
            LIMIT 1
        ";


        $check_stmt = mysqli_prepare(
            $conn,
            $check_query
        );


        mysqli_stmt_bind_param(
            $check_stmt,
            "s",
            $email
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
            // HASH PASSWORD
            // =================================

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            // =================================
            // INSERT CUSTOMER
            // =================================

            $insert_query = "
                INSERT INTO Users
                (
                    name,
                    email,
                    password,
                    role,
                    phone,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'Customer',
                    ?,
                    'Active'
                )
            ";


            $stmt = mysqli_prepare(
                $conn,
                $insert_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $name,
                $email,
                $hashed_password,
                $phone
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                header(
                    "Location: customers.php?success=added"
                );

                exit();

            }

            else {

                $error =
                    "Unable to add customer. Please try again.";

            }

        }

    }

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
        Add Customer - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <style>

        .form-container {

            max-width: 700px;

            margin: 0 auto;

        }


        .admin-form {

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


        .form-group input {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 7px;

            box-sizing: border-box;

            outline: none;

            font-size: 14px;

        }


        .form-group input:focus {

            border-color: #2e7d32;

        }


        .form-error {

            background: #ffebee;

            color: #c62828;

            border-left:
                4px solid #c62828;

            padding: 13px 15px;

            border-radius: 7px;

            margin-bottom: 20px;

        }


        .form-buttons {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

        }


        .save-btn {

            border: none;

            background: #198754;

            color: white;

            padding: 11px 20px;

            border-radius: 7px;

            cursor: pointer;

            font-weight: 600;

        }


        .save-btn:hover {

            background: #157347;

        }


        .cancel-btn {

            text-decoration: none;

            background: #f1f3f5;

            color: #444;

            padding: 11px 20px;

            border-radius: 7px;

            font-weight: 600;

        }


        .cancel-btn:hover {

            background: #e2e6e9;

        }


        @media (max-width: 600px) {

            .admin-form {

                padding: 20px;

            }


            .form-buttons {

                flex-direction: column;

            }


            .save-btn,
            .cancel-btn {

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


    <!-- TOPBAR -->

    <div class="topbar">


        <div>

            <h1>
                Add Customer
            </h1>


            <p>
                Create a new MarketLink customer account.
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



    <!-- CONTENT -->

    <section class="content-box">


        <div class="form-container">


            <?php if ($error !== ""): ?>

                <div class="form-error">

                    <?php

                    echo htmlspecialchars(
                        $error
                    );

                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                class="admin-form"
            >


                <!-- NAME -->

                <div class="form-group">


                    <label for="name">
                        Customer Name
                    </label>


                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter customer name"
                        value="<?php

                            echo htmlspecialchars(
                                $_POST['name'] ?? ''
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
                        placeholder="Enter customer email"
                        value="<?php

                            echo htmlspecialchars(
                                $_POST['email'] ?? ''
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
                                $_POST['phone'] ?? ''
                            );

                        ?>"
                    >


                </div>



                <!-- PASSWORD -->

                <div class="form-group">


                    <label for="password">
                        Password
                    </label>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        required
                    >


                    <small
                        style="
                            display:block;
                            margin-top:6px;
                            color:#777;
                        "
                    >

                        Minimum 6 characters.

                    </small>


                </div>



                <!-- BUTTONS -->

                <div class="form-buttons">


                    <a
                        href="customers.php"
                        class="cancel-btn"
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        name="add_customer"
                        class="save-btn"
                    >

                        + Add Customer

                    </button>


                </div>


            </form>


        </div>


    </section>


</main>


</body>

</html>
