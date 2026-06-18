<?php

use App\Concerns\HandlesScheduleTimes;

/** A probe that exposes the trait's protected helpers for direct testing. */
class ScheduleTimesProbe
{
    use HandlesScheduleTimes;

    public function min(string $t, bool $isEnd = false): int
    {
        return $this->slotMinutes($t, $isEnd);
    }

    public function toTime(int $m): string
    {
        return $this->minutesToTime($m);
    }

    /** @return list<string>|null "start-end" pairs, for easy assertions. */
    public function slots(string $start, string $end, float $hours): ?array
    {
        $slots = $this->buildSlots($start, $end, $hours);

        return $slots === null
            ? null
            : array_map(fn ($s) => $s['start'].'-'.$s['end'], $slots);
    }
}

function probe(): ScheduleTimesProbe
{
    return new ScheduleTimesProbe;
}

test('slotMinutes treats a 00:00 end as end-of-day', function () {
    expect(probe()->min('00:00'))->toBe(0)
        ->and(probe()->min('00:00', isEnd: true))->toBe(1440)
        ->and(probe()->min('20:00'))->toBe(1200)
        ->and(probe()->min('08:30'))->toBe(510);
});

test('minutesToTime renders end-of-day as 00:00', function () {
    expect(probe()->toTime(1440))->toBe('00:00')
        ->and(probe()->toTime(1200))->toBe('20:00')
        ->and(probe()->toTime(510))->toBe('08:30');
});

test('buildSlots splits a window into equal back-to-back slots', function () {
    expect(probe()->slots('18:00', '22:00', 2))->toBe(['18:00-20:00', '20:00-22:00'])
        ->and(probe()->slots('20:00', '21:00', 0.5))->toBe(['20:00-20:30', '20:30-21:00'])
        ->and(probe()->slots('20:00', '00:00', 2))->toBe(['20:00-22:00', '22:00-00:00']);
});

test('buildSlots returns null when the window does not divide evenly', function () {
    expect(probe()->slots('20:00', '23:00', 2))->toBeNull()  // 3h ÷ 2h
        ->and(probe()->slots('11:00', '09:00', 1))->toBeNull(); // end before start
});

test('buildSlots allows a full midnight-to-midnight day', function () {
    $slots = probe()->slots('00:00', '00:00', 4);

    expect($slots)->toHaveCount(6)
        ->and($slots[0])->toBe('00:00-04:00')
        ->and($slots[5])->toBe('20:00-00:00');
});

test('slotPreview reports empty, mismatch and ok states', function () {
    expect(probe()->slotPreview('', '22:00', 2)['state'])->toBe('empty')
        ->and(probe()->slotPreview('20:00', '22:00', 0)['state'])->toBe('empty')
        ->and(probe()->slotPreview('20:00', '23:00', 2)['state'])->toBe('mismatch')
        ->and(probe()->slotPreview('20:00', '22:00', 2)['state'])->toBe('ok')
        ->and(probe()->slotPreview('20:00', '22:00', 2)['slots'])->toHaveCount(1);
});

test('slotLengthLabel reads naturally for minutes and hours', function () {
    expect(probe()->slotLengthLabel(0.5))->toBe('30-minute')
        ->and(probe()->slotLengthLabel(1))->toBe('1-hour')
        ->and(probe()->slotLengthLabel(1.5))->toBe('1.5-hour')
        ->and(probe()->slotLengthLabel(2))->toBe('2-hour');
});

test('time option lists cover the half-hours, with midnight placed sensibly', function () {
    $times = probe()->timeOptions();
    expect($times)->toHaveCount(48)
        ->and(array_key_first($times))->toBe('00:00')          // midnight first as a start
        ->and($times['00:00'])->toContain('midnight')
        ->and($times['12:00'])->toContain('noon');

    $endTimes = probe()->endTimeOptions();
    expect(array_key_last($endTimes))->toBe('00:00')           // midnight last as an end
        ->and($endTimes['00:00'])->toBe('12:00 AM (end of day)');
});
