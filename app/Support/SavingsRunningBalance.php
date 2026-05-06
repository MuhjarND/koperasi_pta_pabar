<?php

namespace App\Support;

class SavingsRunningBalance
{
    public static function applyToMonths(array $months, array $types)
    {
        $typeKeys = self::typeKeys($types);
        $runningTypes = array_fill_keys($typeKeys, 0);

        ksort($months);

        foreach ($months as $key => $month) {
            $movementTypes = $month['types'] ?? [];

            foreach ($typeKeys as $type) {
                $runningTypes[$type] += (float) ($movementTypes[$type] ?? 0);
            }

            $months[$key]['movement_types'] = $movementTypes;
            $months[$key]['movement_total'] = (float) ($month['total'] ?? 0);
            $months[$key]['types'] = $runningTypes;
            $months[$key]['total'] = array_sum($runningTypes);
        }

        return $months;
    }

    private static function typeKeys(array $types)
    {
        if ($types === []) {
            return [];
        }

        return array_keys($types) === range(0, count($types) - 1)
            ? array_values($types)
            : array_keys($types);
    }
}
