<?php

namespace Modules\Dorm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginValidate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'idnum'  =>  'required',
            'password'  =>  'required|min:4|max:20',
            'key'       =>  'required',
            'captcha'   =>  'required'
        ];
    }

    public function messages()
    {
        return [
            'idnum.required'     =>  '请输入账号',
            'password.required'     => '请输入密码',
            'password.min'          => '密码长度不得小于4位',
            'password.max'          => '密码长度不得大于20位',
            'key.required'          =>  '请输入验证码',
            'captcha.required'      =>  '请输入验证码'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $error= $validator->errors()->all();
        throw new HttpResponseException($this->fail(1, $error));
    }

    protected function fail(int $code, array $errors) : JsonResponse
    {
        $msg =  array_first($errors);
        return showMsg($msg,$code);
    }
}
