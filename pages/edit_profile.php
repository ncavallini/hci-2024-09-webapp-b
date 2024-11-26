<?php
    $user = Auth::user();
?>
<h1>Edit Profile</h1>
<br>
<form action="actions/auth/edit.php" method="POST">
    <label for="first_name">First Name</label>
    <input type="text" name="first_name" class="form-control" required value="<?php echo $user['first_name'] ?>">
    <br>
    <label for="last_name">Last Name</label>
    <input type="text" name="last_name" class="form-control" required value="<?php echo $user['last_name'] ?>">
    <br>
    <label for="email">E-mail</label>
    <input type="email" name="email" class="form-control" required value="<?php echo $user['email'] ?>">
    <br>
    <label for="password">New Password</label>
    <input type="password" name="password" class="form-control" placeholder="(unchanged)">
    <br>
    <button type="submit" class="btn btn-primary">Save</button>
</form>