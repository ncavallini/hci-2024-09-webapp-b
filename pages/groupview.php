<?php
require_once __DIR__ . "/../utils/init.php";

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

try {
    $dbconnection = DBConnection::get_connection();
    $user_id = Auth::user()['user_id']; // Get the logged-in user's ID
    $group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Fetch group name
    // Fetch group name based only on group_id
    $sql = "SELECT name FROM groups WHERE group_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id]);
    $groupRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $group = $groupRow['name'] ?? "Unknown Group"; // Fallback if group is not found


    // Fetch tasks for the specified group_id
    $sql = "
        SELECT 
            at.title, 
            at.location, 
            at.description, 
            at.due_date, 
            at.estimated_load, 
            at.is_completed 
        FROM 
            group_tasks at
        WHERE 
            at.group_id = ? AND at.user_id = ?
            AND (at.is_completed = 0 OR at.is_completed IS NULL)
        ORDER BY 
            at.is_completed ASC, at.due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id, $user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate overdue and non-overdue tasks
    $now = new DateTime();
    $overdueTasks = [];
    $nonOverdueTasks = [];
    foreach ($tasks as $task) {
        $dueDate = new DateTime($task['due_date']);
        if ($dueDate < $now) {
            $overdueTasks[] = $task;
        } else {
            $nonOverdueTasks[] = $task;
        }
    }

    // Calculate total mental load for all tasks
    $total_load = array_sum(array_column($tasks, 'estimated_load'));

    // Fetch and update maximum load for the user
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
} catch (Exception $e) {
    die("Error fetching tasks: " . $e->getMessage());
}
?>

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
        gt.group_id = ? AND gt.is_completed = 0
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



   
    <div class="container mt-5">
        <h1 class="mb-4"><?php echo htmlspecialchars($group); ?>'s Tasks</h1>

        <!-- Mental Load Bar -->
        <div class="mb-4 position-relative">
        <h5><?php echo htmlspecialchars($group); ?>'s Mental Load
            <a class= "nav-link" href="index.php?page=pastLoad"> 
                <button 
                    class="btn btn-info position-absolute top-0 end-0" >
                    Past Mental Load
                </button>
            </a>
        </h5>
        <div class="progress mt-3">
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
        <p class="mt-2">Current Load: <?php echo $total_load; ?> / Maximum Load: <?php echo $max_load; ?></p>
    </div>
    <div class="d-flex flex-row align-items-center gap-3">
    <!-- Buttons Section -->
    <div class="d-flex flex-column gap-3" style="width: 20%;">
        <!-- Buttons for List and Pie Chart Views -->
        <div class="d-flex flex-column gap-2">
            <button id="heatmapViewButton" class="btn btn-primary w-100" onclick="showView('heatmapView')">Heatmap View</button>
            <button id="radarChartViewButton" class="btn btn-secondary w-100" onclick="showView('radarChartView')">Radar Chart View</button>
            <button id="scatterChartViewButton" class="btn btn-secondary w-100" onclick="showView('scatterChartView')">Scatter Chart View</button>
        </div>
    </div>

    <!-- Graph Views Section -->
    <div class="flex-grow-1">
        <!-- Heatmap View -->
        <div id="heatmapView" style="display: none;">
            <canvas id="heatmapChart" width="400" height="400"></canvas>
        </div>

        <!-- Radar Chart View -->
        <div id="radarChartView" style="display: none;">
            <canvas id="radarChart" width="400" height="400"></canvas>
        </div>

        <!-- Scatter Chart View -->
        <div id="scatterChartView" style="display: none;">
            <canvas id="scatterChart" width="400" height="400"></canvas>
        </div>

        <!-- Bar Chart -->
        <div id="barChartView">
            <h1 class="mb-4">Group Mental Load Comparison</h1>
            <canvas id="compareChart" width="600" height="400"></canvas>
        </div>
    </div>
</div>

