<?php

namespace Tests\Unit;

use App\Support\SavingsRunningBalance;
use PHPUnit\Framework\TestCase;

class SavingsRunningBalanceTest extends TestCase
{
    public function testItShowsRunningBalanceWhilePreservingMonthlyMovement()
    {
        $types = ['pokok', 'wajib', 'sukarela'];
        $months = [
            '2025-12' => [
                'types' => [
                    'pokok' => 400000,
                    'wajib' => 400000,
                    'sukarela' => 400000,
                ],
                'total' => 1200000,
            ],
            '2026-01' => [
                'types' => [
                    'pokok' => 0,
                    'wajib' => 100000,
                    'sukarela' => -200000,
                ],
                'total' => -100000,
            ],
        ];

        $result = SavingsRunningBalance::applyToMonths($months, $types);

        $this->assertSame(200000.0, $result['2026-01']['types']['sukarela']);
        $this->assertEquals(-200000.0, $result['2026-01']['movement_types']['sukarela']);
    }
}
