<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="inc/styles.css">

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



    

</head>


<body>
  <!-- Header with Back Button and Title -->
  <header class="container-fluid p-3">
    <div class="d-flex align-items-center">
      <!-- Back Button -->
      <button class="btn-secondary me-3" onclick="history.back();" aria-label="Go Back">
        <i class="fas fa-arrow-left me-2"></i> Back
      </button>

    
    </div>

    <!-- Navigation Bar -->
    <?php require_once __DIR__ . "/nav.php"; ?>
  </header>
  


  <!-- Bottom Navigation Bar -->
  <nav class="navbar fixed-bottom navbar-expand-lg p-3" style="background-color: #4B286D;">
    <div class="container-fluid d-flex justify-content-around">
      <!-- Dashboard Link -->
      <a href="index.php?page=dashboard" class="nav-item nav-link text-center" style="color: white;">
        <i class="fa-solid fa-house fa-lg" style="color: #9A7FB5;"></i>
        <span class="d-block" style="font-size: 12px; color: #E9D8F6;">Home</span>
      </a>

      <!-- Manage Link -->
      <a href="index.php?page=manage" class="nav-item nav-link text-center" style="color: white;">
        <i class="fa-solid fa-calendar fa-lg" style="color: #9A7FB5;"></i>
        <span class="d-block" style="font-size: 12px; color: #E9D8F6;">Manage</span>
      </a>

      <!-- Visualize Link -->
      <a href="index.php?page=visualize" class="nav-item nav-link text-center" style="color: white;">
        <i class="fa-solid fa-chart-pie fa-lg" style="color: #9A7FB5;"></i>
        <span class="d-block" style="font-size: 12px; color: #E9D8F6;">Visualize</span>
      </a>
    </div>
  </nav>
  <script>
    (function() {
      let touchStartX = 0;
      let touchStartY = 0;
      let touchEndX = 0;
      let touchEndY = 0;
      const minSwipeDistance = 50; // Minimum swipe distance in pixels
      const maxVerticalMovement = 100; // Maximum vertical movement allowed
      const swipeHint = document.getElementById('swipeHint');
      let hintTimeout;

      function handleGesture() {
        const deltaX = touchEndX - touchStartX;
        const deltaY = Math.abs(touchEndY - touchStartY);

        if (deltaX > minSwipeDistance && deltaY < maxVerticalMovement) {
          // Detected a left-to-right swipe
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
        if (event.touches.length > 1) return; // Ignore multi-touch
        touchStartX = event.touches[0].clientX;
        touchStartY = event.touches[0].clientY;
      }, false);

      document.addEventListener('touchmove', function(event) {
        if (event.touches.length > 1) return; // Ignore multi-touch
        touchEndX = event.touches[0].clientX;
        touchEndY = event.touches[0].clientY;

        // Optionally, show the swipe hint when the user starts swiping
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