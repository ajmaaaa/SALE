<?php

namespace Tests\Feature;

use App\Support\MonitoringRange;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Tests\TestCase;

class MonitoringRangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->disableRoleGateForPreviewBehavior();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_presets_stop_at_current_time_and_month_selection_handles_leap_year(): void
    {
        CarbonImmutable::setTestNow('2026-09-16 10:15:00');
        foreach (['realtime' => 30, 'today' => 11, 'week' => 7, 'month' => 16, 'year' => 9] as $range => $count) {
            $data = MonitoringRange::fromRequest(Request::create('/', 'GET', ['rentang' => $range]));
            $this->assertCount($count, $data['buckets']);
            $this->assertTrue($data['end']->lte(CarbonImmutable::now()));
        }
        $data = MonitoringRange::fromRequest(Request::create('/', 'GET', ['rentang' => 'monthly', 'bulan' => '2024-02']));
        $this->assertCount(29, $data['buckets']);
    }

    public function test_invalid_or_excessive_ranges_fall_back_and_valid_ranges_are_inclusive(): void
    {
        CarbonImmutable::setTestNow('2026-09-16 10:15:00');
        foreach ([['2026-09-15', '2026-09-01'], ['2020-01-01', '2026-09-16']] as [$from, $to]) {
            $data = MonitoringRange::fromRequest(Request::create('/', 'GET', ['rentang' => 'custom', 'mulai' => $from, 'akhir' => $to]));
            $this->assertNotNull($data['notice']);
            $this->assertCount(7, $data['buckets']);
        }
        $data = MonitoringRange::fromRequest(Request::create('/', 'GET', ['rentang' => 'custom', 'mulai' => '2026-09-01', 'akhir' => '2026-09-03']));
        $this->assertCount(3, $data['buckets']);
    }

    public function test_realtime_system_shows_resource_bars_without_visitor_stats_or_chart(): void
    {
        foreach ([0, 1] as $demo) {
            $response = $this->get('/admin/monitoring?detail=server&rentang=realtime&contoh='.$demo);
            $response->assertOk()
                ->assertSee('RAM saat ini')->assertSee('Beban CPU saat ini')
                ->assertSee('Penyimpanan saat ini')
                ->assertDontSee('Pengunjung unik')->assertDontSee('Kunjungan &amp; beban CPU', false)
                ->assertDontSee('30 menit terakhir')->assertDontSee('Rincian penggunaan');
            $response->assertSee('data-demo="'.($demo ? 'true' : 'false').'"', false);
        }
    }

    public function test_ai_defaults_to_per_request_usage_and_legacy_realtime_links_show_requests(): void
    {
        foreach (['', '&rentang=requests', '&rentang=realtime'] as $query) {
            foreach ([0, 1] as $demo) {
                $response = $this->get('/admin/monitoring?detail=ai&contoh='.$demo.$query);
                $response->assertOk()->assertSee('Request terbaru')->assertSee('Token input')
                    ->assertSee('Token output')->assertSee('Sisa kuota bulan ini')
                    ->assertDontSee('Tren pemakaian AI')->assertDontSee('value="realtime"', false);
                $response->assertSee($demo ? 'sale-ai-demo' : 'Belum ada riwayat request');
            }
        }
        $this->get('/admin/monitoring?detail=ai&contoh=1&rentang=month')
            ->assertOk()->assertSee('Tren pemakaian AI');
    }

    public function test_both_charts_render_all_ranges_with_honest_realtime_status(): void
    {
        foreach (['ai', 'server'] as $detail) {
            foreach (['realtime', 'today', 'week', 'month', 'year', 'monthly', 'custom'] as $range) {
                foreach ([0, 1] as $demo) {
                    $response = $this->get('/admin/monitoring?'.http_build_query(['detail' => $detail, 'rentang' => $range, 'contoh' => $demo]));
                    $response->assertOk()->assertDontSee('Tanggal acuan');
                    if ($range === 'realtime' && $detail === 'server') {
                        $response->assertSee($demo ? 'Pratinjau realtime dengan data simulasi' : 'Realtime belum terhubung');
                    }
                }
            }
        }
    }
}
