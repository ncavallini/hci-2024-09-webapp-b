<?php
    if(!isset($_GET['group_id'])) {
        redirect("index.php?page=manage");
        die;
    }

    $group_id = $_GET['group_id'];

    $sql = "SELECT * FROM groups WHERE group_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();

    if(!$group) {
        redirect("index.php?page=manage");
        die;
    }

    $sql = "SELECT * FROM membership WHERE group_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $members_usernames = array_map(function($member) {
        return $member['username'];
    }, $members);
    
    // Sort the members by username to use binary search later
    usort($members_usernames, function($a, $b) {
        return strcmp($a, $b);
    });




?>

<h1 class="text-center">Edit Group <i></i> </h1>
<form action="actions/groups/edit.php" method="POST" id="edit_group" onsubmit="onFormSubmit(this)">
    <h2>Group</h2>
    <br>
    <label for="name">Group Name</label>
    <input type="text" class="form-control" name="name" id="name" value="<?php echo $group['name'] ?>" required/>
    <input type="hidden" name="group_id" value="<?php echo $group_id ?>">
    <br>
    <h2>Members</h2>
    <br>
    <div class="table-responsive">
        <table class="table" id="members-table">
            <thead>
                <tr>
                    <th>
                        Select?
                    </th>
                    <th>
                        Name
                    </th>
                    <th>
                        Username
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $users = $dbconnection->query("SELECT * FROM users");
                    foreach($users as $user){
                        $is_member = binary_search($members_usernames, $user['username'], true) != -1;
                        $is_member_checked = $is_member ? "checked" : "";
                        echo "<tr>";
                        echo "<td><input type='checkbox' $is_member_checked class='form-check-input' x-user='" . $user['username'] . "'></td>";
                        echo "<td>".$user['first_name'] . " " . $user['last_name'] ."</td>";
                        echo "<td>".$user['username']."</td>";
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>    
    <br>
    <button type="button" class="btn btn-primary" onclick="onFormSubmit(this)">Save</button>

</form>

<script>
    function onFormSubmit(event) {
        let usersToAdd = [];
        let checkboxes = Array.from(document.querySelectorAll("[x-user]"));
        checkboxes.forEach(checkbox => {
            if(checkbox.checked) {
                usersToAdd.push(checkbox.getAttribute("x-user"));
            }
        });

        if(usersToAdd.length == 0) {
            bootbox.alert({
                title: 'Uh oh!',
                message: "<div class='alert alert-danger'>You must select at least one member!</div>",
            });
            return;
        }
        const form = document.getElementById("edit_group");
        const membersInput = document.createElement("input");
        membersInput.type = "hidden";
        membersInput.name = "members";
        membersInput.value = JSON.stringify(usersToAdd);
        form.appendChild(membersInput);
        form.submit();

    }

    $(document).ready(function() {
        $('#members-table').DataTable();
    })
</script>