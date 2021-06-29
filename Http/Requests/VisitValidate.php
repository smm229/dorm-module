<?php

namespace Modules\Dorm\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class VisitValidate extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username'        => 'required',
            'headimg'         => 'required',
            'sex'             => 'required|int',
            'visit_place'     => 'required',
            'receptionUserId'=>'required'
        ];
    }

    public function messages()
    {
        return [
            'username.required'        =>  '请输入姓名',
            'headimg.required'         =>  '请添加人脸',
            'sex.required'             =>  '请选择性别',
            'sex.int'                  =>  '性别格式错误',
            'visit_place.required'     =>  '访问地点不能为空',
            'receptionUserId.required'=>'请选择拜访人'
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
        return showMsg($msg,$code);
    }
}
