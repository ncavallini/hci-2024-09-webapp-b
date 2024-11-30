<h1 class="text-center">Welcome, <?php echo $_SESSION['user']['first_name'] ?></h1>
<br>
<div class="list-group">
  <a href="index.php?page=visualize" class="text-center list-group-item list-group-item-action active"><i class="fa-solid fa-chart-pie"></i> Visualize</a>
</div>

<p>&nbsp;&nbsp;</p>

<div class="card">
  <div class="card-header">
  <div class="card-title h5">Tasks to be completed <i>Before</i> the Deadline</div>
  </div>
  <div class="card-body">
  <?php
// SQL query to fetch all tasks that are not overdue and not completed
$sql = "
    (SELECT 
        t.task_id, 
        t.title, 
        0 AS group_id, 
        NULL AS group_name 
     FROM 
        tasks t 
     WHERE 
        DATE(due_date) >= CURRENT_DATE 
        AND user_id = :user_id 
        AND is_completed = 0
    )
    UNION ALL
    (SELECT 
        gt.group_task_id, 
        gt.title, 
        gt.group_id, 
        g.name AS group_name 
     FROM 
        group_tasks gt 
     JOIN 
        groups g USING(group_id) 
     WHERE 
        DATE(due_date) >= CURRENT_DATE 
        AND user_id = :user_id 
        AND is_completed = 0
    )";

$stmt = $dbconnection->prepare($sql);
$stmt->bindValue(":user_id", Auth::user()['user_id']);
$stmt->execute();

$tasks = $stmt->fetchAll();

// If no tasks found, display a message
if (count($tasks) == 0) {
    echo "<p class='text-center'>No incomplete tasks are due or upcoming</p>";
    goto end_task_due;
}

// Display the tasks in a list
echo '<ul class="list-group list-group-flush">';
foreach ($tasks as $task) {
    echo "<li class='list-group-item'>{$task['title']} &nbsp;&nbsp;";
    if ($task['group_id'] != 0) {
        echo "<span class='badge bg-secondary rounded-pill'>{$task['group_name']}</span>";
        echo "<div style='float:right'><a class='btn btn-sm btn-outline-primary' href='index.php?page=survey&task_id=".$task['task_id']."&group=".$task['group_id']."&onD=1'><i class='fa fa-check-square-o'></i></a></div>";
    } else {
        echo "<span class='badge bg-primary rounded-pill'>Personal</span>";
        echo "<div style='float:right'><a class='btn btn-sm btn-outline-primary' href='index.php?page=survey&task_id=".$task['task_id']."&group=".$task['group_id']."&onD=1'><i class='fa fa-check-square-o'></i></a></div>";
    }
    echo "</li>";
}
end_task_due:
?>

  </div>
</div>
<p>&nbsp;&nbsp;</p>


<?php 
$query = "SELECT m.group_id, g.name FROM membership m JOIN groups g ON m.group_id = g.group_id WHERE m.username = ?";
$stmt = $dbconnection->prepare($query);
$stmt->execute([$user['username']]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="card">
  <div class="card-header">
  <div class="card-title h5">Groups & Members</div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-borderless">
        <thead>
          <tr>
          <?php for($i = 0; $i < count($groups); $i++) echo "<th></th>"; ?>
          </tr>
        </thead>
        <tbody>
          <!-- Display Group Names -->
          <tr>
            <?php foreach ($groups as $group): ?>
              <td><p class='h5'><?php echo htmlspecialchars($group['name']); ?></p></td>
            <?php endforeach; ?>
          </tr>

          <!-- Line Under Group Names -->
          <tr>
            <?php foreach ($groups as $group): ?>
              <td style="border-bottom: 2px solid #000;"></td>
            <?php endforeach; ?>
          </tr>

          <?php
          // Initialize variables
          $max_members = 0;
          $group_members = [];

          // Fetch members for each group
          foreach ($groups as $group) {
              $query = "SELECT m.username, u.first_name, u.last_name 
                        FROM membership m 
                        JOIN users u USING(username) 
                        WHERE group_id = ?";
              $stmt = $dbconnection->prepare($query);
              $stmt->execute([$group['group_id']]);
              $members = $stmt->fetchAll();
              $group_members[] = $members;
              $max_members = max($max_members, count($members));
          }

          // Render rows for members
          for ($i = 0; $i < $max_members; $i++) {
            echo "<tr>";
            foreach ($group_members as $members) {
                if (isset($members[$i])) {
                    $name = htmlspecialchars($members[$i]['first_name']) . ' ' . htmlspecialchars($members[$i]['last_name']);
                    echo "<td style='border-right: 1px solid #000; border-left: 1px solid #000;'>{$name}</td>";
                } else {
                    echo "<td style='border-right: 1px solid #000; border-left: 1px solid #000;'></td>";
                }
            }
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>        
    </div>
</div>


<div class="list-group">
  <a href="index.php?page=manage" class="text-center list-group-item list-group-item-action active"><i class="fa-solid fa-gear"></i> Manage</a>
</div>