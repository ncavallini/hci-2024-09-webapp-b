<h1>Sign Up</h1>
<p>Fill in the form below to sign up. All fields are mandatory.</p>

<form action="actions/auth/signup.php" method="POST">
    <label for="username">Username</label>
    <input type="text" name="username" class="form-control" required>
    <br>
    <label for="email">E-mail address</label>
    <input type="email" name="email" class="form-control" required>
    <br>
    <label for="password">Password</label>
    <input type="password" name="password" class="form-control" required>
    <br>
    <label for="first_name">First Name</label>
    <input type="text" name="first_name" class="form-control" required>
    <br>
    <label for="last_name">Last Name</label>
    <input type="text" name="last_name" class="form-control" required>
    <br>
    <button type="submit" class="btn btn-primary">Sign Up</button>
</form>