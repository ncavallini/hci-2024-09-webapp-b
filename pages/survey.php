<?php
    $task_id = $_GET['task_id'];
    $group = $_GET['group'] ?? 0;
    $onD =$_GET['onD'] ?? 0;
?>
<h1 class="text-center">Survey</h1>
<form action="actions/survey/submit.php?task_id=<?php echo $task_id; ?>&group=<?php echo $group; ?>&onD=<?php echo $onD;?>" method="POST">
    <label for="q1">How physically demanding was the task? <span id="q1_span">(5/10)</span></label>
    <input type="range" name="q1" id="q1" min="0" max="10" step="1" class="form-range">
    <br>
    <label for="q2">How mentally demanding was the task? <span id="q2_span">(5/10)</span></label>
    <input type="range" name="q2" id="q2" min="0" max="10" step="1" class="form-range">
    <br>
    <label for="q3">How frustrating was this task? <span id="q3_span">(5/10)</span></label>
    <input type="range" name="q3" id="q3" min="0" max="10" step="1" class="form-range">
    <br>
    <label for="q4">How would you rate your performance? <span id="q4_span">(5/10)</span></label>
    <input type="range" name="q4" id="q4" min="0" max="10" step="1" class="form-range">
    <br>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
<script>
    const range1 = document.getElementById("q1");
    const span1 = document.getElementById("q1_span");
    range1.addEventListener("input", () => {
        span1.innerHTML = "(" + range1.value + "/10" + ")";
    });
    const range2 = document.getElementById("q2");
    const span2 = document.getElementById("q2_span");
    range2.addEventListener("input", () => {
        span2.innerHTML = "(" + range2.value + "/10" + ")";
    });
    const range3 = document.getElementById("q3");
    const span3 = document.getElementById("q3_span");
    range3.addEventListener("input", () => {
        span3.innerHTML = "(" + range3.value + "/10" + ")";
    });
    const range4 = document.getElementById("q4");
    const span4 = document.getElementById("q4_span");
    range4.addEventListener("input", () => {
        span4.innerHTML = "(" + range4.value + "/10" + ")";
    });
</script>