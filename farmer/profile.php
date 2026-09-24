<?php

session_start();

require_once "../config/connection.php";


// =============================
// FARMER LOGIN CHECK
// =============================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['user_id'];

$success = "";
$error = "";


// =============================
// UPDATE PROFILE
// =============================

if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);


    // Validation

    if ($name === "" || $email === "") {

        $error = "Name and email are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        // =============================
        // CHECK DUPLICATE EMAIL
        // =============================

        $check_query = "
            SELECT user_id
            FROM Users
            WHERE email = ?
            AND user_id != ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $check_query
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $email,
            $farmer_id
        );

        mysqli_stmt_execute($stmt);

        $check_result = mysqli_stmt_get_result($stmt);


        if (mysqli_num_rows($check_result) > 0) {

            $error = "This email is already registered.";

        } else {

            // =============================
            // UPDATE USER
            // =============================

            $update_query = "
                UPDATE Users
                SET name = ?,
                    email = ?,
                    phone = ?
                WHERE user_id = ?
                AND role = 'Farmer'
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
                $farmer_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $success =
                    "Profile updated successfully.";

            } else {

                $error =
                    "Unable to update profile.";

            }

        }
    }
}


// =============================
// FETCH FARMER DATA
// =============================

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


if (!$farmer) {
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

    <title>Farmer Profile - MarketLink</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f4f6f8;

            color: #333;

        }


        /* =============================
           SIDEBAR
        ============================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 240px;

            height: 100vh;

            background: #163a24;

            color: white;

            display: flex;

            flex-direction: column;

        }


        .sidebar-logo {

            padding: 25px 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.1);

        }


        .sidebar-logo h2 {

            margin-bottom: 5px;

        }


        .sidebar-logo p {

            font-size: 13px;

            color: #b8d1bd;

        }


        .sidebar-menu {

            padding: 20px 12px;

            flex: 1;

        }


        .sidebar-menu a,
        .sidebar-bottom a {

            display: block;

            color: #d9e8dc;

            text-decoration: none;

            padding: 12px 15px;

            margin-bottom: 5px;

            border-radius: 6px;

            font-size: 14px;

        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {

            background: #2e7d32;

            color: white;

        }


        .sidebar-bottom {

            padding: 15px 12px;

            border-top:
                1px solid
                rgba(255,255,255,0.1);

        }


        .logout:hover {

            background: #b3261e;

            color: white;

        }


        /* =============================
           MAIN CONTENT
        ============================= */

        .main-content {

            margin-left: 240px;

            padding: 30px;

        }


        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            color: #163a24;

            margin-bottom: 7px;

        }


        .page-header p {

            color: #777;

            font-size: 14px;

        }


        /* =============================
           ALERTS
        ============================= */

        .success {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 13px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        .error {

            background: #ffebee;

            color: #c62828;

            padding: 13px 15px;

            border-radius: 6px;

            margin-bottom: 20px;

        }


        /* =============================
           PROFILE CARD
        ============================= */

        .profile-card {

            max-width: 850px;

            background: white;

            padding: 35px;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,0.06);

        }


        /* =============================
           PROFILE TOP
        ============================= */

        .profile-top {

            display: flex;

            align-items: center;

            gap: 20px;

            padding-bottom: 25px;

            margin-bottom: 30px;

            border-bottom: 1px solid #eee;

        }


        .avatar {

            width: 80px;

            height: 80px;

            border-radius: 50%;

            background: #2e7d32;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            font-weight: bold;

        }


        .profile-top h2 {

            color: #163a24;

            margin-bottom: 5px;

        }


        .profile-top p {

            color: #777;

            font-size: 13px;

        }


        .farmer-badge {

            display: inline-block;

            margin-top: 8px;

            padding: 5px 10px;

            border-radius: 20px;

            background: #e8f5e9;

            color: #2e7d32;

            font-size: 11px;

            font-weight: bold;

        }


        /* =============================
           FORM
        ============================= */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group.full {

            grid-column: 1 / -1;

        }


        .form-group label {

            margin-bottom: 7px;

            color: #444;

            font-size: 14px;

            font-weight: bold;

        }


        .form-group input {

            width: 100%;

            padding: 12px 13px;

            border: 1px solid #ddd;

            border-radius: 6px;

            outline: none;

            font-size: 14px;

        }


        .form-group input:focus {

            border-color: #2e7d32;

        }


        .readonly {

            background: #f4f6f8;

            color: #777;

        }


        .form-help {

            margin-top: 6px;

            font-size: 11px;

            color: #888;

        }


        /* =============================
           BUTTON
        ============================= */

        .form-actions {

            margin-top: 25px;

        }


        .update-btn {

            border: none;

            background: #2e7d32;

            color: white;

            padding: 12px 22px;

            border-radius: 6px;

            cursor: pointer;

            font-size: 14px;

            font-weight: bold;

        }


        .update-btn:hover {

            background: #245f27;

        }


        /* =============================
           ACCOUNT INFO
        ============================= */

        .account-info {

            margin-top: 30px;

            padding-top: 25px;

            border-top: 1px solid #eee;

        }


        .account-info h3 {

            color: #163a24;

            margin-bottom: 15px;

        }


        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

        }


        .info-box {

            background: #f8f9fa;

            padding: 15px;

            border-radius: 7px;

        }


        .info-box span {

            display: block;

            color: #888;

            font-size: 11px;

            margin-bottom: 5px;

            text-transform: uppercase;

        }


        .info-box strong {

            color: #333;

            font-size: 14px;

        }


        /* =============================
           RESPONSIVE
        ============================= */

        @media (max-width: 800px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }


            .sidebar-menu {

                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .main-content {

                margin-left: 0;

                padding: 20px;

            }


            .form-grid {

                grid-template-columns: 1fr;

            }


            .form-group.full {

                grid-column: auto;

            }


            .info-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 500px) {

            .profile-card {

                padding: 20px;

            }


            .profile-top {

                align-items: flex-start;

                flex-direction: column;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =============================
     SIDEBAR
============================= -->

<aside class="sidebar">


    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Farmer Panel
        </p>

    </div>


    <nav class="sidebar-menu">

        <a href="dashboard.php">
            Dashboard
        </a>


        <a href="products.php">
            My Products
        </a>


        <a href="orders.php">
            Orders
        </a>


        <a
            href="profile.php"
            class="active"
        >
            Profile
        </a>


        <a href="reports.php">
            Reports
        </a>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="../auth/logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>

</aside>


<!-- =============================
     MAIN CONTENT
============================= -->

<main class="main-content">


    <div class="page-header">

        <h1>
            My Profile
        </h1>

        <p>
            Manage your farmer account information.
        </p>

    </div>


    <!-- ALERTS -->

    <?php if ($success !== ""): ?>

        <div class="success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- PROFILE CARD -->

    <div class="profile-card">


        <!-- PROFILE HEADER -->

        <div class="profile-top">


            <div class="avatar">

                <?php
                echo strtoupper(
                    substr($farmer['name'], 0, 1)
                );
                ?>

            </div>


            <div>

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $farmer['name']
                    );
                    ?>

                </h2>


                <p>

                    <?php
                    echo htmlspecialchars(
                        $farmer['email']
                    );
                    ?>

                </p>


                <span class="farmer-badge">

                    FARMER ACCOUNT

                </span>

            </div>


        </div>


        <!-- PROFILE FORM -->

        <form
            method="POST"
            action=""
        >


            <div class="form-grid">


                <!-- NAME -->

                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?php
                            echo htmlspecialchars(
                                $farmer['name']
                            );
                        ?>"
                        placeholder="Enter your full name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label>
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php
                            echo htmlspecialchars(
                                $farmer['email']
                            );
                        ?>"
                        placeholder="Enter your email"
                        required
                    >

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label>
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php
                            echo htmlspecialchars(
                                $farmer['phone'] ?? ''
                            );
                        ?>"
                        placeholder="Enter your phone number"
                    >

                </div>


                <!-- ROLE -->

                <div class="form-group">

                    <label>
                        Account Role
                    </label>

                    <input
                        type="text"
                        value="Farmer"
                        class="readonly"
                        readonly
                    >

                </div>


            </div>


            <!-- BUTTON -->

            <div class="form-actions">

                <button
                    type="submit"
                    name="update_profile"
                    class="update-btn"
                >

                    Update Profile

                </button>

            </div>


        </form>


        <!-- ACCOUNT INFORMATION -->

        <div class="account-info">

            <h3>
                Account Information
            </h3>


            <div class="info-grid">


                <div class="info-box">

                    <span>
                        User ID
                    </span>

                    <strong>

                        #<?php
                        echo $farmer['user_id'];
                        ?>

                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Account Status
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $farmer['status']
                        );
                        ?>

                    </strong>

                </div>


                <div class="info-box">

                    <span>
                        Role
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $farmer['role']
                        );
                        ?>
                    </strong>

                </div>


            </div>

        </div>


    </div>


</main>


</body>

</html>