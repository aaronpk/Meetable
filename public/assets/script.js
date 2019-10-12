
$(function(){

$(".dropdown-trigger").click(function(){
    $(this).parents(".dropdown").toggleClass("is-active");
});

$(".delete-event").click(function(evt){
    evt.preventDefault();
    $.post($(this).attr('href'), {
        _token: $("input[name=_token]").val()
    }, function(response){
        window.location = '/';
    });
});

});

