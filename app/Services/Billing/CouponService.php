<?php

namespace App\Services\Billing;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;

/**
 * Coupon creation and redemption-time validation. Most of the discount
 * math lives on the Coupon model itself (discountFor/isRedeemable) —
 * this service is the admin-facing creation path plus a single
 * validate() entry point the checkout flow calls, so "is this code
 * usable right now, for this plan" only has one implementation.
 */
class CouponService
{
    public function __construct(
        private readonly StripeGateway $stripe,
    ) {}

    public function create(array $data, ?User $actor = null): Coupon
    {
        $coupon = Coupon::create($data);

        try {
            $stripeCoupon = $this->stripe->createCoupon($this->toStripeParams($coupon));
            $coupon->update(['stripe_coupon_id' => $stripeCoupon['id'] ?? null]);
        } catch (StripeGatewayException) {
            // A coupon still works for locally-computed discounts even
            // if the Stripe-side mirror couldn't be created — checkout
            // just won't be able to pass it through to Stripe's own
            // discount UI until stripe_coupon_id is set (e.g. via a
            // retry/backfill job), but our own discountFor() math is
            // unaffected either way.
        }

        activity()->causedBy($actor)->performedOn($coupon)->log('Coupon created: ' . $coupon->code);

        return $coupon->fresh();
    }

    /**
     * Validates a code for redemption against an (optional) target
     * plan, returning the Coupon if usable or null if not — the
     * caller decides how to communicate "why not" since the reasons
     * differ (expired vs exhausted vs wrong plan) and the UI copy for
     * each is caller-specific.
     */
    public function validate(string $code, ?Plan $plan = null): ?Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->isRedeemable($plan)) {
            return null;
        }

        return $coupon;
    }

    private function toStripeParams(Coupon $coupon): array
    {
        $params = ['duration' => 'once'];

        if ($coupon->type->value === 'percentage') {
            $params['percent_off'] = (string) $coupon->value;
        } else {
            $params['amount_off'] = (string) (int) round($coupon->value * 100);
            $params['currency'] = 'usd';
        }

        return $params;
    }
}
