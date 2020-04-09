@extends('layouts/main')

@section('content')

<section class="section">

<form action="{{ route('upload-event-photo', $event) }}" method="post" enctype="multipart/form-data" class="event-form">

    <h2 class="title">Add a Photo</h2>

    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>

    <br>

    <div id="photo-preview" class="has-delete">
        <button class="delete"></button>
        <img src="" width="720" height="320">
    </div>

    <div class="field">
        <div class="file is-boxed">
            <label class="file-label" style="width: 100%">
                <input id="file-input-field" class="file-input" type="file" name="photo" accept=".jpg,image/jpeg,.png,image/png">
                <span class="file-cta" id="drop-area">
                    <span class="file-icon">@icon(upload)</span>
                    <span class="file-label">Choose a photo...</span>
                </span>
                <span class="file-name hidden" style="width: 100%; max-width: 100%;"></span>
            </label>
        </div>
    </div>

    <div class="field">
        <textarea class="textarea photo-alt-text" name="alt" rows="3" placeholder="alt text"></textarea>
    </div>


    <button class="button is-primary" type="submit" disabled>Add Photo</button>

    {{ csrf_field() }}
</form>

<script>

function handleFiles(files) {
    let input = document.getElementById('file-input-field');
    input.files = files;
    $(".file-input").change();
}

$(".file-input").on("change", function(evt){
    $(".file-name").text(evt.target.files[0].name).removeClass("hidden");
    $("#photo-preview img").attr("src", URL.createObjectURL(evt.target.files[0]));
    $(".file").addClass("has-name");
    $(".button.is-primary").removeAttr("disabled");
});

</script>

</section>

@endsection

