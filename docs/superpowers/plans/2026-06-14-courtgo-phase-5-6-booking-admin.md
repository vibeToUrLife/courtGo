# CourtGo Phases 5 & 6 — Customer Booking + Admin (✅ DONE)

**Status:** Complete. Tags `phase-5-complete`, `phase-6-complete`. **123 tests green.**

---

## Phase 5 — Customers browse, book & pay

### What customers can do
- **Find a Court** (sidebar) → search bookable courts by sport/city.
- Open a court → pick a date → see **available sessions** (from `AvailabilityService`).
- **Book & pay** → reserves a hold, then Stripe Checkout (money goes straight to the owner). Confirmed by webhook.
- **My Bookings** → see all their bookings + statuses.

### The reserve-then-pay flow (no double-booking)
1. **Reserve:** `BookingService::reserve()` creates a `pending` hold inside a `lockForUpdate` transaction; a unique DB index is the true backstop (two people can't hold the same court+date+time).
2. **Pay:** `BookingPaymentService` creates a Stripe Checkout **destination charge** to the owner's connected account (0% platform fee → `application_fee_amount` omitted).
3. **Confirm:** the `BookingWebhookController` confirms the booking on `checkout.session.completed` / `checkout.session.async_payment_succeeded` (FPX/GrabPay settle later) — **idempotent**, only when `payment_status != unpaid`.
4. **Release:** a scheduled command `bookings:expire-holds` (every minute) expires stale unpaid holds; payment failure releases the slot.

**Demo mode:** with no Stripe keys, booking auto-confirms locally so the flow can be tried; with keys it does the real Checkout.

### Key files
`app/Models/Booking.php` + `BookingStatus` enum · `app/Services/{AvailabilityService(updated),BookingService,BookingPaymentService}` · `app/Http/Controllers/{BookingController,BookingWebhookController}` · `app/Console/Commands/ExpireHolds.php` · `app/Livewire/{Browse,CourtShow,MyBookings}` · cross-DB unique-index migration · `routes/console.php` schedule.

---

## Phase 6 — Platform admin

### What the admin can do
- **Admin Dashboard** (sidebar) → platform stats (owners, customers, venues, courts, confirmed bookings, active subscriptions).
- **Owners** → suspend / unsuspend any owner. Suspending instantly hides all their courts (`is_suspended` is folded into `canAcceptBookings()` and the `bookable` scope).

### Key files
`app/Livewire/Admin/{Dashboard,Owners}` · `is_suspended` migration · admin routes (`role:admin`) + sidebar links.

---

## How it's tested
- **Automated (123 tests, no Stripe):** double-booking guard, availability (incl. booked/blocked/expired), reserve hold + races, hold expiry (time-travel), payment webhook confirm + idempotency + async, customer browse/book/my-bookings (demo mode), admin dashboard/suspend + suspension gating.
- **Manual (test-mode keys):** the real Stripe Checkout for a booking (test card `4242…`), FPX/GrabPay async settlement via Stripe CLI. See `docs/STRIPE-SETUP.md`.

## Accounts to try (password `password`)
- **Customer:** register your own, or use any seeded user.
- **Owner (live in demo):** `owner@courtgo.test` — has venue + courts + schedule.
- **Admin:** `admin@courtgo.test`.

---

## 🎉 The whole CourtGo app is now built (Phases 1–6)
Foundation/auth → venues/courts → schedule/availability → subscriptions/Connect → **booking + payment** → **admin**. Remaining is the optional **Go-Live** step (deploy + flip Stripe to live) in `docs/SETUP-AND-LEARNING.md` / the roadmap.
