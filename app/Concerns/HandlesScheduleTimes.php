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

    /**
     * Selectable times on the half-hour, "HH:MM" => "g:i A" label.
     * Owners pick From/Until from this list instead of typing.
     *
     * @return array<string, string>
     */
    public function timeOptions(): array
    {
        $options = [];

        for ($m = 0; $m < 24 * 60; $m += 30) {
            $hour = intdiv($m, 60);
            $minute = $m % 60;
            $hour12 = $hour % 12 === 0 ? 12 : $hour % 12;
            $label = sprintf('%d:%02d %s', $hour12, $minute, $hour < 12 ? 'AM' : 'PM');

            if ($m === 0) {
                $label .= ' (midnight)';
            } elseif ($m === 12 * 60) {
                $label .= ' (noon)';
            }

            $options[$this->minutesToTime($m)] = $label;
        }

        return $options;
    }

    /**
     * Like timeOptions(), but for an END/"Until" picker: midnight moves to the
     * bottom and is labelled "end of day" (it's treated as 1440 everywhere), so
     * a venue open "8pm until midnight" finds midnight after 11:30 PM, not first.
     *
     * @return array<string, string>
     */
    public function endTimeOptions(): array
    {
        $options = $this->timeOptions();
        unset($options['00:00']);
        $options['00:00'] = '12:00 AM (end of day)';

        return $options;
    }

    /** Human label for a slot length, e.g. "30-minute", "1-hour", "1.5-hour". */
    public function slotLengthLabel(float $hours): string
    {
        $minutes = (int) round($hours * 60);

        if ($minutes < 60) {
            return $minutes.'-minute';
        }

        if ($minutes % 60 === 0) {
            return intdiv($minutes, 60).'-hour';
        }

        return rtrim(rtrim(number_format($hours, 2), '0'), '.').'-hour';
    }

    /** "H:i" for a minutes-since-midnight value (1440 → "00:00", i.e. midnight). */
    protected function minutesToTime(int $minutes): string
    {
        $minutes %= 24 * 60;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Split a [start, end] window into back-to-back slots of $hours each.
     *
     * Returns a list of ['start' => 'H:i', 'end' => 'H:i'] slots, or null if the
     * window isn't a positive whole multiple of the slot length (so the caller
     * can tell the owner the times "don't match"). A 00:00 end means midnight.
     *
     * @return array<int, array{start: string, end: string}>|null
     */
    protected function buildSlots(string $start, string $end, float $hours): ?array
    {
        $startMin = $this->slotMinutes($start);
        $endMin = $this->slotMinutes($end, isEnd: true);
        $length = (int) round($hours * 60);
        $window = $endMin - $startMin;

        if ($length <= 0 || $window <= 0 || $window % $length !== 0) {
            return null;
        }

        $slots = [];
        for ($t = $startMin; $t < $endMin; $t += $length) {
            $slots[] = ['start' => $this->minutesToTime($t), 'end' => $this->minutesToTime($t + $length)];
        }

        return $slots;
    }

    /**
     * A preview of the slots a window+duration would create, for the UI.
     * state: 'empty' (inputs incomplete) | 'mismatch' (doesn't divide evenly) | 'ok'.
     *
     * @return array{state: string, slots: array<int, array{start: string, end: string}>}
     */
    public function slotPreview(?string $start, ?string $end, mixed $hours): array
    {
        $start = (string) $start;
        $end = (string) $end;

        $complete = preg_match('/^\d{2}:\d{2}$/', $start)
            && preg_match('/^\d{2}:\d{2}$/', $end)
            && is_numeric($hours) && (float) $hours > 0;

        if (! $complete) {
            return ['state' => 'empty', 'slots' => []];
        }

        $slots = $this->buildSlots($start, $end, (float) $hours);

        return $slots === null
            ? ['state' => 'mismatch', 'slots' => []]
            : ['state' => 'ok', 'slots' => $slots];
    }
}
