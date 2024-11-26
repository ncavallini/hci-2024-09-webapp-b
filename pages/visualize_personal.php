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

    // Fetch tasks with group_id = 0, merging all_tasks with tasks to get the is_completed column
    $sql = "
        SELECT 
            at.task_id, 
            at.title, 
            at.location, 
            at.description, 
            at.due_date, 
            at.estimated_load, 
            t.is_completed 
        FROM 
            all_tasks at
        LEFT JOIN 
            tasks t ON at.task_id = t.task_id
        WHERE 
            at.group_id = 0 AND at.user_id = ?
            AND (t.is_completed = 0 OR t.is_completed IS NULL)
        ORDER BY 
            t.is_completed ASC, at.due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total mental load for these tasks
    $total_load = array_sum(array_map(function ($task) {
        return !$task['is_completed'] ? $task['estimated_load'] : 0;
    }, $tasks));

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Tasks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Personal Tasks</h1>

        <!-- Mental Load Bar -->
        <div class="mb-4">
            <h5>Mental Load from Personal tasks</h5>
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
            <p class="mt-2">Personal Tasks' Load: <?php echo $total_load; ?> / Maximum Load: <?php echo $max_load; ?></p>
        </div>

        <!-- Buttons for List and Pie Chart Views -->
        <div class="d-flex justify-content-between mb-3">
            <button id="listViewButton" class="btn btn-primary" onclick="showView('listView')">List View</button>
            <button id="pieChartViewButton" class="btn btn-secondary" onclick="showView('pieChartView')">Pie Chart View</button>
        </div>

        <!-- List View -->
        <div id="listView" class="d-flex flex-column gap-3">
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <div 
                        class="task-item d-flex justify-content-between align-items-center p-3 border rounded"
                        onclick="showTaskDetails(<?php echo htmlspecialchars(json_encode($task), ENT_QUOTES); ?>)">

                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($task['description']); ?></p>
                            <small class="text-muted">
                                <strong>Due: </strong> <?php echo (new DateTimeImmutable($task['due_date']))->format('Y-m-d H:i:s'); ?>
                            </small>
                        </div>
                        <div>
                            <span class="badge badge-primary badge-pill">Load: <?php echo htmlspecialchars($task['estimated_load']); ?></span>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lead text-muted">No personal tasks found.</p>
            <?php endif; ?>
        </div>

        <!-- Pie Chart View -->
        <div id="pieChartView" style="display: none;">
            <canvas id="pieChart" width="400" height="400"></canvas>
        </div>
    </div>

    <!-- Task Details Modal -->
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
                    <p><strong>Location:</strong> <span id="taskLocation"></span></p>
                    <p><strong>Due Date:</strong> <span id="taskDueDate"></span></p>
                    <p><strong>Estimated Load:</strong> <span id="taskEstimatedLoad"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        const tasks = <?php echo json_encode($tasks); ?>;
        let pieChart;

        function showView(viewId) {
            const listView = document.getElementById('listView');
            const pieChartView = document.getElementById('pieChartView');
            const listViewButton = document.getElementById('listViewButton');
            const pieChartViewButton = document.getElementById('pieChartViewButton');

            // Update the display of views
            listView.style.display = viewId === 'listView' ? 'block' : 'none';
            pieChartView.style.display = viewId === 'pieChartView' ? 'block' : 'none';

            // Update button styles
            if (viewId === 'listView') {
                listViewButton.classList.add('btn-primary');
                listViewButton.classList.remove('btn-secondary');
                pieChartViewButton.classList.add('btn-secondary');
                pieChartViewButton.classList.remove('btn-primary');
            } else if (viewId === 'pieChartView') {
                pieChartViewButton.classList.add('btn-primary');
                pieChartViewButton.classList.remove('btn-secondary');
                listViewButton.classList.add('btn-secondary');
                listViewButton.classList.remove('btn-primary');
            }

            // Show the pie chart when switching to pieChartView
            if (viewId === 'pieChartView') showPieChart();
        }


        function showTaskDetails(task) {
            document.getElementById('taskTitle').textContent = task.title;
            document.getElementById('taskDescription').textContent = task.description;
            document.getElementById('taskLocation').textContent = task.location;
            document.getElementById('taskDueDate').textContent = new Date(task.due_date).toLocaleString();
            document.getElementById('taskEstimatedLoad').textContent = task.estimated_load;
            new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
        }

        function showPieChart() {
            const ctx = document.getElementById('pieChart').getContext('2d');
            const pieData = {
                labels: tasks.map(task => task.title),
                datasets: [{
                    data: tasks.map(task => task.estimated_load),
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
                }]
            };

            if (pieChart) pieChart.destroy();
            pieChart = new Chart(ctx, {
                type: 'pie',
                data: pieData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
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


        document.addEventListener('DOMContentLoaded', () => {
            showView('listView');

            showView('listView');

            // Update the progress bar with the current load percentage
            const loadPercentage = <?php echo round($load_percentage); ?>;
            updateProgressBar(loadPercentage);
        });
    </script>

    <style>
        .task-item:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
    </style>
</body>
</html>
