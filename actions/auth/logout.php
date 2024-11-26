<?php
    require_once __DIR__ . "/../../utils/init.php";
    Auth::logout();
    header("Location: ../../index.php?page=login&message=Logged out successfully&message_style=SUCCESS");
?>