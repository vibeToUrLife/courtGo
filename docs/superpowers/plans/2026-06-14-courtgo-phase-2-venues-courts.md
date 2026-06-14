# CourtGo Phase 2 — Owner: Venues & Courts (✅ DONE)

**Goal:** Let owners (the "bosses") manage their **venues** and the **courts** inside them — the first real CourtGo feature.

**Status:** Complete. Tag `phase-2-complete`. All tests green.

---

## What an owner can now do
- See a **"My Venues"** link in the sidebar (owners only).
- **Add / list / delete venues** (name, description, address, city, state).
- Open a venue and **add / list / delete courts**, and **toggle each court open/closed**.
- Security: an owner can only see and manage **their own** venues and courts (enforced by a Policy). Customers can't reach these pages at all.

**Try it:** log in as the demo owner `owner@courtgo.test` / `password` → click **My Venues**.

---

## Files added/changed

| File | Purpose |
|------|---------|
| `database/migrations/..._create_venues_table.php` | `venues` table (owner_id, name, description, address, city, state) |
| `database/migrations/..._create_courts_table.php` | `courts` table (venue_id, name, sport, is_active) |
| `app/Models/Venue.php` | Venue model — `owner()` (belongsTo User), `courts()` (hasMany) |
| `app/Models/Court.php` | Court model — `venue()` (belongsTo), `is_active` cast to boolean |
| `database/factories/VenueFactory.php`, `CourtFactory.php` | Fake data for tests |
| `app/Models/User.php` | added `venues()` (hasMany) |
| `app/Policies/VenuePolicy.php` | view/update/delete allowed only if `user->id === venue->owner_id` |
| `app/Livewire/Owner/Venues/Index.php` + `resources/views/livewire/owner/venues/index.blade.php` | Venues page: list + create + delete |
| `app/Livewire/Owner/Venues/Courts.php` + `resources/views/livewire/owner/venues/courts.blade.php` | Courts page (per venue): list + create + delete + toggle |
| `routes/web.php` | owner routes (`auth` + `role:owner`): `/owner/venues`, `/owner/venues/{venue}` |
| `resources/views/layouts/app/sidebar.blade.php` | "My Venues" sidebar link (owners only) |
| `database/seeders/OwnerUserSeeder.php` | demo owner + sample venue + 3 courts |
| `tests/Feature/VenueTest.php`, `CourtTest.php`, `Owner/VenuesIndexTest.php`, `Owner/CourtsManageTest.php`, `OwnerSeederTest.php` | tests proving it all works |

---

## Key concepts used (for study)

- **Livewire component** = a PHP class (in `app/Livewire/...`) + a Blade view (`resources/views/livewire/...`). Public **properties** hold form values (bound with `wire:model`); public **methods** are actions (called with `wire:click` / `wire:submit`).
  - Livewire 4 note: generate class-based components with `php artisan make:livewire Name --class` (v4 otherwise makes single-file components).
- **Validation:** the `#[Validate('required|string')]` attribute on a property + `$this->validate()` inside the action.
- **Authorization:** a **Policy** (`VenuePolicy`) decides who can do what. Called with `$this->authorize('delete', $venue)` — throws a 403 if denied. Auto-discovered because it's named `VenuePolicy` for the `Venue` model.
- **Routing a full page to a component:** `Route::get('/owner/venues', Index::class)`, with `#[Layout('layouts.app')]` on the component so it renders inside the app shell.
- **Flux UI:** `flux:input`, `flux:textarea`, `flux:switch`, `flux:button`, `flux:badge` (free). `flux:table` is **Pro**, so the lists use a plain Tailwind `<table>`.
- **Testing Livewire (Pest):** `Livewire::actingAs($user)->test(Index::class)->set('name','X')->call('save')->assertHasNoErrors()`; full-page check `$this->actingAs($user)->get('/owner/venues')->assertSeeLivewire(Index::class)`; denied actions assert `->assertForbidden()`.

---

## What's intentionally NOT in Phase 2 (later)
- Editing a venue's or court's details after creation (only create/delete/toggle for now) — a quick follow-up.
- Venue **photos** (file uploads).
- Anything customer-facing or booking-related (that's Phase 3+).

---

## Next: Phase 3 — Owner schedule & blocked dates
Owners set the **weekly recurring sessions** for each court (day, time, price) and can block specific dates; plus the availability calculation. See the roadmap.
