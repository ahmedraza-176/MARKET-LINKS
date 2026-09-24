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

if ($_SESSION['role'] !== 'Customer') {
    header("Location: ../auth/login.php");
    exit();
}


// =============================
// ACTIVE SIDEBAR PAGE
// =============================

$current_page = basename($_SERVER['PHP_SELF']);


// =============================
// FETCH MARKETS
// =============================

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

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Market query error: " . mysqli_error($conn));
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

    <title>Markets - MarketLink</title>

    <link
        rel="stylesheet"
        href="../css/customers.css"
    >

    <style>

        /* =====================================
           MARKETS PAGE
        ===================================== */

        .markets-page {
            max-width: 1250px;
            margin: 0 auto;
        }


        /* =====================================
           PAGE HEADER
        ===================================== */

        .markets-header {
            margin-bottom: 25px;
        }

        .markets-header h1 {
            color: #163a24;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .markets-header p {
            color: #777;
            font-size: 15px;
        }


        /* =====================================
           MARKETS GRID
        ===================================== */

        .markets-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 25px;
        }


        /* =====================================
           MARKET CARD
        ===================================== */

        .market-card {
            background: #ffffff;

            border-radius: 12px;

            padding: 25px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .market-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 8px 22px rgba(0, 0, 0, 0.10);
        }


        /* =====================================
           MARKET ICON
        ===================================== */

        .market-icon {
            width: 50px;
            height: 50px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #e8f5e9;

            border-radius: 10px;

            font-size: 25px;

            margin-bottom: 18px;
        }


        /* =====================================
           MARKET NAME
        ===================================== */

        .market-card h2 {
            color: #163a24;

            font-size: 21px;

            margin-bottom: 12px;

            line-height: 1.3;
        }


        /* =====================================
           LOCATION
        ===================================== */

        .market-location {
            color: #666;

            font-size: 14px;

            line-height: 1.6;

            min-height: 48px;

            margin-bottom: 18px;
        }

        .market-location strong {
            color: #444;
        }


        /* =====================================
           COORDINATES
        ===================================== */

        .coordinates {
            background: #f4f6f8;

            padding: 10px;

            border-radius: 6px;

            color: #777;

            font-size: 12px;

            margin-bottom: 18px;

            word-break: break-word;
        }


        /* =====================================
           MAP BUTTON
        ===================================== */

        .map-btn {
            display: block;

            width: 100%;

            padding: 12px;

            background: #2e7d32;

            color: #ffffff;

            text-decoration: none;

            text-align: center;

            border-radius: 6px;

            font-size: 14px;

            font-weight: bold;

            box-sizing: border-box;

            transition: 0.2s ease;
        }

        .map-btn:hover {
            background: #245f27;
        }


        /* =====================================
           NO MAP
        ===================================== */

        .no-map {
            display: block;

            width: 100%;

            padding: 12px;

            background: #eeeeee;

            color: #888;

            text-align: center;

            border-radius: 6px;

            font-size: 14px;

            box-sizing: border-box;
        }


        /* =====================================
           NO MARKETS
        ===================================== */

        .no-markets {
            background: #ffffff;

            padding: 60px 20px;

            border-radius: 12px;

            text-align: center;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);
        }

        .no-markets h2 {
            color: #163a24;

            margin-bottom: 8px;
        }

        .no-markets p {
            color: #777;

            font-size: 14px;
        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 1000px) {

            .markets-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 700px) {

            .markets-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 500px) {

            .markets-grid {
                grid-template-columns: 1fr;
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


        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Dashboard

        </a>


        <!-- PRODUCTS -->

        <a
            href="products.php"
            class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Products

        </a>


        <!-- MARKETS -->

        <a
            href="markets.php"
            class="<?php echo $current_page === 'markets.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Markets

        </a>


        <!-- CART -->

        <a
            href="cart.php"
            class="<?php echo $current_page === 'cart.php' ? 'active' : ''; ?>"
        >

            <span>                                                                                                                   </span>

            Cart

        </a>


        <!-- ORDERS -->

        <a
            href="orders.php"
            class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
        >

            <span></span>

            My Orders

        </a>


        <!-- FAVORITES -->

        <a
            href="favorites.php"
            class="<?php echo $current_page === 'favorites.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Favorites

        </a>


        <!-- NOTIFICATIONS -->

        <a
            href="notifications.php"
            class="<?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>"
        >

            <span></span>

            Notifications

        </a>


        <!-- PROFILE -->

        <a
            href="profile.php"
            class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>"
        >

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


<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="customer-main">


    <div class="markets-page">


        <!-- =================================
             PAGE HEADER
        ================================= -->

        <div class="markets-header">

            <h1>
                Local Markets
            </h1>

            <p>
                Find markets where you can collect your pre-orders.
            </p>

        </div>


        <!-- =================================
             MARKETS
        ================================= -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="markets-grid">


                <?php while ($market = mysqli_fetch_assoc($result)): ?>


                    <div class="market-card">


                        <!-- MARKET ICON -->

                        <div class="market-icon">

                            

                        </div>


                        <!-- MARKET NAME -->

                        <h2>

                            <?php
                            echo htmlspecialchars(
                                $market['market_name']
                            );
                            ?>

                        </h2>


                        <!-- LOCATION -->

                        <div class="market-location">

                            <strong>
                                Location:
                            </strong>

                            <br>

                            <?php

                            echo !empty($market['location'])
                                ? htmlspecialchars(
                                    $market['location']
                                )
                                : 'Location not available';

                            ?>

                        </div>


                        <?php

                        $has_coordinates =
                            $market['latitude'] !== null &&
                            $market['latitude'] !== '' &&
                            $market['longitude'] !== null &&
                            $market['longitude'] !== '';

                        ?>


                        <?php if ($has_coordinates): ?>


                            <!-- COORDINATES -->

                            <div class="coordinates">

                                <strong>
                                    Coordinates:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $market['latitude']
                                );
                                ?>

                                ,

                                <?php
                                echo htmlspecialchars(
                                    $market['longitude']
                                );
                                ?>

                            </div>


                            <!-- MAP -->

                            <a
                                href="https://www.google.com/maps?q=<?php
                                    echo urlencode(
                                        $market['latitude'] .
                                        ',' .
                                        $market['longitude']
                                    );
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="map-btn"
                            >

                                📍 View Map / Directions

                            </a>


                        <?php else: ?>


                            <!-- NO MAP -->

                            <div class="no-map">

                                Map location not available

                            </div>


                        <?php endif; ?>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- =================================
                 NO MARKETS
            ================================= -->

            <div class="no-markets">

                <h2>
                    No Markets Available
                </h2>

                <p>
                    There are currently no markets registered.
                </p>

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>