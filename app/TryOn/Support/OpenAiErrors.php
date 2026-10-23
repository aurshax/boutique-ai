<?php

namespace App\TryOn\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI xato javobi → foydalanuvchiga tushunarli o'zbekcha xabar.
 * Log'ga faqat status/kod/tur yoziladi — xabar matni emas (unda kalitning bir qismi bo'lishi mumkin).
 * Try-on provider'i ham, katalog rasm generatori ham shuni ishlatadi.
 */
final class OpenAiErrors
{
    public static function message(Response $response, string $context): string
    {
        $code = (string) $response->json('error.code', '');
        $type = (string) $response->json('error.type', '');

        Log::warning("OpenAI {$context} xatosi", ['status' => $response->status(), 'code' => $code, 'type' => $type]);

        return match (true) {
            $response->status() === 401 => 'OpenAI kaliti noto\'g\'ri yoki o\'chirilgan. Kalitni tekshirib, qayta kiriting.',
            $code === 'insufficient_quota', $code === 'billing_hard_limit_reached' => 'OpenAI hisobingizda mablag\' yoki limit tugagan. platform.openai.com → Billing / Limits.',
            $response->status() === 429 => 'OpenAI so\'rovlar chegarasiga yetdi. Bir daqiqadan keyin qayta urinib ko\'ring.',
            $response->status() === 403 => 'Kalitga bu modeldan foydalanishga ruxsat yo\'q (OpenAI tashkilot tasdiqlashi yoki project ruxsatlari kerak bo\'lishi mumkin).',
            $code === 'moderation_blocked', $code === 'content_policy_violation' => 'OpenAI bu rasmni xavfsizlik qoidalari bo\'yicha qayta ishlamadi. Boshqa rasm bilan urinib ko\'ring.',
            $response->status() === 400 => 'OpenAI so\'rovni qabul qilmadi. Boshqa rasm bilan urinib ko\'ring.',
            $response->serverError() => 'OpenAI serverida vaqtinchalik xato. Birozdan keyin qayta urinib ko\'ring.',
            default => 'OpenAI xatosi ('.$response->status().').',
        };
    }
}
