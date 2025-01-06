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

    // Reset form fields on Reset button click
    $('#reset_button').on('click', function() {
        $('#worklog-form')[0].reset(); // Reset all form fields

    
        $('select[name="author_id"]').val(''); // Reset author dropdown to default option
        
        $('#start_date').val(''); 
        $('#end_date').val('').attr('min', '');

        // Optionally reset the "To Date" field to allow any date
        $('#end_date').removeAttr('min'); 
    });

    $('#start_date').on('change', function() {
        // Get the selected "From Date"
        var fromDate = $(this).val();
        
        // Set the min value of the "To Date" field to the selected "From Date"
        $('#end_date').attr('min', fromDate);
        $('#end_date').val(fromDate);
    });
});
