<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'ticket_number',
        'status',
        'customer_id',
        'total_price',
        'total_paid',
        'total_paid_with_points',
        'points_gained',
        'points_used_to_pay',
        'payment_type',
        'payment_reference',
        'date',
        'delivered_by'
    ];

    public function getStatus()
    {
        switch ($this->status) {
            case 'p':
                return 'Preparing';
            case 'r':
                return 'Ready';
            case 'd':
                return 'Delivered';
            case 'c':
                return 'Cancelled';
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class, 'order_id');
    }
}
