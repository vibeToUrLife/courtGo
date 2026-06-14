# CourtGo Phase 3 — Owner Schedule & Availability (✅ DONE)

**Goal:** Owners set each court's **weekly recurring sessions** (day, time, price) and **block specific dates**, and the system can compute which sessions are **available** on any date.

**Status:** Complete. Tag `phase-3-complete`. All 81 tests green.

---

## What an owner can now do
- On a court, open **Schedule** and add **weekly sessions** (e.g. *every Monday 8–10pm, RM40*). Sessions repeat every week automatically.
- See the week laid out Sunday → Saturday with each session's time + price; remove any session.
- **Block specific dates** (holidays/maintenance) so they can't be booked, and unblock them.
- Security: only the court's owner can edit its schedule.

**Try it:** log in as `owner@courtgo.test` / `password` → My Venues → Manage courts → **Schedule**. The demo courts already have weeknight + weekend sessions.

---

## The availability rule (core logic)
`App\Services\AvailabilityService::availableSessions($court, $date)` returns the bookable sessions for a court on a date:

```
available =
    active weekly sessions for that weekday
  − dates the court is blocked
  − past dates (nothing)
  − sessions that already started (only when the date is today)
```
*(Phase 5 will also subtract slots that already have a confirmed booking.)*

This pure-logic service has its own unit tests — it's the foundation the customer booking screen (Phase 5) will use.

---

## Files added/changed

| File | Purpose |
|------|---------|
| `database/migrations/..._create_session_templates_table.php` | weekly sessions: court_id, day_of_week (0=Sun…6=Sat), start/end time, price, is_active |
| `database/migrations/..._create_blocked_dates_table.php` | closed dates: court_id, date, reason (unique per court+date) |
| `app/Models/SessionTemplate.php`, `BlockedDate.php` (+ factories) | the two new models |
| `app/Models/Court.php` | added `sessionTemplates()` + `blockedDates()` relationships |
| `app/Services/AvailabilityService.php` | **the availability calculator** |
| `app/Livewire/Owner/Courts/Schedule.php` + `resources/views/livewire/owner/courts/schedule.blade.php` | the schedule management page |
| `routes/web.php` | `/owner/courts/{court}/schedule` (owner-only) |
| `resources/views/livewire/owner/venues/courts.blade.php` | added a **Schedule** link per court |
| `database/seeders/OwnerUserSeeder.php` | demo courts now get a sample weekly schedule (idempotent) |
| `tests/Feature/SessionTemplateTest.php`, `BlockedDateTest.php`, `AvailabilityServiceTest.php`, `Owner/ScheduleTest.php` | tests |

---

## Concepts used (for study)
- **A "service" class** (`AvailabilityService`) holds business logic that isn't tied to one screen, so it can be reused and unit-tested on its own. Resolved with `app(AvailabilityService::class)`.
- **`day_of_week` convention:** 0=Sunday … 6=Saturday, matching Carbon's `$date->dayOfWeek`. Storing the weekday (not a real date) is what makes a session "repeat every week".
- **Time handling:** times stored in `time` columns; compared as `H:i:s` strings (we normalise `'09:00'` → `'09:00:00'` before overlap checks so adjacent sessions like 9–11 and 11–13 don't falsely overlap).
- **Validation across two forms in one component:** each action (`addSession`, `blockDate`) calls `$this->validate([... its own rules ...])` so the two forms don't validate each other's fields. Overlap is an extra check that throws `ValidationException::withMessages([...])`.
- **Freezing time in tests:** `$this->travelTo(Carbon::parse('...'))` makes "today/now" deterministic and auto-resets after the test.

---

## What's intentionally NOT in Phase 3
- Editing a session in place (we add/delete; edit = delete + re-add for now).
- One-off extra sessions on a specific date (only a weekly schedule + date blocking).
- Anything customer-facing — browsing/booking is Phase 5 and will consume `AvailabilityService`.

---

## Next: Phase 4 — Subscriptions & Stripe Connect onboarding
Owners pay the monthly subscription (Laravel Cashier) and connect their bank (Stripe Connect) so their courts can go live. See the roadmap.
