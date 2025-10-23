<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Pixel extends Model {
    protected $fillable = ['x','y','color','tx_signature','buyer_address'];
}
