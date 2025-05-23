<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendedLead extends Model
{
    protected $fillable = ['service_id', 'seller_id','buyer_id','lead_id','bid','distance','purchase_type'];

    public function sellers()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyers()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function leads()
    {
        return $this->belongsTo(LeadRequest::class, 'buyer_id');
    }
}
