<?php
    function print_alert(string $msg, bool $needs_bootstrap = false, string $type = 'danger') {
        if($needs_bootstrap) {
            echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
            echo '<div class="container">';
        }
        echo "<div class='alert alert-$type'>$msg";
        echo "<a href='javascript:history.back()' class='alert-link' style='display:block;'>Go back</a>";
        #echo "<a href='../index.php?page=home' class='alert-link' style='display:block;'>Go to the Homepage</a>";
        echo "</div>";
        if($needs_bootstrap) {
            echo '</div>';
        }
    }

    function datetime_as_mysql(int $timestamp = null) : string {
        if($timestamp == null) $timestamp = "now";
        return (new DateTimeImmutable($timestamp))->format("Y-m-d H:i:s");
    } 

    function datetime_as_html_input(int $timestamp = null) : string {
        if($timestamp == null) $timestamp = "now";
        return (new DateTimeImmutable($timestamp))->format("Y-m-dTH:i:s");
    } 

    function redirect(string $url) {
        echo "<script>window.location.href = '$url';</script>";
    }

    function binary_search(array $arr, int|float|string $needle, bool $use_str_cmp = false): int {
        if (count($arr) == 0) return -1; // Handle empty array case
        
        // Determine the comparison function
        $cmp = $use_str_cmp 
            ? function($a, $b) { return strcmp((string)$a, (string)$b); } 
            : function($a, $b) { return $a <=> $b; };
    
        $low = 0;
        $high = count($arr) - 1;
    
        while ($low <= $high) { // Correct condition
            $mid = intdiv($low + $high, 2);
            $res = $cmp($arr[$mid], $needle);
    
            if ($res == 0) {
                return $mid; // Match found
            } elseif ($res < 0) {
                $low = $mid + 1; // Search in the right half
            } else {
                $high = $mid - 1; // Search in the left half
            }
        }
    
        return -1; // Not found
    }
    
?>