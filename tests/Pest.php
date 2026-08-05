<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class, RefreshDatabase::class)->in('Unit');

// The module-system tests scaffold real modules on disk (Modules/ScaffoldProbe).
// Paratest isolates the database but not that shared filesystem path, so run this
// group serially after the parallel pass to avoid cross-worker collisions.
uses()->group('modules')->in('Feature/Company/Modules');

// Architecture assertions parse broad namespace graphs and retain that graph
// for the life of a worker. Run them in the serial phase so an ordinary feature
// test is not handed the parser's memory footprint in the same 128 MB process.
uses()->group('architecture')->in('Unit/Architecture', 'Feature/Architecture');
