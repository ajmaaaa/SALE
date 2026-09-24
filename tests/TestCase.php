<?php

namespace Tests;

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function disableRoleGateForPreviewBehavior(): void
    {
        $this->withoutMiddleware(EnsureRole::class);
    }
}
