<?php

namespace App\Services\Ai;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiModelFetcher
{
    /**
     * Mengambil daftar model dari cache atau langsung dari endpoint API provider.
     */
    public static function getModels(string $provider, ?string $apiKey = null, bool $forceRefresh = false): array
    {
        $providerKey = self::normalizeProvider($provider);
        $cacheKey = 'ai_models_cache_' . $providerKey;

        // 1. Cek memory cache jika tidak dipaksa refresh
        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (! empty($cached) && is_array($cached)) {
                return $cached;
            }

            // Cek persistensi cache di database SystemSetting
            $dbCached = SystemSetting::valueFor('cached_models_' . $providerKey);
            if (! empty($dbCached)) {
                $decoded = json_decode($dbCached, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    Cache::put($cacheKey, $decoded, 86400);
                    return $decoded;
                }
            }
        }

        // 2. Kunci API: gunakan parameter yang dikirim atau ambil dari database SystemSetting
        $key = trim((string) ($apiKey ?: SystemSetting::valueFor('ai_api_key', '')));

        if (! empty($key)) {
            $fetched = self::fetchFromProvider($providerKey, $key);
            if (! empty($fetched)) {
                Cache::put($cacheKey, $fetched, 86400);
                SystemSetting::updateOrCreate(
                    ['key' => 'cached_models_' . $providerKey],
                    ['value' => json_encode($fetched, JSON_UNESCAPED_UNICODE)]
                );
                return $fetched;
            }
        }

        // 3. Fallback jika gagal fetch atau belum ada API Key
        $dbCached = SystemSetting::valueFor('cached_models_' . $providerKey);
        if (! empty($dbCached)) {
            $decoded = json_decode($dbCached, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }

        return self::getDefaultFallback($providerKey);
    }

    /**
     * Normalisasi nama provider.
     */
    public static function normalizeProvider(string $provider): string
    {
        $lower = strtolower(trim($provider));
        if (str_contains($lower, 'open')) {
            return 'openai';
        }
        if (str_contains($lower, 'deep')) {
            return 'deepseek';
        }
        return 'google';
    }

    /**
     * Panggilan langsung ke endpoint "list models" masing-masing provider.
     */
    protected static function fetchFromProvider(string $providerKey, string $key): array
    {
        try {
            if ($providerKey === 'google') {
                // Endpoint Google Gemini: GET https://generativelanguage.googleapis.com/v1beta/models
                $res = Http::withoutVerifying()->timeout(10)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->get('https://generativelanguage.googleapis.com/v1beta/models');

                if ($res->successful()) {
                    $raw = $res->json('models', []);
                    $models = [];
                    foreach ($raw as $m) {
                        $methods = $m['supportedGenerationMethods'] ?? [];
                        $name = $m['name'] ?? '';
                        $desc = strtolower(($m['displayName'] ?? '') . ' ' . ($m['description'] ?? ''));

                        // Hanya model yang mendukung generateContent
                        if (! in_array('generateContent', $methods)) {
                            continue;
                        }

                        // Hanya model keluarga Gemini & Gemma
                        if (! preg_match('/^models\/(gemini|gemma)/i', $name)) {
                            continue;
                        }

                        // Filter ketat: tolak model gambar, audio, TTS, transkripsi, video, embedding, dll yang tidak menghasilkan teks
                        if (preg_match('/(image|imagen|banana|tts|transcribe|audio|music|lyria|video|veo|embed|robotics|computer-use|aqa|customtools)/i', $name . ' ' . $desc)) {
                            continue;
                        }

                        $id = str_replace('models/', '', $name);
                        $displayName = $m['displayName'] ?? ucwords(str_replace('-', ' ', $id));
                        $models[] = [
                            'id' => $id,
                            'displayName' => "{$displayName} ({$id})",
                        ];
                    }
                    return $models;
                }
            } elseif ($providerKey === 'openai') {
                // Endpoint OpenAI: GET https://api.openai.com/v1/models
                $res = Http::withoutVerifying()->timeout(10)
                    ->withToken($key)
                    ->get('https://api.openai.com/v1/models');

                if ($res->successful()) {
                    $raw = $res->json('data', []);
                    $models = [];
                    foreach ($raw as $m) {
                        $id = $m['id'] ?? '';
                        if (preg_match('/^(gpt|o1|o3|chatgpt)/i', $id)) {
                            if (preg_match('/(embedding|audio|realtime|transcription|tts|whisper|dall-e|image|video|sora|moderation|davinci|babbage)/i', $id)) {
                                continue;
                            }
                            $models[] = [
                                'id' => $id,
                                'displayName' => $id,
                            ];
                        }
                    }
                    usort($models, fn ($a, $b) => strcmp($a['id'], $b['id']));
                    return $models;
                }
            } elseif ($providerKey === 'deepseek') {
                // Endpoint DeepSeek: GET https://api.deepseek.com/models
                $res = Http::withoutVerifying()->timeout(10)
                    ->withToken($key)
                    ->get('https://api.deepseek.com/models');

                if ($res->successful()) {
                    $raw = $res->json('data', []);
                    $models = [];
                    foreach ($raw as $m) {
                        $id = $m['id'] ?? '';
                        $displayName = $id === 'deepseek-reasoner'
                            ? 'DeepSeek-R1 (Reasoner)'
                            : ($id === 'deepseek-chat' ? 'DeepSeek-V3 (Chat)' : $id);
                        $models[] = [
                            'id' => $id,
                            'displayName' => $displayName,
                        ];
                    }
                    return $models;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal fetch models dari provider {$providerKey}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Fallback model default apabila jaringan/API key belum tersedia.
     */
    protected static function getDefaultFallback(string $providerKey): array
    {
        if ($providerKey === 'openai') {
            return [
                ['id' => 'gpt-4o', 'displayName' => 'GPT-4o (Model Multimodal Utama)'],
                ['id' => 'gpt-4o-mini', 'displayName' => 'GPT-4o Mini (Efisien & Cepat)'],
                ['id' => 'gpt-4-turbo', 'displayName' => 'GPT-4 Turbo'],
                ['id' => 'gpt-3.5-turbo', 'displayName' => 'GPT-3.5 Turbo'],
            ];
        }

        if ($providerKey === 'deepseek') {
            return [
                ['id' => 'deepseek-chat', 'displayName' => 'DeepSeek-V3 (Chat & Coding Umum)'],
                ['id' => 'deepseek-reasoner', 'displayName' => 'DeepSeek-R1 (Penalaran Logika Mendalam)'],
            ];
        }

        return [
            ['id' => 'gemini-3.6-flash', 'displayName' => 'Gemini 3.6 Flash (gemini-3.6-flash)'],
            ['id' => 'gemini-3.8-flash', 'displayName' => 'Gemini 3.8 Flash (gemini-3.8-flash)'],
            ['id' => 'gemini-2.0-flash', 'displayName' => 'Gemini 2.0 Flash (gemini-2.0-flash)'],
            ['id' => 'gemini-1.5-flash', 'displayName' => 'Gemini 1.5 Flash (gemini-1.5-flash)'],
            ['id' => 'gemini-1.5-pro', 'displayName' => 'Gemini 1.5 Pro (gemini-1.5-pro)'],
        ];
    }
}
