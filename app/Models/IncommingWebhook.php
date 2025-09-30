<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncommingWebhook extends Model
{
    //
    protected $table = 'incoming_webhooks';
    protected $primaryKey = 'id';

    protected $fillable = ['event', 'payload'];
}
