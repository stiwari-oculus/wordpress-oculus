<div class="wrap">
    <h1>Worklog Details</h1>
    <form method="get" class="mb-4">
        <input type="hidden" name="page" value="worklog-settings">
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>">
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>">
        <button type="submit" class="button button-primary">Search</button>
    </form>

    <table class="widefat table-bordered table-hover">
        <thead>
            <tr>
                <th>Author</th>
                <th>Post Title</th>
                <th>Time Logged</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'worklog';
            $query = "
                SELECT 
                    w.author_id, 
                    w.post_id, 
                    w.time_spent, 
                    p.post_title
                FROM $table_name AS w
                JOIN {$wpdb->prefix}posts AS p ON w.post_id = p.ID
            ";

            $conditions = [];
            $params = [];

            if (!empty($_GET['start_date'])) {
                $conditions[] = "w.start_date >= %s";
                $params[] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
                $conditions[] = "w.start_date <= %s";
                $params[] = $_GET['end_date'];
            }

            if ($conditions) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY w.author_id, w.post_id";

            $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

            if ($results) {
                $current_author_id = null;

                foreach ($results as $row) {
                    $author_id = $row['author_id'];
                    $post_id = $row['post_id'];
                    $post_title = $row['post_title'];
                    $time_spent = $row['time_spent'];
                    $post_link = get_permalink($post_id);
                    $author = get_user_by('id', $author_id);

                    // Convert time spent (e.g., "1h 30m") to a readable format
                    preg_match('/(\d+)h/', $time_spent, $hours_match);
                    preg_match('/(\d+)m/', $time_spent, $minutes_match);

                    $hours = isset($hours_match[1]) ? (int)$hours_match[1] : 0;
                    $minutes = isset($minutes_match[1]) ? (int)$minutes_match[1] : 0;

                    // Format the time as h:m
                    $formatted_time = "{$hours}h {$minutes}m";

                    // Show the author name only for the first row of their posts
                    if ($current_author_id !== $author_id) {
                        $current_author_id = $author_id;
                        echo '<tr>';
                        echo '<td>' . esc_html($author->display_name) . '</td>';
                    } else {
                        echo '<tr>';
                        echo '<td></td>'; // Skip showing the author name for subsequent posts
                    }

                    // Show the post details
                    echo '<td><a href="' . esc_url($post_link) . '" target="_blank">' . esc_html($post_title) . '</a></td>';
                    echo '<td>' . esc_html($formatted_time) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3">No records found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
