<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();
$name = $_POST['name'];
$members = json_decode($_POST['members']);
$sql = "INSERT INTO groups (name) VALUES (?)";
$stmt = $connection->prepare($sql);
$stmt->execute([$name]);
$group_id = $connection->lastInsertId("groups");
$sql = "INSERT INTO membership (group_id, username) VALUES (?, ?)";
foreach($members as $member) {
    $stmt = $connection->prepare($sql);
    $stmt->execute([$group_id, $member]);
}
header("Location: ../../index.php?page=manage");
?>