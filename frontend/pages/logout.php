<?php
// Kurt Castro | 9.30
// Ends session + redirects back to login

session_start(); // must load session first, can't destroy uninitialized session
session_destroy();
header('Location: login.php');
exit;
?>
