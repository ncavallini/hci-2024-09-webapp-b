<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();

$is_group_task = $_POST['group_id'] != 0;
$group_id = $_POST['group_id'];

if($is_group_task) {
    $sql = "UPDATE group_tasks SET group_id = ?, title = ?, due_date = ?, location = ?, description = ?, estimated_load = ?, category = ? WHERE group_task_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$_POST['group_id'], $_POST['title'], $_POST['due_date'], $_POST['location'], $_POST['description'], $_POST['estimated_load'], $_POST['category'], $_POST['task_id']]);
    $location = "../../index.php?page=group&id=$group_id"; 
}
else {
    $sql = "UPDATE tasks SET title = ?, due_date = ?, location = ?, description = ?, estimated_load = ?, category = ? WHERE task_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['due_date'], $_POST['location'], $_POST['description'], $_POST['estimated_load'], $_POST['category'], $_POST['task_id']]);
    $location = "../../index.php?page=manage_personal";
}

header("Location: $location");

?>