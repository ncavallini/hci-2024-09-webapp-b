<?php

require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$task_id = $_POST['task_id'];
$group_id = $_POST['group_id'];
$new_member = $_POST['member'];
$connection = DBConnection::get_connection();

$sql = "SELECT estimated_load FROM group_tasks WHERE group_task_id = ?";
$stmt = $connection->prepare($sql);
$stmt->execute([$task_id]);
$estimated_load = $stmt->fetchColumn();



$sql = "UPDATE group_tasks SET user_id = (SELECT user_id FROM users WHERE username = :member) WHERE group_task_id = :task_id";
$stmt = $connection->prepare($sql);
$stmt->execute([":member" => $new_member, ":task_id" => $task_id]);

$sql = "UPDATE users SET coins = coins - :estimated_load WHERE username = :username";
$stmt = $connection->prepare($sql);
$stmt->execute([":estimated_load" => $estimated_load, ":username" => Auth::user()['username']]);


header("Location: ../../index.php?page=group&id=$group_id");



?>