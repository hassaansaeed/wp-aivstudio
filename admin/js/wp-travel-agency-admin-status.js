jQuery(document).ready(function($) {
    $('.update-status-button-admin').on('click', function() {
        var leadId = $(this).data('lead-id');
        var fullName = $(this).data('lead-name');
        var email = $(this).data('lead-email');
        var phone = $(this).data('lead-phone');
        var currentStatus = $(this).data('current-status');
        var currentAgent  =  $(this).data('agent-id');
        document.getElementById('lead-id-display').textContent = leadId;
        document.getElementById('lead-full-name').textContent = fullName;
        document.getElementById('lead-email').textContent = email;
        document.getElementById('lead-phone').textContent = phone;
        
        $('#lead_id').val(leadId);
        $('#status').val(currentStatus);
        if(currentAgent != ""){
         $('#agent_id').val(currentAgent);
        }
        
        $('#update-status-popup-admin').show();
    });
        $('.show_detail').on('click', function(e) {
             e.preventDefault();
            $('#' + $(this).data("id")).slideToggle();
    });
    $('.show_comment').on('click', function(e) {
             e.preventDefault();
            $('#' + $(this).data("id")).slideToggle();
    });

    $('#close-popup-admin').on('click', function() {
        $('#update-status-popup-admin').hide();
    });

    $('#update-status-form-admin').on('submit', function(e) {

        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: wpTravelAgencystatusupdatenyadminAjax.ajax_url,
            type: 'POST',
            data: formData + '&action=update_lead_status_admin&_ajax_nonce='+ wpTravelAgencystatusupdatenyadminAjax.status_nonce,
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

    $('.delete-lead-button-admin').on('click', function(e) {
        e.preventDefault();
        
        var leadId = $(this).data('lead-id'); 
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the record. Do you really want to do this?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: wpTravelAgencystatusupdatenyadminAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_lead_admin', 
                        lead_id: leadId, 
                        _ajax_nonce: wpTravelAgencystatusupdatenyadminAjax.delete_nonce 
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                'The lead has been deleted.',
                                'success'
                            ).then(function() {
                                location.reload(); 
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.data.message,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'There was an issue processing your request.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
