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

<?php
    $sql = "SELECT g.group_id, g.name FROM membership m JOIN users u ON u.username = m.username JOIN groups g ON g.group_id = m.group_id WHERE u.user_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="mb-4 text-center">All Tasks</h1>
    
    <!-- Mental Load Section -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h5>Your Mental Load</h5>
            <a href="index.php?page=pastLoad">
                <button class="btn btn-sm btn-info">Past Mental Load</button>
            </a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
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
            <select id="groupSelect" class="form-select" style="width: 200px;" onchange="groupSelectionChanged()">
                <option value="all">All</option>
                <option value="personal">Personal</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo htmlspecialchars($group['group_id']); ?>">
                        <?php echo htmlspecialchars($group['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Chart Containers -->
    <!-- Heatmap Chart -->
    <div class="card mb-4 shadow-sm rounded" id="heatmapChartCard">
        <div class="card-header">
            <h5 class="card-title mb-0">Task Load Intensity Heatmap</h5>
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
            <h5 class="card-title mb-0">Task Load Distribution Radar</h5>
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
            <h5 class="card-title mb-0">Task Load vs. Due Date Scatter</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="scatterChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bubble Chart -->
    <div class="card mb-4 shadow-sm rounded" id="bubbleChartCard">
        <div class="card-header">
            <h5 class="card-title mb-0">Task Priority Bubble Chart</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="bubbleChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Pie Chart -->
    <div class="card mb-4 shadow-sm rounded" id="pieChartCard">
        <div class="card-header">
            <h5 class="card-title mb-0">Estimated Load Distribution by Task</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bar Chart -->
    <div class="card mb-4 shadow-sm rounded" id="barChartCard">
        <div class="card-header">
            <h5 class="card-title mb-0">Total Estimated Load Over Time</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div style="height: 10vh"></div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart.js Matrix Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.1.0/dist/chartjs-chart-matrix.min.js"></script>

<!-- Moment.js Library -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1"></script>

<!-- Chart.js Moment Adapter -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0/dist/chartjs-adapter-moment.min.js"></script>

<!-- Your custom script should come after the libraries -->
<!-- <script src="path/to/your/custom/script.js"></script> -->

<script>
    console.log('Script loaded');

    let selectedGroup = 'all'; // Default selection
    let tasks = <?php echo json_encode($tasks); ?>;

    let heatmapChartInstance;
    let radarChartInstance;
    let scatterChartInstance;
    let bubbleChartInstance;
    let pieChartInstance;
    let barChartInstance;

    function groupSelectionChanged() {
        const selectElement = document.getElementById('groupSelect');
        selectedGroup = selectElement.value;

        // Update all charts
        updateHeatmapChart();
        updateRadarChart();
        updateScatterChart();
        updateBubbleChart();
        updatePieChart();
        updateBarChart();
    }

    function filterTasksByGroup(tasks, group) {
        if (group === 'all') {
            return tasks; // Return all tasks without filtering
        } else if (group === 'personal') {
            return tasks.filter(task => task.group_id == 0);
        } else {
            return tasks.filter(task => task.group_id == group);
        }
    }

    function updateHeatmapChart() {
        console.log('Updating Heatmap Chart...');
        const ctx = document.getElementById("heatmapChart").getContext("2d");

        // Destroy previous chart instance if it exists
        if (heatmapChartInstance) {
            heatmapChartInstance.destroy();
            heatmapChartInstance = null;
        }

        // Filter tasks based on selected group
        const filteredTasks = filterTasksByGroup(tasks, selectedGroup);

        // Prepare data for heatmap
        const activeTasks = filteredTasks.filter(task => parseInt(task.is_completed, 10) === 0);

        const dataMatrix = [];
        const xLabelsSet = new Set(); // Due Dates
        const yLabelsSet = new Set(); // Task Titles

        activeTasks.forEach(task => {
            const dueDate = new Date(task.due_date).toLocaleDateString();
            const yValue = task.title;

            xLabelsSet.add(dueDate);
            yLabelsSet.add(yValue);

            dataMatrix.push({
                x: dueDate,
                y: yValue,
                v: task.estimated_load,
            });
        });

        if (dataMatrix.length === 0) {
            // No data, hide the chart card
            document.getElementById("heatmapChartCard").style.display = 'none';
            return;
        } else {
            // Data exists, ensure the chart card is visible
            document.getElementById("heatmapChartCard").style.display = '';
        }

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
                    label: 'Task Load Intensity',
                    data: data,
                    backgroundColor: context => {
                        const value = context.dataset.data[context.dataIndex].v;
                        const alpha = Math.min(Math.max(value / 10, 0.2), 1);
                        return `rgba(255, 99, 132, ${alpha})`;
                    },
                    width: ({chart}) => (chart.chartArea || {}).width / xLabels.length - 1,
                    height: ({chart}) => (chart.chartArea || {}).height / yLabels.length - 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                            text: 'Tasks',
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

    function updateRadarChart() {
        console.log('Updating Radar Chart...');
        const ctx = document.getElementById("radarChart").getContext("2d");

        // Destroy previous chart instance if it exists
        if (radarChartInstance) {
            radarChartInstance.destroy();
            radarChartInstance = null;
        }

        // Filter tasks based on selected group
        const filteredTasks = filterTasksByGroup(tasks, selectedGroup);

        // Prepare data
        const maxTasks = 12;
        const taskData = filteredTasks.slice(0, maxTasks);
        const labels = taskData.map(task => task.title);
        const dataValues = taskData.map(task => parseFloat(task.estimated_load));

        if (dataValues.length === 0) {
            // No data, hide the chart card
            document.getElementById("radarChartCard").style.display = 'none';
            return;
        } else {
            // Data exists, ensure the chart card is visible
            document.getElementById("radarChartCard").style.display = '';
        }

        // Create the radar chart
        radarChartInstance = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Estimated Load Comparison',
                    data: dataValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const label = context.label || '';
                                const value = context.parsed.r || 0;
                                return `${label}: ${value}`;
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
        console.log('Updating Task Load vs. Due Date Scatter Chart...');
        const ctx = document.getElementById("scatterChart").getContext("2d");

        // Destroy previous chart instance if it exists
        if (scatterChartInstance) {
            scatterChartInstance.destroy();
            scatterChartInstance = null;
        }

        // Filter tasks based on selected group
        const filteredTasks = filterTasksByGroup(tasks, selectedGroup);

        // Prepare data
        const data = filteredTasks.map(task => {
            return {
                x: new Date(task.due_date),
                y: parseFloat(task.estimated_load),
                label: task.title,
            };
        });

        if (data.length === 0) {
            // No data, hide the chart card
            document.getElementById("scatterChartCard").style.display = 'none';
            return;
        } else {
            // Data exists, ensure the chart card is visible
            document.getElementById("scatterChartCard").style.display = '';
        }

        // Create the scatter chart
        scatterChartInstance = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Task Load vs. Due Date',
                    data: data,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const label = context.raw.label || '';
                                const yValue = context.parsed.y;
                                return `${label}: ${yValue}`;
                            },
                        },
                    },
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM D, YYYY',
                            displayFormats: {
                                day: 'MMM D',
                            },
                        },
                        title: {
                            display: true,
                            text: 'Due Date',
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 20,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Estimated Load',
                        },
                    },
                },
            },
        });
    }

    function updateBubbleChart() {
        console.log('Updating Task Priority Bubble Chart...');
        const ctx = document.getElementById("bubbleChart").getContext("2d");

        // Destroy previous chart instance if it exists
        if (bubbleChartInstance) {
            bubbleChartInstance.destroy();
            bubbleChartInstance = null;
        }

        // Filter tasks based on selected group
        const filteredTasks = filterTasksByGroup(tasks, selectedGroup);

        // Prepare data
        const data = filteredTasks.map(task => {
            return {
                x: new Date(task.due_date).getTime(),
                y: parseFloat(task.estimated_load),
                r: Math.sqrt(task.estimated_load) * 5,
                label: task.title,
            };
        });

        if (data.length === 0) {
            // No data, hide the chart card
            document.getElementById("bubbleChartCard").style.display = 'none';
            return;
        } else {
            // Data exists, ensure the chart card is visible
            document.getElementById("bubbleChartCard").style.display = '';
        }

        // Create the bubble chart
        bubbleChartInstance = new Chart(ctx, {
            type: 'bubble',
            data: {
                datasets: [{
                    label: 'Task Priority',
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const label = context.raw.label || '';
                                const yValue = context.raw.y;
                                return `${label}: Load = ${yValue}`;
                            },
                        },
                    },
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM D, YYYY',
                            displayFormats: {
                                day: 'MMM D',
                            },
                        },
                        title: {
                            display: true,
                            text: 'Due Date',
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 20,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Estimated Load',
                        },
                    },
                },
            },
        });
    }

    function updatePieChart() {
        console.log('Updating Estimated Load Distribution Pie Chart...');
        const ctx = document.getElementById("pieChart").getContext("2d");

        // Destroy previous chart instance if it exists
        if (pieChartInstance) {
            pieChartInstance.destroy();
            pieChartInstance = null;
        }

        // Filter tasks based on selected group
        const filteredTasks = filterTasksByGroup(tasks, selectedGroup);

        // Prepare data: Sum of estimated load per task
        const loadPerTask = {};
        filteredTasks.forEach(task => {
            const title = task.title;
            if (!loadPerTask[title]) {
                loadPerTask[title] = 0;
            }
            loadPerTask[title] += parseFloat(task.estimated_load);
        });

        const labels = Object.keys(loadPerTask);
        const dataValues = Object.values(loadPerTask);
        const backgroundColors = generateColors(labels.length);

        if (dataValues.length === 0) {
            // No data, hide the chart card
            document.getElementById("pieChartCard").style.display = 'none';
            return;
        } else {
            // Data exists, ensure the chart card is visible
            document.getElementById("pieChartCard").style.display = '';
        }

        // Create the pie chart
        pieChartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: backgroundColors,
                    borderColor: '#fff',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.chart._metasets[context.datasetIndex].total;
                                const percentage = ((value / total) * 100).toFixed(2);
                                return `${label}: ${value} (${percentage}%)`;
                            },
                        },
                    },
                    legend: {
                        position: 'right',
                    },
                },
            },
        });
    }

    function updateBarChart() {
        console.log('Updating Total Estimated Load Bar Chart...');
        const ctx = document.getElementById("barChart").getContext("2d");

        // Destroy previous chart instance if it exists
        if (barChartInstance) {
            barChartInstance.destroy();
            barChartInstance = null;
        }

        // Filter tasks based on selected group
        const filteredTasks = filterTasksByGroup(tasks, selectedGroup);

        // Prepare data: Total estimated load per due date
        const loadPerDay = {};
        filteredTasks.forEach(task => {
            const day = new Date(task.due_date).toLocaleDateString();
            if (!loadPerDay[day]) {
                loadPerDay[day] = 0;
            }
            loadPerDay[day] += parseFloat(task.estimated_load);
        });
        const labels = Object.keys(loadPerDay).sort((a, b) => new Date(a) - new Date(b));
        const dataValues = labels.map(day => loadPerDay[day]);

        const backgroundColors = generateColors(labels.length);

        if (dataValues.length === 0) {
            // No data, hide the chart card
            document.getElementById("barChartCard").style.display = 'none';
            return;
        } else {
            // Data exists, ensure the chart card is visible
            document.getElementById("barChartCard").style.display = '';
        }

        // Create the bar chart
        barChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Estimated Load',
                    data: dataValues,
                    backgroundColor: backgroundColors,
                    borderColor: 'rgba(0,0,0,0.1)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
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
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Estimated Load',
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const label = context.label || '';
                                const value = context.parsed.y || 0;
                                return `${label}: ${value}`;
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

    function generateColors(num) {
        const colors = [];
        const baseColors = [
            'rgba(255, 99, 132, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(199, 199, 199, 0.6)',
            'rgba(83, 102, 255, 0.6)',
            'rgba(255, 102, 255, 0.6)',
            'rgba(102, 255, 102, 0.6)',
        ];
        for (let i = 0; i < num; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        return colors;
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
        console.log("DOM fully loaded and parsed");

        // Initialize charts
        updateHeatmapChart();
        updateRadarChart();
        updateScatterChart();
        updateBubbleChart();
        updatePieChart();
        updateBarChart();
    });
</script>

<style>
    .chart-container {
        position: relative;
        width: 100%;
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

    .card-body {
        padding: 10px; /* Adjust as needed */
    }
</style>