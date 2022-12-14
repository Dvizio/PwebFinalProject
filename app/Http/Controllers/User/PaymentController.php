<?php

namespace App\Http\Controllers\User;

use App\Models\Order;
use App\Models\Invoice;
use App\Constants\Status;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\OrderItemStatus;
use App\Constants\PaymentStatus;
use App\Services\MidtransPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Gloudemans\Shoppingcart\Facades\Cart;

class PaymentController extends Controller
{
    public static $tax = 1 / 100; // 1%

    private $service;

    public function __construct()
    {
        $this->service = new MidtransPayment();
    }

    public function index(): View
    {
        $title = 'Payment Method';
        $price = Cart::total();

        $additionalPrice = ceil($price * self::$tax);

        return view('frontend.user.payment.index', compact('title', 'price', 'additionalPrice'));
    }

    public function saveOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            $price = Cart::total();
            $additionalPrice = ceil($price * PaymentController::$tax);

            $totalPrice = intval($price + $additionalPrice);
            $order = Order::create(['user_id' => Auth::guard('user')->id(), 'price' => $totalPrice, 'external_id' => \uniqid()]);

            $orderList = [];

            foreach (Cart::content() as $cart) {
                $orderList[] = [
                    'train_date' => $cart->options->train_date,
                    'train_since' => $cart->options->train_since,
                    'train_until' => $cart->options->train_until,
                    'coach_domain_id' => $cart->id,
                    'order_id' => $order->id,
                    'price' => $cart->total,
                ];
            }

            foreach ($orderList as $key => $value) {
                $orderItem = OrderItem::create($value);
                OrderItemStatus::Create(['order_item_id' => $orderItem->id]);
            }

            [$snap, $clientToken] = $this->service->index($order, Auth::guard('user')->user());
            if($snap == "")
            {
                throw new \Exception('cant generate payment link');
            }

            Invoice::create([
                'order_id' => $order->id,
                'issued_at' => null,
                'transaction_status' => PaymentStatus::PENDING->value,
                'external_id' => $order->external_id,
                'snap_token' => $snap,
            ]);


            Cart::destroy();

            DB::commit();

            return redirect()->action([PaymentController::class, 'midtransView'], compact('snap', 'clientToken'));

        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
        }
    }

    public function midtransView($snap, $clientToken)
    {
        return view('frontend.user.payment.midtrans-payment', compact('snap', 'clientToken'));
    }

    public function callback(Request $request)
    {
        $data = json_decode($request->get('json'));
        if(empty($data->order_id))
        {
            return redirect()->route('user.history.view');
        }

        $order = Order::findOrFail($data->order_id);

        $invoice = Invoice::where('order_id', $order->id)->first();
        if(!$invoice) {
            abort(404);
        }

        try {
            DB::beginTransaction();

            switch($data->transaction_status) {
                case PaymentStatus::SETTLEMENT->value:
                    $invoice->status_message = $data->status_message;
                    $invoice->midtrans_transaction_id = $data->transaction_id ?? null;
                    $invoice->payment_type = $data->payment_type ?? null;
                    $invoice->issued_at = $data->transaction_time ?? null;

                    $invoice->transaction_status = $data->transaction_status ?? null;
                    $invoice->fraud_status = $data->fraud_status ?? null;
                    $invoice->pdf_url = $data->pdf_url ?? null;
                    $invoice->save();

                    DB::commit();

                    return redirect()->route('user.history.view');

                case PaymentStatus::PENDING->value:
                    $invoice->payment_type = $data->payment_type ?? null;
                    $invoice->transaction_status = $data->transaction_status ?? null;
                    $invoice->pdf_url = $data->pdf_url ?? null;

                default:
                    $invoice->status_message = $data->status_message;
                    $invoice->midtrans_transaction_id = $data->transaction_id ?? null;
                    $invoice->payment_type = $data->payment_type ?? null;
                    $invoice->issued_at = null;

                    $invoice->transaction_status = $data->transaction_status ?? null;
                    $invoice->fraud_status = $data->fraud ?? null;
                    $invoice->save();

                    DB::commit();

                    return redirect()->route('user.history.view');
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function repayment(Invoice $invoice)
    {
        $clientToken = $this->service->clientToken;
        $snap = $invoice->snap_token;
        return redirect()->action([PaymentController::class, 'midtransView'], compact('snap', 'clientToken'));
    }
}
