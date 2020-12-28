<?php
/*
 * author by xiangyang
 * create time 2020-12-22
 */
namespace Modules\Dorm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PasswordValidate extends FormRequest
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
		    'password'  =>  'required',
            'newpassword'   =>  'required|min:4|max:20',
            'repassword'   =>  'required'
	    ];
    }
    
    public function messages()
    {
	    return [
		    'password.required'     =>  '请输入旧密码',
            'newpassword.required'     =>  '请输入新密码',
            'newpassword.min'          => '密码长度不得小于4位',
            'newpassword.max'          => '密码长度不得大于20位',
            'repassword.required'      =>  '请重新输入新密码'
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
