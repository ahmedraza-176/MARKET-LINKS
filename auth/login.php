<?php

session_start();

require_once "../config/connection.php";

$error = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    // =============================
    // VALIDATION
    // =============================

    if ($email === "" || $password === "") {

        $error = "Please enter email and password.";

    } else {

        // =============================
        // FIND USER
        // =============================

        $sql = "
            SELECT *
            FROM Users
            WHERE email = ?
            AND status = 'Active'
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);


        // =============================
        // USER FOUND
        // =============================

        if (mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);


            // =============================
            // VERIFY HASHED PASSWORD
            // =============================

            if (
                password_verify(
                    $password,
                    $user['password']
                )
            ) {


                // =============================
                // SESSION
                // =============================

                $_SESSION['user_id'] = $user['user_id'];

                $_SESSION['name'] = $user['name'];

                $_SESSION['role'] = $user['role'];


                // =============================
                // ROLE REDIRECT
                // =============================

                if ($user['role'] === 'Admin') {

                    header(
                        "Location: ../admin/dashboard.php"
                    );

                    exit();


                } elseif ($user['role'] === 'Farmer') {

                    header(
                        "Location: ../farmer/dashboard.php"
                    );

                    exit();


                } elseif ($user['role'] === 'Customer') {

                    header(
                        "Location: ../customers/dashboard.php"
                    );

                    exit();

                }


            } else {

                $error = "Invalid password.";

            }


        } else {

            $error =
                "Account not found or inactive.";

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
        MarketLink Login
    </title>

    <link
        rel="stylesheet"
        href="../css/login.css"
    >

    <script src="../js/dark-mode.js"></script>

</head>


<body>


    <h2>
        MarketLink Login
    </h2>


    <?php if ($error !== ""): ?>

        <p>
            <?php
            echo htmlspecialchars($error);
            ?>
        </p>

    <?php endif; ?>


    <form method="POST">


        <label>
            Email
        </label>

        <br>


        <input
            type="email"
            name="email"
            required
        >


        <br>
        <br>


        <label>
            Password
        </label>

        <br>


        <input
            type="password"
            name="password"
            required
        >


        <br>
        <br>


        <button
            type="submit"
            name="login"
        >

            Login

        </button>


    </form>


</body>

</html>