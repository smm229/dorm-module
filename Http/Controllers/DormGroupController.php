<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryBuildingDevice;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryUsersGroup;
use senselink;

class DormGroupController extends Controller
{
    public function __construct()
    {
        $this->senselink = new senselink();
        $this->middleware('AuthDel')->only(['del','del_cate']);
    }


    /**
     * 权限组分配人员
     * @param Request $request
     */
    public function addPerson(Request $request)
    {
        if (!$request['senselink_ids'] || !$request['groupid'] || !$request['type']) {
            return showMsg('参数错误');
        }
        DB::beginTransaction();
        $userIds = $users = explode(',', $request['senselink_ids']);
        $addArr = [
            'type'    => $request['type'],
            'groupid' => $request['groupid']
        ];
        array_walk($users, function (&$value, $key, $addArr){
            $value = array_merge(['senselink_id' => $value], $addArr);
        }, $addArr);
        $inRes = DormitoryUsersGroup::insert($users);
        if ($inRes) {
            $linkRes = $this->senselink->linkperson_addgroup($userIds, $request['groupid']);
            if (isset($linkRes['code']) && $linkRes['message'] == 'OK') {
                DB::commit();
            } else {
                DB::rollBack();
                return showMsg('添加失败');
            }
        }
        return showMsg('添加成功',200);
    }

    /**
     * 权限组移除人员
     * @param Request $request
     */
    public function delPerson(Request $request)
    {
        if (!$request['senselink_ids'] || !$request['groupid'] || !$request['type']) {
            return showMsg('参数错误');
        }
        DB::beginTransaction();
        $users = explode(',', $request['senselink_ids']);
        $inRes = DormitoryUsersGroup::whereIn('senselink_id', $users)->where('groupid', $request['groupid'])->where('type', $request['type'])->delete();
        if ($inRes) {
            $linkRes = $this->senselink->person_delgroup($users, $request['groupid']);
            if (isset($linkRes['code']) && $linkRes['message'] == 'OK') {
                DB::commit();
            } else {
                DB::rollBack();
                return showMsg('移除失败');
            }
        }
        return showMsg('移除成功',200);
    }


    /**
     * 获取组内人员列表
     * @param Request $request
     */
    public function getPersonList(Request $request)
    {
        if (!$request['groupid'] || !$request['type']) {
            return showMsg('参数错误');
        }
        $persondIds = DormitoryUsersGroup::where('type', $request['type'])->where('groupid', $request['groupid'])->pluck('senselink_id')->toArray();
        if ($request['type'] == 1) {
            $res = Student::where(
                function ($req) use ($request, $persondIds) {
                    $req->whereIn('senselink_id', $persondIds);
                if ($request['grade'])     $req->where('grade', $request['grade']);
                if ($request['campusid']) $req->where('campusid', $request['campusid']);
                if ($request['collegeid']) $req->where('collegeid', $request['collegeid']);
                if ($request['majorid'])   $req->where('majorid', $request['majorid']);
                if ($request['classid'])   $req->where('classid', $request['classid']);
                if ($request['search']) {
                    if (is_numeric($request['search'])) {
                        $req->where('idnum', 'like', '%'.$request['search'].'%')->orwhere('ID_number', 'like', '%'.$request['search'].'%');
                    } else {
                        $req->where('username', 'like', '%'.$request['search'].'%');
                    }}})->orderBy('id', 'desc')->paginate($request['pagesize']);
        }
        if ($request['type'] == 2) {
            $res = Teacher::where(function ($req) use ($request, $persondIds) {
                $req->whereIn('senselink_id', $persondIds);
                if ($request['campusid']) $req->where('campusid', $request['campusid']);
                if ($request['departmentid']) $req->where('departmentid', $request['departmentid']);
                if ($request['search']) {
                    if (is_numeric($request['search'])) {
                        $req->where('idnum', 'like', '%'.$request['search'].'%')->orwhere('ID_number', 'like', '%'.$request['search'].'%');
                    } else {
                        $req->where('username', 'like', '%'.$request['search'].'%');
                    }}})->orderBy('id', 'desc')->paginate($request['pagesize']);
        }
        return showMsg('',200, $res);
    }

    /**
     * 获取组内未绑定人员列表
     * @param Request $request
     */
    public function getUnpersonList(Request $request)
    {
        if (!$request['groupid'] || !$request['type']) {
            return showMsg('参数错误');
        }
        $persondIds = DormitoryUsersGroup::where('type', $request['type'])->where('groupid', $request['groupid'])->pluck('senselink_id')->toArray();
        if ($request['type'] == 1) {
            $res = Student::where(
                function ($req) use ($request, $persondIds) {
                    $req->whereNotIn('senselink_id', $persondIds);
                    if ($request['grade'])     $req->where('grade', $request['grade']);
                    if ($request['campusid']) $req->where('campusid', $request['campusid']);
                    if ($request['collegeid']) $req->where('collegeid', $request['collegeid']);
                    if ($request['majorid'])   $req->where('majorid', $request['majorid']);
                    if ($request['classid'])   $req->where('classid', $request['classid']);
                    if ($request['search']) {
                        if (is_numeric($request['search'])) {
                            $req->where('idnum', 'like', '%'.$request['search'].'%')->orwhere('ID_number', 'like', '%'.$request['search'].'%');
                        } else {
                            $req->where('username', 'like', '%'.$request['search'].'%');
                        }}})->orderBy('id', 'desc')->paginate($request['pagesize']);
        }
        if ($request['type'] == 2) {
            $res = Teacher::where(function ($req) use ($request, $persondIds) {
                $req->whereNotIn('senselink_id', $persondIds);
                if ($request['campusid']) $req->where('campusid', $request['campusid']);
                if ($request['departmentid']) $req->where('departmentid', $request['departmentid']);
                if ($request['search']) {
                    if (is_numeric($request['search'])) {
                        $req->where('idnum', 'like', '%'.$request['search'].'%')->orwhere('ID_number', 'like', '%'.$request['search'].'%');
                    } else {
                        $req->where('username', 'like', '%'.$request['search'].'%');
                    }}})->orderBy('id', 'desc')->paginate($request['pagesize']);
        }
        return showMsg('',200, $res);
    }


    /**
     * 获取未绑定组的设备
     * @param Request $request
     */
    public function getDeviceList(Request $request)
    {
        $list = $this->senselink->linkdevice_list('', $request['page'], $request['pageSize']);
        if (isset($list['code']) && $list['message'] == 'OK') {
            $lists = $list['data'];
            //查询已分配组的设备
            $deviceIds = DormitoryBuildingDevice::where('grouptype', 1)->pluck('deviceid')->toArray();
            foreach ($lists['data'] as $k => $v) {
                $lists['data'][$k]['isin']     = 0;
                if (in_array($v['device']['id'], $deviceIds)) {
                    $lists['data'][$k]['isin'] = 1;
                }
            }
        } else {
            return showMsg('获取列表失败');

        }
        return showMsg('获取列表',200, $lists);
    }



}
