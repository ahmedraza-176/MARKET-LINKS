<?php

session_start();

require_once "../config/connection.php";


// =============================
// CUSTOMER LOGIN CHECK
// =============================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");
    exit();

}


// =============================
// CUSTOMER ROLE CHECK
// =============================

if ($_SESSION['role'] !== 'Customer') {

    header("Location: ../auth/login.php");
    exit();

}


$customer_name = $_SESSION['name'];

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
        Customer Dashboard - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/customers.css"
    >



</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <!-- LOGO -->

    <div class="sidebar-logo">

        <h2>
            MarketLink
        </h2>

        <p>
            Customer Panel
        </p>

    </div>



    <!-- MENU -->

    <nav class="sidebar-menu">


        <a
            href="dashboard.php"
            class="active"
        >

            <span></span>

            Dashboard

        </a>


        <a href="products.php">

            <span></span>

            Products

        </a>


        <a href="markets.php">

            <span></span>

            Markets

        </a>


        <a href="cart.php">

            <span></span>

            Cart

        </a>


        <a href="orders.php">

            <span></span>

            My Orders

        </a>


        <a href="favorites.php">

            <span></span>

            Favorites

        </a>


        <a href="notifications.php">

            <span></span>

            Notifications

        </a>


        <a href="profile.php">

            <span></span>

            Profile

        </a>


    </nav>



    <!-- LOGOUT -->

    <div class="sidebar-bottom">

        <a href="../auth/logout.php">

            <span></span>

            Logout

        </a>

    </div>


</aside>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="customer-main">


    <!-- PAGE HEADER -->

    <div class="page-header">


        <div>

            <h1>
                Customer Dashboard
            </h1>

            <p>
                Manage your MarketLink account and orders.
            </p>

        </div>


        <div class="user-name">

            

            <?php

            echo htmlspecialchars(
                $customer_name
            );

            ?>

        </div>


    </div>



    <!-- =========================
         WELCOME
    ========================= -->

    <section class="welcome-section">


        <p class="small-text">

            Welcome back

        </p>


        <h2>

            Hello,
            <?php

            echo htmlspecialchars(
                $customer_name
            );

            ?>

            

        </h2>


        <p>

            Find fresh products from local farmers
            and markets.

        </p>


    </section>



    <!-- =========================
         CARDS
    ========================= -->

    <section class="dashboard-cards">


        <!-- PRODUCTS -->

        <a
            href="products.php"
            class="dashboard-card"
        >

            <div class="card-icon">

                
            </div>


            <h3>

                Browse Products

            </h3>


            <p>

                Explore fresh products from
                local farmers.

            </p>

        </a>



        <!-- MARKETS -->

        <a
            href="markets.php"
            class="dashboard-card"
        >

            <div class="card-icon">

                

            </div>


            <h3>

                Markets

            </h3>


            <p>

                Find markets and their locations.

            </p>

        </a>



        <!-- CART -->

        <a
            href="cart.php"
            class="dashboard-card"
        >

            <div class="card-icon">

                

            </div>


            <h3>

                My Cart

            </h3>


            <p>

                View products added to your cart.

            </p>

        </a>



        <!-- ORDERS -->

        <a
            href="orders.php"
            class="dashboard-card"
        >

            <div class="card-icon">

               

            </div>


            <h3>

                My Orders

            </h3>


            <p>

                Track your orders and pickup status.

            </p>

        </a>


    </section>



    <!-- =========================
         HOW MARKETLINK WORKS
    ========================= -->

    <section class="info-box">


        <h2>

            How MarketLink Works

        </h2>


        <div class="steps">


            <div>

                <strong>
                    1. Browse
                </strong>

                <p>
                    Find products from local farmers.
                </p>

            </div>


            <div>

                <strong>
                    2. Add to Cart
                </strong>

                <p>
                    Select the products you want.
                </p>

            </div>


            <div>

                <strong>
                    3. Pre-order
                </strong>

                <p>
                    Select your pickup date and time.
                </p>

            </div>


            <div>

                <strong>
                    4. Pickup
                </strong>

                <p>
                    Collect your order from the market.
                </p>

            </div>


        </div>


    </section>


</main>


</body>

</html>