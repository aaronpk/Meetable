@extends('layouts/main')

@section('content')
<section class="section">

    <div class="content">
        <h2 class="title">Subscribe</h2>

        <form class="event-form">
            <div class="field">
                <input class="input" type="url" name="url" autocomplete="off" readonly="readonly" value="{{ $url }}">
            </div>

            <p>Copy the URL above and subscribe to it in your favorite calendar app!</p>
        </form>

    </div>

</section>
<script>
$(function(){
    $("input[name=url]").on("click", function(){
        $(this).select();
    });
})
</script>
@endsection
