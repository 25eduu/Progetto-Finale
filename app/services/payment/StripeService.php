<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

class StripeService
{
    public function __construct()
    {
        if (STRIPE_SECRET_KEY === '') {
            throw new RuntimeException('STRIPE_SECRET_KEY non configurata.');
        }
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    }

    /**
     * Sessione Stripe per un ordine.
     */
    public function createOrderSession(
        int    $orderId,
        float  $amount,
        string $customerEmail,
        string $productName,
        string $appUrl
    ): \Stripe\Checkout\Session {
        return \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'customer_email'       => $customerEmail,
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => ['name' => $productName],
                    'unit_amount'  => (int)round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => $appUrl . '/index.php?r=checkout/success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $appUrl . '/index.php?r=checkout/index',
            'metadata'    => ['order_id' => (string)$orderId],
        ]);
    }

    /**
     * Sessione Stripe per ricarica wallet.
     */
    public function createWalletSession(
        int    $userId,
        float  $amount,
        string $customerEmail,
        string $appUrl
    ): \Stripe\Checkout\Session {
        return \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'customer_email'       => $customerEmail,
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => ['name' => 'Ricarica Wallet TechShop'],
                    'unit_amount'  => (int)round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'success_url' => $appUrl . '/index.php?r=wallet/success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $appUrl . '/index.php?r=account/dashboard',
            'metadata'    => [
                'wallet_recharge' => 'true',
                'user_id'         => (string)$userId,
                'amount'          => (string)$amount,
            ],
        ]);
    }

    public function retrieveSession(string $sessionId): \Stripe\Checkout\Session
    {
        return \Stripe\Checkout\Session::retrieve($sessionId);
    }

    /**
     * Verifica firma webhook Stripe.
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent($payload, $signature, STRIPE_WEBHOOK_SECRET);
    }
}
