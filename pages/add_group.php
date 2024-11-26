<h1 class="text-center">Add Group</h1>
<form action="actions/groups/add.php" method="POST" id="add_group" onsubmit="onFormSubmit(this)">
    <h2>Group</h2>
    <br>
    <label for="name">Group Name</label>
    <input type="text" class="form-control" name="name" id="name" required/>
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
                        $force_current = $user['username'] == Auth::user()['username'] ? "checked disabled" : "";
                        echo "<tr>";
                        echo "<td><input type='checkbox' $force_current class='form-check-input' x-user='" . $user['username'] . "'></td>";
                        echo "<td>".$user['first_name'] . " " . $user['last_name'] ."</td>";
                        echo "<td>".$user['username']."</td>";
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>    
    <br>
    <button type="button" class="btn btn-primary" onclick="onFormSubmit(this)">Add</button>

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

        const form = document.getElementById("add_group");
        const membersInput = document.createElement("input");
        membersInput.type = "hidden";
        membersInput.name = "members";
        membersInput.value = JSON.stringify(usersToAdd);
        form.appendChild(membersInput);
        form.submit();

    }

    $(document).ready(function() {
        $('#members-table').DataTable();
    });
</script>