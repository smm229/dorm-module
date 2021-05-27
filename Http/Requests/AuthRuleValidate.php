<?php

namespace Modules\Dorm\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;


class AuthRuleValidate extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title'     =>  'required',
            'url'       =>  'required',//后端路由
            'router'    =>  'required' //前端路由
        ];
    }

    public function messages()
    {
        return [
            'title.required'     =>  '请输入菜单名称',
            'url.required'       =>  '缺少后端路由',
            'router.required'    => '缺少前端路由'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $error= $validator->errors()->all();
        throw new HttpResponseException($this->fail(201, $error));
    }

    protected function fail(int $code, array $errors) : JsonResponse
    {
        $msg =  array_first($errors);
        return showMsg($msg, $code);
    }
}
