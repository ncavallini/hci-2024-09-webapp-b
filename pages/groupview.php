<?php
require_once __DIR__ . "/../utils/init.php";

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

// Get the group ID from the URL
$group_id = $_GET['id'] ?? null;

if (!$group_id || !is_numeric($group_id)) {
    die("Invalid group ID.");
}

// Get the current user's ID
$user_id = Auth::user()['user_id'];

// Check if the user has tasks in this group
$sql = "SELECT 1 FROM group_tasks WHERE group_id = ? AND user_id = ? LIMIT 1";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$group_id, $user_id]);

if (!$stmt->fetch()) {
    die("You are not authorized to view this group.");
}

// Fetch tasks for the specified group
$sql = "
    SELECT 
        gt.title, 
        gt.description, 
        gt.due_date, 
        gt.estimated_load, 
        gt.is_completed,
        g.name AS group_name
    FROM 
        group_tasks gt
    JOIN 
        groups g ON gt.group_id = g.group_id
    WHERE 
        gt.group_id = ? AND gt.user_id = ?
    ORDER BY 
        gt.is_completed ASC, gt.estimated_load DESC, gt.due_date ASC";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$group_id, $user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the total mental load for this group
$total_load = array_sum(array_column($tasks, 'estimated_load'));

// Fetch and update the user's maximum mental load
$sql = "SELECT max_load FROM users WHERE user_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$max_load = $row['max_load'] ?? 0;
if ($total_load > $max_load) {
    $max_load = $total_load;
    $sql = "UPDATE users SET max_load = ? WHERE user_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$max_load, $user_id]);
}

$load_percentage = ($max_load > 0) ? ($total_load / $max_load) * 100 : 0;
?>

