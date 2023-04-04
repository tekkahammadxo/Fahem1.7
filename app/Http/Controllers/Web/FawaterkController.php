<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FawaterkController extends Controller
{
    /**
     * @var string $apiUrl
     */
    private $apiUrl = "https://app.fawaterk.com/api/";
    /**
     * @var string $vendorKey
     */
    private $vendorKey;

    /**
     * @var array $cartItems
     */
    private $cartItems;

    /**
     * @var string $redirectUrl
     */
    private $redirectUrl;

    /**
     * @var float $cartTotal
     */
    private $cartTotal;

    /**
     * @var array $customer
     */
    private $customer;

    /**
     * @var string $currency
     */
    private $currency;


    /**
     * Set vendor key.
     * @var string $vendorKey
     */
    public function setVendorKey(string $vendorKey)
    {
        $this->vendorKey = $vendorKey;

        return $this;
    }


    /**
     * Set Cart Items
     * @var array $cartItems
     */
    public function setCartItems(array $cartItems)
    {
        $this->cartItems = $cartItems;

        return $this;
    }


    /**
     * set redirect url
     * @var string $redirectUrl
     */
    public function setRedirectUrl(string $redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }


    /**
     * Set cart total
     * @var float $cartTotal
     */
    public function setCartTotal(float $cartTotal)
    {
        $this->cartTotal = $cartTotal;

        return $this;
    }


    /**
     * Set customer
     * @var array $customer
     */
    public function setCustomer(array $customer)
    {
        $this->customer = $customer;

        return $this;
    }


    /**
     * Set currency
     * @var string $currency
     */
    public function setCurrency(string $currency)
    {
        $this->currency = strtoupper($currency);

        return $this;
    }


    /**
     * Sends the request and gets back the invoice URL.
     */
    public function getInvoiceUrl()
    {
        $data = [
            "vendorKey"     => $this->vendorKey,
            "cartItems"     => $this->cartItems,
            "cartTotal"     => $this->cartTotal,
            "customer"      => $this->customer,
            'redirectUrl'   => $this->redirectUrl,
            'currency'      => $this->currency
        ];

        $response = $this->send($data);

        if (property_exists($response, 'url')) {
            return $response;
        }

        throw new \Exception("Invalid Response! " . $response->error);
    }
    /**
     * Send the request to the API
     */
    public function send(array $data)
    {
        $data = json_encode($data);

        $ch = curl_init($this->apiUrl . 'invoice');

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );

        $curlResult = curl_exec($ch);
        return json_decode($curlResult);
    }

    public function getInvoiceData($invoice_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl . "v2/getInvoiceData/" . $invoice_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization: Bearer " . env('FAWATERK_KEY')
            ),
        ));

        $response = json_decode(curl_exec($curl));
        curl_close($curl);
        return $response;
    }
}
