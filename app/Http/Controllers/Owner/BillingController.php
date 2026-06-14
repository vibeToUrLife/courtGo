<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /** Whether Stripe keys are configured (so we never call Stripe unconfigured). */
    private function stripeConfigured(): bool
    {
        return (bool) config('cashier.secret');
    }

    /** Start (or change) the monthly subscription via Stripe Checkout. */
    public function subscribe(Request $request)
    {
        $user = $request->user();
        $priceId = config('services.stripe.price_id');

        if (! $this->stripeConfigured() || ! $priceId) {
            return redirect()->route('owner.billing')->with(
                'stripe_error',
                'Stripe is not set up yet — add your test keys and a Price ID (see docs/STRIPE-SETUP.md).'
            );
        }

        return $user->newSubscription('default', $priceId)->checkout([
            'success_url' => route('owner.billing').'?checkout=success',
            'cancel_url' => route('owner.billing').'?checkout=cancel',
        ]);
    }

    /** Open Stripe's billing portal to manage/cancel the subscription. */
    public function billingPortal(Request $request)
    {
        if (! $this->stripeConfigured()) {
            return redirect()->route('owner.billing');
        }

        return $request->user()->redirectToBillingPortal(route('owner.billing'));
    }

    /** Send the owner to Stripe Connect onboarding to connect their bank. */
    public function connect(Request $request, StripeConnectService $connect)
    {
        if (! $this->stripeConfigured()) {
            return redirect()->route('owner.billing')->with(
                'stripe_error',
                'Stripe is not set up yet — add your test keys (see docs/STRIPE-SETUP.md).'
            );
        }

        $url = $connect->onboardingUrl(
            $request->user(),
            route('owner.connect.return'),
            route('owner.connect.refresh'),
        );

        return redirect($url);
    }

    /** Owner returns from Stripe onboarding — re-check their status. */
    public function connectReturn(Request $request, StripeConnectService $connect)
    {
        if ($this->stripeConfigured()) {
            $connect->refreshStatus($request->user());
        }

        return redirect()->route('owner.billing');
    }

    /** The onboarding link expired — make a fresh one. */
    public function connectRefresh(Request $request, StripeConnectService $connect)
    {
        if (! $this->stripeConfigured()) {
            return redirect()->route('owner.billing');
        }

        $url = $connect->onboardingUrl(
            $request->user(),
            route('owner.connect.return'),
            route('owner.connect.refresh'),
        );

        return redirect($url);
    }
}
