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
    private $stripe;

    public function executePayment(Request $request){

        $dateArr = $this->explodeDate($request->expiry_date);

        $stripe = new \Stripe\StripeClient(config("services.stripe.secret_key"));

        $this->stripe = $stripe;

        $CustomerData = [
            'description' => '..',
            'email' => "omar@gmail.com",
            'name' => "omarkishk"
        ];
        $customer = $this->createCustomer($CustomerData);


        $Card = [
            "number"    => $request->input('card_number'),
            "exp_month" => $dateArr[0],
            "exp_year"  => $dateArr[1],
            "cvc"       => $request->input('cvc'),
            "name"      => $request->input('name')
        ];

        $tokenized = $this->tokenizeCard($Card);
        $product   = $this->createProduct();

        $PriceData = [
            'unit_amount' => 2000,
            'currency' => 'EGP',
            'recurring' => ['interval' => 'month'],
            'product' => $product['id'],
        ];
        $price     = $this->createPrice($PriceData);


        $lineItems = [[
            'price' => $price['id'],
            'quantity' => 1,
        ]];

        $session = [
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'subscription',
            'subscription_data' => [
                'trial_from_plan' => true,
            ],
            'success_url' => "https://www.example1.com/",
            'cancel_url' => "https://www.example2.com",
        ];
        $this->startPaymentSession($session);

        //$this->startPaymentSession($session);

        $PaymentIntent = [
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
        ];

        $payment = $this->createPaymentIntent($PaymentIntent);

        if($this->paymentStatusCheck($payment['status'])){
            return response()->json(['response' => "Your payment was successful"]);
        }
        return response()->json(['response' => "something went wrong , please try again later"]);
    }

    public function explodeDate($date){
        $Date = explode('/', $date);
        return $Date;
    }
    public function createCustomer($data){
        $customer = $this->stripe->customers->create([
            'description' => $data['description'],
            'email'       => $data['email'],
            'name'        => $data['name'],
        ]);
        return $customer;
    }

    public function tokenizeCard($card){
        $tokenized = $this->stripe->tokens->create([
            'card' => $card,
        ]);
        return $tokenized;
    }

    public function createProduct(){
        return $this->stripe->products->create(['active' => true, 'name' => 'My product']);
    }

    public function createPrice($Price){
        return $this->stripe->prices->create($Price);
    }

    public function startPaymentSession($session){
        $this->stripe->checkout->sessions->create($session);
    }

    public function createPaymentIntent($paymentIntent){
        return $this->stripe->paymentIntents->create($paymentIntent);
    }

    public function paymentStatusCheck($status){
        if ($status === "succeeded"){
            return true;
        }
        return false;
    }
}
