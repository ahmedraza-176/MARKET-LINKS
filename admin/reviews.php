<?php

session_start();

require_once "../config/connection.php";


// =========================================
// ADMIN AUTH CHECK
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
// DELETE REVIEW
// =========================================

if (
    isset($_GET['delete']) &&
    is_numeric($_GET['delete'])
) {

    $review_id = (int) $_GET['delete'];


    $delete_query = "
        DELETE FROM Reviews
        WHERE review_id = ?
    ";


    $delete_stmt = mysqli_prepare(
        $conn,
        $delete_query
    );


    mysqli_stmt_bind_param(
        $delete_stmt,
        "i",
        $review_id
    );


    if (
        mysqli_stmt_execute(
            $delete_stmt
        )
    ) {

        header(
            "Location: reviews.php?success=deleted"
        );

        exit();

    } else {

        header(
            "Location: reviews.php?error=delete"
        );

        exit();

    }

}


// =========================================
// FETCH REVIEWS
// =========================================

$query = "
    SELECT

        r.review_id,
        r.rating,
        r.comment,
        r.review_date,

        u.name AS customer_name,
        u.email AS customer_email,

        p.product_name,
        p.category

    FROM Reviews r

    INNER JOIN Users u
        ON r.user_id = u.user_id

    INNER JOIN Products p
        ON r.product_id = p.product_id

    ORDER BY r.review_id DESC
";


$result = mysqli_query(
    $conn,
    $query
);

?>

<?php require_once "partials/menu.php"; ?>

<link
    rel="stylesheet"
    href="../css/admin_style.css"
>


<div class="main-content">


    <!-- =====================================
         PAGE HEADER
    ===================================== -->

    <div class="page-header">

        <div>

            <h2>
                Customer Reviews
            </h2>

            <p>
                View and manage customer reviews.
            </p>

        </div>

    </div>



    <!-- =====================================
         SUCCESS MESSAGE
    ===================================== -->

    <?php if (
        isset($_GET['success']) &&
        $_GET['success'] === 'deleted'
    ): ?>

        <div class="success-message">

            Review deleted successfully.

        </div>

    <?php endif; ?>



    <!-- =====================================
         ERROR MESSAGE
    ===================================== -->

    <?php if (
        isset($_GET['error']) &&
        $_GET['error'] === 'delete'
    ): ?>

        <div class="error-message">

            Unable to delete review. Please try again.

        </div>

    <?php endif; ?>



    <!-- =====================================
         REVIEWS TABLE
    ===================================== -->

    <div class="table-wrapper">

        <table class="admin-table">


            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Product
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Rating
                    </th>

                    <th>
                        Comment
                    </th>

                    <th>
                        Date
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if (
                    $result &&
                    mysqli_num_rows($result) > 0
                ): ?>


                    <?php while (
                        $review =
                        mysqli_fetch_assoc($result)
                    ): ?>


                        <tr>


                            <!-- REVIEW ID -->

                            <td>

                                #<?php

                                echo (int)
                                    $review['review_id'];

                                ?>

                            </td>



                            <!-- CUSTOMER -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $review['customer_name']
                                    );

                                    ?>

                                </strong>


                                <small class="table-subtext">

                                    <?php

                                    echo htmlspecialchars(
                                        $review['customer_email']
                                    );

                                    ?>

                                </small>

                            </td>



                            <!-- PRODUCT -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $review['product_name']
                                    );

                                    ?>

                                </strong>

                            </td>



                            <!-- CATEGORY -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $review['category']
                                );

                                ?>

                            </td>



                            <!-- RATING -->

                            <td>

                                <span class="review-rating">

                                    <?php

                                    $rating =
                                        (int) $review['rating'];


                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ) {

                                        if (
                                            $i <= $rating
                                        ) {

                                            echo "★";

                                        } else {

                                            echo "☆";

                                        }

                                    }

                                    ?>

                                </span>


                                <small class="rating-number">

                                    <?php

                                    echo $rating;

                                    ?>/5

                                </small>

                            </td>



                            <!-- COMMENT -->

                            <td>

                                <?php if (
                                    !empty(
                                        $review['comment']
                                    )
                                ): ?>

                                    <div class="review-comment">

                                        <?php

                                        echo nl2br(
                                            htmlspecialchars(
                                                $review['comment']
                                            )
                                        );

                                        ?>

                                    </div>

                                <?php else: ?>

                                    <span class="table-subtext">

                                        No comment

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $review['review_date']
                                    )
                                );

                                ?>

                                <small class="table-subtext">

                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $review['review_date']
                                        )
                                    );

                                    ?>

                                </small>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <div class="table-actions">


                                    <a
                                        href="reviews.php?delete=<?php
                                            echo (int)
                                                $review['review_id'];
                                        ?>"
                                        class="btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this review?');"
                                    >

                                        Delete

                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="no-data"
                        >

                            No reviews found.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>


        </table>

    </div>


</div>
