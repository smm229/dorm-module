<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/3/10
 * Time: 10:33
 */

namespace Modules\Dorm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Extend\SenseNebula;


class NebulaController extends Controller
{

    public function __construct()
    {
        $this->nebula = new SenseNebula();
    }

    public function PersonPackageList(Request $request){
        $file=$request->file('img');
        $param = [
                [
                    'name'     => 'msg_id',
                    'contents' => '1029'
                ],
                [
                    'name'     => 'lib_id',
                    'contents' => 1
                ],
                [
                    'name'     => 'img',
                    'contents' => fopen($file->getRealPath(), 'r')
                ]
        ];
        $rest = $this->nebula->PersonAdd($param);
        dd($rest);
    }
}