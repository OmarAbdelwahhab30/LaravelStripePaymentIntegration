<?php

namespace App\Http\Controllers\Payments\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Token;

class StripeIntegration extends Controller
{

    public function createPaymentIntent(Request $request){

        $dateArr = $this->explodeDate($request->expiry_date);

        $stripe = new \Stripe\StripeClient(config("services.stripe.secret_key"));

        $customer = $stripe->customers->create([
            'description' => 'My First Test Customer (created for API docs at https://www.stripe.com/docs/api)',
            'email' => "omar@gmail.com",
            'name' => "omarkishk"
        ]);
        $tokenized = $stripe->tokens->create([
            'card' => [
                "number"    => $request->input('card_number'),
                "exp_month" => $dateArr[0],
                "exp_year"  => $dateArr[1],
                "cvc"       => $request->input('cvc'),
                "name"      => $request->input('name')
            ],
        ]);

//        $stripe->charges->create([
//            'amount' => 2000,
//            'currency' => 'EGP',
//            'source' => 'tok_visa',
//            'description' => 'with token , sure?',
//        ]);

        $product = $stripe->products->create(['active' => true, 'name' => 'My product']);
        $price  = $stripe->prices->create([
            'unit_amount' => 2000,
            'currency' => 'EGP',
            'recurring' => ['interval' => 'month'],
            'product' => $product['id'],
        ]);
        $lineItems = [[
            'price' => $price['id'],
            'quantity' => 1,
        ]];
        $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            //'customer_email' => Auth::user()->email,
            'line_items' => $lineItems,
            'mode' => 'subscription',
            'subscription_data' => [
                'trial_from_plan' => true,
            ],
            'success_url' => "https://www.google.com/",
            'cancel_url' => "https://www.youtube.com",
        ]);

        $payemnt = $stripe->paymentIntents->create([
            'amount' => 2000,
            'currency' => 'EGP',
            'payment_method_types' => ['card'],
            'customer' => $customer->id,
            'payment_method_data' => [
                'type' => 'card',
                'card' => [
                    'token' => $tokenized['id']
                ]
            ],
            "confirm" => true
        ]);
        return response()->json($tokenized);
    }

    public function explodeDate($date){
        $Date = explode('/', $date);
        return $Date;
    }
}
