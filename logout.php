<?php
require_once __DIR__ . '/inc/auth.php';

logout_admin();

header('Location: login.php');
exit;
