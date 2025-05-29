<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vulnerable extends Model
{
    use HasFactory;
    protected $table = 'vulnerables';
    protected $fillable = ['asset_id', 'vulnerability_id'];

    // protected $guarded = [];

    public function assets(){
        return $this->hasOne(Asset::class, 'id', 'asset_id');
    }
    public function vulnerabilities(){

        return $this->hasOne(Vulnerability::class, 'id', 'vulnerablity_id');
    }

}
