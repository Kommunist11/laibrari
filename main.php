<?php
session_start();

if (isset($_SESSION['id_users'])) {
    header('Location: profile.php');
} else {
    header('Location: login.php');
}
exit;
?>