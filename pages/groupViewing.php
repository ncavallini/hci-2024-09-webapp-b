<?php
require_once __DIR__ . "/../utils/init.php";

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

try {
    // Add 'max_load' column if it doesn't exist
    $dbconnection->exec("ALTER TABLE users ADD COLUMN max_load INT DEFAULT 0;");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") === false) {
        die("Error updating the database: " . $e->getMessage());
    }
}

// Get the current user's ID
$user_id = Auth::user()['user_id'];

// Fetch all tasks for the current user across all groups
$sql = "
    SELECT 
        gt.title, 
        gt.description, 
        gt.due_date, 
        gt.estimated_load, 
        g.name AS group_name,
        g.group_id
    FROM 
        group_tasks gt
    JOIN 
        groups g ON gt.group_id = g.group_id
    WHERE 
        gt.user_id = ?
    ORDER BY 
        gt.estimated_load DESC, 
        gt.due_date ASC";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calculate the total mental load
$total_load = array_sum(array_column($tasks, 'estimated_load'));

// Fetch the maximum mental load ever recorded (store in database or session)
$sql = "SELECT max_load FROM users WHERE user_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Update the maximum if the current total load exceeds it
$max_load = $row['max_load'] ?? 0; // Default to 0 if no record exists
if ($total_load > $max_load) {
    $max_load = $total_load;

    // Update the maximum in the database
    $sql = "UPDATE users SET max_load = ? WHERE user_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$max_load, $user_id]);
}

// Calculate the percentage of the current load relative to the maximum
$load_percentage = ($max_load > 0) ? ($total_load / $max_load) * 100 : 0;
?>

<?php
try {
    $user_id = Auth::user()['user_id'];
    $dbconnection = DBConnection::get_connection();

    // Fetch personal tasks
    $sql = "SELECT title, description, due_date, estimated_load, 'Personal' AS group_name, 0 as group_id, is_completed 
            FROM tasks 
            WHERE user_id = ? 
            ORDER BY is_completed ASC, estimated_load DESC, due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $personalTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch group tasks
    $sql = "SELECT 
                gt.title, 
                gt.description, 
                gt.due_date, 
                gt.estimated_load, 
                g.name AS group_name, 
                g.group_id,
                gt.is_completed 
            FROM 
                group_tasks gt
            JOIN 
                groups g ON gt.group_id = g.group_id
            WHERE 
                gt.user_id = ?
            ORDER BY 
                gt.is_completed ASC, gt.estimated_load DESC, gt.due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $groupTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge tasks
    $tasks = array_merge($personalTasks, $groupTasks);

    // Calculate total mental load
    $total_load = array_sum(array_map(function ($task) {
        return !$task['is_completed'] ? $task['estimated_load'] : 0;
    }, $tasks));

    // Fetch and update maximum load
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
    $tasks = [];
    $error = $e->getMessage();
}
?>

    <div class="container mt-5">
        <h1 class="mb-4">All Tasks</h1>

        <div class="mb-4 position-relative">
            <h5>Your Mental Load
                <a class="nav-link" href="index.php?page=pastLoad"> 
                    <button 
                        class="btn btn-sm btn-info position-absolute top-0 end-0" >
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

        <!-- Buttons Row -->
        <div class = "d-flex flex-row align-items-center">
            <div class="d-flex flex-column gap-3 mb-3">
                <!-- Static Buttons Row -->
                <div class="d-flex flex-wrap gap-2">
                    <button id="taskListButton" class="btn btn-secondary" onclick="window.location.href='index.php?page=visualize'">Tasks</button>
                    <button id="groupButton" class="btn btn-primary">Groups</button>
                    <button id="heatmapViewButton" class="btn btn-primary" onclick="showView('heatmapView')">Heatmap View</button>
                    <button id="radarChartViewButton" class="btn btn-secondary" onclick="showView('radarChartView')">Radar Chart View</button>
                    <button id="scatterChartViewButton" class="btn btn-secondary" onclick="showView('scatterChartView')">Scatter Chart View</button>
                </div>

                <!-- Dynamically Generated Group Buttons -->
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="navigateToPersonalGroup()">View Personal</button>
                    <?php foreach ($groupTasks as $groupTask): ?>
                        <?php if (!isset($groupsAdded[$groupTask['group_name']])): ?>
                            <?php $groupsAdded[$groupTask['group_name']] = true; ?>
                            <button class="btn btn-outline-primary btn-sm" onclick="navigateToGroup(<?php echo $groupTask['group_id']; ?>)">
                                View <?php echo htmlspecialchars($groupTask['group_name']); ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <!-- Add Personal group button -->
                </div>
            </div>

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
            </div>
        </div>
    </div>
