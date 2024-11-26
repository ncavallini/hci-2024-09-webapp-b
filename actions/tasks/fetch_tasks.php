<?php
    require_once __DIR__ . "/../../utils/init.php";

    if (!Auth::is_logged_in()) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "Unauthorized"]);
        die;
    }

    try {
        $userId = Auth::user()["user_id"];
        $connection = DBConnection::get_connection();

        $sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date ASC";
        $stmt = $connection->prepare($sql);
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header("Content-Type: application/json");
        echo json_encode($tasks);
    } catch (Exception $e){
        header("Content-Type: application/json");
        echo json_encode(["error" => $e->getMessage()]);
        die;
    }
?>