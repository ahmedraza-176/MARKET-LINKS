<?php

session_start();

require_once "../config/connection.php";

// Admin login check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Admin role check
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}


// =============================
// DELETE FARMER
// =============================
if (isset($_GET['delete'])) {

    $farmer_id = intval($_GET['delete']);

    $delete_query = "DELETE FROM Users
                     WHERE user_id = ?
                     AND role = 'Farmer'";

    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $farmer_id);
    mysqli_stmt_execute($stmt);

    header("Location: farmers.php");
    exit();
}


// =============================
// CHANGE STATUS
// =============================
if (isset($_GET['status']) && isset($_GET['id'])) {

    $farmer_id = intval($_GET['id']);
    $status = $_GET['status'];

    if ($status === 'Active' || $status === 'Inactive') {

        $status_query = "UPDATE Users
                         SET status = ?
                         WHERE user_id = ?
                         AND role = 'Farmer'";

        $stmt = mysqli_prepare($conn, $status_query);
        mysqli_stmt_bind_param($stmt, "si", $status, $farmer_id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: farmers.php");
    exit();
}


// =============================
// FETCH FARMERS
// =============================
$query = "SELECT user_id, name, email, phone, status
          FROM Users
          WHERE role = 'Farmer'
          ORDER BY user_id DESC";

$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Farmers - MarketLink</title>

    <link rel="stylesheet" href="../css/admin_style.css">

    <style>

        /* =====================================
           FARMERS DARK MODE
        ===================================== */

        body {
            transition:
                background 0.25s ease,
                color 0.25s ease;
        }

        body.dark-mode {
            background: #111714;
            color: #e8eee9;
        }

        /* Main content */

        body.dark-mode .main-content {
            background: #111714;
        }

        /* Topbar */

        body.dark-mode .topbar h1 {
            color: #e8eee9;
        }

        body.dark-mode .topbar p {
            color: #aebbb2;
        }

        body.dark-mode .admin-info strong {
            color: #e8eee9;
        }

        body.dark-mode .admin-info span {
            color: #aebbb2;
        }

        /* Content box */

        body.dark-mode .content-box {
            background: #1b241f;
            color: #e8eee9;
            box-shadow:
                0 3px 15px
                rgba(0, 0, 0, 0.25);
        }

        /* Page header */

        body.dark-mode .page-header h2 {
            color: #e8eee9;
        }

        body.dark-mode .page-header p {
            color: #aebbb2;
        }

        /* Table */

        body.dark-mode .table-wrapper {
            background: #1b241f;
        }

        body.dark-mode .admin-table {
            background: #1b241f;
            color: #e8eee9;
        }

        body.dark-mode .admin-table th {
            background: #243229;
            color: #e8eee9;
        }

        body.dark-mode .admin-table td {
            color: #cbd6cf;
            border-bottom-color: #344139;
        }

        body.dark-mode .admin-table tbody tr:hover {
            background: #222d26;
        }

        /* Buttons */

        body.dark-mode .btn-edit {
            background: #245c8a;
            color: #ffffff;
        }

        body.dark-mode .btn-warning {
            background: #8a681f;
            color: #ffffff;
        }

        body.dark-mode .btn-success {
            background: #26733d;
            color: #ffffff;
        }

        body.dark-mode .btn-delete {
            background: #8b3030;
            color: #ffffff;
        }

        /* No data */

        body.dark-mode .no-data {
            color: #aebbb2;
        }

        /* Status */

        body.dark-mode .status.active {
            background: #173d26;
            color: #7ee787;
        }

        body.dark-mode .status.inactive {
            background: #3d1e22;
            color: #ff8a8a;
        }


        /* =====================================
           DARK MODE BUTTON
        ===================================== */

        .dark-mode-toggle {

            position: fixed;

            right: 25px;

            bottom: 25px;

            width: 48px;

            height: 48px;

            border: none;

            border-radius: 50%;

            background: #198754;

            color: white;

            font-size: 20px;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow:
                0 4px 15px
                rgba(0, 0, 0, 0.25);

            z-index: 9999;

            transition: 0.2s ease;

        }


        .dark-mode-toggle:hover {

            transform: scale(1.08);

            background: #157347;

        }


        body.dark-mode .dark-mode-toggle {

            background: #f4c542;

            color: #111;

        }


        @media (max-width: 600px) {

            .dark-mode-toggle {

                width: 44px;

                height: 44px;

                right: 15px;

                bottom: 15px;

            }

        }

    </style>

</head>


<body>


    <?php require_once "partials/menu.php"; ?>


    <main class="main-content">


        <!-- TOPBAR -->

        <div class="topbar">

            <div>

                <h1>Farmers</h1>

                <p>Manage MarketLink farmers</p>

            </div>


            <div class="admin-info">

                <strong>
                    <?php echo htmlspecialchars($_SESSION['name']); ?>
                </strong>

                <span>Admin</span>

            </div>

        </div>


        <!-- CONTENT -->

        <section class="content-box">


            <div class="page-header">

                <div>

                    <h2>All Farmers</h2>

                    <p>
                        View and manage registered farmers.
                    </p>

                </div>


                <a
                    href="farmer-add.php"
                    class="add-btn"
                >
                    + Add Farmer
                </a>

            </div>


            <div class="table-wrapper">


                <table class="admin-table">


                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Name</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (mysqli_num_rows($result) > 0): ?>


                            <?php while ($farmer = mysqli_fetch_assoc($result)): ?>


                                <tr>


                                    <td>
                                        <?php echo $farmer['user_id']; ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $farmer['name']
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $farmer['email']
                                        );
                                        ?>
                                    </td>


                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $farmer['phone'] ?? 'N/A'
                                        );
                                        ?>
                                    </td>


                                    <td>


                                        <?php if ($farmer['status'] === 'Active'): ?>


                                            <span class="status active">
                                                Active
                                            </span>


                                        <?php else: ?>


                                            <span class="status inactive">
                                                Inactive
                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <td>


                                        <div class="table-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="farmer-edit.php?id=<?php echo $farmer['user_id']; ?>"
                                                class="btn-edit"
                                            >
                                                Edit
                                            </a>


                                            <!-- STATUS -->

                                            <?php if ($farmer['status'] === 'Active'): ?>


                                                <a
                                                    href="farmers.php?id=<?php echo $farmer['user_id']; ?>&status=Inactive"
                                                    class="btn-warning"
                                                >
                                                    Suspend
                                                </a>


                                            <?php else: ?>


                                                <a
                                                    href="farmers.php?id=<?php echo $farmer['user_id']; ?>&status=Active"
                                                    class="btn-success"
                                                >
                                                    Activate
                                                </a>


                                            <?php endif; ?>


                                            <!-- DELETE -->

                                            <a
                                                href="farmers.php?delete=<?php echo $farmer['user_id']; ?>"
                                                class="btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this farmer?');"
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
                                    colspan="6"
                                    class="no-data"
                                >
                                    No farmers registered yet.
                                </td>

                            </tr>


                        <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>


    </main>


    <!-- =====================================
         DARK MODE TOGGLE
    ===================================== -->

    <button
        type="button"
        id="darkModeToggle"
        class="dark-mode-toggle"
        title="Dark Mode"
        aria-label="Toggle Dark Mode"
    >
        🌙
    </button>


    <script>

        document.addEventListener(
            "DOMContentLoaded",
            function () {

                const toggle =
                    document.getElementById(
                        "darkModeToggle"
                    );


                // Check saved theme

                const savedTheme =
                    localStorage.getItem(
                        "marketlink-theme"
                    );


                if (savedTheme === "dark") {

                    document.body.classList.add(
                        "dark-mode"
                    );

                    toggle.innerHTML = "☀️";

                    toggle.title = "Light Mode";

                }


                // Toggle dark mode

                toggle.addEventListener(
                    "click",
                    function () {

                        document.body.classList.toggle(
                            "dark-mode"
                        );


                        if (
                            document.body.classList.contains(
                                "dark-mode"
                            )
                        ) {

                            localStorage.setItem(
                                "marketlink-theme",
                                "dark"
                            );

                            toggle.innerHTML = "☀️";

                            toggle.title =
                                "Light Mode";

                        } else {

                            localStorage.setItem(
                                "marketlink-theme",
                                "light"
                            );

                            toggle.innerHTML = "🌙";

                            toggle.title =
                                "Dark Mode";

                        }

                    }
                );

            }
        );

    </script>


</body>

</html>
