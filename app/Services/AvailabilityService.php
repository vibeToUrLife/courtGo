<?php

namespace App\Services;

use App\Enums\BookingStatus;
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
     *   − dates the venue is closed (holidays — applies to every court)
     *   − slots already taken by an active booking (confirmed, or a pending hold that hasn't expired)
     *   − sessions whose start time has already passed (when the date is today)
     *   − past dates entirely
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

        // If the venue is closed that date (a holiday), none of its courts are available.
        $isClosed = $court->venue->closedDates()
            ->whereDate('date', $date->toDateString())
            ->exists();

        if ($isClosed) {
            return collect();
        }

        $sessions = $court->sessionTemplates()
            ->where('is_active', true)
            ->where('day_of_week', $date->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        // Remove slots already taken by an active booking on that date
        // (confirmed, or a pending hold that hasn't expired yet).
        $takenStartTimes = $court->bookings()
            ->whereDate('booking_date', $date->toDateString())
            ->where(function ($query) {
                $query->where('status', BookingStatus::Confirmed->value)
                    ->orWhere(function ($pending) {
                        $pending->where('status', BookingStatus::Pending->value)
                            ->where('hold_expires_at', '>', Carbon::now());
                    });
            })
            ->pluck('start_time')
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->all();

        $sessions = $sessions
            ->reject(fn ($session) => in_array(substr((string) $session->start_time, 0, 5), $takenStartTimes, true))
            ->values();

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
