<?php

namespace App\Services;

use App\Models\Court;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * The bookable sessions for a court on a given date.
     *
     * Availability =
     *     active session templates for that weekday
     *   − dates the court is blocked
     *   − sessions whose start time has already passed (when the date is today)
     *   − past dates entirely
     *
     * (Phase 5 will additionally subtract slots that already have a confirmed booking.)
     *
     * @return Collection<int, \App\Models\SessionTemplate>
     */
    public function availableSessions(Court $court, Carbon $date): Collection
    {
        $date = $date->copy()->startOfDay();

        // Past dates have no availability.
        if ($date->isBefore(Carbon::today())) {
            return collect();
        }

        // If the whole court is closed that date, nothing is available.
        $isBlocked = $court->blockedDates()
            ->whereDate('date', $date->toDateString())
            ->exists();

        if ($isBlocked) {
            return collect();
        }

        $sessions = $court->sessionTemplates()
            ->where('is_active', true)
            ->where('day_of_week', $date->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        // For today, hide sessions that have already started.
        if ($date->isToday()) {
            $now = Carbon::now()->format('H:i:s');

            $sessions = $sessions
                ->filter(fn ($session) => $session->start_time > $now)
                ->values();
        }

        return $sessions;
    }
}
