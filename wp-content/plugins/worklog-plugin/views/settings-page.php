<div class="wrap">
    <h1 class="mb-4">Worklog Details</h1>
    <form method="get" class="mb-4" id="worklog-form">
        <input type="hidden" name="page" value="worklog-settings">
        
        <label for="author_id">Author:</label>
        <select name="author_id">
            <option value="">-- All Authors --</option>
            <?php
            $authors = get_users(['fields' => ['ID', 'display_name']]);
            foreach ($authors as $author) {
                $selected = isset($_GET['author_id']) && $_GET['author_id'] == $author->ID ? 'selected' : '';
                echo '<option value="' . esc_attr($author->ID) . '" ' . $selected . '>' . esc_html($author->display_name) . '</option>';
            }
            ?>
        </select>

        <label for="start_date">From Date:</label>
        <input type="date" name="start_date" id="start_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>">
        
        <label for="end_date">To Date:</label>
        <input type="date" name="end_date" id="end_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>">
        
        <button type="submit" class="btn btn-primary">Search</button>
        <button type="button" class="btn btn-danger" id="reset_button">Reset</button>
    </form>

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th scope="col">Author</th>
                <th scope="col">Post</th>
                <th scope="col" colspan="2">TimeLog</th>
                <th scope="col">Total Time</th>
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
                    w.start_date, 
                    p.post_title
                FROM $table_name AS w
                JOIN {$wpdb->prefix}posts AS p ON w.post_id = p.ID
            ";

            $conditions = [];
            $params = [];

            if (!empty($_GET['author_id'])) {
                $conditions[] = "w.author_id = %d";
                $params[] = (int)$_GET['author_id'];
            }

            if (!empty($_GET['start_date'])) {
                $conditions[] = "w.start_date >= %s";
                $params[] = $_GET['start_date'];
            }

            if (!empty($_GET['end_date'])) {
                $conditions[] = "w.start_date < %s";
                $params[] = date('Y-m-d', strtotime($_GET['end_date'] . ' +1 day'));
            }

            if ($conditions) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY w.author_id, w.post_id";

            $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

            if ($results) {
                $current_author_id = null;
                $total_time_by_post = [];
                $displayed_posts = []; // Track displayed posts

                // First loop to accumulate total time per post
                foreach ($results as $row) {
                    $post_id = $row['post_id'];
                    $time_spent = $row['time_spent'];

                    preg_match('/(\d+)h/', $time_spent, $hours_match);
                    preg_match('/(\d+)m/', $time_spent, $minutes_match);

                    $hours = isset($hours_match[1]) ? (int)$hours_match[1] : 0;
                    $minutes = isset($minutes_match[1]) ? (int)$minutes_match[1] : 0;

                    // Accumulate time in minutes
                    if (!isset($total_time_by_post[$post_id])) {
                        $total_time_by_post[$post_id] = 0;
                    }
                    $total_time_by_post[$post_id] += $hours * 60 + $minutes;
                }

                // Second loop to display data with total time for each post
                foreach ($results as $row) {
                    $author_id = $row['author_id'];
                    $post_id = $row['post_id'];
                    $post_title = $row['post_title'];
                    $time_spent = $row['time_spent'];
                    $start_date = $row['start_date'];
                    $post_link = get_permalink($post_id);
                    $author = get_user_by('id', $author_id);

                    preg_match('/(\d+)h/', $time_spent, $hours_match);
                    preg_match('/(\d+)m/', $time_spent, $minutes_match);

                    $hours = isset($hours_match[1]) ? (int)$hours_match[1] : 0;
                    $minutes = isset($minutes_match[1]) ? (int)$minutes_match[1] : 0;

                    $formatted_date = date("F j, Y", strtotime($start_date));
                    $formatted_time = "{$hours}h {$minutes}m";

                    // Show the "Date" and "Time" headings above each new author
                    if ($current_author_id !== $author_id) {
                        $current_author_id = $author_id;
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($author->display_name) . '</strong></td>';
                        echo '<td></td>';
                        echo '<td><strong>Date</strong></td>';
                        echo '<td><strong>Time</strong></td>';
                        echo '<td></td>';
                        echo '</tr>';
                    }

                    echo '<tr>';
                    echo '<td></td>'; // Empty for the author column
                    
                    // Show post title only if it's the first occurrence
                    if (!isset($displayed_posts[$post_id])) {
                        $displayed_posts[$post_id] = true;
                        echo '<td><a href="' . esc_url($post_link) . '" target="_blank">' . esc_html($post_title) . '</a></td>';
                    } else {
                        echo '<td></td>'; // Empty for subsequent rows of the same post
                    }

                    echo '<td>' . esc_html($formatted_date) . '</td>';
                    echo '<td>' . esc_html($formatted_time) . '</td>';

                    // Display total time only for the first occurrence of each post
                    if (!isset($displayed_posts[$post_id . '-total'])) {
                        // Mark the post as displayed for total time
                        $displayed_posts[$post_id . '-total'] = true;

                        // Show total time
                        $total_minutes = $total_time_by_post[$post_id];
                        $total_hours = intdiv($total_minutes, 60);
                        $remaining_minutes = $total_minutes % 60;
                        $formatted_total_time = "{$total_hours}h {$remaining_minutes}m";

                        echo '<td>' . esc_html($formatted_total_time) . '</td>';
                    } else {
                        echo '<td></td>'; // Leave empty for subsequent rows of the same post
                    }

                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5">No records found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

