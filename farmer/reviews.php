<?php

session_start();

require_once "../config/connection.php";


// =========================================
// FARMER LOGIN CHECK
// =========================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");
    exit();

}

if ($_SESSION['role'] !== 'Farmer') {

    header("Location: ../auth/login.php");
    exit();

}


$farmer_id = (int) $_SESSION['user_id'];

$current_page = basename($_SERVER['PHP_SELF']);

$success = "";
$error = "";


// =========================================
// REPLY TO REVIEW
// =========================================

if (isset($_POST['reply_review'])) {

    $review_id = (int) ($_POST['review_id'] ?? 0);

    $farmer_reply = trim(
        $_POST['farmer_reply'] ?? ''
    );


    // =====================================
    // VALIDATION
    // =====================================

    if ($review_id <= 0) {

        $error = "Invalid review.";

    }

    elseif ($farmer_reply === "") {

        $error = "Please enter a reply.";

    }

    else {


        // =====================================
        // CHECK REVIEW BELONGS TO FARMER
        // =====================================

        $check_query = "
            SELECT
                r.review_id

            FROM Reviews r

            INNER JOIN Products p
                ON r.product_id = p.product_id

            WHERE r.review_id = ?
            AND p.farmer_id = ?

            LIMIT 1
        ";


        $stmt = mysqli_prepare(
            $conn,
            $check_query
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $review_id,
            $farmer_id
        );


        mysqli_stmt_execute($stmt);


        $check_result =
            mysqli_stmt_get_result($stmt);


        if (
            mysqli_num_rows(
                $check_result
            ) === 0
        ) {

            $error =
                "You cannot reply to this review.";

        }

        else {


            // =====================================
            // UPDATE REPLY
            // =====================================

            $update_query = "
                UPDATE Reviews

                SET
                    farmer_reply = ?,
                    reply_date = CURRENT_TIMESTAMP

                WHERE review_id = ?
            ";


            $stmt = mysqli_prepare(
                $conn,
                $update_query
            );


            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $farmer_reply,
                $review_id
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                $success =
                    "Reply saved successfully.";

            }

            else {

                $error =
                    "Unable to save reply. Please try again.";

            }

        }

    }

}


// =========================================
// FETCH FARMER REVIEWS
// =========================================

$query = "
    SELECT

        r.review_id,
        r.rating,
        r.comment,
        r.review_date,
        r.farmer_reply,
        r.reply_date,

        p.product_id,
        p.product_name,
        p.category,

        u.name AS customer_name

    FROM Reviews r

    INNER JOIN Products p
        ON r.product_id = p.product_id

    INNER JOIN Users u
        ON r.user_id = u.user_id

    WHERE p.farmer_id = ?

    ORDER BY r.review_date DESC
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


