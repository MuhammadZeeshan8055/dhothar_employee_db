<?php

include('session.php');

clearAuthUser();
session_destroy();

header('Location: ' . $base_url . 'login');
exit;

?>