</div>


    <script>
    let currentMode = 'tasks'; // Default to 'tasks'
    let currentView = 'heatmapView'; // Default to 'heatmapView'
    let heatmapChart;
    let radarChart;
    let scatterChart;

    function showView(viewId) {
        // Hide all views
        document.getElementById('heatmapView').style.display = 'none';
        document.getElementById('radarChartView').style.display = 'none';
        document.getElementById('scatterChartView').style.display = 'none';

        // Remove active class from buttons
        document.getElementById('heatmapViewButton').classList.remove('btn-primary');
        document.getElementById('heatmapViewButton').classList.add('btn-secondary');
        document.getElementById('radarChartViewButton').classList.remove('btn-primary');
        document.getElementById('radarChartViewButton').classList.add('btn-secondary');
        document.getElementById('scatterChartViewButton').classList.remove('btn-primary');
        document.getElementById('scatterChartViewButton').classList.add('btn-secondary');

        // Show selected view
        document.getElementById(viewId).style.display = 'block';
        document.getElementById(`${viewId}Button`).classList.add('btn-primary');
        document.getElementById(`${viewId}Button`).classList.remove('btn-secondary');

        // Update current view
        currentView = viewId;

        // Call appropriate function
        if (viewId === 'heatmapView') {
            showHeatmapView(currentMode);
        } else if (viewId === 'radarChartView') {
            showRadarChartView(currentMode);
        } else if (viewId === 'scatterChartView') {
            showScatterChartView(currentMode);
        }
    }

    function toggleTaskGroup(mode) {
        currentMode = mode;

        if (currentView === 'heatmapView') {
            showHeatmapView(mode);
        } else if (currentView === 'radarChartView') {
            showRadarChartView(mode);
        } else if (currentView === 'scatterChartView') {
            showScatterChartView(mode);
        }

        // Update button styles
        document.getElementById("taskListButton").classList.toggle("btn-primary", mode === "tasks");
        document.getElementById("taskListButton").classList.toggle("btn-secondary", mode !== "tasks");
        document.getElementById("groupListButton").classList.toggle("btn-primary", mode === "groups");
        document.getElementById("groupListButton").classList.toggle("btn-secondary", mode !== "groups");
    }

    function showHeatmapView(mode) {
        const tasks = <?php echo json_encode($tasks); ?>;
        const canvas = document.getElementById("heatmapChart");
        const ctx = canvas.getContext("2d");

        // Filter tasks to exclude completed ones
        const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

        // Assign group colors dynamically
        const colorPalette = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#E7E9ED', '#B39DDB', '#9CCC65', '#FF7043',
        ];
        const groupColorMap = {};
        let colorIndex = 0;

        // Prepare data for heatmap
        const dataMatrix = [];
        const xLabelsSet = new Set(); // Use Set to ensure uniqueness
        const yLabelsSet = new Set();

        // Process tasks to populate labels and matrix
        activeTasks.forEach(task => {
            const dueDate = new Date(task.due_date).toLocaleDateString();
            const yValue = mode === 'tasks' ? task.title : task.group_name || 'Personal';
            const groupName = task.group_name || 'Personal';

            xLabelsSet.add(dueDate);
            yLabelsSet.add(yValue);

            // Assign color to group if not already assigned
            if (!groupColorMap[groupName]) {
                groupColorMap[groupName] = colorPalette[colorIndex % colorPalette.length];
                colorIndex++;
            }

            // Map task to correct cell
            dataMatrix.push({
                xLabel: dueDate,
                yLabel: yValue,
                v: task.estimated_load,
                task: task,
                groupColor: groupColorMap[groupName],
            });
        });

        // Convert Sets to sorted arrays
        const xLabels = Array.from(xLabelsSet).sort((a, b) => new Date(a) - new Date(b)); // Sort due dates chronologically
        const yLabels = Array.from(yLabelsSet); // No need to sort task titles/groups unless desired

        // Map dataMatrix to correct indices
        const matrixData = dataMatrix.map(data => ({
            x: xLabels.indexOf(data.xLabel),
            y: yLabels.indexOf(data.yLabel),
            v: data.v,
            task: data.task,
            groupColor: data.groupColor,
        }));

        // Compute maximum estimated load for alpha scaling
        const maxLoad = Math.max(...matrixData.map(d => d.v));

        // Dynamically calculate canvas size
        const cellSize = 40; // Fixed size for each cell (adjust as needed)
        const canvasWidth = cellSize * xLabels.length + 200; // Add extra padding for labels
        const canvasHeight = cellSize * yLabels.length + 100; // Add extra padding for labels

        // Set canvas size
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;

        // Helper function to convert hex color to RGB
        function hexToRgb(hex) {
            hex = hex.replace('#', '');
            const bigint = parseInt(hex, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return [r, g, b];
        }

        // Destroy previous chart if it exists
        if (heatmapChart) heatmapChart.destroy();

        // Create the heatmap chart
        heatmapChart = new Chart(ctx, {
            type: 'matrix',
            data: {
                datasets: [{
                    label: 'Task Heatmap',
                    data: matrixData,
                    backgroundColor(context) {
                        const dataPoint = context.dataset.data[context.dataIndex];
                        const [r, g, b] = hexToRgb(dataPoint.groupColor);
                        const minAlpha = 0.2; // Minimum alpha to ensure visibility
                        const alpha = minAlpha + (dataPoint.v / maxLoad) * (1 - minAlpha);
                        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                    },
                    borderWidth: 1,
                    borderColor: 'white',
                    width: cellSize - 5, // Adjust cell width
                    height: cellSize - 5, // Adjust cell height
                }]
            },
            options: {
                maintainAspectRatio: false, // Allow chart to resize based on container
                scales: {
                    x: {
                        type: 'category',
                        labels: xLabels,
                        title: {
                            display: true,
                            text: 'Due Dates',
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 90,
                            minRotation: 45,
                        },
                    },
                    y: {
                        type: 'category',
                        labels: yLabels,
                        title: {
                            display: true,
                            text: mode === 'tasks' ? 'Tasks' : 'Groups',
                        },
                        ticks: {
                            autoSkip: false,
                            padding: 10,
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            generateLabels(chart) {
                                return Object.keys(groupColorMap).map(groupName => ({
                                    text: groupName,
                                    fillStyle: groupColorMap[groupName],
                                    strokeStyle: groupColorMap[groupName],
                                    hidden: false,
                                    lineWidth: 0,
                                }));
                            },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            title: () => '',
                            label: context => {
                                const dataPoint = context.dataset.data[context.dataIndex];
                                const taskName = yLabels[dataPoint.y];
                                const dueDate = xLabels[dataPoint.x];
                                const groupName = dataPoint.task.group_name || 'Personal';
                                return `${taskName} (Due: ${dueDate}, Group: ${groupName}, Load: ${dataPoint.v})`;
                            }
                        }
                    }
                }
            }
        });
    }

    function showRadarChartView(mode) {
        const tasks = <?php echo json_encode($tasks); ?>;
        const ctx = document.getElementById("radarChart").getContext("2d");

        // Destroy previous chart if it exists
        if (radarChart) radarChart.destroy();

        // Prepare data for radar chart by listing individual tasks
        // To prevent cluttering, we can limit the number of tasks displayed
        const maxTasksToDisplay = 12; // Adjust as needed based on readability
        let taskData;

        if (mode === 'groups') {
            // Filter tasks by groups and flatten them into a single array
            taskData = tasks.filter(task => task.group_name && task.group_name !== 'Personal');
        } else {
            // Personal tasks or all tasks
            taskData = tasks;
        }

        // Sort tasks by estimated load in descending order and take the top tasks
        taskData = taskData.sort((a, b) => b.estimated_load - a.estimated_load).slice(0, maxTasksToDisplay);

        // Prepare labels and data
        const labels = taskData.map(task => task.title || 'Unnamed Task');
        const dataValues = taskData.map(task => parseFloat(task.estimated_load));

        // Create the radar chart
        radarChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Estimated Load per Task',
                    data: dataValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)', // Light teal
                    borderColor: 'rgba(75, 192, 192, 1)', // Darker teal
                    borderWidth: 1,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(75, 192, 192, 1)',
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        pointLabels: {
                            font: {
                                size: 14,
                            },
                        },
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const task = taskData[context.dataIndex];
                                return `${task.title}: Estimated Load ${task.estimated_load}`;
                            }
                        }
                    },
                    legend: {
                        display: false,
                    }
                }
            }
        });
    }


    function showScatterChartView(mode) {
        const tasks = <?php echo json_encode($tasks); ?>;
        const ctx = document.getElementById("scatterChart").getContext("2d");

        // Destroy previous chart if it exists
        if (scatterChart) scatterChart.destroy();

        // Create a color map for groups
        const colorPalette = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#E7E9ED', '#B39DDB', '#9CCC65', '#FF7043'
        ];
        const overdueColor = '#FF0000'; // Color for overdue tasks
        const groupColorMap = {};
        let colorIndex = 0;

        // Assign unique colors to groups
        tasks.forEach(task => {
            const groupName = task.group_name || 'Personal';
            if (!groupColorMap[groupName]) {
                groupColorMap[groupName] = colorPalette[colorIndex % colorPalette.length];
                colorIndex++;
            }
        });

        // Get the current date
        const now = new Date();

        // Prepare data for scatter chart
        const scatterData = tasks.map(task => {
            const dueDate = new Date(task.due_date);
            const isOverdue = dueDate < now;

            return {
                x: mode === 'tasks' ? dueDate : task.group_name,
                y: task.estimated_load,
                r: task.estimated_load * 2, // Adjust size as needed
                groupName: task.group_name || 'Personal',
                backgroundColor: isOverdue ? overdueColor : groupColorMap[task.group_name || 'Personal'],
                isOverdue: isOverdue, // Include overdue flag for tooltips
            };
        });

        // Prepare legend items for groups and overdue tasks
        const legendItems = Object.entries(groupColorMap).map(([groupName, color]) => ({
            text: groupName,
            fillStyle: color,
        }));
        legendItems.push({
            text: 'Overdue Tasks',
            fillStyle: overdueColor,
        });

        // Create the scatter chart
        scatterChart = new Chart(ctx, {
            type: 'bubble',
            data: {
                datasets: [{
                    label: mode === 'tasks' ? 'Tasks' : 'Groups',
                    data: scatterData,
                    backgroundColor: scatterData.map(point => point.backgroundColor),
                }]
            },
            options: {
                scales: {
                    x: {
                        type: mode === 'tasks' ? 'time' : 'category',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM d, yyyy',
                        },
                        title: {
                            display: true,
                            text: mode === 'tasks' ? 'Due Date' : 'Group Name',
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Estimated Load',
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            generateLabels: function() {
                                return legendItems;
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const dataPoint = context.raw;
                                const overdueText = dataPoint.isOverdue ? ' (Overdue)' : '';
                                return `${dataPoint.groupName}: Load ${dataPoint.y}${overdueText}`;
                            },
                        },
                    },
                },
            },
        });
    }

    function updateProgressBar(loadPercentage) {
        const loadProgressBar = document.querySelector("#loadProgressBar");

        if (!loadProgressBar) {
            console.error("Progress bar element not found!");
            return;
        }

        // Set the progress bar width and aria attributes
        loadProgressBar.style.width = `${loadPercentage}%`;
        loadProgressBar.setAttribute("aria-valuenow", loadPercentage);

        // Change the progress bar's background color based on the percentage
        if (loadPercentage >= 80) {
            loadProgressBar.style.backgroundColor = "darkred";
        } else if (loadPercentage >= 50) {
            loadProgressBar.style.backgroundColor = "orange";
        } else {
            loadProgressBar.style.backgroundColor = "lightgreen";
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const loadPercentage = <?php echo $load_percentage; ?>;
        updateProgressBar(loadPercentage);
        renderComparisonChart(userLoads);
        // Initialize default view and mode
        showView('heatmapView');
        toggleTaskGroup('tasks');

        // Event listeners for switching between views
        document.getElementById('heatmapViewButton').addEventListener('click', () => showView('heatmapView'));
        document.getElementById('radarChartViewButton').addEventListener('click', () => showView('radarChartView'));
        document.getElementById('scatterChartViewButton').addEventListener('click', () => showView('scatterChartView'));

        // Event listeners for toggling tasks and groups
        document.getElementById('taskListButton').addEventListener('click', () => {
            toggleTaskGroup('tasks');
        });

        document.getElementById('groupListButton').addEventListener('click', () => {
            toggleTaskGroup('groups');
        });
    });

    function showTaskDetails(task) {
        document.getElementById('taskTitle').textContent = task.title;
        document.getElementById('taskDescription').textContent = task.description;
        document.getElementById('taskLocation').textContent = task.location;
        document.getElementById('taskDueDate').textContent = new Date(task.due_date).toLocaleString();
        document.getElementById('taskEstimatedLoad').textContent = task.estimated_load;
        new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
    }


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
</script>

<style>
        .task-item:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .hidden {
            display: none !important;
        }

        .visible {
            display: block !important;
        }

    </style>