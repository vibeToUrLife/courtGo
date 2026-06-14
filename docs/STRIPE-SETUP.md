# Stripe Setup (Test Mode) + How to Use & Test Phase 4

Phase 4 adds **owner subscriptions** + **bank payouts (Stripe Connect)**. Everything runs in Stripe **TEST mode** — no real money. Follow this once to turn it on, then use the test cases at the bottom to check it works.

> You don't need any of this for Phases 1–3. It's only for the **Billing** page.

---

## Part A — Create a Stripe account & get your TEST keys
1. Sign up free at **https://stripe.com**.
2. In the dashboard, make sure the **"Test mode"** toggle (top-right) is **ON**.
3. Go to **Developers → API keys**. Copy:
   - **Publishable key** → starts with `pk_test_...`
   - **Secret key** → starts with `sk_test_...`
4. Put them in your project's **`.env`**:
   ```
   STRIPE_KEY=pk_test_xxxxx
   STRIPE_SECRET=sk_test_xxxxx
   CASHIER_CURRENCY=myr
   ```

## Part B — Create the monthly subscription Price
1. Dashboard → **Product catalog → Add product**.
2. Name: `CourtGo Owner Plan`. Pricing: **Recurring**, **Monthly**, currency **MYR**, amount e.g. `50.00`.
3. Save, then click the price and copy its **Price ID** (starts with `price_...`).
4. `.env`:
   ```
   STRIPE_PRICE_ID=price_xxxxx
   ```

## Part C — Enable Connect (for owner payouts)
1. Dashboard → **Connect → Get started** (test mode). Complete the short platform profile.
2. That's all — the app already creates **Express** connected accounts for owners in code.

## Part D — Webhooks (keep the app in sync with Stripe)
There are **two** webhooks. Locally, the easiest way is the **Stripe CLI**:

1. Install the Stripe CLI: https://docs.stripe.com/stripe-cli, then run `stripe login`.
2. In one terminal, forward **billing** events:
   ```
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```
   Copy the printed `whsec_...` into `.env` → `STRIPE_WEBHOOK_SECRET=whsec_...`
3. For **Connect** events (owner onboarding), forward connected-account events:
   ```
   stripe listen --forward-connect-to localhost:8000/stripe/connect/webhook
   ```
   Copy that `whsec_...` into `.env` → `STRIPE_CONNECT_WEBHOOK_SECRET=whsec_...`

*(In production you'd instead add these two endpoints under Developers → Webhooks, one of them scoped to "Connected accounts" for `account.updated`.)*

## Part E — Apply the settings
```
php artisan config:clear
php artisan serve
```

---

## ✅ How to USE it (as an owner)
1. Log in as an owner (e.g. `owner@courtgo.test` / `password`).
2. Click **Billing** in the sidebar.
3. **Step 1 – Subscribe:** click **Subscribe now** → you're sent to Stripe Checkout → pay with the test card below → you return to Billing showing **Active**.
4. **Step 2 – Connect bank:** enter your **BRN** and click **Connect bank** → complete Stripe's test onboarding → you return showing **Connected**.
5. When **both** are done, the page shows **"✅ Your courts are live!"** — that's the go-live gate (`User::canAcceptBookings()`).

**Test cards (test mode only):**
| Card number | Result |
|-------------|--------|
| `4242 4242 4242 4242` | Payment succeeds |
| `4000 0000 0000 9995` | Card declined |
| `4000 0027 6000 3184` | Requires 3-D Secure |
Use any future expiry (e.g. 12/34), any CVC, any postcode.

---

## 🧪 Test cases

### Automated (already written — run `php artisan test`)
| Test file | What it proves (no Stripe/keys needed) |
|-----------|----------------------------------------|
| `GoLiveGateTest` | Owner can accept bookings **only** with active subscription **and** Connect onboarded; a court is bookable only when active + owner ready. |
| `ConnectWebhookTest` | The `account.updated` webhook flips `connect_onboarded` on/off based on the account's readiness; ignores unrelated events. |
| `Owner/BillingTest` | Billing page renders for owners (403 for customers); BRN saves; subscribe/connect routes don't crash when Stripe isn't configured. |

### Manual (test mode, with your keys)
1. **Subscribe – happy path:** Billing → Subscribe → pay `4242…` → return → badge shows **Active** (Stripe CLI shows the webhook; the subscription row appears in `subscriptions`).
2. **Subscribe – declined:** use `4000 0000 0000 9995` → Stripe blocks it → you stay un-subscribed.
3. **Connect – happy path:** Connect bank → finish test onboarding → return → badge shows **Connected**, and `account.updated` flips `connect_onboarded` to true.
4. **Go-live gate:** with only one of the two done, the banner says **not live**; with both, it says **live**.
5. **Not configured (no keys):** with empty Stripe keys, clicking Subscribe/Connect just returns to Billing with a friendly notice (no crash).

---

## How the money will flow (recap)
- **Owner → Platform:** the monthly subscription (this page, Step 1) = your revenue.
- **Customer → Owner:** booking payments (built in **Phase 5**) go straight to the owner's connected account, 0% to the platform.
