<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();
$group_id = $_GET['id'];
$sql = "DELETE FROM groups WHERE group_id = ?";
$stmt = $connection->prepare($sql);
$stmt->execute([$group_id]);
header("Location: ../../index.php?page=manage");