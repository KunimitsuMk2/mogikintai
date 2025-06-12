<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ログインユーザーと勤怠データの所有者が一致するか確認
        $attendance = $this->route('attendance');
        if (!$attendance) {
            return false;
            }
        return $this->user()->id === $attendance->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'rest_times' => 'array',
            'rest_times.*.start' => 'nullable|date_format:H:i',
            'rest_times.*.end' => 'nullable|date_format:H:i',
            'remarks' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_time.required' => '出勤時間を入力してください',
            'end_time.required' => '退勤時間を入力してください',
            'remarks.required' => '備考を記入してください',
        ];
    }

    /**
     * バリデーションが成功した後に追加の検証を行います
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 出勤時間と退勤時間の整合性をチェック
            $startTime = Carbon::createFromFormat('H:i', $this->start_time);
            $endTime = Carbon::createFromFormat('H:i', $this->end_time);
            
            if ($startTime >= $endTime) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }
            
            // 休憩時間のチェック
            if ($this->filled('rest_times')) {
                foreach ($this->rest_times as $index => $restTime) {
                    // 休憩開始時間と終了時間が両方入力されている場合のみチェック
                    if (!empty($restTime['start']) && !empty($restTime['end'])) {
                        $restStart = Carbon::createFromFormat('H:i', $restTime['start']);
                        $restEnd = Carbon::createFromFormat('H:i', $restTime['end']);
                        
                        // 休憩時間の整合性チェック
                        if ($restStart >= $restEnd) {
                            $validator->errors()->add("rest_times.{$index}.start", '休憩時間が不適切な値です');
                        }
                        
                        // 休憩時間が勤務時間内かチェック
                        if ($restStart < $startTime || $restEnd > $endTime) {
                            $validator->errors()->add("rest_times.{$index}.start", '休憩時間が勤務時間外です');
                        }
                    }
                }
            }
        });
    }
}