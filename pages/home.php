<?php
    if(Auth::is_logged_in()) {
        redirect('index.php?page=dashboard');
    }

    else {
        redirect('index.php?page=login');
    }
?>
