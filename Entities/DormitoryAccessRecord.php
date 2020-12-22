<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryAccessRecord extends Model
{
    use HasFactory,SerializeDate;

    protected $table='dormitory_access_record';
    protected $fillable = [];
    protected $appends = ['type_name'];

    /*
     * 类型
     */
    public function getTypeNameAttribute()
    {
        return $this->type==1 ? '学员': '教职工';
    }

}
