<?php

namespace Modules\Dorm\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use senselink;

class FacilityController extends Controller
{
    use Helpers;
    public function test(Request $request)
    {
        $senselink = new senselink();
        $groupname = '大黑';
        $name = '宏达';
        $userId = '4';
        $deviceid = 1;
        $dateTimeFrom = '2020-12-07 12:00:00';
        $dateTimeTo   = '2020-12-09 17:00:00';
        $avatarFile   = $request->file('avatarFile');
        //$res = $senselink->linkguest_del('41', $name, '35', $avatarFile, $dateTimeFrom, $dateTimeTo);
        //$res = $senselink->linkdevice_edit($deviceid);
        //$res = $senselink->linkblacklist_moveout('35');
        $res = $senselink->linkperson_del('42');
//        $res = $senselink->linkperson_add($name, $avatarFile, $groups = '1', $icNumber = '123', $jobNumber = '123', $mobile = '111111', $remark = '123');
        dd($res);
    }
}
