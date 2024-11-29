<?php
    require_once __DIR__ . "/../../utils/init.php";
    if(!Auth::is_logged_in()) {
        header("Location: ../../index.php?page=login");
        die;
    }
    $task_id = $_GET['task_id'];
    $isgroup = $_GET['group'] != 0;
    $connection = DBConnection::get_connection();
    //Check that the task id isn't 0
    if($task_id == 0) {
        header("Location: ../../index.php?page=home");
        die;
    }

    if($isgroup){
        //Check that a survey for this task doesn't exist yet
        $sql = "SELECT group_task_id FROM group_surveys WHERE group_task_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$task_id]);
        $var1 = $stmt->fetchAll();
        if(count($var1) != 0) {
            header("Location: ../../index.php?page=group&id=". $_GET['group']);
            die;
        }
        //Check that the task belongs to the current user
        $sql = "SELECT group_task_id FROM group_tasks WHERE group_task_id = ? AND user_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$task_id, Auth::user()["user_id"]]);
        $var1 = $stmt->fetchAll();
        if(count($var1) == 0){
            header("Location: ../../index.php?page=group&id=". $_GET['group']);
            die;
        }
    
        $sql = "INSERT INTO group_surveys(group_task_id, user_id, ans1, ans2, ans3, ans4) VALUE (:group_task_id, :user_id, :ans1, :ans2, :ans3, :ans4)";
        $stmt = $connection->prepare($sql);
        $stmt->bindParam(":group_task_id", $task_id);
        $stmt->bindParam(":user_id", Auth::user()["user_id"]);
        $stmt->bindParam(":ans1", $_POST['q1']);
        $stmt->bindParam(":ans2", $_POST['q2']);
        $stmt->bindParam(":ans3", $_POST['q3']);
        $stmt->bindParam(':ans4', $_POST['q4']);
        $stmt->execute();

        header("Location: ../../index.php?page=group&id=". $_GET['group']);
        die;
    }
    else{
        //Check that a survey for this task doesn't exist yet
        $sql = "SELECT task_id FROM surveys WHERE task_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$task_id]);
        $var1 = $stmt->fetchAll();
        if(count($var1) != 0) {
            header("Location: ../../index.php?page=manage_personal");
            die;
        }
        //Check that the task belongs to the current user
        $sql = "SELECT task_id FROM tasks WHERE task_id = ? AND user_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$task_id, Auth::user()["user_id"]]);
        $var1 = $stmt->fetchAll();
        if(count($var1) == 0){
            header("Location: ../../index.php?page=manage_personal");
            die;
        }
    
        $sql = "INSERT INTO surveys(task_id, ans1, ans2, ans3, ans4, user_id) VALUE (:task_id, :ans1, :ans2, :ans3, :ans4, :user_id)";
        $stmt = $connection->prepare($sql);
        $stmt->bindParam(":task_id", $task_id);
        $stmt->bindParam(":user_id", Auth::user()["user_id"]);
        $stmt->bindParam(":ans1", $_POST['q1']);
        $stmt->bindParam(":ans2", $_POST['q2']);
        $stmt->bindParam(":ans3", $_POST['q3']);
        $stmt->bindParam(':ans4', $_POST['q4']);
        $stmt->execute();

        header("Location: ../../index.php?page=manage_personal");
        die;
    }

    
?>