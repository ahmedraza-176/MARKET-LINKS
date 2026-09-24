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
// CHECK MARKET ID
// =========================================

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    header("Location: markets.php");
    exit();

}


$market_id = (int) $_GET['id'];


// =========================================
// FETCH MARKET
// =========================================

$query = "
    SELECT
        market_id,
        market_name,
        location,
        latitude,
        longitude,
        map_provider

    FROM Markets

    WHERE market_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $query
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $market_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$market =
    mysqli_fetch_assoc($result);


// =========================================
// MARKET NOT FOUND
// =========================================

if (!$market) {

    header("Location: markets.php");
    exit();

}


$error = "";


// =========================================
// UPDATE MARKET
// =========================================

if (isset($_POST['update_market'])) {


    $market_name = trim(
        $_POST['market_name'] ?? ''
    );


    $location = trim(
        $_POST['location'] ?? ''
    );


    $latitude = trim(
        $_POST['latitude'] ?? ''
    );


    $longitude = trim(
        $_POST['longitude'] ?? ''
    );


    $map_provider = trim(
        $_POST['map_provider'] ?? ''
    );


    // =====================================
    // VALIDATION
    // =====================================

    if ($market_name === "") {

        $error =
            "Market name is required.";

    }

    elseif ($location === "") {

        $error =
            "Market location is required.";

    }

    elseif (
        $latitude === "" ||
        !is_numeric($latitude)
    ) {

        $error =
            "Please enter a valid latitude.";

    }

    elseif (
        $longitude === "" ||
        !is_numeric($longitude)
    ) {

        $error =
            "Please enter a valid longitude.";

    }

    elseif (
        $latitude < -90 ||
        $latitude > 90
    ) {

        $error =
            "Latitude must be between -90 and 90.";

    }

    elseif (
        $longitude < -180 ||
        $longitude > 180
    ) {

        $error =
            "Longitude must be between -180 and 180.";

    }

    else {


        // =================================
        // UPDATE MARKET
        // =================================

        $update_query = "

            UPDATE Markets

            SET
                market_name = ?,
                location = ?,
                latitude = ?,
                longitude = ?,
                map_provider = ?

            WHERE market_id = ?

        ";


        $update_stmt =
            mysqli_prepare(
                $conn,
                $update_query
            );


        mysqli_stmt_bind_param(
            $update_stmt,
            "ssddsi",
            $market_name,
            $location,
            $latitude,
            $longitude,
            $map_provider,
            $market_id
        );


        if (
            mysqli_stmt_execute(
                $update_stmt
            )
        ) {

            header(
                "Location: markets.php?success=updated"
            );

            exit();

        }

        else {

            $error =
                "Unable to update market. Please try again.";

        }

    }


    // =====================================
    // KEEP VALUES AFTER ERROR
    // =====================================

    $market['market_name'] =
        $market_name;

    $market['location'] =
        $location;

    $market['latitude'] =
        $latitude;

    $market['longitude'] =
        $longitude;

    $market['map_provider'] =
        $map_provider;

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
        Edit Market - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <style>

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

            padding: 12px 13px;

            border: 1px solid #ddd;

            border-radius: 7px;

            outline: none;

            font-size: 14px;

            box-sizing: border-box;

        }


        .form-group input:focus {

            border-color: #2e7d32;

            box-shadow:
                0 0 0 3px
                rgba(46, 125, 50, 0.08);

        }


        .form-error {

            max-width: 750px;

            margin: 0 auto 20px;

            padding: 13px 15px;

            background: #ffebee;

            color: #c62828;

            border-left:
                4px solid #c62828;

            border-radius: 7px;

        }


        .form-buttons {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #eee;

        }


        .save-btn {

            border: none;

            background: #198754;

            color: #fff;

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


        .help-text {

            display: block;

            margin-top: 6px;

            color: #777;

            font-size: 12px;

        }


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


    <!-- =====================================
         TOPBAR
    ===================================== -->

    <div class="topbar">


        <div>

            <h1>
                Edit Market
            </h1>


            <p>
                Update market location information.
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


            <h2>
                Market Information
            </h2>


            <a
                href="markets.php"
                class="back-btn"
            >

                ← Back to Markets

            </a>


        </div>



        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div class="form-error">

                <?php

                echo htmlspecialchars(
                    $error
                );

                ?>

            </div>

        <?php endif; ?>



        <!-- FORM -->

        <form
            method="POST"
            class="admin-form"
        >


            <!-- MARKET NAME -->

            <div class="form-group">


                <label for="market_name">

                    Market Name

                </label>


                <input
                    type="text"
                    id="market_name"
                    name="market_name"
                    value="<?php

                        echo htmlspecialchars(
                            $market['market_name']
                        );

                    ?>"
                    required
                >


            </div>



            <!-- LOCATION -->

            <div class="form-group">


                <label for="location">

                    Location

                </label>


                <input
                    type="text"
                    id="location"
                    name="location"
                    value="<?php

                        echo htmlspecialchars(
                            $market['location']
                            ?? ''
                        );

                    ?>"
                    required
                >


            </div>



            <!-- LATITUDE -->

            <div class="form-group">


                <label for="latitude">

                    Latitude

                </label>


                <input
                    type="number"
                    step="any"
                    id="latitude"
                    name="latitude"
                    value="<?php

                        echo htmlspecialchars(
                            $market['latitude']
                            ?? ''
                        );

                    ?>"
                    required
                >


                <small class="help-text">

                    Latitude must be between -90 and 90.

                </small>


            </div>



            <!-- LONGITUDE -->

            <div class="form-group">


                <label for="longitude">

                    Longitude

                </label>


                <input
                    type="number"
                    step="any"
                    id="longitude"
                    name="longitude"
                    value="<?php

                        echo htmlspecialchars(
                            $market['longitude']
                            ?? ''
                        );

                    ?>"
                    required
                >


                <small class="help-text">

                    Longitude must be between -180 and 180.

                </small>


            </div>



            <!-- MAP PROVIDER -->

            <div class="form-group">


                <label for="map_provider">

                    Map Provider

                </label>


                <input
                    type="text"
                    id="map_provider"
                    name="map_provider"
                    value="<?php

                        echo htmlspecialchars(
                            $market['map_provider']
                            ?? 'Google Maps'
                        );

                    ?>"
                >


            </div>



            <!-- BUTTONS -->

            <div class="form-buttons">


                <a
                    href="markets.php"
                    class="cancel-btn"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    name="update_market"
                    class="save-btn"
                >

                    Update Market

                </button>


            </div>


        </form>


    </section>


</main>


</body>

</html>
