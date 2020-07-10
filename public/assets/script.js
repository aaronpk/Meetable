
$(function(){

  $(".dropdown-trigger").click(function(){
    $(this).parents(".dropdown").toggleClass("is-active");
  });

  // Add local time info into the tooltip in the event lists
  $(".event-timezone").each(function(){
    var date = new Date($(this).attr("datetime"));
    console.log("=====================");
    console.log($(this).attr("datetime"));
    console.log(date);
    var event_timezone = $(this).data("tooltip");
    console.log($(this).data("tooltip"));
    var event_time = $(this).data("event-time");
    var local_time = date_to_display_time(date);
    if(event_time != local_time) {
      local = "\n("+local_time+" in your timezone)";
    } else {
      local = "";
    }
    $(this).attr("data-tooltip", event_timezone+local);
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
    $("#photo-preview img").attr("src", ""); // blank out the previous photo
    $(".photo-popup").removeClass("active-photo");
    $(this).addClass("active-photo");
    var src = $(evt.currentTarget).attr("href");
    var source_url = $(evt.currentTarget).data("original-url");
    var author_name = $(evt.currentTarget).data("author-name");
    var alt_text = $(evt.currentTarget).data("alt-text");
    var response_id = $(evt.currentTarget).data("response-id");
    var photo_id = $(evt.currentTarget).data("photo-id");
    evt.preventDefault();
    $("#photo-preview img").attr("src", src);
    $("#photo-preview .original-source a").attr("href", source_url);
    $("#photo-preview .original-source a").text(author_name);
    $("#photo-preview .photo-alt-text").val(alt_text)
    $("#photo-preview #response_id").val(response_id)
    $("#photo-preview #photo_id").val(photo_id)
    $("#photo-preview").addClass("is-active");
  });

  $("#photo-preview .photo-alt-text").keyup(function(){
    $("#photo-preview #save-photo-alt").addClass("is-info");
  });

  $("#photo-preview #save-photo-alt").click(function(){
    $("#photo-preview .control.has-icons-right").addClass("is-loading");

    $.post("/event/"+$("#event_id").val()+"/responses/save_alt_text", {
        _token: csrf_token(),
        response_id: $("#response_id").val(),
        photo_id: $("#photo-preview #photo_id").val(),
        alt: $("#photo-preview .photo-alt-text").val()
    }, function(){
        var photo_id = $("#photo-preview #photo_id").val();
        $("a.active-photo").data("alt-text", $("#photo-preview .photo-alt-text").val());
        $("#photo-preview .control.has-icons-right").removeClass("is-loading");
        $("#photo-preview .hidden.icon").removeClass("hidden");
        $("#photo-preview #save-photo-alt").removeClass("is-info");
    });
  });

  $("#photo-preview .modal-close").click(function(){
    $("#photo-preview-img").attr("src", "");
  });

  $(".modal-close, .modal-background").click(function(){
    $(".modal").removeClass("is-active");
  });

  $(document).keyup(function(e){
    if(e.key == 'Escape') {
        $(".modal").removeClass("is-active");
    }
  });

    // Global listeners for file upload drop areas. Requires a function on the page called handleFiles()
    let dropArea = document.getElementById('drop-area');

    if(dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
          dropArea.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
          }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, function(e){
                dropArea.classList.add('active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, function(e){
                dropArea.classList.remove('active');
            }, false);
        });

        dropArea.addEventListener('drop', handleDrop, false);
    }

    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;

        handleFiles(files);
    }


});

function csrf_token() {
    return $("input[name=_token]").val();
}

function zero_pad(num) {
  num = "" + num;
  if(num.length == 1) {
    num = "0" + num;
  }
  return num;
}

function tz_minutes_to_offset(minutes) {
  var hours = zero_pad(Math.floor(Math.abs(minutes / 60)));
  var min = zero_pad(Math.abs(minutes) % 60);
  return (minutes > 0 ? '-' : '+') + hours + ":" + min;
}

function date_to_display_time(date) {
  var h = date.getHours() % 12;
  if(date.getHours() == 0)
      h = 12;
  var m = zero_pad(date.getMinutes());
  var pm = date.getHours() >= 12 ? 'pm' : 'am';
  return h+":"+m+pm;
}
