# CourtGo Phase 4 — Subscriptions & Stripe Connect (✅ DONE)

**Goal:** Owners pay a **monthly subscription** (Laravel Cashier) and **connect their bank** (Stripe Connect) so their courts can **go live** for booking.

**Status:** Complete. Tag `phase-4-complete`. 94 tests green. Live Stripe flows verified **manually in test mode** (see `docs/STRIPE-SETUP.md`).

---

## What's new
- **Billing page** (`/owner/billing`, sidebar "Billing"): shows a go-live banner, a **Subscribe** step, a **Connect bank** step, and a **BRN** field.
- **Subscriptions** via Cashier (Stripe Checkout) — the platform's revenue.
- **Stripe Connect** onboarding (Express accounts) so booking money can later be paid out to owners.
- **Go-live gate:** a court is bookable only when it's active **and** its owner is subscribed **and** Connect-onboarded.
- **Webhooks:** Cashier's billing webhook (`/stripe/webhook`) + a separate Connect webhook (`/stripe/connect/webhook`) that updates onboarding status.

---

## Files added/changed

| File | Purpose |
|------|---------|
| `composer.json` | added `laravel/cashier` (16.5; pulls stripe-php 17.6) |
| Cashier migrations | customer columns on `users` + `subscriptions`/`subscription_items` tables |
| `..._add_connect_fields_to_users_table.php` | `stripe_connect_account_id`, `connect_onboarded`, `business_registration_number` |
| `app/Models/User.php` | `Billable` trait; `canAcceptBookings()` gate; fillable/casts for new fields |
| `app/Models/Court.php` | `isBookable()` |
| `app/Services/StripeConnectService.php` | create connected account, onboarding link, refresh status |
| `app/Http/Controllers/Owner/BillingController.php` | subscribe (Checkout), billing portal, Connect onboarding + return/refresh |
| `app/Http/Controllers/StripeConnectWebhookController.php` | handles `account.updated` |
| `app/Livewire/Owner/Billing.php` + view | the Billing page |
| `routes/web.php` | owner billing/connect routes + `/stripe/connect/webhook` |
| `bootstrap/app.php` | CSRF-exempt `stripe/*` |
| `config/services.php` | `stripe.price_id`, `stripe.connect_webhook_secret` |
| `.env(.example)` | Stripe test-mode placeholders + `CASHIER_CURRENCY=myr` |
| `tests/Feature/GoLiveGateTest.php`, `ConnectWebhookTest.php`, `Owner/BillingTest.php` | tests |

---

## How it's tested (important pattern)
Stripe charges money and the PHP SDK bypasses Laravel's HTTP fakes, so we split testing:
- **Automated (no keys, in CI):** the **gate** (seed an active `subscriptions` row, assert `subscribed('default')`), the **webhook handler** (POST a fake `account.updated`, assert the flag flips — the signature check is skipped when no secret is set), and **route guards** (subscribe/connect return safely when Stripe isn't configured).
- **Manual (test mode + your keys):** the real Checkout redirect and real Connect onboarding, driven with test cards and the Stripe CLI. Full steps + test cases in **`docs/STRIPE-SETUP.md`**.

Key gotchas baked in: the subscription identifier column is **`type`** (not `name`) in Cashier 16; the webhook secret env var is **`STRIPE_WEBHOOK_SECRET`**; Connect events use a **separate** endpoint + signing secret; we reuse the **one** stripe-php that Cashier ships (never pin a newer one).

---

## What's intentionally NOT in Phase 4
- Taking the actual booking payment (Phase 5) — that's where Connect destination charges send money to owners.
- Enforcing the gate on a customer-facing booking screen (Phase 5 will use `Court::isBookable()`).
- Editing/cancelling subscription UI beyond Stripe's billing portal link.

---

## Next: Phase 5 — Customer browse + booking + payment
Customers browse courts, see availability (`AvailabilityService`), and **book + pay** (reserve-then-pay with a destination charge to the owner; webhook-confirmed; no double-booking). See the roadmap.
