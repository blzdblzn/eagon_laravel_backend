<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'dob' => 'required|date',
            'sex' => 'required|in:Male,Female', // Adjust as needed
            'desg' => 'required|string',
            'department_id' => 'required|integer',
            'join_date' => 'required|date',
            'salary' => 'required|numeric',
        ];
    }
}
