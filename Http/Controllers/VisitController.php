<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryAccessRecord;
use Modules\Dorm\Entities\DormitoryGroup;
use Modules\Dorm\Entities\DormitoryGuestAccessRecord;
use Modules\Dorm\Entities\Visit;
use Modules\Dorm\Http\Requests\VisitValidate;
use senselink;

class VisitController extends Controller
{

    use Helpers;
    public function __construct()
    {
        $this->senselink = new senselink();
    }

    /**
     * 添加访客
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function create(VisitValidate $request)
    {
        $visit_place = explode(',', $request['visit_place']);
        $groups = DB::table('dormitory_group')->whereIn('id', $visit_place)->pluck('visitor_groupid')->toArray();
        $groups = implode(',', $groups);
        $headimg['path'] = public_path($request['headimg']);
        $imagetype = substr(strrchr($headimg['path'],'.'),1);
        if (strtolower($imagetype) == 'jpg') {
            $imagetype = 'jpeg';
        }
        $receptionUserId = is_null($request['receptionUserId']) ? 65497088:$request['receptionUserId'];
        $headimg['imgtype'] = 'image/'.$imagetype;
        $result_link = $this->senselink->linkguest_add($request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
        if ($result_link['message'] == 'Similar User Exist') {
            //如果link判断人员已存在，则先判断人员步骤如下：1 黑名单（暂时忽略） 2 学生 教职工 3 已存在的访客

            //2.判断学生or教师
            $stuInfo = Student::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
            if ($stuInfo == true) {
                return $this->response->error('此人员为校内学生, 无法添加为访客',201);
            }
            $teacherInfo = Teacher::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
            if ($teacherInfo == true) {
                return $this->response->error('此人员为校内教职工, 无法添加为访客',201);
            }
            $perInfo = Visit::where('link_id', $result_link['data']['similar_user_id'])->get()->toArray();
            if (!$perInfo) {
                $result_links = $this->senselink->linkguest_del($result_link['data']['similar_user_id']);
                if ($result_links['code'] == 200) {
                    $result_link = $this->senselink->linkguest_add($request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
                }
            } else {
                return $this->response->array(['status_code' => 200, 'message'=> '访客已存在，请重新编辑', 'data' => $perInfo]);
            }
        }
        if ($result_link['code'] == 200 && isset($result_link['code'])) {
            $addArr = [
                'username'        => $request['username'],
                'headimg'         => $request['headimg'],
                'sex'             => $request['sex'],
                'begin_time'      => $request['begin_time'],
                'end_time'        => $request['end_time'],
                'visit_place'     => $request['visit_place'],
                'receptionUserId' => $receptionUserId,
                'link_id'         => $result_link['data']['id']
            ];
            if ($request['mobile']) {
                $addArr['mobile'] = $request['mobile'];
            }
            if ($request['ID_number']) {
                $addArr['ID_number'] = $request['ID_number'];
            }
            if ($request['visit_note']) {
                $addArr['visit_note'] = $request['visit_note'];
            }
            $res = Visit::insertGetId($addArr);
            if ($res) {
                return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
            }
        } else {
            return $this->response->error('添加失败,请联系管理员',201);
        }
    }

    /**
     * 编辑访客
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function edit(VisitValidate $request)
    {
        $perInfo = Visit::where('id', $request['id'])->get()->toArray();
        if (!$perInfo) {
            return $this->response->error('编辑失败',201);
        }
        $visit_place = explode(',', $request['visit_place']);
        $groups = DB::table('dormitory_group')->whereIn('id', $visit_place)->pluck('visitor_groupid')->toArray();
        $groups = implode(',', $groups);
        $headimg['path'] = public_path($request['headimg']);
        $imagetype = substr(strrchr($headimg['path'],'.'),1);
        if (strtolower($imagetype) == 'jpg') {
            $imagetype = 'jpeg';
        }
        $receptionUserId = is_null($request['receptionUserId']) ? 65497088:$request['receptionUserId'];
        $headimg['imgtype'] = 'image/'.$imagetype;
        $result_link = $this->senselink->linkguest_edit($perInfo[0]['link_id'], $request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
        if ($result_link['message'] == 'Similar User Exist') {
            //如果link判断人员已存在，则先判断人员步骤如下：1 黑名单（暂时忽略） 2 学生 教职工 3 已存在的访客

            //2.判断学生or教师
            $stuInfo = Student::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
            if ($stuInfo == true) {
                return $this->response->error('此人员为校内学生, 无法添加为访客',201);
            }
            $teacherInfo = Teacher::where('senselink_id', $result_link['data']['similar_user_id'])->exists();
            if ($teacherInfo == true) {
                return $this->response->error('此人员为校内教职工, 无法添加为访客',201);
            }
            $perInfo = Visit::where('link_id', $result_link['data']['similar_user_id'])->get()->toArray();
            if (!$perInfo) {
                $result_links = $this->senselink->linkguest_del($result_link['data']['similar_user_id']);
                if ($result_links['code'] == 200) {
                    $result_link = $this->senselink->linkguest_edit($perInfo[0]['link_id'], $request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
                }
            }
            if ($perInfo && $perInfo[0]['id'] != $request['id']) {
                return $this->response->array(['status_code' => 200, 'message'=> '访客已存在，请重新编辑', 'data' => $perInfo]);
            }
        }
        if ($result_link['code'] == 200 && isset($result_link['code'])) {
            $addArr = [
                'username'        => $request['username'],
                'headimg'         => $request['headimg'],
                'sex'             => $request['sex'],
                'begin_time'      => $request['begin_time'],
                'end_time'        => $request['end_time'],
                'visit_place'     => $request['visit_place'],
                'receptionUserId' => $receptionUserId
            ];
            if ($request['mobile']) {
                $addArr['mobile'] = $request['mobile'];
            }
            if ($request['ID_number']) {
                $addArr['ID_number'] = $request['ID_number'];
            }
            if ($request['visit_note']) {
                $addArr['visit_note'] = $request['visit_note'];
            }
            $res = Visit::where('link_id', $perInfo[0]['link_id'])->update($addArr);
            if ($res) {
                return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
            }
        } else {
            return $this->response->error('添加失败',201);
        }
    }


    /**
     * 删除访客
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function del(Request $request)
    {
        $perInfo = Visit::where('id', $request['id'])->get()->toArray();
        if (!$perInfo) {
            return $this->response->error('删除失败',201);
        }
        $result_link = $this->senselink->linkguest_del($perInfo[0]['link_id']);
        if ($result_link['code'] == 200 && isset($result_link['code'])) {
           $res = Visit::where('id', $request['id'])->delete();
            if ($res) {
                return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
            }
        } else {
            return $this->response->error('删除失败',201);
        }
    }

    /**
     * 获取访客列表
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function lists(Request $request)
    {
        $res = Visit::where(function ($req) use ($request) {
            if ($request['search'] && is_numeric($request['search'])) $req->where('ID_number', 'like', '%'.$request['search'].'%');
            if ($request['search'] && is_numeric($request['search']) == false) $req->where('username', 'like', '%'.$request['search'].'%');
            if ($request['begin_time']) $req->where('begin_time', '>=', $request['begin_time']);
            if ($request['end_time']) $req->where('end_time', '<=', $request['end_time']);
        })->orderBy('id', 'desc')->paginate($request['pageSize'])->toArray();
        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

    /**
     * 获取访客列表
     * @param VisitValidate $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function logss(Request $request)
    {
        $res = DormitoryGuestAccessRecord::where(function ($req) use ($request) {
            if ($request['buildid'])    $req->where('buildid', $request['buildid']);
            if ($request['begin_time']) $req->where('pass_time', '>=', $request['begin_time']);
            if ($request['end_time'])   $req->where('pass_time', '<=', $request['end_time']);
        })->orderBy('id', 'desc')->paginate($request['pageSize'])->toArray();
        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }

}