<script>
    let heatmapChart;
    let radarChart;
    let scatterChart;

    function navigateToGroup(groupId) {
        if (groupId) {
            window.location.href = `index.php?page=groupview&id=${groupId}`;
        }
    }

    function navigateToPersonalGroup() {
        window.location.href = `index.php?page=visualize_personal`;
    }

    function showView(viewId) {
        const heatmapView = document.getElementById('heatmapView');
        const radarChartView = document.getElementById('radarChartView');
        const scatterChartView = document.getElementById('scatterChartView');
        const heatmapViewButton = document.getElementById('heatmapViewButton');
        const radarChartViewButton = document.getElementById('radarChartViewButton');
        const scatterChartViewButton = document.getElementById('scatterChartViewButton');

        // Reset views
        heatmapView.style.display = 'none';
        radarChartView.style.display = 'none';
        scatterChartView.style.display = 'none';

        // Update button styles
        heatmapViewButton.classList.toggle('btn-primary', viewId === 'heatmapView');
        heatmapViewButton.classList.toggle('btn-secondary', viewId !== 'heatmapView');

        radarChartViewButton.classList.toggle('btn-primary', viewId === 'radarChartView');
        radarChartViewButton.classList.toggle('btn-secondary', viewId !== 'radarChartView');

        scatterChartViewButton.classList.toggle('btn-primary', viewId === 'scatterChartView');
        scatterChartViewButton.classList.toggle('btn-secondary', viewId !== 'scatterChartView');

        // Show the selected view
        if (viewId === 'heatmapView') {
            heatmapView.style.display = 'block';
            showHeatmapView();
        } else if (viewId === 'radarChartView') {
            radarChartView.style.display = 'block';
            showRadarChartView();
        } else if (viewId === 'scatterChartView') {
            scatterChartView.style.display = 'block';
            showScatterChartView();
        }
    }

    function showHeatmapView() {
    const tasks = <?php echo json_encode($tasks); ?>;
    const canvas = document.getElementById("heatmapChart");
    const ctx = canvas.getContext("2d");

    // Filter tasks to exclude completed ones
    const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

    // Prepare data for heatmap
    const groupDataMap = {};

    activeTasks.forEach(task => {
        const groupName = task.group_name || 'Personal';
        if (!groupDataMap[groupName]) {
            groupDataMap[groupName] = { total_load: 0, task_count: 0 };
        }
        groupDataMap[groupName].total_load += parseFloat(task.estimated_load);
        groupDataMap[groupName].task_count += 1;
    });

    // Assign colors to groups dynamically
    const colorPalette = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#E7E9ED', '#B39DDB', '#9CCC65', '#FF7043',
    ];
    const groupColorMap = {};
    let colorIndex = 0;

    Object.keys(groupDataMap).forEach(groupName => {
        groupColorMap[groupName] = colorPalette[colorIndex % colorPalette.length];
        colorIndex++;
    });

    // Prepare axes labels
    const taskCounts = Array.from(new Set(Object.values(groupDataMap).map(g => g.task_count))).sort((a, b) => a - b);
    const loadCounts = Array.from(new Set(Object.values(groupDataMap).map(g => g.total_load))).sort((a, b) => b - a); // Descending for higher loads at top

    // Map axes labels
    const xLabels = taskCounts.map(count => `${count} Tasks`);
    const yLabels = loadCounts.map(load => `Load ${load}`);

    // Prepare data matrix
    const dataMatrix = [];
    Object.entries(groupDataMap).forEach(([groupName, groupData]) => {
        const xIndex = taskCounts.indexOf(groupData.task_count);
        const yIndex = loadCounts.indexOf(groupData.total_load); // Correct load mapping

        dataMatrix.push({
            x: xIndex,
            y: yIndex,
            v: 1, // Each group contributes one data point
            groupName: groupName,
            taskCount: groupData.task_count,
            totalLoad: groupData.total_load,
            backgroundColor: groupColorMap[groupName],
        });
    });

    // Debugging
    console.log('Task Counts:', taskCounts);
    console.log('Load Counts:', loadCounts);
    console.log('Data Matrix:', dataMatrix);

    // Set up the canvas
    const cellSize = 50;
    const canvasWidth = cellSize * xLabels.length + 200;
    const canvasHeight = cellSize * yLabels.length + 100;
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    // Destroy previous chart if it exists
    if (heatmapChart) heatmapChart.destroy();

    // Create the heatmap chart
    heatmapChart = new Chart(ctx, {
        type: 'matrix',
        data: {
            datasets: [{
                label: 'Load Count vs Amount of Tasks',
                data: dataMatrix,
                backgroundColor(context) {
                    return context.raw.backgroundColor;
                },
                borderWidth: 1,
                borderColor: 'white',
                width: cellSize - 5,
                height: cellSize - 5,
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'category',
                    labels: xLabels,
                    title: {
                        display: true,
                        text: 'Amount of Tasks',
                    },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0,
                    },
                },
                y: {
                    type: 'category',
                    labels: yLabels,
                    title: {
                        display: true,
                        text: 'Load Count',
                    },
                    ticks: {
                        autoSkip: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        generateLabels: function(chart) {
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
                        label: context => {
                            const dataPoint = context.dataset.data[context.dataIndex];
                            return `${dataPoint.groupName}: ${dataPoint.taskCount} tasks, Load ${dataPoint.totalLoad}`;
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

    // Prepare data for radar chart by grouping tasks
    const groupDataMap = {};

    tasks.forEach(task => {
        const groupName = task.group_name || 'Personal';
        if (!groupDataMap[groupName]) {
            groupDataMap[groupName] = { totalLoad: 0 };
        }
        groupDataMap[groupName].totalLoad += parseFloat(task.estimated_load);
    });

    // Prepare labels and data
    const labels = Object.keys(groupDataMap); // Group names
    const dataValues = Object.values(groupDataMap).map(group => group.totalLoad); // Total load per group

    // Sort groups by total load in descending order for better readability
    const sortedData = labels
        .map((label, index) => ({ label, load: dataValues[index] }))
        .sort((a, b) => b.load - a.load)
        .slice(0, 12); // Limit to top 12 groups for readability

    const sortedLabels = sortedData.map(item => item.label);
    const sortedDataValues = sortedData.map(item => item.load);

    // Create the radar chart
    radarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: sortedLabels, // Group names as labels
            datasets: [{
                label: 'Total Load per Group',
                data: sortedDataValues, // Total loads as data
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
                            const group = sortedData[context.dataIndex];
                            return `${group.label}: Total Load ${group.load}`;
                        }
                    }
                },
                legend: {
                    display: true, // Enable legend for group visualization
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

    // Aggregate data by groups
    const groupDataMap = {};

    tasks.forEach(task => {
        const groupName = task.group_name || 'Personal';
        if (!groupDataMap[groupName]) {
            groupDataMap[groupName] = { totalLoad: 0, taskCount: 0 };
        }
        groupDataMap[groupName].totalLoad += parseFloat(task.estimated_load);
        groupDataMap[groupName].taskCount += 1;
    });

    // Assign unique colors to groups
    const colorPalette = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#E7E9ED', '#B39DDB', '#9CCC65', '#FF7043'
    ];
    const groupColorMap = {};
    let colorIndex = 0;

    Object.keys(groupDataMap).forEach(groupName => {
        groupColorMap[groupName] = colorPalette[colorIndex % colorPalette.length];
        colorIndex++;
    });

    // Prepare data for scatter chart
    const scatterData = Object.entries(groupDataMap).map(([groupName, groupData]) => ({
        x: groupName, // Group Name
        y: groupData.totalLoad, // Total Load
        r: groupData.taskCount * 5, // Bubble size based on number of tasks (adjust multiplier as needed)
        groupName: groupName,
        taskCount: groupData.taskCount,
        totalLoad: groupData.totalLoad,
        backgroundColor: groupColorMap[groupName],
    }));

    // Create legend items for groups
    const legendItems = Object.entries(groupColorMap).map(([groupName, color]) => ({
        text: groupName,
        fillStyle: color,
    }));

    // Create the scatter chart
    scatterChart = new Chart(ctx, {
        type: 'bubble',
        data: {
            datasets: [{
                label: 'Groups',
                data: scatterData,
                backgroundColor: scatterData.map(point => point.backgroundColor),
            }]
        },
        options: {
            scales: {
                x: {
                    type: 'category',
                    labels: Object.keys(groupDataMap), // Group Names
                    title: {
                        display: true,
                        text: 'Groups',
                    },
                },
                y: {
                    title: {
                        display: true,
                        text: 'Total Estimated Load',
                    },
                    beginAtZero: true,
                },
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        generateLabels: function(chart) {
                            return legendItems;
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dataPoint = context.raw;
                            return `${dataPoint.groupName}: ${dataPoint.taskCount} tasks, Total Load ${dataPoint.totalLoad}`;
                        },
                    },
                },
            },
        },
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
        // Default to Heatmap View
        showView('heatmapView');

        // Update the progress bar
        const loadPercentage = <?php echo round($load_percentage); ?>;
        updateProgressBar(loadPercentage);

        // Event listeners for switching between views
        document.getElementById('heatmapViewButton').addEventListener('click', () => showView('heatmapView'));
        document.getElementById('radarChartViewButton').addEventListener('click', () => showView('radarChartView'));
        document.getElementById('scatterChartViewButton').addEventListener('click', () => showView('scatterChartView'));
    });
</script>

<!-- Include necessary Chart.js libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.1.0/dist/chartjs-chart-matrix.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<style>
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

    #heatmapView {
        overflow: auto;
        max-height: 100%; /* Adjust height as necessary */
    }

    #heatmapChart {
        width: 1000px; /* Adjust width as necessary */
        height: 1000px; /* Adjust height as necessary */
    }
</style>
