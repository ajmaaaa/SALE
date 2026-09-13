<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssessmentAndSettingsTest extends TestCase
{
    public function test_all_assessment_tabs_render_without_binary_and_without_cpmk_badge(): void
    {
        foreach (['tugas', 'uts', 'uas', 'quiz', 'project'] as $type) {
            $response = $this->get('/dosen/penilaian?course=1&room=1&type=' . $type);
            $response->assertOk();
            // Verify icon & text "Penilaian berbasis poin dan CPMK" is gone
            $response->assertDontSee('Penilaian berbasis poin dan CPMK');
            // Verify tab is reverted back to "Pengaturan CPMK"
            $response->assertSee('Pengaturan CPMK');
            $response->assertDontSee('Atur Bobot Komponen (%)');
            $response->assertDontSee('(⚙️ Ubah Bobot %)');
            // Verify table headers display clean CPMK weights (% MK) and 0-100 scale
            $response->assertSee('Bobot:');
            $response->assertSee('% MK');
            $response->assertSee('0–100');
        }

        // On tugas tab:
        $tugasRes = $this->get('/dosen/penilaian?course=1&room=1&type=tugas');
        $tugasRes->assertOk();
        // Verify Praktikum Binary Tree is NOT present in assessments
        $tugasRes->assertDontSee('Praktikum Binary Tree');
        // Verify Tugas 1, Tugas 2, Tugas 3 ARE present
        $tugasRes->assertSee('Tugas 1');
        $tugasRes->assertSee('Tugas 2');
        $tugasRes->assertSee('Tugas 3');
    }

    public function test_academic_settings_page_renders_cleanly_with_weights_prominent(): void
    {
        $response = $this->get('/dosen/course/1/akademik');
        $response->assertOk();
        $response->assertSee('Perencanaan & penilaian', false);
        $response->assertSee('Capaian pembelajaran lulusan (CPL)');
        $response->assertSee('Capaian pembelajaran mata kuliah (CPMK)');
        $response->assertSee('Komponen &amp; bobot nilai', false);
        $response->assertSee('data-weight-total', false);
    }
}
