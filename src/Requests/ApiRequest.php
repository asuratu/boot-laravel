<?php

namespace ZhuiTech\BootLaravel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

class ApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
        ];
    }

    public function validateData($data, $rules, $message = [], $attributes = []): void
    {
        $validate = Validator::make($data, $rules, $message, $attributes);

        if ($validate->fails()) {
            $error = $validate->errors()->all();
            throw new HttpResponseException(response()->json([
                'status' => false,
                'code' => REST_DATA_VALIDATE_FAIL,
                'message' => $error[0],
                'data' => ""
            ]));
        }
    }
}
