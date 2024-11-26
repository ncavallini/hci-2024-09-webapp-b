<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();
$isGroupTask = $_POST['group'] != 0;
$group_id = $isGroupTask ? $_POST['group'] : null;

if($isGroupTask) {
    $sql = "INSERT INTO group_tasks (group_id, user_id, title, location, description, due_date, estimated_load, category, is_completed, created_at) 
    VALUES (:group_id, :user_id, :title, :location, :description, :due_date, :estimated_load, :category, 0, NOW())";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(":group_id", $group_id);
    $stmt->bindParam(":user_id", Auth::user()["user_id"]);
    $stmt->bindParam(":title", $_POST['title']);
    $stmt->bindParam(":location", $_POST['location']);
    $stmt->bindParam(":description", $_POST['description']);
    $stmt->bindParam(':due_date', $_POST['due_date']);
    $stmt->bindParam(':estimated_load', $_POST['estimated_load']);
    $stmt->bindParam(':category', $_POST['category']);
    $stmt->execute();

}
else {
    $sql = "INSERT INTO tasks (user_id, title, location, description, due_date, estimated_load, category, is_completed, created_at) 
    VALUES (:user_id, :title, :location, :description, :due_date, :estimated_load, :category, 0, NOW())";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(":user_id", Auth::user()["user_id"]);
    $stmt->bindParam(":title", $_POST['title']);
    $stmt->bindParam(":location", $_POST['location']);
    $stmt->bindParam(":description", $_POST['description']);
    $stmt->bindParam(':due_date', $_POST['due_date']);
    $stmt->bindParam(':estimated_load', $_POST['estimated_load']);
    $stmt->bindParam(':category', $_POST['category']);
    $stmt->execute();
}

if($isGroupTask) header("Location: ../../index.php?page=group&id=$group_id");
else header("Location: ../../index.php?page=manage_personal");
?>