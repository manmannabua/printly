<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;

class UploadOrderFileRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200', // 50 MB — print files run large
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
        ];
    }
}
