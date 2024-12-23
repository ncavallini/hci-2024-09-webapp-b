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
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }
    body {
    background-color: #4B286D;
    color: #FFFFFF;
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
a {
    color: #E9D8F6;
}
a:hover {
    color: #FFFFFF;
}

button, .btn {
    background-color: #E0D7F3;
    color: #4B286D;
    border: none;
    border-radius: 8px;
    padding: 12px 18px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s ease, transform 0.2s ease;
}

button:hover, .btn:hover {
    background-color: #D2C6EE;
    transform: scale(1.05);
}

button:active, .btn:active {
    background-color: #C3B3E8;
    transform: scale(0.98);
}

button:disabled, .btn:disabled {
    background-color: #F0EAFB;
    color: #A8A2BA;
    cursor: not-allowed;
}
</style>