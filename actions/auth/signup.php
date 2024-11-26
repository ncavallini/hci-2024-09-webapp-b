<?php
    require_once __DIR__ . "/../../utils/init.php";

    $connection = DBConnection::get_connection();
    $sql = "SELECT * FROM users WHERE username = :username OR email = :email";
    $stmt = $connection->prepare($sql);
    $stmt->execute([":username" => $_POST["username"], ":email" => $_POST["email"]]);
    $alreadyExist = $stmt->rowCount() > 0;
    if( $alreadyExist ) {
        print_alert("A user with the following username or e-mail address already exists", true);
        die;
    }

    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (:username, :email, :password_hash, :first_name, :last_name)";
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        ":username"=> $_POST["username"],
        ":email" => $_POST["email"],
        ":first_name" => $_POST["first_name"],
        ":last_name" => $_POST["last_name"],
        ":password_hash" => password_hash($_POST["password"], PASSWORD_BCRYPT)
    ]);

    print_alert("User created successfully! <a href='../../index.php?page=login' class='link alert-link'>Go to Log In</a>'", true, "success");
?>