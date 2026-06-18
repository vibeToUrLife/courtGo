<?php

namespace App\Concerns;

trait HandlesScheduleTimes
{
    /**
     * Minutes since midnight for a "H:i" or "H:i:s" time.
     *
     * An END time of 00:00 is treated as end-of-day (1440) so a slot can finish
     * exactly at midnight (e.g. 20:00 → 00:00) without looking like it runs
     * backwards. Any other time keeps its real value, so a slot that would cross
     * past midnight (e.g. 23:00 → 01:00) is still rejected.
     */
    protected function slotMinutes(string $time, bool $isEnd = false): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));
        $total = ($hours * 60) + $minutes;

        return ($isEnd && $total === 0) ? 24 * 60 : $total;
    }
}
