<?php

session_start();

require_once "../config/connection.php";


// =============================
// ADMIN LOGIN CHECK
// =============================

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}


// =============================
// FORM ERROR
// =============================

$error = "";


// =============================
// ADD FARMER
// =============================

if (isset($_POST['add_farmer'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $status = $_POST['status'];


    // =============================
    // VALIDATION
    // =============================

    if ($name === "" || $email === "" || $password === "") {

        $error = "Name, Email and Password are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($status !== 'Active' && $status !== 'Inactive') {

        $error = "Invalid status.";

    } else {

        // =============================
        // CHECK EMAIL
        // =============================

        $check_query = "SELECT user_id
                        FROM Users
                        WHERE email = ?";

        $check_stmt = mysqli_prepare($conn, $check_query);

        mysqli_stmt_bind_param(
            $check_stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);


        if (mysqli_num_rows($check_result) > 0) {

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

            $insert_query = "INSERT INTO Users
                            (name, email, password, role, phone, status)
                            VALUES (?, ?, ?, 'Farmer', ?, ?)";

            $insert_stmt = mysqli_prepare(
                $conn,
                $insert_query
            );

            mysqli_stmt_bind_param(
                $insert_stmt,
                "sssss",
                $name,
                $email,
                $hashed_password,
                $phone,
                $status
            );


            if (mysqli_stmt_execute($insert_stmt)) {

                header("Location: farmers.php");
                exit();

            } else {

                $error = "Failed to add farmer. Please try again.";

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

    <title>Add Farmer - MarketLink</title>

    <link
        rel="stylesheet"
        href="../css/admin_style.css"
    >


    <style>

        /* =================================
           DARK MODE
        ================================= */

        body.dark-mode {
            background: #111827;
            color: #e5e7eb;
        }


        body.dark-mode .main-content {
            background: #111827;
        }


        body.dark-mode .topbar {
            background: #1f2937;
            color: #f9fafb;
            border-bottom: 1px solid #374151;
        }


        body.dark-mode .topbar h1 {
            color: #f9fafb;
        }


        body.dark-mode .topbar p {
            color: #9ca3af;
        }


        body.dark-mode .admin-info strong {
            color: #f9fafb;
        }


        body.dark-mode .admin-info span {
            color: #9ca3af;
        }


        /* CONTENT BOX */

        body.dark-mode .content-box {
            background: #1f2937;
            color: #e5e7eb;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.25);
        }


        /* FORM HEADER */

        body.dark-mode .form-header h2 {
            color: #f9fafb;
        }


        body.dark-mode .form-header p {
            color: #9ca3af;
        }


        /* FORM LABEL */

        body.dark-mode .form-group label {
            color: #e5e7eb;
        }


        body.dark-mode .form-group small {
            color: #9ca3af;
        }


        /* INPUTS */

        body.dark-mode .admin-form input,
        body.dark-mode .admin-form select,
        body.dark-mode .admin-form textarea {
            background: #111827;
            color: #f9fafb;
            border: 1px solid #374151;
        }


        body.dark-mode .admin-form input::placeholder,
        body.dark-mode .admin-form textarea::placeholder {
            color: #6b7280;
        }


        body.dark-mode .admin-form input:focus,
        body.dark-mode .admin-form select:focus,
        body.dark-mode .admin-form textarea:focus {
            border-color: #198754;
            outline: none;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15);
        }


        body.dark-mode .admin-form select option {
            background: #111827;
            color: #f9fafb;
        }


        /* ERROR */

        body.dark-mode .form-error {
            background: #3b1717;
            color: #fca5a5;
            border-left-color: #ef4444;
        }


        /* BACK BUTTON */

        body.dark-mode .back-btn {
            background: #374151;
            color: #f9fafb;
        }


        body.dark-mode .back-btn:hover {
            background: #4b5563;
        }


        /* CANCEL BUTTON */

        body.dark-mode .cancel-btn {
            background: #374151;
            color: #f9fafb;
        }


        body.dark-mode .cancel-btn:hover {
            background: #4b5563;
        }


        /* SAVE BUTTON */

        body.dark-mode .save-btn {
            background: #198754;
            color: #ffffff;
        }


        body.dark-mode .save-btn:hover {
            background: #157347;
        }


        /* =================================
           DARK MODE TOGGLE
        ================================= */

        .dark-mode-toggle {
            position: fixed;
            right: 25px;
            bottom: 25px;
            z-index: 2000;

            width: 48px;
            height: 48px;

            border: none;
            border-radius: 50%;

            background: #163a24;
            color: #ffffff;

            font-size: 20px;
            cursor: pointer;

            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);

            transition: 0.3s;
        }


        .dark-mode-toggle:hover {
            transform: translateY(-3px);
            background: #2e7d32;
        }


        body.dark-mode .dark-mode-toggle {
            background: #f3f4f6;
            color: #111827;
        }


        body.dark-mode .dark-mode-toggle:hover {
            background: #ffffff;
        }


        @media (max-width: 700px) {

            .dark-mode-toggle {
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

            <h1>Add Farmer</h1>

            <p>Create a new MarketLink farmer account</p>

        </div>


        <div class="admin-info">

            <strong>
                <?php echo htmlspecialchars($_SESSION['name']); ?>
            </strong>

            <span>Admin</span>

        </div>

    </div>



    <!-- FORM -->

    <section class="content-box">


        <div class="form-header">

            <div>

                <h2>Farmer Information</h2>

                <p>Enter the details of the new farmer.</p>

            </div>


            <a
                href="farmers.php"
                class="back-btn"
            >
                ← Back to Farmers
            </a>

        </div>



        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div class="form-error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            class="admin-form"
        >


            <!-- NAME -->

            <div class="form-group">

                <label for="name">
                    Farmer Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter farmer name"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter farmer email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >

            </div>



            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">
                    Phone
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter phone number"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                >

            </div>



            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

                <small>
                    Password must be at least 6 characters.
                </small>

            </div>



            <!-- STATUS -->

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    required
                >

                    <option value="Active">
                        Active
                    </option>

                    <option value="Inactive">
                        Inactive
                    </option>

                </select>

            </div>



            <!-- BUTTONS -->

            <div class="form-buttons">

                <a
                    href="farmers.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    name="add_farmer"
                    class="save-btn"
                >
                    Add Farmer
                </button>

            </div>


        </form>


    </section>


</main>


<!-- DARK MODE BUTTON -->

<button
    type="button"
    class="dark-mode-toggle"
    id="darkModeToggle"
    title="Toggle Dark Mode"
>
    🌙
</button>


<script>

    const darkModeToggle =
        document.getElementById("darkModeToggle");


    // Load saved theme

    const savedTheme =
        localStorage.getItem("marketlink-theme");


    if (savedTheme === "dark") {

        document.body.classList.add("dark-mode");

        darkModeToggle.textContent = "☀️";

    }


    // Toggle theme

    darkModeToggle.addEventListener("click", function () {

        document.body.classList.toggle("dark-mode");


        if (document.body.classList.contains("dark-mode")) {

            localStorage.setItem(
                "marketlink-theme",
                "dark"
            );

            darkModeToggle.textContent = "☀️";

        } else {

            localStorage.setItem(
                "marketlink-theme",
                "light"
            );

            darkModeToggle.textContent = "🌙";

        }

    });

</script>


</body>

</html>