<div class="container mt-5">
    <h1 class="mb-4">Tasks for Group: <?php echo htmlspecialchars($tasks[0]['group_name'] ?? 'Unknown'); ?></h1>

    <!-- Mental Load Bar -->
    <div class="mb-4">
        <h5>Mental Load of Group: <?php echo htmlspecialchars($tasks[0]['group_name'] ?? 'Unknown'); ?></h5>
        <div class="progress">
            <div 
                class="progress-bar" 
                id="loadProgressBar" 
                role="progressbar" 
                style="width: <?php echo $load_percentage; ?>%;" 
                aria-valuenow="<?php echo $total_load; ?>" 
                aria-valuemin="0" 
                aria-valuemax="<?php echo $max_load; ?>">
                <?php echo round($load_percentage); ?>%
            </div>
        </div>

        <p class="mt-2">Group Load: <?php echo $total_load; ?> / Maximum Load: <?php echo $max_load; ?></p>
    </div>

    <!-- Buttons Row -->
    <div class="d-flex justify-content-between mb-3 gap-2">
        <!-- Left-aligned Task/Group Buttons -->
        <div class="d-flex flex-wrap gap-2">
            <button id="taskListButton" class="btn btn-primary" onclick="showListView('tasks')">Tasks</button>
            <button id="groupListButton" class="btn btn-secondary" onclick="showListView('groups')">Groups</button>
        </div>

        <!-- Right-aligned List/Pie Chart View Buttons -->
        <div class="d-flex flex-wrap gap-2">
            <button id="listViewButton" class="btn btn-primary" onclick="showView('listView')">List View</button>
            <button id="pieChartViewButton" class="btn btn-secondary" onclick="showView('pieChartView')">Pie Chart View</button>
        </div>
    </div>


    <!-- List View -->
    <div id="listView" class="d-flex flex-column gap-3">
        <div id="taskItems">
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item d-flex justify-content-between align-items-center p-3 border rounded"
                        onclick="showTaskDetails(<?php echo json_encode($task, ENT_QUOTES) ?>)">
                        <div class="task-info">
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="mb-1 text-muted">Group: <?php echo htmlspecialchars($task['group_name']); ?></p>
                            <small class="text-muted">Due: <?php echo (new DateTimeImmutable($task['due_date']))->format('Y-m-d H:i:s'); ?></small>
                        </div>
                        <div class="task-load text-end">
                            <span class="badge bg-primary">Load: <?php echo htmlspecialchars($task['estimated_load']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lead text-center text-muted">No tasks found for you across any groups.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Task Details OPENS OVERLAY DETAILS-->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskDetailsModalLabel">Task Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Title:</strong> <span id="taskTitle"></span></p>
                    <p><strong>Description:</strong> <span id="taskDescription"></span></p>
                    <p><strong>Due Date:</strong> <span id="taskDueDate"></span></p>
                    <p><strong>Estimated Load:</strong> <span id="taskEstimatedLoad"></span></p>
                    <p><strong>Group:</strong> <span id="taskGroupName"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Group Details OPENS OVERLAY DETAILS-->
    <div class="modal fade" id="groupDetailsModal" tabindex="-1" aria-labelledby="groupDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupDetailsModalLabel">Group Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 id="groupName"></h5>
                    <ul id="groupTasksList" class="list-unstyled"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie Chart View -->
    <div id="pieChartView" style="display: none;">
        <canvas id="pieChart" width="400" height="400"></canvas>
    </div>
</div>


    <!-- Pie Chart View -->
    <div id="pieChartView" style="display: none;">
        <canvas id="pieChart" width="400" height="400"></canvas>
    </div>

</div>

<script>
    let pieChart; // Chart.js instance
    let currentMode = 'tasks'; // Default to 'tasks'
    

    function showView(viewId) {
        // Get references to views and buttons
        const listView = document.getElementById('listView');
        const pieChartView = document.getElementById('pieChartView');
        const listViewButton = document.getElementById('listViewButton');
        const pieChartViewButton = document.getElementById('pieChartViewButton');

        // Ensure all elements exist
        if (!listView || !pieChartView || !listViewButton || !pieChartViewButton) {
            console.error("Required elements not found!");
            return;
        }

        // Reset both views
        listView.classList.remove('visible', 'hidden');
        pieChartView.classList.remove('visible', 'hidden');

        // Update button styles
        listViewButton.classList.add(viewId === 'listView' ? 'btn-primary' : 'btn-secondary');
        listViewButton.classList.remove(viewId === 'listView' ? 'btn-secondary' : 'btn-primary');

        pieChartViewButton.classList.add(viewId === 'pieChartView' ? 'btn-primary' : 'btn-secondary');
        pieChartViewButton.classList.remove(viewId === 'pieChartView' ? 'btn-secondary' : 'btn-primary');

        // Show the selected view
        if (viewId === 'listView') {
            listView.classList.add('visible');
            pieChartView.classList.add('hidden');
            showListView(currentMode); // Respect the current mode
        } else if (viewId === 'pieChartView') {
            listView.classList.add('hidden');
            pieChartView.classList.add('visible');
            showPieChart(currentMode); // Render Pie Chart in Tasks mode
        }

    }




    function showListView(mode) {
        const container = document.getElementById("taskItems");
        container.innerHTML = "";

        const tasks = <?php echo json_encode($tasks); ?>;

        const activeTasks = tasks.filter(task => task.is_completed !== 1);

        if (mode === "tasks") {
            activeTasks.forEach(task => {
                const isCompleted = task.is_completed === 1;
                const dueDate = new Date(task.due_date);
                const now = new Date();
                const isOverdue = dueDate < now && !isCompleted;

                const taskDiv = document.createElement("div");
                taskDiv.className = "task-item d-flex justify-content-between align-items-center p-3 border rounded";
                taskDiv.style.backgroundColor = isOverdue ? "lightcoral" : "";
                taskDiv.onclick = () => showTaskDetails(JSON.stringify(task));
                taskDiv.innerHTML = `
                    <div class="task-info">
                        <h5 class="${isCompleted ? 'text-primary' : ''} mb-1">
                            ${task.title} ${isCompleted ? '<span class="badge bg-success">Completed</span>' : ''}
                        </h5>
                        `;
                       taskDiv.innerHTML += ` <p class="mb-1 text-muted">${task.group_name === 'Personal' ? 'Personal Task' : 'Group: ' +  task.group_name}</p>
                        <small class="text-muted">Due: ${dueDate.toLocaleString()}</small>
                    </div>
                    <div class="task-load text-end">
                        <span class="badge bg-primary">Load: ${task.estimated_load}</span>
                    </div>
    `;
                container.appendChild(taskDiv);
            });
        } else if (mode === "groups") {
            const groupedTasks = tasks.reduce((acc, task) => {
                acc[task.group_name] = acc[task.group_name] || { group_id: task.group_id, tasks: [] };
                acc[task.group_name].tasks.push(task);
                return acc;
            }, {});


            //NICCOLO HELP
            Object.entries(groupedTasks).forEach(([groupName, groupData]) => {
                const groupDiv = document.createElement("div");
                groupDiv.className = "group-item border rounded p-3 mb-3";
                groupDiv.innerHTML = `
                    <h5>
                        <a href="#" class="text-decoration-none group-link">${groupName}</a>
                    </h5>
                    <p>${groupData.tasks.length} tasks in this group</p>
                `;

                // Add click event listener for redirection
                groupDiv.querySelector(".group-link").addEventListener("click", (event) => {
                    event.preventDefault(); // Prevent the default link behavior
                    if(groupData.group_id != 0)
                        window.location.href = `index.php?page=groupview&id=${groupData.group_id}`
                    else 
                        window.location.href = `index.php?page=visualize_personal`
                });

                container.appendChild(groupDiv);
            });

    }

    document.getElementById("taskListButton").classList.toggle("btn-primary", mode === "tasks");
    document.getElementById("taskListButton").classList.toggle("btn-secondary", mode !== "tasks");
    document.getElementById("groupListButton").classList.toggle("btn-primary", mode === "groups");
    document.getElementById("groupListButton").classList.toggle("btn-secondary", mode !== "groups");
}




    function showTaskDetails(taskJson) {
        const task = JSON.parse(taskJson);
        document.getElementById('taskTitle').textContent = task.title;
        document.getElementById('taskDescription').textContent = task.description;
        document.getElementById('taskDueDate').textContent = new Date(task.due_date).toLocaleString();
        document.getElementById('taskEstimatedLoad').textContent = task.estimated_load;
        document.getElementById('taskGroupName').textContent = task.group_name;
        new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
    }

    function showPieChart(mode) {
    currentMode = mode; // Remember the current mode
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("pieChart").getContext("2d");

    // Filter tasks to exclude completed ones
    const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

    console.log("Active tasks for pie chart:", activeTasks); // Debugging active tasks

    let pieData;

    if (mode === 'tasks') {
        // Prepare data for tasks
        pieData = {
            labels: activeTasks.map(task => task.title),
            datasets: [{
                data: activeTasks.map(task => task.estimated_load),
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
            }]
        };
    } else if (mode === 'groups') {
        // Prepare data for groups
        const groupData = activeTasks.reduce((acc, task) => {
            if (!acc[task.group_name]) {
                acc[task.group_name] = { group_id: task.group_id, load: 0 };
            }
            acc[task.group_name].load += task.estimated_load;
            return acc;
        }, {});

        console.log("Group data for pie chart:", groupData); // Debugging grouped data

        pieData = {
            labels: Object.keys(groupData),
            datasets: [{
                data: Object.values(groupData).map(group => group.load),
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
            }]
        };
    } else {
        console.error("Invalid mode provided for pie chart");
        return;
    }

    // Destroy previous chart instance if it exists
    if (pieChart) pieChart.destroy();

    // Create the new pie chart
    pieChart = new Chart(ctx, {
        type: 'pie',
        data: pieData,
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (tooltipItem) => {
                            const label = pieData.labels[tooltipItem.dataIndex];
                            const value = pieData.datasets[0].data[tooltipItem.dataIndex];
                            return `${label}: ${value}`;
                        }
                    }
                }
            }
        }
    });
}



    function updateProgressBar(loadPercentage) {
        const progressBar = document.querySelector(".progress-bar");

        if (!progressBar) {
            console.error("Progress bar element not found!");
            return;
        }

        // Set the progress bar width and aria attributes
        progressBar.style.width = `${loadPercentage}%`;
        progressBar.setAttribute("aria-valuenow", loadPercentage);

        // Change the progress bar's background color based on the percentage
        if (loadPercentage >= 80) {
            progressBar.style.backgroundColor = "darkred"; // High load
        } else if (loadPercentage >= 50) {
            progressBar.style.backgroundColor = "orange"; // Moderate load
        } else {
            progressBar.style.backgroundColor = "lightgreen"; // Low load
        }
    }

    function highlightOverdueTasks() {
        const taskItems = document.querySelectorAll(".task-item");

        if (!taskItems) {
            console.error("Task items not found!");
            return;
        }

        // Get the current date and time
        const now = new Date();

        // Loop through each task item
        taskItems.forEach(taskItem => {
            // Extract the due date from the task's data attribute or inner HTML
            const dueDateElement = taskItem.querySelector(".task-info small");
            if (dueDateElement) {
                const dueDateText = dueDateElement.textContent.replace("Due: ", "");
                const dueDate = new Date(dueDateText);


            }
        });
    }



    document.addEventListener('DOMContentLoaded', () => {
        // Default to List View and Tasks mode
        showView('listView');
        showListView('tasks');

        // Example: Use PHP to pass the load_percentage to JavaScript
        const progressBar = document.getElementById("loadProgressBar");
        const loadPercentage = <?php echo round($load_percentage); ?>;

        // Call the updateProgressBar function with the initial load percentage
        updateProgressBar(loadPercentage);


        // Event listeners for switching between views
        document.getElementById('listViewButton').addEventListener('click', () => showView('listView'));
        document.getElementById('pieChartViewButton').addEventListener('click', () => showView('pieChartView'));

        // Event listeners for toggling tasks and groups in List View
        document.getElementById('taskListButton').addEventListener('click', () => {
            console.log("Switching to Task View in List Chart");
            showListView('tasks')
        });
        document.getElementById('groupListButton').addEventListener('click', () => showListView('groups'));

        // Event listeners for toggling tasks and groups in Pie Chart View
        document.getElementById('taskListButton').addEventListener('click', () => {
            console.log("Switching to Task View in Pie Chart");
            showPieChart('tasks');
        });
        document.getElementById('groupListButton').addEventListener('click', () => {
            console.log("Switching to Group View in Pie Chart");
            showPieChart('groups');
        });

        highlightOverdueTasks();
    });

