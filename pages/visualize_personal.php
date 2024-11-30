<?php
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
            t.is_completed ASC, estimated_load DESC,at.due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);

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

    // Calculate total mental load for non-overdue tasks
    $total_load = array_sum(array_column($nonOverdueTasks, 'estimated_load'));

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



    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Tasks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
   

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
            <p class="mt-2">Personal Tasks Load: <?php echo $total_load; ?> / Maximum Load: <?php echo $max_load; ?></p>
        </div>

        <!-- Buttons for List and Pie Chart Views -->
        <div class="d-flex justify-content-between mb-3">
            <button id="listViewButton" class="btn btn-primary" onclick="showView('listView')">List View</button>
            <button id="pieChartViewButton" class="btn btn-secondary" onclick="showView('pieChartView')">Pie Chart View</button>
            <button id="bubbleChartViewButton" class="btn btn-secondary" onclick="showView('bubbleChartView')">Bubble Chart View</button>
        </div>

        <div id="listView" class="d-flex flex-column gap-3 overflow-auto" style="max-height: 80vh;">
            <h3>Non-Overdue Tasks</h3>
            <?php if (!empty($nonOverdueTasks)): ?>
                <?php foreach ($nonOverdueTasks as $task): ?>
                    <div class="task-item d-flex justify-content-between align-items-center p-3 border rounded flex-wrap" 
                        style="cursor: pointer;" 
                        onclick="showTaskDetails(<?php echo htmlspecialchars(json_encode($task), ENT_QUOTES); ?>)">
                        <div class="flex-grow-1 me-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($task['description']); ?></p>
                            <small class="text-muted">
                                <strong>Due: </strong> <?php echo (new DateTime($task['due_date']))->format('Y-m-d H:i:s'); ?>
                            </small>
                        </div>
                        <div>
                            <span class="badge bg-primary">Load: <?php echo htmlspecialchars($task['estimated_load']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lead text-muted">No non-overdue tasks found.</p>
            <?php endif; ?>

            <h3>Overdue Tasks</h3>
            <?php if (!empty($overdueTasks)): ?>
                <?php foreach ($overdueTasks as $task): ?>
                    <div class="task-item d-flex justify-content-between align-items-center p-3 border rounded flex-wrap" 
                        style="cursor: pointer; background-color: lightcoral;" 
                        onclick="showTaskDetails(<?php echo htmlspecialchars(json_encode($task), ENT_QUOTES); ?>)">
                        <div class="flex-grow-1 me-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($task['description']); ?></p>
                            <small class="text-muted">
                                <strong>Due: </strong> <?php echo (new DateTime($task['due_date']))->format('Y-m-d H:i:s'); ?>
                            </small>
                        </div>
                        <div>
                            <span class="badge bg-primary">Load: <?php echo htmlspecialchars($task['estimated_load']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lead text-muted">No overdue tasks found.</p>
            <?php endif; ?>
        </div>




        <!-- Pie Chart View -->
        <div id="pieChartView" class="hidden" style="display: none;">
            <canvas id="pieChart" width="400" height="400"></canvas>
        </div>

        <div id="bubbleChartView" class="hidden" style="display: none">
            <canvas id="bubbleChart" width="400" height="400"></canvas>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/date-fns"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<!-- Include jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<!-- Bootstrap JS Bundle includes Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        const tasks = <?php echo json_encode($tasks); ?>;
        let pieChart;

        function showView(viewId) {
    const listView = document.getElementById('listView');
    const pieChartView = document.getElementById('pieChartView');
    const bubbleChartView = document.getElementById('bubbleChartView');
    const listViewButton = document.getElementById('listViewButton');
    const pieChartViewButton = document.getElementById('pieChartViewButton');
    const bubbleChartViewButton = document.getElementById('bubbleChartViewButton');

    listView.classList.add('hidden');
    pieChartView.classList.add('hidden');
    bubbleChartView.classList.add('hidden');

    listViewButton.classList.remove('btn-primary');
    listViewButton.classList.add('btn-secondary');
    pieChartViewButton.classList.remove('btn-primary');
    pieChartViewButton.classList.add('btn-secondary');
    bubbleChartViewButton.classList.remove('btn-primary');
    bubbleChartViewButton.classList.add('btn-secondary');

    if (viewId === 'listView') {
        bubbleChartView.classList.remove('visible');
        bubbleChartView.classList.add('hidden');
        listView.classList.remove('hidden');
        pieChartView.classList.remove('visible');
        pieChartView.classList.add('hidden');
        listViewButton.classList.add('btn-primary');
        listViewButton.classList.remove('btn-secondary');
    } else if (viewId === 'pieChartView') {
        bubbleChartView.classList.remove('visible');
        bubbleChartView.classList.add('hidden');
        pieChartView.classList.remove('hidden');
        pieChartViewButton.classList.add('btn-primary');
        pieChartView.classList.add('visible');
        pieChartViewButton.classList.remove('btn-secondary');
        showPieChart();
    } else if (viewId === 'bubbleChartView') {
        pieChartView.classList.remove('visible');
        pieChartView.classList.add('hidden');
        bubbleChartView.classList.remove('hidden');
        bubbleChartView.classList.add('visible');
        bubbleChartViewButton.classList.add('btn-primary');
        bubbleChartViewButton.classList.remove('btn-secondary');
        showBubbleChart();
    }
}



        function showBubbleChart() {
    const ctx = document.getElementById('bubbleChart').getContext('2d');

    // Map the tasks data to the bubble chart data format
    const bubbleDataPoints = tasks.map(task => {
        // Convert due_date to a Date object or a string in ISO format
        const dueDate = new Date(task.due_date);
        if (isNaN(dueDate)) {
            console.error(`Invalid date for task "${task.title}": ${task.due_date}`);
            return null; // Exclude invalid data points
        }

        return {
            x: dueDate, // Use the Date object directly
            y: Number(task.estimated_load),
            r: Number(task.estimated_load) * 2, // Adjust as needed
            taskTitle: task.title,
        };
    }).filter(dataPoint => dataPoint !== null); // Remove any null entries due to invalid dates

    // Log the data to verify the structure
    console.log('Bubble Data Points:', bubbleDataPoints);

    const bubbleData = {
        datasets: [{
            label: 'Personal Tasks',
            data: bubbleDataPoints,
            backgroundColor: '#36A2EB'
        }]
    };

    // Destroy previous chart instance if it exists
    if (bubbleChart && typeof bubbleChart.destroy === 'function') {
        bubbleChart.destroy();
    }

    try {
        bubbleChart = new Chart(ctx, {
            type: 'bubble',
            data: bubbleData,
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM d, yyyy',
                        },
                        title: {
                            display: true,
                            text: 'Due Date',
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
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const dataPoint = context.raw;
                                return `${dataPoint.taskTitle}: Load ${dataPoint.y}`;
                            },
                        },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Error creating bubbleChart:', error);
    }
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
        .hidden {
            display: none !important;
        }

        .visible {
            display: block !important;
        }

    </style>

