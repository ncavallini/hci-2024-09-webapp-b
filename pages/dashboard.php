<h1 class="text-center">Welcome, <?php echo $_SESSION['user']['first_name'] ?></h1>
<br>
<div class="list-group">
  <a href="index.php?page=visualize" class="text-center list-group-item list-group-item-action active"><i class="fa-solid fa-chart-pie"></i> Visualize</a>
</div>

<p>&nbsp;&nbsp;</p>

<div class="card">
  <div class="card-header">
  <div class="card-title h5">Tasks due <i>Today</i> </div>
  </div>
  <div class="card-body">
    <?php
    $sql = "(SELECT t.task_id, t.title, 0 AS group_id, NULL as group_name FROM tasks t WHERE DATE(due_date) = :due_date AND user_id = :user_id AND is_completed = 0) UNION ALL 
            (SELECT gt.group_task_id, gt.title, gt.group_id, g.name as group_name FROM group_tasks gt JOIN groups g USING(group_id) WHERE DATE(due_date) = :due_date AND user_id = :user_id AND is_completed = 0)";
    $stmt = $dbconnection->prepare($sql);
    $tasks = $stmt->fetchAll();
    $stmt->bindValue(":due_date", date("Y-m-d"));
    $stmt->bindValue(":user_id", Auth::user()['user_id']);

    $stmt->execute();
    $tasks = $stmt->fetchAll();

    if (count($tasks) == 0) {
      echo "<p class='text-center'>No (incomplete) tasks due today</p>";
      goto end_task_due_today;
    } 
    echo '<ul class="list-group list-group-flush">';
    foreach($tasks as $task) {
      echo "<li class='list-group-item'>{$task['title']} &nbsp;&nbsp;";
      if($task['group_id'] != 0) {
        echo "<span class='badge bg-secondary rounded-pill'>{$task['group_name']}</span>";
      }
      else {
        echo "<span class='badge bg-primary rounded-pill'>Personal</span>";
      }
      echo "</li>"; 
    }
    end_task_due_today:
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
          <tr>
          <?php
            foreach($groups as $group) {
              echo "<td><p class='h5'>{$group['name']}</p></td>";
            }
          ?>
          </tr>

          <?php
          $max_members = 0;
          foreach($groups as &$group) {
            $query = "SELECT m.username, u.first_name, u.last_name FROM membership m JOIN users u USING(username) WHERE group_id = ?";
            $stmt = $dbconnection->prepare($query);
            $stmt->execute([$group['group_id']]);
            $members = $stmt->fetchAll();
            $group['members'] = $members;
            $max_members = max($max_members, count($members));
        
          }
          
          for($i = 0; $i < $max_members; $i++) {
            echo "<tr class='border-start'>";
            foreach($groups as $group) {
              if($i < count($group['members'])) {
                $name = $group['members'][$i]['first_name'] . ' ' . $group['members'][$i]['last_name'] ;
                echo "<td class='border-start'>{$name}</td>";
              }
              else {
                echo "<td class='border-start'></td>";
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