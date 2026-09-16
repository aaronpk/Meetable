<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Creating events from forwarded calendar invites was removed because the webhook
 * trusted the From header of whatever was posted to it.
 */
class InboundEmailRemovedTest extends TestCase
{
    public function testTheInboundEmailWebhookIsGone()
    {
        $this->post('/email/sendgrid', ['email' => "From: someone@example.com\r\n\r\nhello"])
            ->assertNotFound();
    }
}
