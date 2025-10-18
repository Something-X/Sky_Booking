<?php
session_start();

// Hapus session user (tapi keep admin session jika ada)
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

// Redirect ke home
header("Location: index.php");
exit;
?>