<?php

namespace Tests\Feature;

use App\Services\LineConversationService;
use Mockery;
use Tests\TestCase;

class SendConversationMessageTest extends TestCase
{
    public function test_logged_in_doctor_can_send_a_conversation_message(): void
    {
        $lineMessages = Mockery::mock(LineConversationService::class);
        $lineMessages->shouldReceive('send')
            ->once()
            ->with('doctor-1', 'direct', 'patient-1', 'สวัสดีค่ะ')
            ->andReturn([
                'sender' => 'คุณหมอ ทดสอบ',
                'time' => '10:15',
                'body' => 'สวัสดีค่ะ',
                'direction' => 'outgoing',
                'tone' => 'info',
                'delivered_count' => 1,
            ]);
        $this->app->instance(LineConversationService::class, $lineMessages);

        $response = $this->withSession(['doctor_id' => 'doctor-1'])
            ->postJson(route('conversations.messages.store'), [
                'type' => 'direct',
                'target_id' => 'patient-1',
                'message' => 'สวัสดีค่ะ',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'สวัสดีค่ะ')
            ->assertJsonPath('data.delivered_count', 1);
    }

    public function test_conversation_message_requires_a_logged_in_doctor(): void
    {
        $this->postJson(route('conversations.messages.store'), [
            'type' => 'group',
            'target_id' => 'doctor-patient-cohort',
            'message' => 'แจ้งเตือนค่ะ',
        ])->assertUnauthorized();
    }

    public function test_conversation_message_is_limited_to_log_column_length(): void
    {
        $this->withSession(['doctor_id' => 'doctor-1'])
            ->postJson(route('conversations.messages.store'), [
                'type' => 'direct',
                'target_id' => 'patient-1',
                'message' => str_repeat('ก', 256),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }
}
