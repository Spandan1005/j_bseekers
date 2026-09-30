<?php
// Kurt Castro | 9.30
// Ends session + redirects back to login

session_start();
session_destroy();
header('Location: login.php');
exit;
?>
