<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\Customer;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'status' => $this->status,
            'customer_id' => new CustomerResource(Customer::find($this->customer_id)),
            'total_price' => $this->total_price,
            'total_paid' => $this->total_paid,
            'total_paid_with_points' => $this->total_paid_with_points,
            'points_gained' => $this->points_gained,
            'points_used_to_pay' => $this->points_used_to_pay,
            'payment_type' => $this->payment_type,
            'payment_reference' => $this->payment_reference,
            'date' => $this->date,
            'delivered_by' => new UserResource(User::find($this->delivered_by)),
            'custom' => $this->custom, 
            'updated_at' => $this->updated_at
        ];
    }
}
