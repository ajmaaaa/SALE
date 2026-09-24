<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class MonitoringRange
{
    public static function fromRequest(Request $request): array
    {
        $now = CarbonImmutable::now();
        $today = $now->startOfDay();
        $choices = ['realtime' => 'Realtime', 'today' => 'Hari ini', 'week' => '7 hari terakhir', 'month' => 'Bulan ini', 'year' => 'Tahun ini', 'monthly' => 'Pilih bulan', 'custom' => 'Rentang tanggal'];
        $range = $request->query('rentang', 'month');
        $range = is_string($range) && isset($choices[$range]) ? $range : 'month';
        $parseDate = function ($value) {
            if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                return null;
            }

            return CarbonImmutable::parse($value)->startOfDay();
        };
        $monthInput = $request->query('bulan');
        $month = is_string($monthInput) ? $parseDate($monthInput.'-01') : null;
        $month = ($month ?? $today)->min($today)->startOfMonth();
        $from = $parseDate($request->query('mulai')) ?? $today->subDays(6);
        $to = ($parseDate($request->query('akhir')) ?? $today)->min($today);
        $notice = null;
        if ($from->gt($to) || $from->diffInDays($to) > 366) {
            $from = $to->subDays(6);
            if ($range === 'custom') {
                $notice = 'Pilih tanggal mulai sebelum tanggal akhir, maksimal 366 hari. Sementara ditampilkan 7 hari terakhir.';
            }
        }
        [$start, $end, $step] = match ($range) {
            'realtime' => [$now->startOfMinute()->subMinutes(29), $now->startOfMinute(), 'minute'],
            'today' => [$today, $now->startOfHour(), 'hour'],
            'week' => [$today->subDays(6), $today, 'day'],
            'year' => [$today->startOfYear(), $today->startOfMonth(), 'month'],
            'monthly' => [$month, $month->endOfMonth()->startOfDay()->min($today), 'day'],
            'custom' => [$from, $to, $from->diffInDays($to) > 62 ? 'month' : 'day'],
            default => [$today->startOfMonth(), $today, 'day'],
        };
        $buckets = [];
        for ($cursor = $step === 'month' ? $start->startOfMonth() : $start; $cursor->lte($end); $cursor = match ($step) {
            'minute' => $cursor->addMinute(), 'hour' => $cursor->addHour(), 'month' => $cursor->addMonth(), default => $cursor->addDay()
        }) {
            $buckets[] = $cursor;
        }
        $caption = $range === 'realtime' ? '30 menit terakhir' : ($range === 'today' ? $today->translatedFormat('d F Y') : $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'));
        $granularity = match ($step) {
            'minute' => 'Per menit', 'hour' => 'Per jam', 'month' => 'Per bulan', default => 'Per hari'
        };
        $label = fn ($date) => match ($step) {
            'minute', 'hour' => $date->format('H:i'), 'month' => $date->translatedFormat('M Y'), default => $date->format('d M')
        };

        return compact('choices', 'range', 'start', 'end', 'step', 'buckets', 'caption', 'granularity', 'label', 'notice', 'month', 'from', 'to');
    }
}
