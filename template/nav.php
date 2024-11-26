<nav class="navbar bg-body-tertiary ">
  <div class="container-fluid">
   <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-toggler" href="index.php?page=dashboard"><i class="fa-solid fa-house"></i></a>
    <button class="navbar-toggler" onclick="javascript:window.history.go(-1)"><i class="fa-solid fa-arrow-left"></i></button>
    <!--<a class="navbar-brand" href="#">Visualizing Mental Load</a>-->
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Visualizing Mental Load</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">

        <li class="nav-item">
          <span class="badge rounded-pill bg-warning"><i class="fa fa-coins"></i> <?php echo UserUtils::get_coins(); ?></span>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="index.php?page=dashboard">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php?page=visualize">Visualize</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php?page=manage">Manage</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<br>