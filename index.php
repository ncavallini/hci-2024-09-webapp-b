<?php
    require_once __DIR__ . "/utils/init.php";
    $dbconnection = DBConnection::get_connection();
    if(Auth::is_logged_in()) {
        $user = Auth::user();
    }

    require_once __DIR__ . "/template/header.php";

    $page = $_GET['page'] ?? 'dashboard';
    if(!Auth::is_allowed_page($page)) {
        $page = 'login';
    }
 
    ?>
    <main class="container" id="container">
    <?php
    $path = __DIR__ . "/pages/$page.php";
    if(!file_exists($path)) {
        redirect("index.php?page=dashboard&message=Page not found&message_style=danger");
    }
    
    require_once $path;

    ?>
    </main> 


    <div id="loading" style="display: none;">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>


    <?php

    require_once __DIR__ . "/template/footer.php";

?>


<script>
    const urlSearchParam = new URLSearchParams(window.location.search);
    if(urlSearchParam.has('message')) {
        const message = urlSearchParam.get('message');
        const style = urlSearchParam.get('message_style').toUpperCase() || "INFO";
        const toast = {
        title: "",
        message: message,
        status: TOAST_STATUS[style],
        timeout: 5000
    };
    Toast.create(toast);
    
    }


    // LOADING ANIMATION
    document.querySelectorAll('a:not(.no-loading)').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('loading').style.display = 'flex';
        window.location.href = this.href;
         
      });
    });

    window.onload = function () {
      document.getElementById('loading').style.display = 'none';
    };
</script>

<style>
     #loading {
        position: fixed; /* Ensures it overlays the whole screen */
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8); /* Light semi-transparent background */
        display: flex; /* Center content using Flexbox */
        justify-content: center; /* Center horizontally */
        align-items: center; /* Center vertically */
        z-index: 1050; /* Ensure it's on top of other content */
    }
</style>