<?php

namespace Modules\Dorm\Entities;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = 'dormitory_guest';
    protected $appends = ['visitplacename', 'sex_name', 'receptionusername'];

    public function getSexNameAttribute()
    {
        $sex = ['1'=>'男', '2'=>'女', '3' => '保密'];
        return $this->sex ? $sex[$this->sex] : '保密';
    }

    public function getVisitplacenameAttribute()
    {
        $this->visit_place = explode(',', $this->visit_place);
        return DormitoryGroup::whereIn('id', $this->visit_place)->get()->toArray();
    }

    public function getReceptionusernameAttribute()
    {
         return Teacher::where('senselink_id', $this->receptionUserId)->value('username');
    }


}
