<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Get available plans.
     */
    public function plans(): JsonResponse
    {
        $plans = [
            [
                'id' => 'free',
                'name' => 'Gratis',
                'price' => 0,
                'currency' => 'NOK',
                'interval' => 'month',
                'features' => [
                    '1 nettsted',
                    '10 søkeord',
                    '10 AI-analyser per måned',
                    'Daglig rangeringssjekk',
                ],
                'limits' => [
                    'domains' => 1,
                    'keywords' => 10,
                    'ai_analyses' => 10,
                ],
            ],
            [
                'id' => 'starter',
                'name' => 'Starter',
                'price' => 299,
                'currency' => 'NOK',
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_STARTER'),
                'features' => [
                    '3 nettsteder',
                    '100 søkeord',
                    '100 AI-analyser per måned',
                    'Daglig rangeringssjekk',
                    'E-postvarsler',
                    'Eksport til CSV',
                ],
                'limits' => [
                    'domains' => 3,
                    'keywords' => 100,
                    'ai_analyses' => 100,
                ],
            ],
            [
                'id' => 'professional',
                'name' => 'Profesjonell',
                'price' => 799,
                'currency' => 'NOK',
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_PROFESSIONAL'),
                'features' => [
                    '10 nettsteder',
                    '500 søkeord',
                    '500 AI-analyser per måned',
                    'Daglig rangeringssjekk',
                    'E-postvarsler',
                    'Konkurrentanalyse',
                    'API-tilgang',
                    'Prioritert support',
                ],
                'limits' => [
                    'domains' => 10,
                    'keywords' => 500,
                    'ai_analyses' => 500,
                ],
                'popular' => true,
            ],
            [
                'id' => 'agency',
                'name' => 'Byrå',
                'price' => 1999,
                'currency' => 'NOK',
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_AGENCY'),
                'features' => [
                    'Ubegrenset nettsteder',
                    'Ubegrenset søkeord',
                    'Ubegrenset AI-analyser',
                    'Daglig rangeringssjekk',
                    'E-postvarsler',
                    'Konkurrentanalyse',
                    'API-tilgang',
                    'White-label rapporter',
                    'Dedikert support',
                    'Tilpassede integrasjoner',
                ],
                'limits' => [
                    'domains' => -1, // Unlimited
                    'keywords' => -1,
                    'ai_analyses' => -1,
                ],
            ],
        ];

        return response()->json([
            'data' => $plans,
        ]);
    }

    /**
     * Create checkout session for subscription.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'in:starter,professional,agency'],
        ]);

        $user = $request->user();
        $planId = $validated['plan_id'];

        // Get Stripe price ID
        $priceId = match($planId) {
            'starter' => env('STRIPE_PRICE_STARTER'),
            'professional' => env('STRIPE_PRICE_PROFESSIONAL'),
            'agency' => env('STRIPE_PRICE_AGENCY'),
            default => null,
        };

        if (!$priceId) {
            return response()->json([
                'message' => 'Ugyldig plan.',
            ], 400);
        }

        try {
            // Create or get Stripe customer
            $customerId = $user->stripe_customer_id;
            
            if (!$customerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
                $customerId = $customer->id;
                $user->update(['stripe_customer_id' => $customerId]);
            }

            // Create checkout session
            $session = Session::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/dashboard/billing?success=true',
                'cancel_url' => config('app.frontend_url') . '/dashboard/billing?canceled=true',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                ],
            ]);

            return response()->json([
                'data' => [
                    'checkout_url' => $session->url,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe checkout error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Kunne ikke opprette betalingsøkt. Prøv igjen.',
            ], 500);
        }
    }

    /**
     * Create billing portal session.
     */
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_customer_id) {
            return response()->json([
                'message' => 'Ingen aktiv betalingsprofil funnet.',
            ], 400);
        }

        try {
            $session = PortalSession::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => config('app.frontend_url') . '/dashboard/billing',
            ]);

            return response()->json([
                'data' => [
                    'portal_url' => $session->url,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe portal error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Kunne ikke åpne betalingsportalen. Prøv igjen.',
            ], 500);
        }
    }

    /**
     * Get user invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_customer_id) {
            return response()->json([
                'data' => [],
            ]);
        }

        try {
            $invoices = \Stripe\Invoice::all([
                'customer' => $user->stripe_customer_id,
                'limit' => 24,
            ]);

            $formattedInvoices = collect($invoices->data)->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'amount' => $invoice->amount_paid / 100,
                    'currency' => strtoupper($invoice->currency),
                    'status' => $invoice->status,
                    'created_at' => date('c', $invoice->created),
                    'pdf_url' => $invoice->invoice_pdf,
                ];
            });

            return response()->json([
                'data' => $formattedInvoices,
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe invoices error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'data' => [],
            ]);
        }
    }

    /**
     * Handle Stripe webhook.
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe event', ['type' => $event->type]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle checkout completed.
     */
    private function handleCheckoutCompleted($session): void
    {
        $userId = $session->metadata->user_id ?? null;
        $planId = $session->metadata->plan_id ?? null;

        if (!$userId || !$planId) {
            Log::error('Missing metadata in checkout session', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('User not found for checkout', ['user_id' => $userId]);
            return;
        }

        $user->update([
            'plan' => $planId,
            'stripe_subscription_id' => $session->subscription,
        ]);

        Log::info('User plan updated after checkout', [
            'user_id' => $userId,
            'plan' => $planId,
        ]);
    }

    /**
     * Handle subscription updated.
     */
    private function handleSubscriptionUpdated($subscription): void
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();
        
        if (!$user) {
            Log::warning('User not found for subscription update', [
                'subscription_id' => $subscription->id,
            ]);
            return;
        }

        // Update plan based on price
        $priceId = $subscription->items->data[0]->price->id ?? null;
        $plan = $this->getPlanFromPriceId($priceId);

        if ($plan) {
            $user->update(['plan' => $plan]);
            Log::info('User plan updated', [
                'user_id' => $user->id,
                'plan' => $plan,
            ]);
        }
    }

    /**
     * Handle subscription deleted.
     */
    private function handleSubscriptionDeleted($subscription): void
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();
        
        if (!$user) {
            return;
        }

        $user->update([
            'plan' => 'free',
            'stripe_subscription_id' => null,
        ]);

        Log::info('User downgraded to free plan', ['user_id' => $user->id]);
    }

    /**
     * Handle payment failed.
     */
    private function handlePaymentFailed($invoice): void
    {
        $user = User::where('stripe_customer_id', $invoice->customer)->first();
        
        if (!$user) {
            return;
        }

        Log::warning('Payment failed for user', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);

        // TODO: Send email notification about failed payment
    }

    /**
     * Get plan ID from Stripe price ID.
     */
    private function getPlanFromPriceId(?string $priceId): ?string
    {
        if (!$priceId) {
            return null;
        }

        return match($priceId) {
            env('STRIPE_PRICE_STARTER') => 'starter',
            env('STRIPE_PRICE_PROFESSIONAL') => 'professional',
            env('STRIPE_PRICE_AGENCY') => 'agency',
            default => null,
        };
    }
}
