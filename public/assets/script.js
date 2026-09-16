
$(function(){

  $(".dropdown-trigger").click(function(){
    $(this).parents(".dropdown").toggleClass("is-active");
  });

  // Add local time info into the tooltip in the event lists
  $(".event-localize-date").each(function(){
    var date = new Date($(this).attr("datetime"));
    var end = $(this).data("end") ? new Date($(this).data("end")) : null;
    var event_time = $(this).data("event-time");
    var local_time;
    var tooltip="";
    if($(this).data("dateformat") == "dateonly") {
        local_time = date_to_display_date(date, end);
    } else if($(this).data("dateformat") == "timeonly") {
        local_time = date_to_display_time(date, end);
    } else {
        local_time = date_to_display_datetime(date);
    }
    if($(this).hasClass("is-virtual-event")) {
      tooltip = lang("in_event_timezone", {date: $(this).data("original-date").replace(/\s+/g," ").trim()})+"\n"+$(this).data("timezone");
      $(this).text(local_time);
    } else {
      tooltip = $(this).data("timezone")+"\n"+lang("in_your_timezone", {date: date_to_display_datetime(date)});
    }
    if($(this).data("show-tooltip") != false) {
        $(this).attr("data-tooltip", tooltip);
    }
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

  // Voting on the dates of a proposed event. Clicking the answer already given clears it.
  $("#date-poll").on("click", ".vote-button", function(evt){
    evt.preventDefault();
    var $button = $(this);
    var $row = $button.closest(".poll-option");
    var $poll = $("#date-poll");
    var vote = $button.hasClass("is-pressed") ? "" : $button.attr("data-vote");

    $row.find(".vote-button").prop("disabled", true);
    $.ajax({
      url: $poll.attr("data-action"),
      method: "POST",
      dataType: "json",
      data: {
        _token: csrf_token(),
        option_id: $row.attr("data-option-id"),
        vote: vote
      },
      success: function(response){
        update_poll($poll, response);
      },
      error: function(){
        alert(lang("vote_failed"));
      },
      complete: function(){
        $row.find(".vote-button").prop("disabled", false);
      }
    });
  });

  // Fills in the counts, voters and leading date from the vote endpoint's response
  function update_poll($poll, response) {
    $.each(response.options, function(id, option){
      var $row = $poll.find('.poll-option[data-option-id="'+id+'"]');
      $.each(["yes", "ifneedbe", "no"], function(_, vote){
        var $cell = $row.find(".count-"+vote);
        $cell.find(".number").text(option[vote]);
        var $voters = $cell.find(".voters").empty();
        $.each(option.voters[vote] || [], function(_, voter){
          var $img = $("<img>", {
            "class": "vote-avatar",
            src: voter.photo || "/images/placeholder.png",
            alt: voter.name,
            title: voter.name,
            width: 20,
            height: 20
          });
          if(voter.url) {
            $voters.append($("<a>", {href: voter.url}).append($img));
          } else {
            $voters.append($img);
          }
        });
      });
      $row.find(".vote-button").removeClass("is-pressed");
      if(option.user_vote) {
        $row.find('.vote-button[data-vote="'+option.user_vote+'"]').addClass("is-pressed");
      }
    });
    $poll.find(".poll-option").removeClass("is-leading");
    if(response.leading_option_id) {
      $poll.find('.poll-option[data-option-id="'+response.leading_option_id+'"]').addClass("is-leading");
    }
  }

  $(".tabs li").click(function(){
    $(".tab-content").addClass("hidden");
    $(".tabs li").removeClass("is-active");
    $(this).addClass("is-active");
    $("#tab-"+$(this).data("tab")).removeClass("hidden");
  });
  $(".tabs li.is-active").click();

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

// Looks up text from resources/lang/{locale}/js.php and fills in :placeholders
function lang(key, replacements) {
  var text = (window.Meetable && window.Meetable.lang && window.Meetable.lang[key]) || key;
  Object.keys(replacements || {}).forEach(function(name){
    text = text.split(":"+name).join(replacements[name]);
  });
  return text;
}

// Dates are shown in the site's language, using that language's usual clock
function page_locale() {
  return document.documentElement.lang || [];
}

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

function date_to_display_datetime(date) {
  return date.toLocaleString(page_locale(), {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour:'numeric',
    minute: '2-digit'
  });
}

// Formats a start time, or a range like "6:30 – 8:00 PM" when there is an end
function date_to_display_time(date, end) {
  var format = new Intl.DateTimeFormat(page_locale(), {
    hour:'numeric',
    minute: '2-digit'
  });
  if(!end) {
    return format.format(date);
  }
  if(format.formatRange) {
    return format.formatRange(date, end);
  }
  return format.format(date) + " – " + format.format(end);
}

// Formats a date with its weekday. With an end, this is one date when the event starts
// and ends on the same day where the viewer is, or a range when it crosses midnight.
function date_to_display_date(date, end) {
  var format = new Intl.DateTimeFormat(page_locale(), {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  if(end && format.formatRange) {
    return format.formatRange(date, end);
  }
  return format.format(date);
}
