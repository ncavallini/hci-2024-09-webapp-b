<?php
try {
    $user_id = Auth::user()['user_id'];
    $dbconnection = DBConnection::get_connection();

    // Fetch tasks (personal and group)
    $sql = "
        SELECT t.title, t.description, t.due_date, t.estimated_load, 'Personal' AS group_name, 0 as group_id, t.is_completed 
        FROM tasks t
        WHERE t.user_id = ? and t.is_completed = 0
        UNION ALL
        SELECT gt.title, gt.description, gt.due_date, gt.estimated_load, g.name AS group_name, g.group_id, gt.is_completed 
        FROM group_tasks gt
        JOIN groups g ON gt.group_id = g.group_id
        WHERE gt.user_id = ? and gt.is_completed = 0
        ORDER BY is_completed ASC, estimated_load DESC, due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total mental load
    $total_load = array_sum(array_column($tasks, 'estimated_load'));

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
    <h1 class="mb-4 text-center">All Tasks</h1>
    <!-- Mental Load Bar -->
    <div class="row mb-4 position-relative">
        <div class="col-12">
            <h5>Your Mental Load
                <a class="nav-link d-inline" href="index.php?page=pastLoad">
                    <button class="btn btn-sm btn-info float-end">
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
    </div>
    <!-- Mode Buttons (Tasks or Groups) -->
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-center gap-2">
            <button id="taskListButton" class="btn btn-primary btn-uniform" onclick="toggleTaskGroup('tasks')">Tasks</button>
            <button id="groupListButton" class="btn btn-secondary btn-uniform" onclick="toggleTaskGroup('groups')">Groups</button>
        </div>
    </div>

    <!-- Chart Container -->
    <!-- Heatmap Chart -->
<div class="card mb-4 shadow-sm rounded" id="heatmapChartCard">
    <div class="card-header">
        <h5 class="card-title mb-0">Heatmap</h5>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="heatmapChart"></canvas>
        </div>
    </div>
</div>

<!-- Radar Chart -->
<div class="card mb-4 shadow-sm rounded" id="radarChartCard">
    <div class="card-header">
        <h5 class="card-title mb-0">Radar Chart</h5>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="radarChart"></canvas>
        </div>
    </div>
</div>

<!-- Scatter Chart -->
    <div class="card mb-4 shadow-sm rounded" id="scatterChartCard">
        <div class="card-header">
            <h5 class="card-title mb-0">Scatter Chart</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="scatterChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart.js Matrix Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.1.0/dist/chartjs-chart-matrix.min.js"></script>

<!-- Moment.js Library -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1"></script>

<!-- Chart.js Moment Adapter -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0/dist/chartjs-adapter-moment.min.js"></script>

<!-- Your custom script should come after the libraries -->
<script src="path/to/your/custom/script.js"></script>
<script>
    let currentMode = 'tasks'; // Default mode

    let heatmapChartInstance;
    let radarChartInstance;
    let scatterChartInstance;

    function toggleTaskGroup(mode) {
    currentMode = mode;

    // Update button styles for mode buttons
    document.getElementById("taskListButton").classList.toggle("btn-primary", mode === "tasks");
    document.getElementById("taskListButton").classList.toggle("btn-secondary", mode !== "tasks");
    document.getElementById("groupListButton").classList.toggle("btn-primary", mode === "groups");
    document.getElementById("groupListButton").classList.toggle("btn-secondary", mode !== "groups");

    // Update all charts
    updateHeatmapChart();
    updateRadarChart();
    updateScatterChart();
}

function updateHeatmapChart() {
    console.log("heat");
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("heatmapChart").getContext("2d");

    // Destroy previous chart instance if it exists
    if (heatmapChartInstance) {
        heatmapChartInstance.destroy();
        heatmapChartInstance = null;
    }

    // Filter tasks to exclude completed ones
    const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

    // Prepare data for heatmap
    const dataMatrix = [];
    const xLabelsSet = new Set(); // Due Dates
    const yLabelsSet = new Set(); // Task Titles or Group Names

    activeTasks.forEach(task => {
        const dueDate = new Date(task.due_date).toLocaleDateString();
        const yValue = currentMode === 'tasks' ? task.title : task.group_name || 'Personal';

        xLabelsSet.add(dueDate);
        yLabelsSet.add(yValue);

        dataMatrix.push({
            x: dueDate,
            y: yValue,
            v: task.estimated_load,
        });
    });

    const xLabels = Array.from(xLabelsSet).sort((a, b) => new Date(a) - new Date(b));
    const yLabels = Array.from(yLabelsSet);

    // Map dataMatrix to correct indices with error handling
    const data = dataMatrix.map(item => {
        const xIndex = xLabels.indexOf(item.x);
        const yIndex = yLabels.indexOf(item.y);
        if (xIndex === -1 || yIndex === -1) {
            console.warn(`Label not found for x: ${item.x}, y: ${item.y}`);
        }
        return {
            x: xIndex !== -1 ? xIndex : 0, // Assign to first index if not found
            y: yIndex !== -1 ? yIndex : 0, // Assign to first index if not found
            v: typeof item.v !== 'undefined' ? item.v : 0, // Default to 0 if undefined
        };
    });

    // Log the prepared data
    console.log('Heatmap Data:', data);
    console.log('X Labels:', xLabels);
    console.log('Y Labels:', yLabels);

    // Create the heatmap chart
    heatmapChartInstance = new Chart(ctx, {
        type: 'matrix',
        data: {
            datasets: [{
                label: 'Heatmap',
                data: data,
                backgroundColor: context => {
                    const dataPoint = context.dataset.data[context.dataIndex];
                    if (dataPoint && typeof dataPoint.v !== 'undefined') {
                        const alpha = Math.min(dataPoint.v / 10, 1); // Ensure alpha doesn't exceed 1
                        return `rgba(255, 99, 132, ${alpha})`;
                    } else {
                        // Default color for undefined values
                        return 'rgba(200, 200, 200, 0.5)';
                    }
                },
                width: ({chart}) => (chart.chartArea || {}).width / xLabels.length - 1,
                height: ({chart}) => (chart.chartArea || {}).height / yLabels.length - 1,
            }]
        },
        options: {
            scales: {
                x: {
                    type: 'category',
                    labels: xLabels,
                    offset: true,
                    grid: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Due Dates',
                    },
                },
                y: {
                    type: 'category',
                    labels: yLabels,
                    offset: true,
                    grid: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: currentMode === 'tasks' ? 'Tasks' : 'Groups',
                    },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => {
                            const xLabel = xLabels[context.data.x];
                            const yLabel = yLabels[context.data.y];
                            const value = context.data.v;
                            return `${yLabel} - ${xLabel}: ${value}`;
                        },
                    },
                },
                legend: {
                    display: false,
                },
            },
        },
    });
}

    function updateHeatmapChart() {
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("heatmapChart").getContext("2d");

    // Destroy previous chart instance if it exists
    if (heatmapChartInstance) {
        heatmapChartInstance.destroy();
        heatmapChartInstance = null;
    }

    // Filter tasks to exclude completed ones
    const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

    // Prepare data for heatmap
    const dataMatrix = [];
    const xLabelsSet = new Set(); // Due Dates
    const yLabelsSet = new Set(); // Task Titles or Group Names

    activeTasks.forEach(task => {
        const dueDate = new Date(task.due_date).toLocaleDateString();
        const yValue = currentMode === 'tasks' ? task.title : task.group_name || 'Personal';

        xLabelsSet.add(dueDate);
        yLabelsSet.add(yValue);

        dataMatrix.push({
            x: dueDate,
            y: yValue,
            v: task.estimated_load,
        });
    });

    const xLabels = Array.from(xLabelsSet).sort((a, b) => new Date(a) - new Date(b));
    const yLabels = Array.from(yLabelsSet);

    // Map dataMatrix to correct indices
    const data = dataMatrix.map(item => ({
        x: xLabels.indexOf(item.x),
        y: yLabels.indexOf(item.y),
        v: item.v,
    }));

    // Log the prepared data
    console.log('Heatmap Data:', data);
    console.log('X Labels:', xLabels);
    console.log('Y Labels:', yLabels);

    // Create the heatmap chart
    heatmapChartInstance = new Chart(ctx, {
        type: 'matrix',
        data: {
            datasets: [{
                label: 'Heatmap',
                data: data,
                backgroundColor: context => {
                    const dataPoint = context.dataset.data[context.dataIndex];
                    if (dataPoint && typeof dataPoint.v !== 'undefined') {
                        const alpha = Math.min(dataPoint.v / 10, 1); // Ensure alpha doesn't exceed 1
                        return `rgba(255, 99, 132, ${alpha})`;
                    } else {
                        // Default color for undefined values
                        return 'rgba(200, 200, 200, 0.5)';
                    }
                },
                width: ({chart}) => (chart.chartArea || {}).width / xLabels.length - 1,
                height: ({chart}) => (chart.chartArea || {}).height / yLabels.length - 1,
            }]
        },
        options: {
            scales: {
                x: {
                    type: 'category',
                    labels: xLabels,
                    offset: true,
                    grid: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Due Dates',
                    },
                },
                y: {
                    type: 'category',
                    labels: yLabels,
                    offset: true,
                    grid: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: currentMode === 'tasks' ? 'Tasks' : 'Groups',
                    },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => {
                            const xLabel = xLabels[context.data.x];
                            const yLabel = yLabels[context.data.y];
                            const value = context.data.v;
                            return `${yLabel} - ${xLabel}: ${value}`;
                        },
                    },
                },
                legend: {
                    display: false,
                },
            },
        },
    });
}

    function updateHeatmapChart() {
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("heatmapChart").getContext("2d");

    // Destroy previous chart instance if it exists
    if (heatmapChartInstance) {
        heatmapChartInstance.destroy();
        heatmapChartInstance = null;
    }

    // Prepare data for heatmap
    const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

    const dataMatrix = [];
    const xLabelsSet = new Set(); // Due Dates
    const yLabelsSet = new Set(); // Task Titles or Group Names

    activeTasks.forEach(task => {
        const dueDate = new Date(task.due_date).toLocaleDateString();
        const yValue = currentMode === 'tasks' ? task.title : task.group_name || 'Personal';

        xLabelsSet.add(dueDate);
        yLabelsSet.add(yValue);

        dataMatrix.push({
            x: dueDate,
            y: yValue,
            v: task.estimated_load,
        });
    });

    const xLabels = Array.from(xLabelsSet).sort((a, b) => new Date(a) - new Date(b));
    const yLabels = Array.from(yLabelsSet);

    // Map dataMatrix to correct indices
    const data = dataMatrix.map(item => ({
        x: xLabels.indexOf(item.x),
        y: yLabels.indexOf(item.y),
        v: item.v,
    }));

    // Create the heatmap chart
    heatmapChartInstance = new Chart(ctx, {
        type: 'matrix',
        data: {
            datasets: [{
                label: 'Heatmap',
                data: data,
                backgroundColor: context => {
                    const value = context.dataset.data[context.dataIndex].v;
                    const alpha = value / 10; // Adjust as needed
                    return `rgba(255, 99, 132, ${alpha})`;
                },
                width: ({chart}) => (chart.chartArea || {}).width / xLabels.length - 1,
                height: ({chart}) => (chart.chartArea || {}).height / yLabels.length - 1,
            }]
        },
        options: {
            scales: {
                x: {
                    type: 'category',
                    labels: xLabels,
                    offset: true,
                    grid: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Due Dates',
                    },
                },
                y: {
                    type: 'category',
                    labels: yLabels,
                    offset: true,
                    grid: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: currentMode === 'tasks' ? 'Tasks' : 'Groups',
                    },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => {
                            const xLabel = xLabels[context.data.x];
                            const yLabel = yLabels[context.data.y];
                            const value = context.data.v;
                            return `${yLabel} - ${xLabel}: ${value}`;
                        },
                    },
                },
                legend: {
                    display: false,
                },
            },
        },
    });
}
function updateScatterChart() {
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("scatterChart").getContext("2d");

    // Destroy previous chart instance if it exists
    if (scatterChartInstance) {
        scatterChartInstance.destroy();
        scatterChartInstance = null;
    }

    // Prepare data
    const data = tasks.map(task => {
        const xValue = currentMode === 'tasks' ? new Date(task.due_date).getTime() : task.group_name || 'Personal';
        return {
            x: xValue,
            y: parseFloat(task.estimated_load),
            label: task.title,
            group: task.group_name || 'Personal',
        };
    });

    // For tasks mode, we need to handle date on x-axis
    let xScaleOptions;
    if (currentMode === 'tasks') {
        xScaleOptions = {
            type: 'time',
            time: {
                unit: 'day',
                tooltipFormat: 'MMM d, yyyy',
            },
            title: {
                display: true,
                text: 'Due Date',
            },
        };
    } else {
        xScaleOptions = {
            type: 'category',
            title: {
                display: true,
                text: 'Groups',
            },
        };
    }

    // Create the scatter chart
    scatterChartInstance = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Tasks',
                data: data,
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
            }],
        },
        options: {
            scales: {
                x: xScaleOptions,
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Estimated Load',
                    },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => {
                            const label = context.raw.label || '';
                            const group = context.raw.group || '';
                            const yValue = context.parsed.y;
                            return `${label} (${group}): ${yValue}`;
                        },
                    },
                },
                legend: {
                    display: false,
                },
            },
        },
    });
}

