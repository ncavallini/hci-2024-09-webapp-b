<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();
if(!isset($_POST['group_id'])) {
    header("Location: ../../index.php?page=manage");
    die;
}

$group_id = $_POST['group_id'];

$sql = "UPDATE groups SET name = ? WHERE group_id = ?";
$stmt = $connection->prepare($sql);
$stmt->execute([$_POST['name'], $group_id]);

$sql = "DELETE FROM membership WHERE group_id = ?";
$stmt = $connection->prepare($sql);
$stmt->execute([$group_id]);

$sql = "INSERT INTO membership (group_id, username) VALUES (?, ?)";
$members = json_decode($_POST['members']);

foreach($members as $member) {
    $stmt = $connection->prepare($sql);
    $stmt->execute([$group_id, $member]);
}

header("Location: ../../index.php?page=group&id=$group_id");

?>