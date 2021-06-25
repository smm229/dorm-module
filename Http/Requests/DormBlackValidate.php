<?php

namespace Modules\Dorm\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * 黑名单验证
 * Class DormBlackValidate
 * @package Modules\Dorm\Http\Requests
 */
class DormBlackValidate extends FormRequest
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
            'type'           => 'required|int',
            'headimg'        => 'required',
            'username'       => 'required',
            'sex'            => 'required|int'
        ];
    }

    public function messages()
    {
        return [
            'headimg.required'         =>  '请添加人脸',
            'username.required'        =>  '请输入姓名',
            'type.required'            =>  '请选择类型',
            'type.int'                 =>  '类型格式错误',
            'sex.required'             =>  '请选择性别',
            'sex.int'                  =>  '性别格式错误'
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
