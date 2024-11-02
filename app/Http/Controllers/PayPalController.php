<?php

namespace App\Http\Controllers;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaypalController extends Controller
{
    public function payment(Request $request)
    {
        $provider = new PayPalClient;

        // Set PayPal API credentials
        $provider->setApiCredentials(config('paypal'));

        // Get PayPal token
        $paypalToken = $provider->getAccessToken();

        // Create PayPal order
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.success'),
                "cancel_url" => route('paypal.cancel'),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->price,
                    ]
                ]
            ]
        ]);

        // Check if the order was created successfully
        if (isset($response['id'])) {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === "approve") {
                    return response()->json([
                        'status' => 'success',
                        'approval_url' => $link['href'],
                        'id' => $response['id']
                    ]);
                }
            }
        }

        // Log error if the order wasn't created
        Log::error('PayPal Order Creation Failed', $response);

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to create PayPal order.'
        ], 500);
    }

    public function success(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $response = $provider->capturePaymentOrder($request->token);

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment completed successfully.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Payment failed.'
        ]);
    }


    public function cancel()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Payment was canceled.'
        ]);
    }
}
