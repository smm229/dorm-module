<?php

namespace Modules\Dorm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBuildings;
use Modules\Dorm\Entities\DormitoryCategory;
use Modules\Dorm\Entities\DormitoryRoom;
use Modules\Dorm\Entities\DormitoryUsersBuilding;
use Modules\Dorm\Http\Requests\DormitoryBuildingsValidate;

class DormController extends Controller
{

    public function __construct()
    {
        $this->middleware('AuthDel')->only(['del','del_cate']);
    }

    /*
     * 宿舍楼列表
     */
    public function lists(Request $request){
        $pagesize = $request->pagesize ?? 12;
        //只查询自己权限的楼宇
        $uid = auth()->user() ? auth()->user()->id : 1;//白名单
        $idnum = auth()->user() ? auth()->user()->idnum : '';

        $list = DormitoryBuildings::select('id', 'title', 'floor')
            ->where(function ($query) use ($idnum,$uid){
                //根据人员查询对应楼宇
                if($uid!=1)  $query->whereIn('id',function ($q) use ($idnum){
                    $q->from('dormitory_users_building')->where('idnum',$idnum)->pluck('buildid');
                });
            })
            ->with(['dormitory_users' => function ($q) {
                $q->select('dormitory_users.id', 'dormitory_users.username', 'dormitory_users.idnum');
            }])
            ->paginate($pagesize);

        return showMsg('获取成功',200,$list);
    }

    /*
     * 添加楼宇
     * @param title 名称
     * @param buildtype 楼宇类型id
     * @param floor 楼层
     * @param ename 英文名称
     * @param icon 图标
     * @param teachers 宿管老师idnum集合
     */
    public function add(DormitoryBuildingsValidate $request){
        try{
            if(DormitoryBuildings::whereTitle($request->title)->first()){
                throw new \Exception('请更换名称');
            }
            DB::transaction(function () use ($request){
                $buildid = DormitoryBuildings::insertGetId([
                    'title'         =>  $request->title,
                    'buildtype'    =>  $request->buildtype,
                    'floor'         =>  $request->floor,
                    'ename'         =>  $request->ename ?? '',
                    'icon'          =>  $request->icon ?? ''
                ]);
                $users = explode(',',$request->teachers);
                $build = ['buildid'=>$buildid];
                //宿管关联表
                array_walk($users, function (&$value, $key, $build) {
                    $value = array_merge(['idnum'=>$value], $build);
                }, $build);
                DormitoryUsersBuilding::insert($users);
            });
            return showMsg('操作成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }
    }

    /*
    * 编辑楼宇
    * @param title 名称
    * @param buildtype 楼宇类型id
    * @param floor 楼层
    * @param ename 英文名称
    * @param icon 图标
    * @param teachers 宿管老师idnum集合
    */
    public function edit(DormitoryBuildingsValidate $request){
        try{
            if($info = DormitoryBuildings::whereId($request->id)->first()){
                throw new \Exception('信息不存在');
            }
            if(DormitoryBuildings::whereTitle($request->title)->where('id','<>',$request->id)->first()){
                throw new \Exception('请更换名称');
            }
            //查看楼层
            if($request->floor<$info->floor){
                throw new \Exception('楼层不能低于原楼层');
            }
            DB::transaction(function () use ($request,$info){
                $info->title         =  $request->title;
                $info->buildtype    =  $request->buildtype;
                $info->floor         =  $request->floor;
                $info->ename         =  $request->ename ?? '';
                $info->icon          =  $request->icon ?? '';
                $info->save();
                $build = ['buildid'=>$info->id];

                DormitoryUsersBuilding::where($build)->delete();
                $users = explode(',',$request->teachers);
                //宿管关联表
                array_walk($users, function (&$value, $key, $build) {
                    $value = array_merge(['idnum'=>$value], $build);
                }, $build);
                DormitoryUsersBuilding::insert($users);
            });
            return showMsg('操作成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }
    }

    /*
     * 删除楼宇
     */
    public function del(Request $request){
        if(!DormitoryBuildings::whereId($request->id)->first()){
            return showMsg('信息不存在');
        }
        if(DormitoryUsersBuilding::where('buildid',$request->id)->count()>0 || DormitoryRoom::where('buildid',$request->id)->count()>0){
            return showMsg('无法删除');
        }
        DormitoryBuildings::whereId($request->id)->delete();
        return showMsg('删除成功',200);
    }

    /*
     * 添加楼宇类型
     * @param name string 名称
     * @param sort int 排序
     * @param describ string 描述
     */
    public function add_cate(Request $request){
        if(!$request->name){
            return showMsg('请填写名称');
        }
        $ckey = $request->ckey ?? 'dormitory';
        if(DormitoryCategory::whereName($request->name)->where( 'ckey' ,$ckey)->first()){
            return showMsg('名称已存在');
        }
        try {
            DormitoryCategory::insert([
                'name' => $request->name,
                'ckey' => $ckey,
                'sort' => $request->sort ?? 0,
                'describ' => $request->describ ?? ''
            ]);
            return showMsg('添加成功',200);
        }catch(\Exception $e){
            return showMsg('添加失败');
        }

    }

    /*
     * 编辑楼宇类型
     * @param id int id
     * @param name string 名称
     * @param sort int 排序
     * @param describ string 描述
     */
    public function edit_cate(Request $request){
        if(!$request->name) return showMsg('请填写名称');

        if(!$info = DormitoryCategory::find($request->id)){
            return showMsg('信息不存在');
        }
        if($r=DormitoryCategory::whereName($request->name)->where( 'ckey' ,$info->ckey)->first()){
            if($r->id !=$request->id)  return showMsg('名称已存在');
        }
        try {
            $info->name = $request->name;
            $info->sort = $request->sort ?? 0;
            $info->describ = $request->describ ?? '';
            $info->save();
            return showMsg('编辑成功',200);
        }catch(\Exception $e){
            return showMsg('编辑失败');
        }
    }

    /*
     * 删除楼宇类型
     */
    public function del_cate(Request $request){
        $info = DormitoryCategory::find($request->id);
        if(!$info){
            return showMsg('信息不存在');
        }
        if($info->ckey=='dormitory') { //楼宇
            if (DormitoryBuildings::where('buildtype', $request->id)->count() > 0) {
                return showMsg('无法删除');
            }
        }else{
            if(DormitoryRoom::where('buildtype',$request->id)->count()>0){
                return showMsg('无法删除');
            }
        }
        if(DormitoryCategory::where('ckey',$info->ckey)->whereId($request->id)->delete()) {
            return showMsg('删除成功', 200);
        }
        return showMsg('删除失败');
    }

    /*
     * 类型列表
     */
    public function cate_list(Request $request){
        $ckey = $request->ckey ?? 'dormitory';
        $list = DormitoryCategory::where('ckey',$ckey)
            ->orderBy('sort','asc')
            ->get(['id','name']);
        return showMsg('获取成功',200,$list);
    }
}
