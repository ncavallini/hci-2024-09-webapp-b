<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}
$dbconnection = DBConnection::get_connection();
$sql = "DELETE FROM users WHERE user_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([Auth::user()["user_id"]]);
Auth::logout();
header("Location: ../../index.php?page=login");