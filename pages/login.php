<?php
    if(Auth::is_logged_in()){
        header('Location: index.php?page=home');
        exit;
    }
?>
<h1>Login</h1>

<form action="actions/auth/login.php" method="POST">
    <label for="username">Username</label>
    <input type="text" name="username" class="form-control" id="username" required>

    <br>
    <label for="password">Password</label>
    <input type="password" name="password" class="form-control" id="password" required>
    <br>
    <button type="submit" class="btn btn-primary">Login</button>
</form>
<br>
<p>Don't have an account? <a href="index.php?page=signup">Sign Up</a>!</p>
