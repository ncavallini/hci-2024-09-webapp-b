<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();
$group_id = $_GET['group_id'] ?? 0; 
$task_id = $_GET['task_id'];
$onDashboard = $_GET['onD'] ?? 0;





if($group_id == 0) {

    $sql = "SELECT estimated_load FROM tasks WHERE task_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$task_id]);
    $estimated_load = $stmt->fetchColumn();
    //var_dump('Estimated load ' . (int)$estimated_load);
   
    $sql = "UPDATE tasks SET is_completed = NOT is_completed, completed_at = NOW() WHERE task_id = ?";    
    $location = "../../index.php?page=manage_personal";
}
else {

    $sql = "SELECT estimated_load FROM group_tasks WHERE group_task_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$task_id]);
    $estimated_load = $stmt->fetchColumn();
    var_dump('Estimated load ' . (int)$estimated_load);

    $sql = "UPDATE group_tasks SET is_completed = NOT is_completed, completed_at = NOW() WHERE group_task_id = ?";
    $location = "../../index.php?page=group&id=$group_id";
}

$stmt = $connection->prepare($sql);
$stmt->execute([$task_id]);


if($group_id == 0) {
    $sql = "SELECT is_completed, NOW() < due_date AS on_time FROM tasks WHERE task_id = ?";
}
else {
    $sql = "SELECT is_completed, NOW() < due_date AS on_time FROM group_tasks WHERE group_task_id = ?";
}


$stmt = $connection->prepare($sql);
$stmt->execute([$task_id]);
$conditions = $stmt->fetch(PDO::FETCH_ASSOC);
$is_completed = $conditions['is_completed'];
$on_time = $conditions['on_time'];


if($is_completed == 1 && $on_time == 1) {
    $coins_to_add = $estimated_load;
}

else if($is_completed == 1 && $on_time == 0) {
    $coins_to_add = 0;
}
else if($is_completed == 0) {
    $coins_to_add = -$estimated_load;
    $t = $group_id == 0 ? 'tasks' : 'group_tasks';
    $c = $group_id == 0 ? 'task_id' : 'group_task_id';
    $sql = "UPDATE $t SET completed_at = NULL WHERE $c = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$task_id]);
}


$sql = "UPDATE users SET coins = GREATEST(0, coins + (?)) WHERE user_id = ?";
$stmt = $connection->prepare($sql);
$stmt->execute([$coins_to_add, Auth::user()['user_id']]);

if($onDashboard != 0) $location = "../../index.php?page=dashboard";
header("Location: $location");
?>