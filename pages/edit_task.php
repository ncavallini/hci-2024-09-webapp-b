<?php 
if(!isset($_GET['task_id'])) {
    header("Location: index.php?page=manage_personal");
    die;
}
$task_id = $_GET['task_id'];
$group_id = $_GET['group_id'] ?? 0;
$is_group_task = $group_id != 0;
$sql = $is_group_task ? "SELECT * FROM group_tasks WHERE group_task_id = ?" : "SELECT * FROM tasks WHERE task_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$task_id]);
$task = $stmt->fetch();
if(!$task) {
    header("Location: index.php?page=manage_personal");
    die;
}
?>
<h1 class="text-center">Edit Task</h1>
<form action="actions/tasks/edit.php" method="POST">
    <label for="title">Title *</label>
    <input type="text" name="title" class="form-control" required value="<?php echo $task['title'] ?>">
    <br> 
    <label for="due_date">Date & Time *</label>
    <input type="datetime-local" name="due_date" class="form-control" required value="<?php echo $task['due_date'] ?>">
    <br>
    <label for="location">Location</label>
    <input type="text" name="location" class="form-control" value="<?php echo $task['location'] ?>">
    <br>
    <label for="member">Member</label>
    <input type="text" disabled class="form-control" value="<?php echo Auth::user()['first_name'] . " " . Auth::user()['last_name'] ?>">
    <br>
    <label for="description">Description</label>
    <textarea name="description" class="form-control" rows="5"><?php echo $task['description'] ?></textarea>
    <br>
    <label for="estimated_load">Estimated load <span id="estimated_load_span">(<?php echo $task['estimated_load'] ?>/10)</span></label>
    <input type="range" name="estimated_load" id="estimated_load" min="0" max="10" step="1" value="<?php echo $task['estimated_load'] ?>" class="form-range">
    <br>
   <label for="category">Category</label>
   <select name="category" class="form-select">
        <option <?php echo $task['category'] == "STRESS" ? "selected" : "" ?> value="STRESS">General Stress</option>
        <option <?php echo $task['category'] == "MENTAL" ? "selected" : "" ?> value="MENTAL">Mental Load</option>
        <option <?php echo $task['category'] == "PHYSICAL" ? "selected" : "" ?> value="PHYSICAL">Physical Load</option>
   </select>
    <br>
    <input type="hidden" name="task_id" value="<?php echo $task_id ?>">
    <input type="hidden" name="group_id" value="<?php echo $group_id ?>">
    <button type="submit" class="btn btn-primary">Save</button>
</form>

<script>
    const range = document.getElementById("estimated_load");
    const span = document.getElementById("estimated_load_span");
    range.addEventListener("input", () => {
        span.innerHTML = "(" + range.value + "/10" + ")";
    });
</script>