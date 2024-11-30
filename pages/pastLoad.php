<?php
require_once __DIR__ . "/../utils/init.php";

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$user_id = Auth::user()['user_id'];

// Query to fetch tasks and group tasks
$sql = "
    SELECT 
    generated_dates.load_date,
    t.title AS task_title,
    NULL AS group_name, -- Personal tasks have no group name
    t.estimated_load
FROM 
    (
        SELECT 
            CURDATE() - INTERVAL seq.seq DAY AS load_date
        FROM 
            (SELECT 0 AS seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) seq
    ) generated_dates
LEFT JOIN 
    tasks t ON DATE(t.created_at) <= generated_dates.load_date
    AND (t.completed_at IS NULL OR DATE(t.completed_at) > generated_dates.load_date) -- Include incomplete tasks
    AND t.user_id = :user_id

UNION ALL

SELECT 
    generated_dates.load_date,
    gt.title AS task_title,
    g.name AS group_name, -- Group tasks include group name
    gt.estimated_load
FROM 
    (
        SELECT 
            CURDATE() - INTERVAL seq.seq DAY AS load_date
        FROM 
            (SELECT 0 AS seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) seq
    ) generated_dates
LEFT JOIN 
    group_tasks gt ON DATE(gt.created_at) <= generated_dates.load_date
    AND (gt.completed_at IS NULL OR DATE(gt.completed_at) > generated_dates.load_date) -- Include incomplete group tasks
    AND gt.user_id = :user_id
JOIN 
    groups g ON gt.group_id = g.group_id
ORDER BY 
    load_date ASC;



";

$stmt = $dbconnection->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$load_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data for Chart.js
$groupedData = [];
foreach ($load_data as $row) {
    $date = $row['load_date'];
    if (!isset($groupedData[$date])) {
        $groupedData[$date] = ['total_load' => 0, 'groups' => []];
    }
    $groupedData[$date]['total_load'] += $row['estimated_load'];

    $groupName = $row['group_name'] ?? 'Personal'; // Default to 'Personal' if no group name
    if (!isset($groupedData[$date]['groups'][$groupName])) {
        $groupedData[$date]['groups'][$groupName] = [];
    }

    $groupedData[$date]['groups'][$groupName][] = [
        'task_title' => $row['task_title'],
        'load' => $row['estimated_load']
    ];
}

$labels = json_encode(array_keys($groupedData)); // Dates
$data = json_encode(array_column($groupedData, 'total_load')); // Total load values
$taskDetails = json_encode($groupedData); // Task details grouped by date
?>



    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Load Visualization</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">

    <h1>Total Mental Load Over the Past 7 Days</h1>
    <div class="container mt-4">
        <canvas id="lineChart" width="400" height="200"></canvas>
    </div>

    <!-- Modal for Task Details -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskDetailsModalLabel">Contributing Tasks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="taskDetailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const labels = <?php echo $labels; ?>; // Dates
        const data = <?php echo $data; ?>;    // Total load values
        const taskDetails = <?php echo $taskDetails; ?>; // Task details grouped by date

        const mentalLoadData = {
            labels: labels,
            datasets: [{
                label: "Mental Load (Past 7 Days)",
                data: data,
                fill: false,
                borderColor: "blue",
                tension: 0.1,
                pointBackgroundColor: "blue",
                pointBorderColor: "white",
                pointRadius: 5,
            }]
        };

        const config = {
            type: 'line',
            data: mentalLoadData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `Load Amount: ${tooltipItem.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: "Date"
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: "Mental Load"
                        },
                        beginAtZero: true
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index; // Get the index of the clicked point
                        const date = labels[index];
                        const dateDetails = taskDetails[date]['groups'];

                        const taskDetailsContent = document.getElementById("taskDetailsContent");
                        taskDetailsContent.innerHTML = ""; // Clear previous data

                        // Iterate through each group
                        for (const groupName in dateDetails) {
                            const groupHeader = document.createElement("h5");
                            groupHeader.textContent = `Group: ${groupName}`;
                            groupHeader.className = "mt-3 mb-2";
                            taskDetailsContent.appendChild(groupHeader);

                            // Create a list of tasks under each group
                            const taskList = document.createElement("ul");
                            taskList.className = "list-unstyled";
                            let groupTotalLoad = 0; // Calculate group total load
                            dateDetails[groupName].forEach(task => {
                                const taskItem = document.createElement("li");
                                taskItem.className = "mb-2 pb-2 border-bottom"; // Add subtle separator
                                taskItem.innerHTML = `
                                    <strong>Task:</strong> ${task.task_title} <br> 
                                    <strong>Load:</strong> ${task.load}`;
                                taskList.appendChild(taskItem);

                                groupTotalLoad += task.load; // Increment total load for this group
                            });

                            taskDetailsContent.appendChild(taskList);

                            // Add total load summary for the group
                            const groupLoadSummary = document.createElement("p");
                            groupLoadSummary.className = "fw-bold mt-2";
                            groupLoadSummary.textContent = `Total Load: ${groupTotalLoad}`;
                            taskDetailsContent.appendChild(groupLoadSummary);

                            // Add a separator line between groups
                            const separator = document.createElement("hr");
                            taskDetailsContent.appendChild(separator);
                        }

                        // Remove the last separator for better appearance
                        if (taskDetailsContent.lastChild.tagName === "HR") {
                            taskDetailsContent.lastChild.remove();
                        }

                        // Show the modal
                        new bootstrap.Modal(document.getElementById("taskDetailsModal")).show();
                    }
                }
            }
        };

        const ctx = document.getElementById("lineChart").getContext("2d");
        new Chart(ctx, config);
    });
    </script>

