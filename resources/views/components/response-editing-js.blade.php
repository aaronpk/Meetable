<script>
$(function(){

    $(".delete-response").click(function(evt){
        evt.preventDefault();
        $.post($(evt.currentTarget).attr("href"), {
            _token: csrf_token()
        }, function(response){
            $("#response-"+response.response_id).remove();
            $(".pending-response-count").text(response.pending);
        });
    });

    $(".approve-response").click(function(evt){
        evt.preventDefault();
        $.post($(evt.currentTarget).attr("href"), {
            _token: csrf_token()
        }, function(response){
            $("#response-"+response.response_id).remove();
            $(".pending-response-count").text(response.pending);
        });
    });

    $(".view-response-details").click(function(evt){
        evt.preventDefault();

        $(this).parents(".dropdown").removeClass("is-active");

        $.get($(evt.currentTarget).attr("href"), function(response){
            ['created_at','updated_at','url','source_url','published','post_type','data',
             'author_name','author_photo','author_url','name','content_text','rsvp'].forEach(function(field){
                if(response[field]) {
                    $("#response-"+field).val(response[field]);
                    $("#response-"+field).parents(".field").removeClass("hidden");
                } else {
                    $("#response-"+field).parents(".field").addClass("hidden");
                }
            });
            if(response.photos.length > 0) {
                $("#response-photos").val(response.photos.map(function(p){ return p.original_url; }).join("\n\n"));
                $("#response-photos").parents(".field").removeClass("hidden");
            } else {
                $("#response-photos").val("");
                $("#response-photos").parents(".field").addClass("hidden");
            }
            $("#response-details").addClass("is-active");
        });
    });

});
</script>
