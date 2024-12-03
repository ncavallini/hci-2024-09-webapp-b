<?php
// Fetch survey and task data
$sql = "
  SELECT t.title AS task_name,
    s.ans1, s.ans2, s.ans3, s.ans4
  FROM surveys s
  JOIN tasks t ON s.task_id = t.task_id
  WHERE s.user_id = :user_id
";
$stmt = $dbconnection->prepare($sql);
$stmt->bindValue(":user_id", Auth::user()['user_id']);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$chartData = [
    "labels" => [],
    "datasets" => []
];

$sums = []; // To store the total scores for each task
$likertLabels = ["Physical Demand", "Mental Demand", "Frustration", "Performance"];
$colors = [
  "rgba(34, 193, 195, 0.8)",
  "rgba(253, 187, 45, 0.8)",
  "rgba(238, 83, 83, 0.8)",
  "rgba(102, 16, 242, 0.8)" 
];

foreach ($result as $row) {
  $taskName = $row['task_name'];
  $likertScores = [$row['ans1'], $row['ans2'], $row['ans3'], $row['ans4']];
  // $sums[] = array_sum($likertScores); // No longer needed for the chart

  // Add task name to labels
  $chartData["labels"][] = $taskName;

  // Add each Likert score to the respective dataset
  foreach ($likertScores as $index => $score) {
      if (!isset($chartData["datasets"][$index])) {
          $chartData["datasets"][$index] = [
              "label" => $likertLabels[$index],
              "data" => [],
              "backgroundColor" => $colors[$index]
          ];
      }
      $chartData["datasets"][$index]["data"][] = $score;
  }
}



?>
<h1 class="text-center">Welcome, <?php echo $_SESSION['user']['first_name'] ?></h1>
<br>
<div class="list-group">
  <a href="index.php?page=visualize" class="text-center btn btn-visualize"><i class="fa-solid fa-chart-pie"></i> Visualize</a>
</div>

<p>&nbsp;&nbsp;</p>

<div class="card mb-4 shadow-sm rounded">
  <div class="card-header">
    <h5 class="card-title mb-0">Likert Scale Results</h5>
  </div>
  <div class="card-body">
    <div class="chart-container">
      <canvas id="stackedBarChart"></canvas>
    </div>
    <!-- Navigation Buttons -->
    <div class="d-flex justify-content-center mt-3">
      <button id="prevButton" class="btn btn-secondary me-2">Previous</button>
      <button id="nextButton" class="btn btn-secondary">Next</button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
  const fullChartData = <?php echo json_encode($chartData); ?>;
  const itemsPerPage = 2;

  let currentIndex = Math.max(0, fullChartData.labels.length - itemsPerPage);
    
  console.log(currentIndex);

  // Function to get the subset of data to display
  function getSubsetData() {
    // Clone the chart data to avoid modifying the original
    const subsetData = JSON.parse(JSON.stringify(fullChartData));

    // Get the subset of labels
    subsetData.labels = fullChartData.labels.slice(currentIndex, currentIndex + itemsPerPage);

    // Get the subset of data for each dataset
    subsetData.datasets.forEach((dataset) => {
      dataset.data = dataset.data.slice(currentIndex, currentIndex + itemsPerPage);
    });

    return subsetData;
  }

  // Create the Chart.js stacked bar chart
  const ctx = document.getElementById("stackedBarChart").getContext("2d");
  let stackedBarChart = new Chart(ctx, {
    type: "bar",
    data: getSubsetData(),
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: function (tooltipItem) {
              const label = stackedBarChart.data.datasets[tooltipItem.datasetIndex].label;
              const value = tooltipItem.raw; // Get the raw data value
              return `${label}: ${value}`; // Show Likert score
            },
          },
        },
        legend: {
          position: "top",
        },
        datalabels: {
          display: true,
          formatter: function(value, context) {
            // Calculate total for each data point
            const totals = context.chart.data.datasets
              .map(dataset => dataset.data[context.dataIndex]);

            const total = totals.reduce((a, b) => a + b, 0);

            // Only display on the last dataset
            if (context.datasetIndex === context.chart.data.datasets.length - 1) {
              return '';
            } else {
              return '';
            }
          },
          anchor: 'end',
          align: 'start',
          offset: -10
        }
      },
      scales: {
        x: {
          stacked: true,
          title: {
            display: true,
            text: "Tasks",
          },
        },
        y: {
          stacked: true,
          title: {
            display: true,
            text: "Likert Scores",
          },
          ticks: {
            beginAtZero: true,
          },
        },
      },
    },
    plugins: [ChartDataLabels], // Register the Data Labels plugin
  });

  // Function to update the chart when navigating
  function updateChart() {
    const newData = getSubsetData();
    stackedBarChart.data.labels = newData.labels;
    stackedBarChart.data.datasets.forEach((dataset, index) => {
      dataset.data = newData.datasets[index].data;
    });
    stackedBarChart.update();

    // Disable/Enable navigation buttons based on the current index
    document.getElementById('prevButton').disabled = currentIndex === 0;
    document.getElementById('nextButton').disabled = currentIndex + itemsPerPage >= fullChartData.labels.length;
  }

  // Event listeners for navigation buttons
  document.getElementById('prevButton').addEventListener('click', function () {
    currentIndex = Math.max(0, currentIndex - itemsPerPage);
    updateChart();
  });

  document.getElementById('nextButton').addEventListener('click', function () {
    currentIndex = Math.min(fullChartData.labels.length - itemsPerPage, currentIndex + itemsPerPage);
    updateChart();
  });

  // Initialize the chart and buttons
  updateChart();