</script>

<style>
    .task-item:hover {
        background-color: #e9ecef; /* Light grey hover */
        color: inherit;
        cursor: pointer;
    }
    .group-item:hover {
        background-color: #e9ecef;
    }

    .hidden {
        display: none !important;
    }
    .visible {
        display: block !important;
    }

    .text-primary {
        color: #007bff !important;
    }

    .bg-success {
        background-color: #28a745 !important;
        color: white;
    }

    .task-item:hover {
        background-color: #e9ecef;
        cursor: pointer;
    }

    .btn-uniform {
        min-width: 120px; /* Set a uniform minimum width */
        text-align: center; /* Center align text */
    }

    @media (max-width: 576px) {
        .btn-uniform {
            min-width: 100px; /* Adjust size for smaller screens */
        }
    }

    .d-flex .btn {
        flex-grow: 1; /* Ensures buttons expand equally */
    }
</style>


<!-- AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA -->

<?php
require_once __DIR__ . "/../utils/init.php";

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

// Get the group ID from the URL
$group_id = $_GET['id'] ?? null;

if (!$group_id || !is_numeric($group_id)) {
    die("Invalid group ID.");
}

// Get the current user's ID
$user_id = Auth::user()['user_id'];

// Fetch total mental loads for all users in the group
$sql = "
    SELECT 
        gt.user_id, 
        u.first_name, 
        u.last_name, 
        SUM(gt.estimated_load) AS total_load
    FROM 
        group_tasks gt
    JOIN 
        users u ON gt.user_id = u.user_id
    WHERE 
        gt.group_id = ?
    GROUP BY 
        gt.user_id
    ORDER BY 
        total_load DESC
