<?php

namespace Modules\Dorm\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Entities\DormitoryGroup;
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
        $Res = config('filesystems.disks.public.root');
        $headimg['path'] = $Res.$request['headimg'];
        $imagetype = substr(strrchr($headimg['path'],'.'),1);
        if (strtolower($imagetype) == 'jpg') {
            $imagetype = 'jpeg';
        }
        $receptionUserId = is_null($request['receptionUserId']) ? 157:$request['receptionUserId'];
        $headimg['imgtype'] = 'image/'.$imagetype;
        $result_link = $this->senselink->linkguest_add($request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
        if ($result_link['message'] == 'Similar User Exist') {
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
        $Res = config('filesystems.disks.public.root');
        $headimg['path'] = $Res.$request['headimg'];
        $imagetype = substr(strrchr($headimg['path'],'.'),1);
        if (strtolower($imagetype) == 'jpg') {
            $imagetype = 'jpeg';
        }
        $receptionUserId = is_null($request['receptionUserId']) ? 157:$request['receptionUserId'];
        $headimg['imgtype'] = 'image/'.$imagetype;
        $result_link = $this->senselink->linkguest_edit($perInfo[0]['link_id'], $request['username'], $receptionUserId, $headimg, $request['begin_time'], $request['end_time'], $groups);
        if ($result_link['code'] == 200 && isset($result_link['code'])) {
            $addArr = [
                'username'    => $request['username'],
                'headimg'     => $request['headimg'],
                'sex'         => $request['sex'],
                'begin_time'  => $request['begin_time'],
                'end_time'    => $request['end_time'],
                'visit_place' => $request['visit_place'],
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
        $res = Visit::orderBy('id', 'desc')->paginate($request['pageSize'])->toArray();
        if (!$res) {
            return $this->response->error('获取失败',201);
        }
        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $res]);
    }
}
