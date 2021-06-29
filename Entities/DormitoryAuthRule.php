<?php

namespace Modules\Dorm\Entities;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DormitoryAuthRule extends Model
{
    use HasFactory,SerializeDate;

    protected $table = 'dormitory_auth_rule';

    /**
     * 查看是否超过三级
     * @param $fid
     * @return bool
     */
    public static function level3($fid){
        if($fid==0) return false;

        $top = self::find($fid);
        if($top->fid>0){ //三级
            $top2 = self::find($top->fid);
            if($top2->fid>0){
                return true;
            }
        }
        return false;

    }
}
