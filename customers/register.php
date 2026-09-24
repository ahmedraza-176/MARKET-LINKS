<?php

session_start();

require_once "../config/connection.php";

$error = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    // =============================
    // VALIDATION
    // =============================

    if ($name === "" || $email === "" || $password === "") {

        $error = "Name, email and password are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {


        // =============================
        // CHECK EMAIL
        // =============================

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

        if (!$check_stmt) {

            $error = "Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($check_stmt);

            $check_result =
                mysqli_stmt_get_result($check_stmt);


            if (mysqli_num_rows($check_result) > 0) {

                $error =
                    "This email is already registered.";

            } else {


                // =============================
                // HASH PASSWORD
                // =============================

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                // =============================
                // INSERT CUSTOMER
                // =============================

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

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_query
                );


                if (!$insert_stmt) {

                    $error =
                        "Database error: "
                        . mysqli_error($conn);

                } else {

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "ssss",
                        $name,
                        $email,
                        $hashed_password,
                        $phone
                    );


                    if (
                        mysqli_stmt_execute(
                            $insert_stmt
                        )
                    ) {

                        header(
                            "Location: ../auth/login.php"
                        );

                        exit();

                    } else {

                        $error =
                            "Registration failed: "
                            . mysqli_stmt_error(
                                $insert_stmt
                            );

                    }

                }

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
        Customer Registration - MarketLink
    </title>

    <link
        rel="stylesheet"
        href="../css/customers.css?v=2"
    >

    <script src="../js/dark-mode.js"></script>

</head>


<body class="register-page">




<div class="register-container">


    <h2>
        MarketLink
    </h2>


    <p class="subtitle">
        Create Customer Account
    </p>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <div class="form-group">

            <label for="name">
                Full Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your name"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['name'] ?? ''
                    );
                ?>"
                required
            >

        </div>


        <div class="form-group">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['email'] ?? ''
                    );
                ?>"
                required
            >

        </div>


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

        </div>


        <div class="form-group">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm password"
                required
            >

        </div>


        <button
            type="submit"
            name="register"
            class="register-btn"
        >

            Create Account

        </button>


    </form>


    <p class="login-link">

        Already have an account?

        <a href="../auth/login.php">
            Login
        </a>

    </p>


</div>


<body class="register-page">

<nav class="navbar">

    <div class="logo">MarketLink</div>

    <div class="nav-links">
        <a href="../auth/login.php">Login</a>
        <a href="register.php" class="active">Register</a>
    </div>

</nav>

</html>