<?php

namespace App\Services;

use App\Models\User;
use Laravel\Cashier\Cashier;

/**
 * Stripe Connect onboarding for court owners (so booking money is paid out to them).
 * Uses the stripe-php client that Cashier configures (one shared library + secret).
 * These methods call Stripe, so they're exercised manually in test mode.
 */
class StripeConnectService
{
    /**
     * Ensure the owner has a connected Stripe account; return its id.
     */
    public function ensureAccount(User $owner): string
    {
        if ($owner->stripe_connect_account_id) {
            return $owner->stripe_connect_account_id;
        }

        $account = Cashier::stripe()->accounts->create([
            'country' => 'MY',
            'email' => $owner->email,
            'business_type' => 'company',
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'controller' => [
                'stripe_dashboard' => ['type' => 'express'],
                'fees' => ['payer' => 'application'],
                'losses' => ['payments' => 'stripe'],
                'requirement_collection' => 'stripe',
            ],
        ]);

        $owner->update(['stripe_connect_account_id' => $account->id]);

        return $account->id;
    }

    /**
     * Create a hosted onboarding link and return its URL.
     */
    public function onboardingUrl(User $owner, string $returnUrl, string $refreshUrl): string
    {
        $accountId = $this->ensureAccount($owner);

        $link = Cashier::stripe()->accountLinks->create([
            'account' => $accountId,
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * Re-check the account with Stripe and update the owner's onboarded flag.
     */
    public function refreshStatus(User $owner): bool
    {
        if (! $owner->stripe_connect_account_id) {
            return false;
        }

        $account = Cashier::stripe()->accounts->retrieve($owner->stripe_connect_account_id);

        $ready = $account->charges_enabled
            && $account->payouts_enabled
            && $account->details_submitted;

        $owner->update(['connect_onboarded' => $ready]);

        return (bool) $ready;
    }
}
