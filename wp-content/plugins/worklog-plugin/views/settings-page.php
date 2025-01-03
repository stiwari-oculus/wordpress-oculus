<div class="wrap">
    <h1 class="mb-4">Worklog Details</h1>
    <form method="get" class="mb-4">
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

        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>">
        
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>">
        
        <button type="submit" class="button button-primary">Search</button>
    </form>

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th scope="col">Author</th>
                <th scope="col">Post Title</th>
                <th scope="col">Date</th>
                <th scope="col">Time Logged</th>
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
                    $start_date = $row['start_date'];
                    $post_link = get_permalink($post_id);
                    $author = get_user_by('id', $author_id);

                    // Convert the start_date to "December 24, 2024"
                    $formatted_date = date("F j, Y", strtotime($start_date));

                    // Convert time spent (e.g., "1h 30m") to a readable format
                    preg_match('/(\d+)h/', $time_spent, $hours_match);
                    preg_match('/(\d+)m/', $time_spent, $minutes_match);

                    $hours = isset($hours_match[1]) ? (int)$hours_match[1] : 0;
                    $minutes = isset($minutes_match[1]) ? (int)$minutes_match[1] : 0;
                    $formatted_time = "{$hours}h {$minutes}m";

                    // Show author name only for the first row
                    if ($current_author_id !== $author_id) {
                        $current_author_id = $author_id;
                        echo '<tr>';
                        echo '<td>' . esc_html($author->display_name) . '</td>';
                    } else {
                        echo '<tr>';
                        echo '<td></td>'; // Skip duplicate author names
                    }

                    echo '<td><a href="' . esc_url($post_link) . '" target="_blank">' . esc_html($post_title) . '</a></td>';
                    echo '<td>' . esc_html($formatted_date) . '</td>';
                    echo '<td>' . esc_html($formatted_time) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4">No records found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