</script>
<p>&nbsp;&nbsp;</p>

<div class="card">
  <div class="card-header">
  <div class="card-title h5">Tasks to be completed <i>Before</i> the Deadline</div>
  </div>
  <div class="card-body">
  <?php
// SQL query to fetch all tasks that are not overdue and not completed
$sql = "
    (SELECT 
        t.task_id, 
        t.title,
        t.due_date,
        t.is_completed,
        0 AS group_id, 
        NULL AS group_name 
     FROM 
        tasks t 
     WHERE 
        DATE(due_date) >= CURRENT_DATE 
        AND user_id = :user_id 
        AND is_completed = 0
    )
    UNION ALL
    (SELECT 
        gt.group_task_id, 
        gt.title,
        gt.due_date,
        gt.is_completed,
        gt.group_id, 
        g.name AS group_name 
     FROM 
        group_tasks gt 
     JOIN 
        groups g USING(group_id) 
     WHERE 
        DATE(due_date) >= CURRENT_DATE 
        AND user_id = :user_id 
        AND is_completed = 0
    )";

    $stmt = $dbconnection->prepare($sql);
    $stmt->bindValue(":user_id", Auth::user()['user_id']);
    $stmt->execute();
    
    $tasks = $stmt->fetchAll();



// If no tasks found, display a message
if (count($tasks) == 0) {
    echo "<p class='text-center'>No incomplete tasks are due or upcoming</p>";
    goto end_task_due;
}

// Display the tasks in a list
echo '<ul class="list-group list-group-flush">';
foreach ($tasks as $task) {
    echo "<li class='list-group-item'>{$task['title']} &nbsp;&nbsp;";
    if ($task['group_id'] != 0) {
        echo "<span class='badge bg-secondary rounded-pill'>{$task['group_name']}</span>";
        echo "<span class='text-muted ms-3'>Due: " . date("M d, Y", strtotime($task['due_date'])) . "</span>";
        //checkbox
        echo "<input type='checkbox' class='form-check-input me-2' style='float:right'";
        echo $task['is_completed'] ? "checked" : "";
        echo " onclick=\"window.location.href='./actions/tasks/toggle_completed_backTOdahboard.php?group_id={$task['group_id']}&task_id={$task['task_id']}'\">";
        //checkbox
    } else {
        echo "<span class='badge bg-primary rounded-pill'>Personal</span>";
        echo "<span class='text-muted ms-3'>Due: " . date("M d, Y", strtotime($task['due_date'])) . "</span>";
        //checkbox
        echo "<input type='checkbox' class='form-check-input me-2' style='float:right'";
        echo $task['is_completed'] ? "checked" : "";
        echo " onclick=\"window.location.href='./actions/tasks/toggle_completed_backTOdahboard.php?group_id={$task['group_id']}&task_id={$task['task_id']}'\">";
        //checkbox
    }
    echo "</li>";
}
end_task_due:
?>

  </div>
</div>
<p>&nbsp;&nbsp;</p>

<div class="card">
  <div class="card-header">
  <div class="card-title h5"><i>Overdue</i> tasks</div>
  </div>
  <div class="card-body">
  <?php
// SQL query to fetch all tasks that are not overdue and not completed
$sql = "
    (SELECT 
        t.task_id, 
        t.title,
        t.due_date,
        t.is_completed,
        0 AS group_id, 
        NULL AS group_name 
     FROM 
        tasks t 
     WHERE 
        DATE(due_date) < CURRENT_DATE 
        AND user_id = :user_id 
        AND is_completed = 0
    )
    UNION ALL
    (SELECT 
        gt.group_task_id, 
        gt.title,
        gt.due_date,
        gt.is_completed,
        gt.group_id, 
        g.name AS group_name 
     FROM 
        group_tasks gt 
     JOIN 
        groups g USING(group_id) 
     WHERE 
        DATE(due_date) < CURRENT_DATE 
        AND user_id = :user_id 
        AND is_completed = 0
    )";

$stmt = $dbconnection->prepare($sql);
$stmt->bindValue(":user_id", Auth::user()['user_id']);
$stmt->execute();

$tasks = $stmt->fetchAll();

// If no tasks found, display a message
if (count($tasks) == 0) {
    echo "<p class='text-center'>No incomplete tasks are due or upcoming</p>";
    goto end_task_due2;
}

