jQuery(document).ready(function($) {
    $('#wp_travel_agency_post_type').on('change', function() {
        var postType = $(this).val();

        if (postType === '') {
            $('#wp_travel_agency_form_id').html('<option value="">Select a Post</option>');
            return;
        }

        $.ajax({
            url: wpTravelAgencyAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_travel_agency_get_posts_by_type',
                post_type: postType,
                nonce: wpTravelAgencyAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var posts = response.data.posts;
                    var options = '<option value="">Select a Post</option>';

                    posts.forEach(function(post) {
                        options += '<option value="' + post.ID + '">' + post.post_title + '</option>';
                    });

                    $('#wp_travel_agency_form_id').html(options);
                }
            }
        });
    });
});


