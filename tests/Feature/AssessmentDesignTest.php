<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssessmentDesignTest extends TestCase
{
    public function test_old_prototype_assessment_route_is_removed(): void
    {
        $response = $this->withSession(['auth_user' => ['role' => 'dosen', 'name' => 'Budi']])
            ->get('/dosen/penilaian?room=1&course=1&type=uts');

        $response->assertNotFound();
    }
}
