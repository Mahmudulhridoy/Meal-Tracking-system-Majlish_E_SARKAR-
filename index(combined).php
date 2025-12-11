<?php
require_once "configuration.php";

if (!loggedIn()) {
    header("Location: login.php");
    exit;
}

if (isAdmin()) {
    header("Location: index(admin).php");
    exit;
}

header("Location: index(member).php");
exit;
?>
