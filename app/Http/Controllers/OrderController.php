<?php
namespace App\Http\Controllers;

use App\Mail\ChangeStatusNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Fruit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index()
    {
        try {
            if(auth()->user()->role === 'admin'){
                $orders = Order::with('orderItems.fruit')->get();

                return response()->json([
                    'status' => true,
                    'data' => $orders,
                    'message' => 'Orders retrieved successfully'
                ], 200);
            }else {
                $orders = Order::where('user_id', auth()->id())
                    ->with('orderItems.fruit')
                    ->orderBy('id', 'desc')
                    ->get();

                return response()->json([
                    'status' => true,
                    'data' => $orders,
                    'message' => 'Your orders retrieved successfully'
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.fruit_id' => 'required|exists:fruits,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems = [];

            // Check stock and calculate total
            foreach ($request->items as $item) {
                $fruit = Fruit::find($item['fruit_id']);

                if ($fruit->stock < $item['quantity']) {
                    return response()->json([
                        'status' => false,
                        'message' => "Not enough stock for {$fruit->name}. Available: {$fruit->stock}",
                        'data' => null
                    ], 400);
                }

                $itemTotal = $fruit->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'fruit_id' => $fruit->id,
                    'quantity' => $item['quantity'],
                    'price' => $fruit->price,
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'delivery_address' => $request->delivery_address,
                'status' => 'processing',
            ]);

            // Create order items and update stock
            foreach ($orderItems as $item) {
                $order->orderItems()->create($item);

                // Update fruit stock
                $fruit = Fruit::find($item['fruit_id']);
                $fruit->decrement('stock', $item['quantity']);
            }

            DB::commit();

            $order->load('orderItems.fruit');

            return response()->json([
                'status' => true,
                'data' => $order,
                'message' => 'Order placed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Order $order)
    {
        try {
            // Check if user owns this order
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only view your own orders',
                    'data' => null
                ], 403);
            }

            $order->load('orderItems.fruit');

            return response()->json([
                'status' => true,
                'data' => $order,
                'message' => 'Order retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Order $order)
    {

        // If only updating status
        if ($request->has('status') && count($request->all()) === 1) {
            $request->validate([
                'status' => 'required|string|in:processing,shipped,delivered,cancelled',
            ]);

            $order->update(['status' => $request->status]);
            $order->load('orderItems.fruit');

            $this->notifyCustomersAboutChangeStatus($order);

            return response()->json([
                'status' => true, // Note: changed to match frontend expectation
                'data' => $order,
                'message' => 'Order status updated successfully'
            ], 200);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.fruit_id' => 'required|exists:fruits,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|array',
            'status' => 'required|string|in:processing,shipped,delivered,cancelled',
        ]);

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems = [];

            // Check stock and calculate total
            foreach ($request->items as $item) {
                $fruit = Fruit::find($item['fruit_id']);

                if ($fruit->stock < $item['quantity']) {
                    return response()->json([
                        'status' => false,
                        'message' => "Not enough stock for {$fruit->name}. Available: {$fruit->stock}",
                        'data' => null
                    ], 400);
                }

                $itemTotal = $fruit->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'fruit_id' => $fruit->id,
                    'quantity' => $item['quantity'],
                    'price' => $fruit->price,
                ];
            }

            // Update order
            $order->update([
                'total_amount' => $totalAmount,
                'delivery_address' => $request->delivery_address,
                'status' => $request->status,
            ]);

            // Update order items and stock
            foreach ($orderItems as $item) {
                $order->orderItems()->updateOrCreate(
                    ['fruit_id' => $item['fruit_id']],
                    ['quantity' => $item['quantity'], 'price' => $item['price']]
                );

                // Update fruit stock
                $fruit = Fruit::find($item['fruit_id']);
                $fruit->decrement('stock', $item['quantity']);
            }

            DB::commit();

            $order->load('orderItems.fruit');

            return response()->json([
                'status' => true,
                'data' => $order,
                'message' => 'Order updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function notifyCustomersAboutChangeStatus($order)
    {
        \Log::debug('-----------------------------------------------------');
        \Log::debug("order data : " . json_encode($order->toArray()));

        $user = User::find($order->user_id);

        if (!$user || !$user->email) {
            \Log::debug('User not found or email missing for order status notification.', [
                'order_id' => $order->id,
                'user' => $user
            ]);
            return;
        }

        try {
            // Create database notification
            $user->notifications()->create([
                'user_id' => $order->user_id,
                'type' => 'status_update',
                'message' => "Your order #{$order->id} status has been updated to {$order->status}.",
                'data' => json_encode([  // Convert array to JSON string
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'total_amount' => $order->total_amount,
                ])
            ]);
            \Log::debug('Database notification created for user.', [
                'user_id' => $user->id,
                'order_id' => $order->id
            ]);

            // Send email notification
            Mail::to($user->email)->send(new ChangeStatusNotification($order, $user));
            \Log::debug('Email notification sent.', [
                'user_email' => $user->email,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to notify user_id {$user->id} about order status update: " . $e->getMessage());
        }
        \Log::debug('-----------------------------------------------------');
    }

}
