<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizing Mental Load (B)</title>

    <!-- External CSS Libraries -->
    <link rel="stylesheet" href="inc/styles.css">
    <link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Visualizing Mental Load" />
    <link rel="manifest" href="site.webmanifest" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.1.8/r-3.0.3/datatables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-toaster@4.0.1/css/bootstrap-toaster.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p6fDw+68MEHapGNrvUJEnKTpuI5xVXP9k95AlXwhRkIVxeQ5pqnx90E6YVCcIJCAFDNlqhsgmzIc5V6rVT3K0A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- External JS Libraries -->
    <script src="https://kit.fontawesome.com/f5ed638bc2.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.2.0/dist/chartjs-chart-matrix.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0/dist/chartjs-adapter-moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.0/bootbox.min.js" integrity="sha512-oVbWSv2O4y1UzvExJMHaHcaib4wsBMS5tEP3/YkMP6GmkwRJAa79Jwsv+Y/w7w2Vb/98/Xhvck10LyJweB8Jsw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@3.1.0/dist/chartjs-chart-treemap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-toaster/js/bootstrap-toaster.min.js"></script>
    <style>
        /* Logout Button Styling */
.logout-button {
    color: #ffffff; /* White text */
    background-color: #9A7FB5; /* Darker shade of purple */
    border: none;
    font-weight: bold;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.logout-button:hover {
    background-color: #7a5fa0; /* Slightly darker on hover */
    text-decoration: none;
}

/* Standard Logout Link Styling */
.logout-link {
    color: #9A7FB5; /* Darker shade of purple */
    text-decoration: none;
    font-weight: bold;
}

.logout-link:hover {
    color: #4B286D; /* Even darker on hover */
    text-decoration: underline;
}

/* User Link Styling in Header */
.user-link {
    color: #4B286D; /* Match with text color */
    text-decoration: none;
    font-weight: bold;
}

.user-link:hover {
    text-decoration: underline;
    color: #4B286D;
}
/* Logout Button Styling */
.logout-button {
    color: #ffffff; /* White text */
    background-color: #9A7FB5; /* Darker shade of purple */
    border: none;
    font-weight: bold;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.logout-button:hover {
    background-color: #7a5fa0; /* Slightly darker on hover */
    text-decoration: none;
}

/* Standard Logout Link Styling */
.logout-link {
    color: #9A7FB5; /* Darker shade of purple */
    text-decoration: none;
    font-weight: bold;
}

.logout-link:hover {
    color: #4B286D; /* Even darker on hover */
    text-decoration: underline;
}

/* User Link Styling in Header */
.user-link {
    color: #4B286D; /* Match with text color */
    text-decoration: none;
    font-weight: bold;
}

.user-link:hover {
    text-decoration: underline;
    color: #4B286D;
}

/* Ensure content above the footer is not hidden */
body {
    padding-bottom: 100px; /* Adjust based on footer height */
}

/* Custom Navbar Color */
.custom-navbar {
    background-color: #311432; /* Example darker shade: Eggplant */
}

/* Ensure icons and text are properly aligned */
.custom-navbar .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: white; /* Ensures text remains visible */
    text-decoration: none;
}

.custom-navbar .nav-item:hover .nav-text {
    color: #9A7FB5; /* Change text color on hover */
}

/* Adjust icon and text spacing */
.custom-navbar .nav-item i {
    margin-bottom: 12px; /* Space between icon and text */
}

.custom-navbar .nav-text {
    font-size: 0.8rem; /* Relative font size for responsiveness */
    color: #E9D8F6;
}

/* Back Button Styling */
.back-button {
    color: #4B286D; /* Text color */
    background-color: transparent; /* Transparent background */
    border: none; /* No border */
    font-weight: bold;
    display: flex;
    align-items: center;
}

.back-button:hover {
    color: #311432; /* Darker shade on hover */
    text-decoration: none;
}

/* Adjust Logout Button Icon */
.logout-button i {
    margin-right: 8px; /* Space between icon and text */
}
</style>
</head>


<body>
<header class="container-fluid p-3">
        <div class="d-flex align-items-center justify-content-between">
            <?php
                // Determine the current page
                $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                // If not on dashboard, show back button
                if ($current_page !== 'dashboard') {
                    echo "
                        <button class='btn btn-secondary' onclick='history.back();' aria-label='Go Back' style='color: #4B286D; background-color: transparent; border: none;'>
                            <i class='fas fa-arrow-left me-2'></i> Back
                        </button>
                    ";
                } else {
                    echo '<a href="actions/auth/logout.php" class="btn btn-danger logout-button">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>  <div style="height: 20vh"></div>
';
                }
            ?>

            
        </div>

        <!-- Navigation Bar -->
        <?php require_once __DIR__ . "/nav.php"; ?>
    </header>


    <!-- Bottom Navigation Bar -->
   
    <!-- Footer Section -->
    <nav class="navbar fixed-bottom navbar-expand-lg p-3 custom-navbar">
    <div class="container-fluid d-flex justify-content-around">
        <!-- Dashboard Link -->
        <a href="index.php?page=dashboard" class="nav-item nav-link text-center">
            <i class="fa-solid fa-house fa-lg"></i>
            <span class="d-block nav-text">Home</span>
        </a>

        <!-- Manage Link -->
        <a href="index.php?page=manage" class="nav-item nav-link text-center">
            <i class="fa-solid fa-calendar fa-lg"></i>
            <span class="d-block nav-text">Manage</span>
        </a>

        <!-- Visualize Link -->
        <a href="index.php?page=visualize" class="nav-item nav-link text-center">
            <i class="fa-solid fa-chart-pie fa-lg"></i>
            <span class="d-block nav-text">Visualize</span>
        </a>
    </div>
</nav>

<!-- External JS Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-toaster/js/bootstrap-toaster.min.js"></script>
    <script src="./inc/abtest.js"></script>
    


    <!-- Additional Scripts (if any) -->
</body>
</html>