// Display the tasks in a list
echo '<ul class="list-group list-group-flush">';
foreach ($tasks as $task) {
    echo "<li class='list-group-item rounded' style='background-color: lightcoral;'>{$task['title']} &nbsp;&nbsp;";
    if ($task['group_id'] != 0) {
        echo "<span class='badge bg-secondary rounded-pill'>{$task['group_name']}</span>";
        echo "<span class='text-muted ms-3'>Due: " . date("M d, Y", strtotime($task['due_date'])) . "</span>";
        //checkbox
        echo "<input type='checkbox' class='form-check-input me-2' style='float:right'";
        echo $task['is_completed'] ? "checked" : "";
        echo " onclick=\"window.location.href='./actions/tasks/toggle_completed_backTOdahboard.php?group_id={$task['group_id']}&task_id={$task['task_id']}'\">";
        //checkbox
    } else {
        echo "<span class='badge bg-primary rounded-pill'>Personal</span>";
        echo "<span class='text-muted ms-3'>Due: " . date("M d, Y", strtotime($task['due_date'])) . "</span>";
        //checkbox
        echo "<input type='checkbox' class='form-check-input me-2' style='float:right'";
        echo $task['is_completed'] ? "checked" : "";
        echo " onclick=\"window.location.href='./actions/tasks/toggle_completed_backTOdahboard.php?group_id={$task['group_id']}&task_id={$task['task_id']}'\">";
        //checkbox
    }
    echo "</li>";
}
end_task_due2:
?>

  </div>
</div>

<p>&nbsp;&nbsp;</p>

<?php 
$query = "SELECT m.group_id, g.name FROM membership m JOIN groups g ON m.group_id = g.group_id WHERE m.username = ?";
$stmt = $dbconnection->prepare($query);
$stmt->execute([$user['username']]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="card">
  <div class="card-header">
  <div class="card-title h5">Groups & Members</div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-borderless">
        <thead>
          <tr>
          <?php for($i = 0; $i < count($groups); $i++) echo "<th></th>"; ?>
          </tr>
        </thead>
        <tbody>
          <!-- Display Group Names -->
          <tr>
            <?php foreach ($groups as $group): ?>
              <td><p class='h5'><?php echo htmlspecialchars($group['name']); ?></p></td>
            <?php endforeach; ?>
          </tr>

          <!-- Line Under Group Names -->
          <tr>
            <?php foreach ($groups as $group): ?>
              <td style="border-bottom: 2px solid #000;"></td>
            <?php endforeach; ?>
          </tr>

          <?php
          // Initialize variables
          $max_members = 0;
          $group_members = [];

          // Fetch members for each group
          foreach ($groups as $group) {
              $query = "SELECT m.username, u.first_name, u.last_name 
                        FROM membership m 
                        JOIN users u USING(username) 
                        WHERE group_id = ?";
              $stmt = $dbconnection->prepare($query);
              $stmt->execute([$group['group_id']]);
              $members = $stmt->fetchAll();
              $group_members[] = $members;
              $max_members = max($max_members, count($members));
          }

          // Render rows for members
          for ($i = 0; $i < $max_members; $i++) {
            echo "<tr>";
            foreach ($group_members as $members) {
                if (isset($members[$i])) {
                    $name = htmlspecialchars($members[$i]['first_name']) . ' ' . htmlspecialchars($members[$i]['last_name']);
                    echo "<td style='border-right: 1px solid #000; border-left: 1px solid #000;'>{$name}</td>";
                } else {
                    echo "<td style='border-right: 1px solid #000; border-left: 1px solid #000;'></td>";
                }
            }
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>        
    </div>
</div>


<div class="list-group">
  <a href="index.php?page=manage" class="btn btn-visualize text-center "><i class="fa-solid fa-gear"></i> Manage</a>
</div>
<style>
 
/* General Button Style */
.btn {
    display: inline-block;
    text-align: center;
    padding: 12px 16px;
    font-size: 16px;
    font-weight: bold;
    color: #4B286D; /* Dark Purple text */
    text-decoration: none;
    border-radius: 8px; /* Rounded corners */
    transition: background-color 0.3s, transform 0.2s ease;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

/* Specific Button Colors */
.btn-visualize {
    background-color: #E0D7F3; /* Light Lavender */
}

.btn-manage {
    background-color: #D9E2FA; /* Slightly blue lavender for Manage */
}

/* Hover State */
.btn:hover {
    transform: scale(1.05); /* Slightly larger on hover */
    box-shadow: 0px 6px 8px rgba(0, 0, 0, 0.2); /* More pronounced shadow */
}

/* Active/Clicked State */
.btn:active {
    transform: scale(0.98); /* Slightly smaller on click */
    box-shadow: 0px 3px 5px rgba(0, 0, 0, 0.1); /* Softer shadow */
}

/* Disabled State */
.btn[disabled] {
    background-color: #F0EAFB; /* Muted Lavender */
    color: #A8A2BA; /* Faded text */
    pointer-events: none;
    cursor: not-allowed;
}
</style>