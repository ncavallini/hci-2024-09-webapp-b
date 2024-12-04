
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="inc/styles.css">

    <link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="favicon.svg" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="Visualizing Mental Load" />
<link rel="manifest" href="site.webmanifest" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://kit.fontawesome.com/f5ed638bc2.js" crossorigin="anonymous"></script>
    
    <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.1.8/r-3.0.3/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.1.8/r-3.0.3/datatables.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.0/bootbox.min.js" integrity="sha512-oVbWSv2O4y1UzvExJMHaHcaib4wsBMS5tEP3/YkMP6GmkwRJAa79Jwsv+Y/w7w2Vb/98/Xhvck10LyJweB8Jsw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-toaster@4.0.1/css/bootstrap-toaster.min.css" />
   
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>


    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.2.0/dist/chartjs-chart-matrix.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@3.1.0/dist/chartjs-chart-treemap.min.js"></script>

<style>
  /* Footer Styling */
footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background-color: #f8f9fa; /* Light background for visibility */
    z-index: 1040; /* Higher than Bootstrap's navbar z-index (1030) */
}

footer .card {
    border-radius: 0; /* Remove border radius for a seamless fit */
}

footer .card-header {
    background-color: #f8f9fa; /* Match with footer background */
    color: #4B286D; /* Text color as specified */
}

footer .card-body {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 10px;
    color: #4B286D; /* Clock text color */
}

/* Ensure content above the footer is not hidden */
body {
    padding-bottom: 100px; /* Adjust based on footer height */
}

/* Logout Link Hover Effect */
.logout-link:hover {
    text-decoration: underline;
    color: #4B286D; /* Change color on hover if desired */
}

a {
  color: #4B286D;
}
</style>

    

</head>


<body>
  
  <!-- Header with Back Button and Title -->
  <header class="container-fluid p-3">
    <div class="d-flex align-items-center">
      <!-- Back Button -->
      

      <?php
    // Determine the current page
    $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    // If not on dashboard, show back button
    if ($current_page !== 'dashboard') {
        echo '
            <button class="btn-secondary me-3" onclick="history.back();" aria-label="Go Back">
              <i class="fas fa-arrow-left me-2"></i> Back
            </button>
        ';
    }else {
      echo '
          <a href="actions/auth/logout.php" class="btn btn-danger logout-button">
              <i class="fas fa-sign-out-alt me-2"></i> Logout
          </a>
      ';
  }
?>
    </div>

    <!-- Navigation Bar -->
  </header>
  


  <!-- Bottom Navigation Bar -->
  
  <script>
    (function() {
      let touchStartX = 0;
      let touchStartY = 0;
      let touchEndX = 0;
      let touchEndY = 0;
      const minSwipeDistance = 50;
      const maxVerticalMovement = 100;
      const swipeHint = document.getElementById('swipeHint');
      let hintTimeout;

      function handleGesture() {
        const deltaX = touchEndX - touchStartX;
        const deltaY = Math.abs(touchEndY - touchStartY);

        if (deltaX > minSwipeDistance && deltaY < maxVerticalMovement) {
          history.back();
        }
      }

      function showSwipeHint() {
        if (swipeHint) {
          swipeHint.style.display = 'block';
          clearTimeout(hintTimeout);
          hintTimeout = setTimeout(() => {
            swipeHint.style.display = 'none';
          }, 3000); // Hide after 3 seconds
        }
      }

      document.addEventListener('touchstart', function(event) {
        if (event.touches.length > 1) return;
        touchStartX = event.touches[0].clientX;
        touchStartY = event.touches[0].clientY;
      }, false);

      document.addEventListener('touchmove', function(event) {
        if (event.touches.length > 1) return;
        touchEndX = event.touches[0].clientX;
        touchEndY = event.touches[0].clientY;

        if (Math.abs(touchEndX - touchStartX) > 10) {
          showSwipeHint();
        }
      }, false);

      document.addEventListener('touchend', function(event) {
        handleGesture();
      }, false);
    })();
  </script>

</body>