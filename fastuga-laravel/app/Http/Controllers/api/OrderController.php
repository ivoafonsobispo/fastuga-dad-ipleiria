<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpdateOrderItemsRequest;
use App\Http\Requests\StoreUpdateOrderRequest;
use App\Http\Resources\OrderItemsResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;

class OrderController extends Controller
{

    public function getOrdersStatus()
    {
        return Order::groupBy('status')->pluck('status');
    }

    public function getOrderByStatus(String $status)
    {
        $status = strtoupper($status);
        if ($status == 'P' or  $status == 'R' or $status == 'D' or $status == 'C') {
            return Order::where('status', $status)->with("orderItems", "orderItems.product")->get();
        }
    }

    public function getOrderByStatusTAES(String $status)
    {
        $status = strtoupper($status);
        if ($status == 'P' or  $status == 'R' or $status == 'D' or $status == 'C') {
            return OrderResource::collection(Order::where('status', $status)->get()); 
        }
    }

    public function getOrderOfOrderItems(OrderItems $orderItems)
    {
        return new OrderResource($orderItems->order);
    }

    public function index()
    {
        return Order::all();
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try{
            $orderRequest = new StoreUpdateOrderRequest($request->all());
            $validatedOrder = $orderRequest->validate($orderRequest->rules());
            $newOrder = Order::create($validatedOrder);
            $newOrder->save();

            $orderItemsArray = $request->order_items;
            $local_number = 0;
            foreach($orderItemsArray as $item){
                $this->store_each_order_item($item, $newOrder['id'], $local_number);
                $local_number++;
            }
            
            if($newOrder['customer_id'] != null){
                $customer = Customer::find($newOrder['customer_id']);
                $previousPoints = $customer->points;
                $customer->points = $previousPoints - $newOrder['points_used_to_pay'] + $newOrder['points_gained'];
                $customer->save();
            }

            DB::commit();
            return new OrderResource($newOrder);
        }catch(\Exception $e){
            DB::rollBack();
            return response($e->getMessage());
        }
    }

    function store_each_order_item($item, $order_id, $local_number){
        $status = $item['type'] == 'hot dish' ? 'W' : 'R';
        $itemRequest = new StoreUpdateOrderItemsRequest([
            'order_id' => $order_id,
            'order_local_number' => $local_number,
            'product_id' => $item['id'],
            'status' => $status,
            'price' => $item['price'],
            'preparation_by' => null,
            'notes' => null,
            'custom' => null
        ]);
        $validateItem = $itemRequest->validate($itemRequest->rules());
        $newItem = OrderItems::create($validateItem);
        $newItem->save();
    }

    public function show(Order $order)
    {
        return new OrderResource($order);
    }

    public function update(StoreUpdateOrderRequest $request, Order $order)
    {
        $order->fill($request->validated());
        $order->custom = json_encode($request["custom"]);
        $order->save();
        return new OrderResource($order);
    }

    public function destroy(Order $order)
    {
        $order->delete();
    }
}
