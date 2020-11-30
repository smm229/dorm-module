<?php

namespace Modules\Dorm\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Dorm\Entities\DormitoryBeds;
use Modules\Dorm\Entities\DormitoryBuildings;

class DormController extends Controller
{
    public function __construct()
    {
        $this->middleware('refresh');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $list = DormitoryBeds::with(['dormitory_room','student'])->get()->toArray();dd($list);
        $client = new Client();
        $request_url = "http://user.top/api/admin/menu/list";
        $header['Content-type'] = "application/json;charset='utf-8'";
        $header['Authorization'] = "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9hcGl0ZXN0LmhucnR4eC5jb21cL2FwaVwvYWRtaW5cL2xvZ2luIiwiaWF0IjoxNjA2MzY3NzQzLCJleHAiOjE2MDc0NDc3NDMsIm5iZiI6MTYwNjM2Nzc0MywianRpIjoiQXhWNnpqZzdFMEQ1SEY1eSIsInN1YiI6MSwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.qIKEdalsMZhygOyQAEIp8HNqbPYy5z-E8lZNJZ6PRew";
        $response = $client->post($request_url,[
            'headers'  => $header
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        dd(DormitoryBeds::all());
        return view('dorm::index');
    }

    /*
     * 宿舍楼列表
     */
    public function lists(Request $request){
        $pagesize = $request->pagesize ?? 12;
        $list = DormitoryBuildings::with(['dormitory_room','student'])
            ->paginate($pagesize);
        return showMsg('获取成功',200,$list);
    }

}
