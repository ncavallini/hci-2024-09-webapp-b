<h1>Transfer Task</h1>
<br>
<div class="card">
    <div class="card-header h5">Task Details</div>
    <div class="card-body">
        <?php

        $task_id = $_GET['task_id'];
        $group_id = $_GET['group_id'];
        $sql = "SELECT * FROM group_tasks WHERE group_task_id = ? AND group_id = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([$task_id, $group_id]);
        $task = $stmt->fetch();

        if(!$task) {
            echo "<div class='alert alert-danger'>Task not found</div>";
            goto end;
        }

        $user_coins = UserUtils::get_coins();
        if($task['estimated_load'] > $user_coins) {
            echo "<div class='alert alert-warning'>You do not have enough coins to transfer this task</div>";
            goto end;
        }

        $late = (new DateTimeImmutable($task['due_date'])) < new DateTimeImmutable();

        echo "<ul>";
        echo "<li><strong>Title:</strong> {$task['title']}</li>";
        echo "<li><strong>Due Date:</strong> "  . (new DateTimeImmutable($task['due_date']))->format("d/m/Y, H:i:s") . ($late ? " <span class='badge text-bg-secondary'>LATE</span>" : "") . "</li>";
        echo "<li><strong>Description:</strong> {$task['description']}</li>";
        echo "<li><strong>Estimated Load:</strong> {$task['estimated_load']}/10</li>";
        echo "</ul><br>";
        ?>
    </div>
</div>

<br>

<div class="card">
    <div class="card-header h5">Transfer To...</div>
    <div class="card-body">
        <form action="actions/tasks/transfer.php" method="POST">
            <input type="hidden" name="group_id" value="<?php echo $group_id ?>">
            <input type="hidden" name="task_id" value="<?php echo $task_id ?>">
            <div class="mb-3">
                <label for="member" class="form-label">Member</label>
                <select class="form-select" name="member">
                    <?php
                    $query = "SELECT m.username, CONCAT(u.first_name, ' ', u.last_name) AS name FROM membership m JOIN users u ON m.username = u.username WHERE m.group_id = ? AND m.username <> ?";
                    $stmt = $dbconnection->prepare($query);
                    $stmt->execute([$group_id, Auth::user()['username']]);
                    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach($members as $member) {
                        echo "<option value='{$member['username']}'>{$member['name']}</option>";
                    }
                    ?>
                </select>
                <br>
                <button type="submit" class="btn btn-primary">Transfer</button>
            </div>
        </form>
    </div>
<?php end: ?>