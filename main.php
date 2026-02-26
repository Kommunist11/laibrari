<?php
session_start();

if (isset($_SESSION['auth_id'])) {
    header('Location: profile.php');
} else {
    header('Location: login.php');
}
exit;
?>