$reviews_result =
    mysqli_stmt_get_result($stmt);

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
        Reviews - MarketLink
    </title>


    <link
        rel="stylesheet"
        href="../css/farmer.css"
    >


    <style>

        /* =====================================
           MAIN
        ===================================== */

        .farmer-main {

            margin-left: 240px;

            min-height: 100vh;

            padding: 35px;

            background: #f4f6f8;

        }


        .reviews-page {

            max-width: 1100px;

            margin: 0 auto;

        }


        /* =====================================
           HEADER
        ===================================== */

        .page-header {

            margin-bottom: 25px;

        }


        .page-header h1 {

            margin: 0 0 8px;

            color: #163a24;

            font-size: 30px;

        }


        .page-header p {

            margin: 0;

            color: #777;

        }


        /* =====================================
           MESSAGES
        ===================================== */

        .success-message {

            background: #e8f5e9;

            color: #2e7d32;

            padding: 13px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            border-left: 4px solid #2e7d32;

        }


        .error-message {

            background: #ffebee;

            color: #c62828;

            padding: 13px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            border-left: 4px solid #c62828;

        }


        /* =====================================
           REVIEW GRID
        ===================================== */

        .reviews-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(350px, 1fr));

            gap: 20px;

        }


        /* =====================================
           REVIEW CARD
        ===================================== */

        .review-card {

            background: white;

            border-radius: 12px;

            padding: 22px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.06);

        }


        /* =====================================
           REVIEW TOP
        ===================================== */

        .review-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            padding-bottom: 16px;

            border-bottom: 1px solid #eee;

            margin-bottom: 16px;

        }


        .customer-info h3 {

            margin: 0 0 5px;

            color: #163a24;

            font-size: 17px;

        }


        .customer-info p {

            margin: 0;

            color: #888;

            font-size: 12px;

        }


        .rating {

            color: #f59e0b;

            font-size: 17px;

            white-space: nowrap;

        }


        /* =====================================
           PRODUCT
        ===================================== */

        .product-info {

            background: #f7f9f8;

            padding: 12px;

            border-radius: 8px;

            margin-bottom: 16px;

        }


        .product-info strong {

            display: block;

            color: #163a24;

            margin-bottom: 4px;

        }


        .product-info span {

            color: #888;

            font-size: 12px;

        }


        /* =====================================
           COMMENT
        ===================================== */

        .review-comment {

            margin-bottom: 18px;

        }


        .review-comment h4 {

            margin: 0 0 7px;

            color: #444;

            font-size: 13px;

        }


        .review-comment p {

            margin: 0;

            color: #555;

            line-height: 1.6;

            font-size: 14px;

        }


        /* =====================================
           REPLY
        ===================================== */

        .reply-section {

            border-top: 1px solid #eee;

            padding-top: 18px;

        }


        .reply-section h4 {

            margin: 0 0 10px;

            color: #163a24;

            font-size: 14px;

        }


        .reply-section textarea {

            width: 100%;

            min-height: 100px;

            resize: vertical;

            box-sizing: border-box;

            padding: 11px;

            border: 1px solid #ddd;

            border-radius: 7px;

            outline: none;

            font-family: inherit;

            font-size: 14px;

        }


        .reply-section textarea:focus {

            border-color: #2e7d32;

        }


        .reply-btn {

            margin-top: 10px;

            border: none;

            background: #2e7d32;

            color: white;

            padding: 10px 18px;

            border-radius: 7px;

            font-weight: bold;

            cursor: pointer;

        }


        .reply-btn:hover {

            background: #245f27;

        }


        /* =====================================
           EXISTING REPLY
        ===================================== */

        .existing-reply {

            background: #f0f7f1;

            border-left: 4px solid #2e7d32;

            padding: 14px;

            border-radius: 7px;

        }


        .existing-reply strong {

            display: block;

            color: #163a24;

            margin-bottom: 7px;

            font-size: 13px;

        }


        .existing-reply p {

            margin: 0 0 7px;

            color: #555;

            font-size: 14px;

            line-height: 1.5;

        }


        .reply-date {

            color: #888;

            font-size: 11px;

        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty-box {

            background: white;

            padding: 50px 20px;

            text-align: center;

            border-radius: 12px;

            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.05);

        }


        .empty-box h3 {

            color: #163a24;

            margin-bottom: 8px;

        }


        .empty-box p {

            color: #777;

            margin: 0;

        }


        /* =====================================
           SIDEBAR
        ===================================== */

        .sidebar {

            position: fixed;

            top: 0;

            left: 0;

            width: 240px;

            height: 100vh;

            background: #163a24;

            padding: 25px 15px;

            display: flex;

            flex-direction: column;

            box-shadow:
                3px 0 15px
                rgba(0, 0, 0, 0.08);

            z-index: 1000;

            box-sizing: border-box;

        }


        .sidebar-logo {

            padding: 0 10px 25px;

            border-bottom: 1px solid
                rgba(255,255,255,0.1);

        }


        .sidebar-logo h2 {

            color: white;

            margin: 0 0 5px;

        }


        .sidebar-logo p {

            color: #b8cdbd;

            margin: 0;

            font-size: 13px;

        }


        .sidebar-menu {

            margin-top: 25px;

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .sidebar-menu a,
        .sidebar-bottom a {

            text-decoration: none;

            color: #dce9df;

            padding: 12px;

            border-radius: 7px;

            display: flex;

            align-items: center;

            gap: 10px;

            font-size: 14px;

        }


        .sidebar-menu a:hover,
        .sidebar-bottom a:hover {

            background: rgba(
                255,
                255,
                255,
                0.08
            );

        }


        .sidebar-menu a.active {

            background: #2e7d32;

            color: white;

        }


        .sidebar-bottom {

            margin-top: auto;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 800px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }


            .farmer-main {

                margin-left: 0;

                padding: 25px 15px;

            }


            .sidebar-menu {

                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .sidebar-bottom {

                margin-top: 15px;

            }

        }


        @media (max-width: 550px) {

            .reviews-grid {

                grid-template-columns: 1fr;

            }


            .sidebar-menu {

                grid-template-columns: 1fr;

            }


            .review-top {

                flex-direction: column;

            }


            .page-header h1 {

                font-size: 25px;

            }

        }

    </style>

</head>

<script src="../js/dark-mode.js"></script>


<body>


<!-- =====================================
     FARMER SIDEBAR
===================================== -->

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


        <a
            href="dashboard.php"
            class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
        >

            <span>🏠</span>

            Dashboard

        </a>


        <a
            href="products.php"
            class="<?php echo $current_page === 'products.php' ? 'active' : ''; ?>"
        >

            <span>🥕</span>

            Products

        </a>


        <a
            href="weekly-stock.php"
            class="<?php echo $current_page === 'weekly-stock.php' ? 'active' : ''; ?>"
        >

            <span>📅</span>

            Weekly Stock

        </a>


        <a
            href="stock-templates.php"
            class="<?php echo $current_page === 'stock-templates.php' ? 'active' : ''; ?>"
        >

            <span>🔄</span>

            Templates

        </a>


        <a
            href="orders.php"
            class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
        >

            <span>📦</span>

            Orders

        </a>


        <a
            href="reviews.php"
            class="<?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>"
        >

            <span>⭐</span>

            Reviews

        </a>


        <a
            href="insights.php"
            class="<?php echo $current_page === 'insights.php' ? 'active' : ''; ?>"
        >

            <span>📊</span>

            Insights

        </a>


        <a
            href="profile.php"
            class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>"
        >

            <span>👤</span>

            Profile

        </a>


    </nav>


    <div class="sidebar-bottom">

        <a href="../auth/logout.php">

            <span>🚪</span>

            Logout

        </a>

    </div>


</aside>


<!-- =====================================
     MAIN
===================================== -->

<main class="farmer-main">


    <div class="reviews-page">


        <!-- =====================================
             HEADER
        ===================================== -->

        <div class="page-header">

            <h1>
                Customer Reviews
            </h1>

            <p>
                View customer feedback and reply to reviews.
            </p>

        </div>


        <!-- =====================================
             SUCCESS
        ===================================== -->

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
             ERROR
        ===================================== -->

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
             REVIEWS
        ===================================== -->

        <?php if (
            mysqli_num_rows(
                $reviews_result
            ) > 0
        ): ?>


            <div class="reviews-grid">


                <?php while (
                    $review =
                    mysqli_fetch_assoc(
                        $reviews_result
                    )
                ): ?>


                    <div class="review-card">


                        <!-- REVIEW TOP -->

                        <div class="review-top">


                            <div class="customer-info">

                                <h3>

                                    <?php

                                    echo htmlspecialchars(
                                        $review['customer_name']
                                    );

                                    ?>

                                </h3>


                                <p>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $review['review_date']
                                        )
                                    );

                                    ?>

                                </p>

                            </div>


                            <div class="rating">

                                <?php

                                $rating =
                                    (int)
                                    $review['rating'];


                                for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ) {

                                    echo
                                        $i <= $rating
                                        ? "★"
                                        : "☆";

                                }

                                ?>

                            </div>


                        </div>


                        <!-- PRODUCT -->

                        <div class="product-info">

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $review['product_name']
                                );

                                ?>

                            </strong>


                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $review['category']
                                );

                                ?>

                            </span>

                        </div>


                        <!-- CUSTOMER COMMENT -->

                        <div class="review-comment">

                            <h4>
                                Customer Review
                            </h4>


                            <p>

                                <?php

                                if (
                                    !empty(
                                        $review['comment']
                                    )
                                ) {

                                    echo nl2br(
                                        htmlspecialchars(
                                            $review['comment']
                                        )
                                    );

                                }

                                else {

                                    echo "No written comment.";

                                }

                                ?>

                            </p>

                        </div>


                        <!-- =================================
                             FARMER REPLY
                        ================================= -->

                        <div class="reply-section">


                            <?php if (
                                !empty(
                                    $review['farmer_reply']
                                )
                            ): ?>


                                <div class="existing-reply">

                                    <strong>
                                        Your Reply
                                    </strong>


                                    <p>

                                        <?php

                                        echo nl2br(
                                            htmlspecialchars(
                                                $review['farmer_reply']
                                            )
                                        );

                                        ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $review['reply_date']
                                        )
                                    ): ?>

                                        <span
                                            class="reply-date"
                                        >

                                            <?php

                                            echo date(
                                                "d M Y h:i A",
                                                strtotime(
                                                    $review['reply_date']
                                                )
                                            );

                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </div>


                                <form
                                    method="POST"
                                    action=""
                                    style="margin-top:15px;"
                                >


                                    <input
                                        type="hidden"
                                        name="review_id"
                                        value="<?php
                                            echo (int)
                                                $review['review_id'];
                                        ?>"
                                    >


                                    <textarea
                                        name="farmer_reply"
                                        placeholder="Update your reply..."
                                        required
                                    ><?php
                                        echo htmlspecialchars(
                                            $review['farmer_reply']
                                        );
                                    ?></textarea>


                                    <button
                                        type="submit"
                                        name="reply_review"
                                        class="reply-btn"
                                    >

                                        Update Reply

                                    </button>


                                </form>


                            <?php else: ?>


                                <h4>
                                    Reply to Customer
                                </h4>


                                <form
                                    method="POST"
                                    action=""
                                >


                                    <input
                                        type="hidden"
                                        name="review_id"
                                        value="<?php
                                            echo (int)
                                                $review['review_id'];
                                        ?>"
                                    >


                                    <textarea
                                        name="farmer_reply"
                                        placeholder="Write your reply..."
                                        required
                                    ></textarea>


                                    <button
                                        type="submit"
                                        name="reply_review"
                                        class="reply-btn"
                                    >

                                        Reply to Review

                                    </button>


                                </form>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div class="empty-box">

                <h3>
                    No Reviews Yet
                </h3>

                <p>
                    Customer reviews for your products
                    will appear here.
                </p>

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>