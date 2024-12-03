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

<style>
  .navbar {
    box-shadow: 0 -3px 5px rgba(0, 0, 0, 0.2);
    border-top: 2px solid #9A7FB5;
  }

  .nav-item:hover i {
    color: #E9D8F6;
  }

  .nav-item:hover span {
    color: #FFFFFF;
  }
</style>