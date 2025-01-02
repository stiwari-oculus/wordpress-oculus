jQuery(document).ready(function($) {
    $('.datetimepicker').datetimepicker();

    $('#save_worklog').on('click', function(e) {
        e.preventDefault();

        var data = {
            action: 'save_worklog',
            security: worklogAjax.nonce,
            post_id: $('#post_ID').val(),
            time_spent: $('#time_spent').val(),
            start_date: $('#start_date').val(),
            purpose: $('#purpose').val(),
            notes: $('#notes').val(),
        };

        $.post(worklogAjax.ajax_url, data, function(response) {
            if (response.success) {
                alert(response.data);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
