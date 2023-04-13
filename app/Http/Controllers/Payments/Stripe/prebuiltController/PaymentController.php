<?php

namespace App\Http\Controllers\Payments\Stripe\prebuiltController;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PaymentController extends Controller
{

    use ApiResponseHandler;

    private $interval ;
    private $currentProductID ;

    private $currentSessionID ;

    private $UserID ;

    private $cachedData ;


    public function execPayment(Request $request)
    {

        $this->interval = $request->interval;

        $price_id = $this->SwitchPrice($this->interval);

        $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));


        $session = $stripe->checkout->sessions->create([
            'line_items' => [
                [
                    'price' => $price_id,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'subscription',
            'currency' => "EGP",
            'success_url' => 'http://127.0.0.1:8000/api/PaymentSuccess',
            'cancel_url' =>  'http://127.0.0.1:8000/api/PaymentCanceled',
        ]);

        $this->currentSessionID = $session->id;

        $this->UserID = auth("sanctum")->user()->id;

        $this->setCurrentProductID();

        $this->CacheData();

        return $this->returnData('url',$session->url,"here is the stripe url");

    }

    public function success()
    {
        $this->cachedData = Cache::get('key');

        $this->attachPaymentToDataBase();

        //return response()->json("payment has done successfully.");
        return view("payment.success");
    }

    public function cancel(): \Illuminate\Http\JsonResponse
    {
        return response()->json("something went wrong , try again later.");
    }

    private function SwitchPrice($interval)
    {
        return Product::where("interval",$interval)->first()->price_id;
    }


    private function setCurrentProductID()
    {
        $this->currentProductID = Product::where("interval",$this->interval)->first()->id;
    }


    private function CacheData()
    {
        $cache = [
            'user_id'       => $this->UserID,
            'session_id'    => $this->currentSessionID,
            'product_id'    => $this->currentProductID,
        ];
        Cache::put('key', json_encode($cache), now()->addMinutes(10));
    }

    private function attachPaymentToDataBase()
    {
        $User = User::find(json_decode($this->cachedData,true)['user_id']);

        $currentSessionID = json_decode($this->cachedData,true)['session_id'];

        $currentProductID = json_decode($this->cachedData,true)['product_id'];

        $product = Product::find($currentProductID);

        $product->users()->attach($User,[
            'session_id' => $currentSessionID
        ]);
    }

}
