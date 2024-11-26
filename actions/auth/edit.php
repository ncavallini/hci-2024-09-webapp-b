<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}
    $user = Auth::user();
    $reset_password = !empty($_POST['password']);
    $dbconnection = DBConnection::get_connection();
    if($reset_password) {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, password_hash = ? WHERE user_id = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $hash, $user['user_id']]);
    }
    else {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $user['user_id']]);
    }

    Auth::logout();

    redirect("../../index.php?page=manage_personal");
?>