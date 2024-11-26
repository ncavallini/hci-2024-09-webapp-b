<?php
if (!isset($_GET['id'])) {
    redirect("index.php?page=manage");
    die;
}

$group_id = $_GET['id'];

// Fetch group details
$sql = "SELECT g.* FROM groups_with_coins g WHERE group_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    redirect("index.php?page=manage");
    die;
}
?>

<h1>Group <i><?php echo ($group['name']); ?></i></h1>
<br>

<h2>Coins</h2>
<p class="lead"> <i class="fa fa-coins"></i> You earned <strong><?php echo ($group['coins']); ?> coins.</strong></p>

<h2>Members & Tasks</h2>
<a class="no-loading link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" id="set-accordion-status-link"></a>
<p>&nbsp;</p>
<div class="accordion" id="members-accordion">
    <?php
    // Fetch group members
    $sql = "SELECT m.username, u.first_name, u.last_name, u.user_id 
            FROM membership m 
            JOIN users u USING(username) 
            WHERE group_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll();
    $max_load = UserUtils::get_max_load();

    foreach ($members as $member):
        $accordion_id = "accordion-" . ($member['username']);
        $is_current_user = $member['user_id'] === Auth::user()['user_id'];
    ?>
    <div class="accordion-item">
        <h3 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $accordion_id; ?>">
                <?php echo UserUtils::get_avatar($member['first_name'][0] . $member['last_name'][0]); ?> &nbsp;&nbsp;
                &nbsp;&nbsp;<?php echo ($member['first_name'] . " " . $member['last_name']); ?>
                <div class="inline-progress">
                    &nbsp; &nbsp;
        <div class="progress" style="width: 200px;">
            <div class="progress-bar" role="progressbar" style="width: <?php echo UserUtils::get_total_load($member['username'])*100/UserUtils::get_max_load() ?>%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

            </button>
        </h3>
        <div id="<?php echo $accordion_id; ?>" class="accordion-collapse collapse show">
            <div class="accordion-body">
                <?php if ($is_current_user): ?>
                <a href="index.php?page=add_task&group_id=<?php echo $group_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i>
                </a>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table" id="table-member-<?php echo $member['user_id'] ?>">
                        <thead>
                            <tr>
                                <th>Done?</th>
                                <th>Task</th>
                                <th>Due</th>
                                <th data-dt-order="disable"><!-- Edit --></th>
                                <?php if ($is_current_user): ?>
                                <th data-dt-order="disable"><!-- Transfer --></th>
                                <?php endif; ?>
                                <th data-dt-order="disable"><!-- Delete --></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * 
                                    FROM group_tasks 
                                    WHERE group_id = ? AND user_id = ? 
                                    ORDER BY is_completed ASC, due_date ASC";
                            $stmt = $dbconnection->prepare($sql);
                            $stmt->execute([$group_id, $member['user_id']]);
                            $tasks = $stmt->fetchAll();

                            foreach ($tasks as $task):
                                $done_class = $task["is_completed"] ? "table-success" : "";
                            ?>
                            <tr class="<?php echo $done_class; ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input" 
                                           <?php echo $task['is_completed'] ? "checked" : ""; ?> 
                                           onclick="window.location.href='./actions/tasks/toggle_completed.php?group_id=<?php echo $group_id; ?>&task_id=<?php echo $task['group_task_id']; ?>'">
                                </td>
                                <td><?php echo ($task['title']); ?></td>
                                <td><?php echo (new DateTimeImmutable($task['due_date']))->format("d/m/Y, H:i"); ?></td> 
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" 
                                       href="index.php?page=edit_task&group_id=<?php echo $group_id; ?>&task_id=<?php echo $task['group_task_id']; ?>">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <?php if ($is_current_user): ?>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" 
                                       href="index.php?page=transfer_task&group_id=<?php echo $group_id; ?>&task_id=<?php echo $task['group_task_id']; ?>">
                                        <i class="fa fa-share"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <a class="btn btn-sm btn-outline-danger" 
                                       href="./actions/tasks/delete.php?group_id=<?php echo $group_id; ?>&task_id=<?php echo $task['group_task_id']; ?>">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div> <!-- end of accordion -->

<br>

<h2>Management</h2>
<div class="list-group">
    <a href="index.php?page=edit_group&group_id=<?php echo $group_id; ?>" class="list-group-item list-group-item-action">
        <i class="fa fa-edit"></i> Edit Group
    </a>
    <a href="actions/groups/delete.php?id=<?php echo $group_id; ?>" class="list-group-item list-group-item-action list-group-item-danger">
        <i class="fa fa-trash"></i> Delete Group
    </a>
</div>

<script>
    const setAccordionStatusLink = document.getElementById("set-accordion-status-link");
    let allOpen = true;
    const accordions = document.querySelectorAll(".accordion-collapse");

    accordions.forEach(accordion => {
        if (allOpen && !accordion.classList.contains("show")) {
            allOpen = false;
        }
    });

    setLinkIcon(allOpen, setAccordionStatusLink);

    setAccordionStatusLink.addEventListener("click", () => {
        accordions.forEach(accordion => {
            accordion.classList.toggle("show");
            const id = accordion.id;
            const button = document.querySelector(`button[data-bs-target="#${id}"]`);
            button.classList.toggle("collapsed");
        });

        allOpen = !allOpen;
        setLinkIcon(allOpen, setAccordionStatusLink);
    });


    function setLinkIcon(allOpen, el) {
        el.innerHTML = allOpen ? "<i class='fa-solid fa-caret-up'></i> Collapse All" : "<i class='fa-solid fa-caret-down'></i> Expand All";
    }


    $(document).ready(function() {
       const tables = document.querySelectorAll("table");
         tables.forEach(table => {
            if(table.id.indexOf("table-member-") >= 0)
              $(table).DataTable();
         });
    });
</script>
