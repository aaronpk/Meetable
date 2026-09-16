@extends('layouts/main')

@section('content')
<section class="section">

    <div class="content">
        <h2 class="title">{{ __('events.subscribe.title') }}</h2>

        <form class="event-form">
            <div class="field">
                <input class="input" type="url" name="url" autocomplete="off" readonly="readonly" value="{{ $url }}">
            </div>

            <p>{{ __('events.subscribe.instructions') }}</p>
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
