<?php

namespace Modules\Dorm\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Dorm\Http\Requests\DormBlackValidate;
use senselink;

class CampusController extends Controller
{

    /**
     * 校区列表
     * response array
     */
    public function campus(Request $request)
    {
        $pid = $request->fid ?? 1;
        $res = Campus::whereFid($pid)->orderBy('sort', 'asc')->get();
        return showMsg('成功', 200,$res);
    }

}
