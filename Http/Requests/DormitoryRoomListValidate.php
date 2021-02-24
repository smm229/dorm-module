<?php

namespace Modules\Dorm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DormitoryRoomListValidate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start'       =>  'required',
            'end'       =>  'required',
            'buildtype'     =>  'required',
            'floornum'      =>  'required',
            'bedsnum'       =>  'required',
            'buildid'       =>  'required'
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
            'start.required'       =>  '请输入起始房间号',
            'end.required'       =>  '请输入截止房间号',
            'buildtype.required'     => '请选择宿舍类型',
            'floornum.required'      =>  '请输入楼层',
            'bedsnum.required'      =>  '请输入床位数量',
            'buildid.required'      =>  '请选择楼栋'
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
