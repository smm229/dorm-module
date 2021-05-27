<?php
namespace Modules\Dorm\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Traits\SerializeDate;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Modules\Dorm\Entities\DormitoryAuthRule;
use Modules\Dorm\Http\Requests\AuthRuleValidate;

/**
 * 菜单规则
 * Class AuthRuleController
 * @package App\Api\Controllers\V1
 */
class AuthRuleController extends Controller{
    use SerializeDate;
    use Helpers;

    /**
     * 菜单规则列表
     * @param Request $request
     */
    public function lists(Request $request){

        $list = DormitoryAuthRule::where(['type' => 1, 'disable' => 0])->orderBy('sort','asc')->get()->toArray();

        $list = self::arr2tree($list,'id','fid','children');

        return $this->response->array(['status_code' => 200, 'message'=> '成功', 'data' => $list]);

    }

    /**
     * 添加菜单规则
     * @param AuthRuleValidate $request
     */
    public function add(AuthRuleValidate $request){
        if(DormitoryAuthRule::level3($request->fid)){
            return $this->response->error('分类不得超过三级',201);
        }
        $data = $request->only('title','fid','url','sort','icon','component','router','disable','type');
        try {
            if (DormitoryAuthRule::insert($data)) {

                return $this->response->array(['status_code' => 200, 'message'=> '成功']);
            }
            throw new \Exception('添加失败');
        }catch(\Exception $e){
            return $this->response->error($e->getMessage(),201);
        }
    }

    /**
     * 编辑菜单规则
     * @param AuthRuleValidate $request
     */
    public function edit(AuthRuleValidate $request){

        if(DormitoryAuthRule::level3($request->fid)){
            return $this->response->error('分类不得超过三级',201);
        }
        if(!$request->id){
            return $this->response->error('请选择要编辑的菜单',201);
        }
        $data = $request->only('title','fid','url','sort','icon','component','router','disable','type');
        try {
            if(DormitoryAuthRule::whereId($request->id)->update($data)){
                return $this->response->array(['status_code' => 200, 'message'=> '成功']);
            }
            throw new \Exception('编辑失败');
        }catch(\Exception $e){
            return $this->response->error($e->getMessage(),201);
        }
    }

    /**
     * 删除菜单规则
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function del(Request $request){
        $menu = DormitoryAuthRule::find($request->id);
        if(!$menu){
            return $this->response->error('菜单不存在',201);
        }
        if(DormitoryAuthRule::whereFid($request->id)->first()){
            return $this->response->error('存在下级菜单，禁止删除',201);
        }
        DormitoryAuthRule::whereId($request->id)->delete();
        return $this->response->array(['status_code' => 200, 'message'=> '成功']);
    }


    /**
     * 生成数据树
     * @param array $list 数据列表
     * @param string $id 父ID Key
     * @param string $pid ID Key
     * @param string $son 定义子数据Key
     */
    public static function arr2tree($list, $id = 'id', $pid = 'pid', $son = 'sub')
    {
        list($tree, $map) = [[], []];
        foreach ($list as $item) {
            $map[$item[$id]] = $item;
        }

        foreach ($list as $item) {
            if (isset($item[$pid]) && isset($map[$item[$pid]])) {
                $map[$item[$pid]][$son][] = &$map[$item[$id]];
            } else {
                $tree[] = &$map[$item[$id]];
            }
        }
        unset($map);
        return $tree;
    }

}
