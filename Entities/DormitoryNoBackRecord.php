<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//未归
class DormitoryNoBackRecord extends Model
{
    use HasFactory,SerializeDate;

    protected $table='dormitory_no_back_record';
    protected $fillable = [];
}
