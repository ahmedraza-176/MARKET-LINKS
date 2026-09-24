<?php

session_start();

require_once "../config/connection.php";


// =========================================
// CUSTOMER LOGIN CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");
    exit();

}


if ($_SESSION['role'] !== 'Customer') {

    header("Location: ../auth/login.php");
    exit();

}


$user_id = (int) $_SESSION['user_id'];

$success = "";
$error = "";


// =========================================
// ACTIVE PAGE
// =========================================

$current_page = basename($_SERVER['PHP_SELF']);


// =========================================
// UPDATE PROFILE
// =========================================

if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');


    // =====================================
    // VALIDATION
    // =====================================

    if ($name === "" || $email === "") {

        $error = "Name and email are required.";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }

    else {


        // =====================================
        // CHECK DUPLICATE EMAIL
        // =====================================

        $check_email = "
            SELECT user_id
            FROM Users
            WHERE email = ?
            AND user_id != ?
            LIMIT 1
        ";


        $stmt = mysqli_prepare(
            $conn,
            $check_email
        );


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $email,
            $user_id
        );


        mysqli_stmt_execute($stmt);


        $email_result =
            mysqli_stmt_get_result($stmt);


        if (mysqli_num_rows($email_result) > 0) {

            $error =
                "This email is already being used by another account.";

        }

        else {


            // =====================================
            // UPDATE PROFILE
            // =====================================

            $update_query = "
                UPDATE Users

                SET
                    name = ?,
                    email = ?,
                    phone = ?

                WHERE user_id = ?
            ";


            $stmt = mysqli_prepare(
                $conn,
                $update_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $name,
                $email,
                $phone,
                $user_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $success =
                    "Profile updated successfully.";

            }

            else {

                $error =
                    "Something went wrong. Please try again.";

            }

        }

    }

}


// =========================================
// FETCH CUSTOMER DATA
// =========================================

$query = "
    SELECT
        user_id,
        name,
        email,
        phone,
        role,
        status

    FROM Users

    WHERE user_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$user =
    mysqli_fetch_assoc($result);


// =========================================
// USER NOT FOUND
// =========================================

if (!$user) {

    session_destroy();

    header("Location: ../auth/login.php");

    exit();

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
        My Profile - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/customers.css"
    >


    <style>

        /* =====================================
           PROFILE PAGE
        ===================================== */

        .profile-page {

            max-width: 900px;

            margin: 0 auto;

            padding: 40px 25px;

        }


        /* =====================================
           HEADER
        ===================================== */

        .profile-header {

            margin-bottom: 25px;

        }


        .profile-header h1 {

            color: #163a24;

            margin-bottom: 8px;

        }


        .profile-header p {

            color: #777;

        }


        /* =====================================
           PROFILE CARD
        ===================================== */

        .profile-card {

            background: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        /* =====================================
           PROFILE TOP
        ===================================== */

        .profile-top {

            display: flex;

            align-items: center;

            gap: 20px;

            padding-bottom: 25px;

            margin-bottom: 25px;

            border-bottom: 1px solid #eee;

        }


        .profile-avatar {

            width: 65px;

            height: 65px;

            min-width: 65px;

            border-radius: 50%;

            background: #e8f5e9;

            color: #2e7d32;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

            font-weight: bold;

        }


        .profile-top h2 {

            margin: 0 0 5px;

            color: #163a24;

        }


        .profile-top p {

            margin: 0;

            color: #777;

            font-size: 14px;

        }


        /* =====================================
           FORM
        ===================================== */

        .form-group {

            margin-bottom: 20px;

        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

            color: #444;

        }


        .form-group input {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 6px;

            font-size: 15px;

            outline: none;

            box-sizing: border-box;

        }


        .form-group input:focus {

            border-color: #2e7d32;

        }


        .form-help {

            display: block;

            margin-top: 5px;

            font-size: 12px;

            color: #888;

        }


        /* =====================================
           UPDATE BUTTON
        ===================================== */

        .update-btn {

            border: none;

            background: #2e7d32;

            color: white;

            padding: 12px 22px;

            border-radius: 6px;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

        }


        .update-btn:hover {

            background: #245f27;

        }


        /* =====================================
           MESSAGES
        ===================================== */

        .success-message {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .error-message {

            background: #ffebee;

            color: #c62828;

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 700px) {

            .profile-page {

                padding: 25px 15px;

            }

        }


        @media (max-width: 600px) {

            .profile-card {

                padding: 20px;

            }


            .profile-top {

                align-items: flex-start;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =====================================
     CUSTOMER SIDEBAR
===================================== -->

<aside class="sidebar">


    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Customer Panel
        </p>

    </div>


    <nav class="sidebar-menu">


        <a
            href="dashboard.php"
            class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Dashboard

        </a>


        <a
            href="products.php"
            class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Products

        </a>


        <a
            href="markets.php"
            class="<?php echo $current_page === 'markets.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Markets

        </a>


        <a
            href="cart.php"
            class="<?php echo $current_page === 'cart.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Cart

        </a>


        <a
            href="orders.php"
            class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
        >

            <span></span>

            My Orders

        </a>


        <a
            href="favorites.php"
            class="<?php echo $current_page === 'favorites.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Favorites

        </a>


        <a
            href="notifications.php"
            class="<?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Notifications

        </a>


        <a
            href="profile.php"
            class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Profile

        </a>


    </nav>


    <div class="sidebar-bottom">

        <a href="../auth/logout.php">

            <span></span>

            Logout

        </a>

    </div>


</aside>


<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="customer-main">


    <div class="profile-page">


        <!-- =====================================
             HEADER
        ====================================== -->

        <div class="profile-header">

            <h1>
                My Profile
            </h1>

            <p>
                View and update your account information.
            </p>

        </div>


        <!-- =====================================
             PROFILE CARD
        ====================================== -->

        <div class="profile-card">


            <!-- PROFILE TOP -->

            <div class="profile-top">


                <div class="profile-avatar">

                    <?php

                    echo strtoupper(
                        substr($user['name'], 0, 1)
                    );

                    ?>

                </div>


                <div>

                    <h2>

                        <?php

                        echo htmlspecialchars(
                            $user['name']
                        );

                        ?>

                    </h2>


                    <p>
                        Customer Account
                    </p>

                </div>


            </div>


            <!-- =====================================
                 SUCCESS MESSAGE
            ====================================== -->

            <?php if ($success !== ""): ?>

                <div class="success-message">

                    <?php

                    echo htmlspecialchars(
                        $success
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- =====================================
                 ERROR MESSAGE
            ====================================== -->

            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <?php

                    echo htmlspecialchars(
                        $error
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- =====================================
                 PROFILE FORM
            ====================================== -->

            <form
                method="POST"
                action=""
            >


                <!-- NAME -->

                <div class="form-group">

                    <label for="name">

                        Full Name

                    </label>


                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php
                            echo htmlspecialchars(
                                $user['name']
                            );
                        ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">

                        Email Address

                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php
                            echo htmlspecialchars(
                                $user['email']
                            );
                        ?>"
                        required
                    >


                    <span class="form-help">

                        Email must be unique.

                    </span>

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label for="phone">

                        Phone Number

                    </label>


                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?php
                            echo htmlspecialchars(
                                $user['phone'] ?? ''
                            );
                        ?>"
                        placeholder="Enter your phone number"
                    >

                </div>


                <!-- UPDATE -->

                <button
                    type="submit"
                    name="update_profile"
                    class="update-btn"
                >

                    Update Profile

                </button>


            </form>


        </div>


    </div>


</main>


</body>

</html>