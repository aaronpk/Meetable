<div class="modal" id="response-details">
    <div class="modal-background"></div>
    <div class="modal-card">
        <div class="modal-card-body" style="border-radius: 8px;">

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.author_name') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-author_name" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.author_photo') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-author_photo" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.author_url') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="url" id="response-author_url" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.name') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-name" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.content') }}</label>
                </div>
                <div class="field-body">
                    <textarea class="textarea" id="response-content_text" rows="4" readonly></textarea>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.rsvp') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-rsvp" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.photos') }}</label>
                </div>
                <div class="field-body">
                    <textarea class="textarea" id="response-photos" rows="4" readonly></textarea>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.url') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="url" id="response-url" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.source_url') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="url" id="response-source_url" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.post_type') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-post_type" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.published_at') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-published" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.created_at') }}</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-created_at" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">{{ __('responses.details.parsed_data') }}</label>
                </div>
                <div class="field-body">
                    <textarea class="textarea" id="response-data" rows="6" readonly></textarea>
                </div>
            </div>



        </div>
    </div>
    <button class="modal-close is-large" aria-label="{{ __('common.close') }}"></button>
</div>
