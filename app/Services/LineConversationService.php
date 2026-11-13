<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use RuntimeException;
use Throwable;

class LineConversationService
{
    private const GROUP_RECEIVER_PREFIX = 'cohort:';

    /**
     * Push a message through LINE, then write the successful delivery to the
     * existing log_message table. The cohort is a virtual group, so LINE uses
     * multicast while the database keeps one group-level history entry.
     */
    public function send(string $doctorId, string $type, string $targetId, string $message): array
    {
        $doctor = DB::table('doctor')
            ->where('doctor_id', $doctorId)
            ->whereNull('deleted_at')
            ->first(['doctor_id', 'name', 'lastname']);

        if (!$doctor) {
            throw new RuntimeException('ไม่พบบัญชีแพทย์', 404);
        }

        $recipients = $this->recipients($doctorId, $type, $targetId);

        if ($recipients === []) {
            throw new RuntimeException('ไม่พบผู้รับข้อความในบทสนทนานี้', 422);
        }

        $accessToken = (string) config('line.access_token', '');
        $channelSecret = (string) config('line.channel_secret', '');

        if ($accessToken === '' || $channelSecret === '') {
            throw new RuntimeException('ยังไม่ได้ตั้งค่า LINE Messaging API', 503);
        }

        try {
            $bot = new LINEBot(new CurlHTTPClient($accessToken), [
                'channelSecret' => $channelSecret,
            ]);
            $lineMessage = new TextMessageBuilder('👩‍⚕ : ' . $message);
            $linePayload = [
                'to' => $type === 'group' ? $recipients : $recipients[0],
                'messages' => $lineMessage->buildMessage(),
                'notificationDisabled' => false,
            ];

            Log::info('LINE conversation API request', [
                'doctor_id' => $doctorId,
                'conversation_type' => $type,
                'target_id' => $targetId,
                'recipient_count' => count($recipients),
                'endpoint' => $type === 'group'
                    ? '/v2/bot/message/multicast'
                    : '/v2/bot/message/push',
                'payload' => $linePayload,
            ]);

            $response = $type === 'group'
                ? $bot->multicast($recipients, $lineMessage)
                : $bot->pushMessage($recipients[0], $lineMessage);
        } catch (Throwable $exception) {
            Log::error('LINE conversation connection failed', [
                'doctor_id' => $doctorId,
                'conversation_type' => $type,
                'target_id' => $targetId,
                'recipient_count' => count($recipients),
                'exception' => $exception->getMessage(),
            ]);
            report($exception);
            throw new RuntimeException('เชื่อมต่อ LINE ไม่สำเร็จ กรุณาลองใหม่', 502, $exception);
        }

        if (!$response->isSucceeded()) {
            Log::error('LINE conversation send rejected', [
                'doctor_id' => $doctorId,
                'conversation_type' => $type,
                'target_id' => $targetId,
                'recipient_count' => count($recipients),
                'http_status' => $response->getHTTPStatus(),
                'line_response' => $response->getRawBody(),
            ]);
            report(new RuntimeException(sprintf(
                'LINE message failed (%s): %s',
                $response->getHTTPStatus(),
                $response->getRawBody()
            )));

            throw new RuntimeException('LINE ไม่สามารถส่งข้อความได้ กรุณาลองใหม่', 502);
        }

        $sentAt = now();
        DB::table('log_message')->insert([
            'sender_id' => $doctorId,
            'receiver_id' => $type === 'group'
                ? self::groupReceiverId($doctorId)
                : $recipients[0],
            'message_type' => '03',
            'message' => $message,
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ]);

        Log::info('LINE conversation sent and logged', [
            'doctor_id' => $doctorId,
            'conversation_type' => $type,
            'target_id' => $targetId,
            'recipient_count' => count($recipients),
            'http_status' => $response->getHTTPStatus(),
            'log_receiver_id' => $type === 'group'
                ? self::groupReceiverId($doctorId)
                : $recipients[0],
            'sent_at' => $sentAt->toIso8601String(),
        ]);

        return [
            'sender' => trim($doctor->name . ' ' . $doctor->lastname),
            'time' => $sentAt->format('H:i'),
            'body' => $message,
            'direction' => 'outgoing',
            'tone' => 'info',
            'delivered_count' => count($recipients),
        ];
    }

    public static function groupReceiverId(string $doctorId): string
    {
        return self::GROUP_RECEIVER_PREFIX . $doctorId;
    }

    private function recipients(string $doctorId, string $type, string $targetId): array
    {
        if ($type === 'direct') {
            $isAssigned = DB::table('users_register as patient')
                ->where('patient.user_id', $targetId)
                ->whereNull('patient.deleted_at')
                ->whereExists(function ($query) use ($doctorId) {
                    $query->selectRaw('1')
                        ->from('personal_doctor_mom as assignment')
                        ->whereColumn('assignment.user_id', 'patient.user_id')
                        ->where('assignment.doctor_id', $doctorId)
                        ->whereNull('assignment.deleted_at');
                })
                ->exists();

            if (!$isAssigned) {
                throw new RuntimeException('ไม่มีสิทธิ์ส่งข้อความถึงผู้รับบริการรายนี้', 403);
            }

            return [$targetId];
        }

        if ($type !== 'group' || !in_array($targetId, ['doctor-patient-cohort', $doctorId], true)) {
            throw new RuntimeException('ประเภทบทสนทนาไม่ถูกต้อง', 422);
        }

        $recipients = DB::table('users_register as patient')
            ->join('personal_doctor_mom as assignment', 'assignment.user_id', '=', 'patient.user_id')
            ->where('assignment.doctor_id', $doctorId)
            ->whereNull('assignment.deleted_at')
            ->whereNull('patient.deleted_at')
            ->distinct()
            ->pluck('patient.user_id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();

        if (count($recipients) > 500) {
            throw new RuntimeException('กลุ่มมีสมาชิกเกิน 500 คน กรุณาแบ่งกลุ่มก่อนส่ง', 422);
        }

        return $recipients;
    }
}
