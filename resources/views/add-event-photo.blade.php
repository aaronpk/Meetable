@extends('layouts/main')

@section('content')

<section class="section">

<form action="{{ route('upload-event-photo', $event) }}" method="post" enctype="multipart/form-data">

    <h2 class="title">Add a Photo</h2>

    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>

    <br>

    <div class="field">
        <div class="file is-boxed">
            <label class="file-label">
                <input id="file-input-field" class="file-input" type="file" name="photo" accept=".jpg,image/jpeg">
                <span class="file-cta" id="drop-area">
                    <span class="file-icon">@icon(upload)</span>
                    <span class="file-label">Choose a photo...</span>
                </span>
                <span class="file-name hidden"></span>
            </label>
        </div>
    </div>

    <button class="button is-primary" type="submit" disabled>Add Photo</button>

    {{ csrf_field() }}
</form>

<script>

let dropArea = document.getElementById('drop-area');

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

function handleDrop(e) {
    let dt = e.dataTransfer;
    let files = dt.files;

    handleFiles(files);
}

function handleFiles(files) {
    console.log(files);
    let input = document.getElementById('file-input-field');
    input.files = files;
    $(".file-input").change();
}

$(".file-input").on("change", function(evt){
    $(".file-name").text(evt.target.files[0].name).removeClass("hidden");
    $(".file").addClass("has-name");
    $(".button.is-primary").removeAttr("disabled");
});

</script>

<style>
#drop-area.active {
    background-color: #e4e4e4;
}
</style>

</section>

@endsection