function updateRadarChart() {
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("radarChart").getContext("2d");

    // Destroy previous chart instance if it exists
    if (radarChartInstance) {
        radarChartInstance.destroy();
        radarChartInstance = null;
    }

    // Prepare data
    let labels, dataValues;
    if (currentMode === 'tasks') {
        // Limit the number of tasks for readability
        const maxTasks = 12;
        const taskData = tasks.slice(0, maxTasks);
        labels = taskData.map(task => task.title);
        dataValues = taskData.map(task => parseFloat(task.estimated_load));
    } else {
        // Aggregate by groups
        const groupData = {};
        tasks.forEach(task => {
            const groupName = task.group_name || 'Personal';
            if (!groupData[groupName]) {
                groupData[groupName] = 0;
            }
            groupData[groupName] += parseFloat(task.estimated_load);
        });
        labels = Object.keys(groupData);
        dataValues = Object.values(groupData);
    }

    // Create the radar chart
    radarChartInstance = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Estimated Load',
                data: dataValues,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
            }],
        },
        options: {
            scales: {
                r: {
                    beginAtZero: true,
                },
            },
            plugins: {
                legend: {
                    display: false,
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
        console.log("gasdfa");

        // Initialize charts
        updateRadarChart();
        updateScatterChart();
    });
</script>

<style>
    .chart-container {
        position: relative;
        width: 100%;
        height: 60vh; /* Adjust height as needed */
    }

    .card.shadow-sm.rounded {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-uniform {
        min-width: 120px;
        text-align: center;
    }

    @media (max-width: 576px) {
        .btn-uniform {
            min-width: 100px;
        }
    }
</style>