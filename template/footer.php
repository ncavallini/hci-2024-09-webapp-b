<br>
<p>&nbsp;&nbsp;</p>
<footer class="container-fluid">
    <div class="card">
    <h5 class="card-header"><?php echo (Auth::is_logged_in()) ? "You are logged in as <a href='index.php?page=manage_personal'>" . $_SESSION['user']['first_name'] . " " . $_SESSION['user']['last_name'] . "</a>&nbsp; &nbsp; <a class='link-with-icon small'  href='actions/auth/logout.php'>Logout</a>" : "You are not logged in." ?>    </h5>
    <div class="card-body">
        <div id="clock" style="text-align: right;"></div>
    </div>
    </div>

</footer>


</body>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-toaster/js/bootstrap-toaster.min.js"></script>
<script src="./inc/abtest.js"></script>

<script>
    const clockElement = document.getElementById('clock');
    function updateClock() {
        clockElement.innerHTML = new Date().toLocaleString();
    }
    setInterval(updateClock, 1000);
</script>