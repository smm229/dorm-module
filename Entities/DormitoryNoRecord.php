<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//无记录
class DormitoryNoRecord extends Model
{
    use HasFactory,SerializeDate;

    protected $table='dormitory_no_record';
    protected $fillable = [];
}
