<?php

session_start();

require_once "../config/connection.php";

$success = "";
$error = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // =============================
    // VALIDATION
    // =============================

    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Please fill all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

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

        $stmt = mysqli_prepare(
            $conn,
            $check_query
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);


        if (mysqli_num_rows($result) > 0) {

            $error = "This email is already registered.";

        } else {

            // =============================
            // HASH PASSWORD
            // =============================

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // =============================
            // INSERT FARMER
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
                (?, ?, ?, 'Farmer', ?, 'Active')
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


            if (mysqli_stmt_execute($stmt)) {

                $success = "Farmer account created successfully.";

            } else {

                $error = "Registration failed. Please try again.";

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

    <title>Farmer Registration - MarketLink</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            font-family: Arial, sans-serif;

            background: #f4f6f8;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;

        }

        .register-container {

            width: 100%;

            max-width: 480px;

            background: white;

            padding: 35px;

            border-radius: 12px;

            box-shadow:
                0 5px 25px rgba(0, 0, 0, 0.08);

        }

        .register-header {

            text-align: center;

            margin-bottom: 25px;

        }

        .register-header h1 {

            color: #163a24;

            margin-bottom: 8px;

        }

        .register-header p {

            color: #777;

            font-size: 14px;

        }

        .form-group {

            margin-bottom: 18px;

        }

        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: bold;

            color: #444;

        }

        .form-group input {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 6px;

            font-size: 14px;

            outline: none;

        }

        .form-group input:focus {

            border-color: #2e7d32;

        }

        .register-btn {

            width: 100%;

            border: none;

            padding: 13px;

            background: #2e7d32;

            color: white;

            border-radius: 6px;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

        }

        .register-btn:hover {

            background: #245f27;

        }

        .success {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 18px;

            font-size: 14px;

        }

        .error {

            background: #ffebee;

            color: #c62828;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 18px;

            font-size: 14px;

        }

        .login-link {

            text-align: center;

            margin-top: 20px;

            font-size: 14px;

        }

        .login-link a {

            color: #2e7d32;

            text-decoration: none;

            font-weight: bold;

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>

<body>


<div class="register-container">


    <div class="register-header">

        <h1>
            MarketLink
        </h1>

        <p>
            Create your Farmer Account
        </p>

    </div>


    <?php if ($success !== ""): ?>

        <div class="success">

            <?php
            echo htmlspecialchars($success);
            ?>

            <br><br>

            <a href="../auth/login.php">
                Go to Login
            </a>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($success === ""): ?>


        <form
            method="POST"
            action=""
        >


            <div class="form-group">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter your full name"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Phone Number
                </label>

                <input
                    type="text"
                    name="phone"
                    placeholder="Enter your phone number"
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Confirm Password
                </label>

                <input
                    type="password"
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
                Register as Farmer
            </button>


        </form>


        <div class="login-link">

            Already have an account?

            <a href="../auth/login.php">
                Login
            </a>

        </div>


    <?php endif; ?>


</div>


</body>

</html>