<div class="wrap">
    <h1>Worklog Settings</h1>
    <form method="get">
        <input type="hidden" name="page" value="worklog-settings">
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>">
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>">
        <button type="submit" class="button button-primary">Search</button>
    </form>

    <table class="widefat">
        <thead>
            <tr>
                <th>Author</th>
                <th>Total Time Logged</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'worklog';
            $query = "SELECT author_id, time_spent FROM $table_name";

            $conditions = [];
            $params = [];

            if (!empty($_GET['start_date'])) {
                $conditions[] = "start_date >= %s";
                $params[] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
                $conditions[] = "start_date <= %s";
                $params[] = $_GET['end_date'];
            }

            if ($conditions) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

            if ($results) {
                $author_times = [];

                // Loop through results to sum up time spent per author
                foreach ($results as $row) {
                    $author_id = $row['author_id'];
                    $time_spent = $row['time_spent'];

                    // Convert time spent (e.g., "1h 30m") to seconds
                    preg_match('/(\d+)h/', $time_spent, $hours_match);
                    preg_match('/(\d+)m/', $time_spent, $minutes_match);

                    $hours = isset($hours_match[1]) ? (int)$hours_match[1] : 0;
                    $minutes = isset($minutes_match[1]) ? (int)$minutes_match[1] : 0;

                    // Convert to total seconds
                    $total_seconds = ($hours * 3600) + ($minutes * 60);

                    if (!isset($author_times[$author_id])) {
                        $author_times[$author_id] = 0;
                    }

                    // Add the time spent for the current row
                    $author_times[$author_id] += $total_seconds;
                }

                // Loop through the authors and display the total time in w:h:m:s format
                foreach ($author_times as $author_id => $total_seconds) {
                    $author = get_user_by('id', $author_id);

                    // Calculate weeks, hours, minutes, and seconds
                    $weeks = floor($total_seconds / 604800); // 1 week = 604800 seconds
                    $total_seconds %= 604800;
                    $hours = floor($total_seconds / 3600); // 1 hour = 3600 seconds
                    $total_seconds %= 3600;
                    $minutes = floor($total_seconds / 60); // 1 minute = 60 seconds
                    $seconds = $total_seconds % 60;

                    // Format the time as w:h:m:s
                    $formatted_time = "{$weeks}w {$hours}h {$minutes}m {$seconds}s";

                    echo '<tr>';
                    echo '<td>' . esc_html($author->display_name) . '</td>';
                    echo '<td>' . esc_html($formatted_time) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="2">No records found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
