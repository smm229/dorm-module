<?php

namespace Modules\Dorm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DormitoryBuildingDeviceValidate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id'               =>  'required',
            'type'             =>  'required',
            'devicename'       =>  'required',
            'position'         =>  'required',
            'groupid'          =>  'required',
            'campusid'         =>  'required',
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

    public function messages()
    {
        return [
            'id.required'               => 'id',
            'type.required'             => '类型',
            'devicename.required'       => '设备名称',
            'position.required'         => '设备所在位置',
            'groupid.required'          => '设备所在楼',
            'campusid.required'         => '校区'
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
