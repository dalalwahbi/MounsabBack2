<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripePaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $request->amount,
            'currency' => 'mad',
            'payment_method_types' => ['card'],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:card,paypal',
        ]);

        if ($request->payment_method === 'card') {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Create a PaymentIntent with customer details
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100,
                'currency' => 'USD',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'customer_name' => auth()->user()->firstName,
                    'customer_email' => auth()->user()->email,
                ],
            ]);


            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'name' => auth()->user()->firstName,
                'email' => auth()->user()->email,
            ]);
        } elseif ($request->payment_method === 'paypal') {
            //     // Implement PayPal payment processing here
            //     // You'll need to set up PayPal API credentials and logic to create a payment
            //     // This is just an example and you should replace with your PayPal logic

            //     $response = Http::withBasicAuth(env('PAYPAL_CLIENT_ID'), env('PAYPAL_SECRET'))
            //         ->post('https://api.sandbox.paypal.com/v1/payments/payment', [
            //             'intent' => 'sale',
            //             'redirect_urls' => [
            //                 'return_url' => route('payments.success'), // Define a success route
            //                 'cancel_url' => route('payments.cancel'), // Define a cancel route
            //             ],
            //             'payer' => [
            //                 'payment_method' => 'paypal',
            //             ],
            //             'transactions' => [[
            //                 'amount' => [
            //                     'total' => $request->amount,
            //                     'currency' => 'MAD',
            //                 ],
            //                 'description' => 'Payment for announcement ID: ' . $request->announce_id,
            //             ]],
            //         ]);

            //     if ($response->successful()) {
            //         // You would need to redirect the user to the approval URL returned by PayPal
            //         $approvalUrl = $response->json()['links'][1]['href'];

            //         return redirect()->away($approvalUrl); // Redirect to PayPal for approval
            //     } else {
            //         return redirect()->back()->with('error', 'PayPal payment could not be processed.');
            //     }
        }

        return redirect()->back()->with('success', 'Payment processed successfully.');
    }
}
