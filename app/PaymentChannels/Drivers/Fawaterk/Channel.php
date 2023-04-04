<?php

namespace App\PaymentChannels\Drivers\Fawaterk;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\PaymentChannel;
use App\PaymentChannels\IChannel;
use App\Http\Controllers\web\FawaterkController;
use KingFlamez\Rave\Facades\Rave as FlutterWave;

class Channel implements IChannel
{

    /**
     * Channel constructor.
     * @param PaymentChannel $paymentChannel
     */
    public function __construct(PaymentChannel $paymentChannel)
    {
    }

    public function paymentRequest(Order $order)
    {
        $user = $order->user;
        $generalSettings = getGeneralSettings();

        $currency = currency();
        $cartTotal = $order->total_amount;
        $redirectUrl = $this->makeCallbackUrl($order);

        $customer = [
            'first_name'    => explode(' ', $user->full_name)[0] ?? ' ',
            'last_name'    => explode(' ', $user->full_name)[1] ?? ' ',
            'email'   => $user->email ?? $generalSettings['site_email'],
            'phone'   => $user->mobile ?? '--',
            'address' => $user->address ?? 'not provided'
        ];
        $cartItems = [];
        foreach ($order->orderItems as $key => $item) {
            array_push($cartItems, ['name' => $item->getSlug() ?? "Item " . ($key + 1), 'price' => $item->total_amount, 'quantity' => 1]);
        }

        $fawaterk = new FawaterkController;

        // fill the object with the correct data
        $fawaterk->setVendorKey(env('FAWATERK_KEY'))
            ->setCartItems($cartItems)
            ->setCustomer($customer)
            ->setCartTotal($cartTotal)
            ->setRedirectUrl($redirectUrl)
            ->setCurrency($currency);

        // send the request and receive the invoice url
        return $fawaterk->getInvoiceUrl();
    }

    private function makeCallbackUrl($order)
    {
        $callbackUrl = route('payment_verify', [
            'gateway' => 'Fawaterk',
            'order_id' => $order->id
        ]);

        return $callbackUrl;
    }

    public function verify(Request $request)
    {
        $invoice_id = $request->get('invoice_id');
        $fawaterk = new FawaterkController;
        $invoiceData = $fawaterk->getInvoiceData($invoice_id);

        $user = auth()->user();
        $order = Order::where('Fawaterk_invoice_id', $invoice_id)
            ->where('user_id', $user->id)
            ->with('user')
            ->first();

        if (!empty($order)) {
            if ($invoiceData->status == 'success' && $invoiceData->data->paid) {
                $order->update([
                    'status' => Order::$paying
                ]);

                return $order;
            } else {
                if (!empty($order)) {
                    $order->update(['status' => Order::$fail]);
                }
                $toastData = [
                    'title' => trans('cart.fail_purchase'),
                    'msg' => 'You canceled payment request',
                    'status' => 'error'
                ];

                return back()->with(['toast' => $toastData])->withInput();
            }
        }

        return $order;
    }
}
