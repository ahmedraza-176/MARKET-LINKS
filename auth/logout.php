<?php

session_start();

// Saari session data remove karo
session_unset();

// Session destroy karo
session_destroy();

// Login page par redirect
header("Location: login.php");
exit();

?>