";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$group_id]);
$user_loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Find the current user's load
$current_user_load = 0;
foreach ($user_loads as $user) {
    if ($user['user_id'] == $user_id) {
        $current_user_load = $user['total_load'];
        break;
    }
}
?>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Mental Load Comparison</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-5">
    <h1 class="mb-4">Group Mental Load Comparison</h1>

    <!-- Bar Chart -->
    <div>
        <canvas id="compareChart" width="400" height="200"></canvas>
    </div>
</div>

<script>
    const userLoads = <?php echo json_encode($user_loads); ?>;
    const currentUserId = <?php echo json_encode($user_id); ?>;

    function renderComparisonChart(users) {
        const ctx = document.getElementById('compareChart').getContext('2d');
        const labels = users.map(user => {
            if (user.user_id == currentUserId) return `${user.first_name} ${user.last_name} (You)`;
            return `${user.first_name} ${user.last_name}`;
        });
        const data = users.map(user => user.total_load);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Mental Load',
                    data: data,
                    backgroundColor: users.map(user => 
                        user.user_id == currentUserId ? '#007bff' : '#ffc107'
                    )
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { 
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Mental Load'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (tooltipItem) => `${tooltipItem.raw} units`
                        }
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderComparisonChart(userLoads);
    });
</script>

