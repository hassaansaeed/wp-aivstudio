jQuery(document).ready(function($) {
    $('#service_type').select2({
        placeholder: 'Select Service Types',
        allowClear: true
    });
    $('.update-status-button').on('click', function() {
        var leadId = $(this).data('lead-id');
        var fullName = $(this).data('lead-name');
        var email = $(this).data('lead-email');
        var phone = $(this).data('lead-phone');
        var currentStatus = $(this).data('current-status');
        document.getElementById('lead-id-display').textContent = leadId;
        document.getElementById('lead-full-name').textContent = fullName;
        document.getElementById('lead-email').textContent = email;
        document.getElementById('lead-phone').textContent = phone;
        
        $('#lead_id').val(leadId);
        $('#status').val(currentStatus);
        $('#update-status-popup').show();
    });

    $('#close-popup').on('click', function() {
        $('#update-status-popup').hide();
    });

    $('#update-status-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: wpTravelAgencyagentajax.ajax_url,
            type: 'POST',
            data: formData + '&action=update_lead_status&_ajax_nonce='+ wpTravelAgencyagentajax.status_nonce_agent,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message
                    }).then(function() {
                        location.reload(); 
                    });
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message
                    });
                }
            }
        });
    });
});
