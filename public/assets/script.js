
$(function(){

  $(".dropdown-trigger").click(function(){
    $(this).parents(".dropdown").toggleClass("is-active");
  });

  // Check for click events on the navbar burger icon
  $(".navbar-burger").click(function() {
    // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
    $(".navbar-burger").toggleClass("is-active");
    $(".navbar-menu").toggleClass("is-active");
  });

  $(".delete-event").click(function(evt){
    evt.preventDefault();
    $.post($(this).attr('href'), {
        _token: $("input[name=_token]").val()
    }, function(response){
        window.location = '/';
    });
  });

  $("#rsvp-button").click(function(evt){
    evt.preventDefault();
    $.post($(this).data('action'), {
        _token: $("input[name=_token]").val(),
        rsvp: $(this).hasClass('is-pressed') ? 0 : 1
    }, function(response){
        window.location = response.redirect;
    });
  });

  $("#rsvp-delete").click(function(evt){
    evt.preventDefault();
    $.post($(this).data('action'), {
        _token: $("input[name=_token]").val()
    }, function(response){
        window.location = response.redirect;
    });
  });

  $(".photo-popup").click(function(evt){
    var src = $(evt.currentTarget).attr("href");
    var source_url = $(evt.currentTarget).data("original-url");
    var author_name = $(evt.currentTarget).data("author-name");
    evt.preventDefault();
    $("#photo-preview img").attr("src", src);
    $("#photo-preview .original-source a").attr("href", source_url);
    $("#photo-preview .original-source a").text(author_name);
    $("#photo-preview").addClass("is-active");
  });

  $("#photo-preview .modal-close").click(function(){
    $("#photo-preview-img").attr("src", "");
    $("#photo-preview").removeClass("is-active");
  });

  $(document).keyup(function(e){
    if(e.key == 'Escape') {
        $(".modal").removeClass("is-active");
    }
  });

});

