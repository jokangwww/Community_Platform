<?php

namespace App\Http\Requests\Buddy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string '1'/'0' to boolean for is_repeater
        if ($this->has('is_repeater')) {
            $this->merge([
                'is_repeater' => filter_var($this->is_repeater, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Convert year_of_study to integer
        if ($this->has('year_of_study')) {
            $this->merge([
                'year_of_study' => (int) $this->year_of_study,
            ]);
        }

        // Convert cgpa to float
        if ($this->has('cgpa')) {
            $this->merge([
                'cgpa' => (float) $this->cgpa,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $activeSemesterId = \App\Models\BuddySemesterSetting::getActiveSemester()?->id;

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'student_id' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('buddy_participants', 'student_id')
                    ->where('semester_id', $activeSemesterId),
            ],
            'course' => ['required', 'string', 'max:255'],
            'faculty' => ['required', 'string', Rule::in([
                'Faculty of Accountancy, Finance and Business',
                'Faculty of Applied Sciences',
                'Faculty of Computing and Information Technology',
                'Faculty of Computing and Informatics',
                'Faculty of Built Environment',
                'Faculty of Engineering and Technology',
                'Faculty of Engineering',
                'Faculty of Communication and Creative Industries',
                'Faculty of Social Science and Humanities',
                'Faculty of Science',
                'Faculty of Arts',
                'Faculty of Business',
                'Faculty of Medicine',
                'Faculty of Law'
            ])],
            'year_of_study' => ['required', 'integer', 'min:1', 'max:4'],
            'cgpa' => ['required', 'numeric', 'min:0', 'max:4.00'],
            'role' => ['required', Rule::in(['mentor', 'mentee'])],
            'is_repeater' => ['nullable', 'boolean'],
            // Subject/Skill selection - either existing ID or new subject data
            'subject_id' => ['nullable', 'integer', 'exists:buddy_subjects,id'],
            'new_subject_name' => ['nullable', 'string', 'max:255'],
            'new_subject_code' => ['nullable', 'string', 'max:50'],
            'subject_type' => ['nullable', Rule::in(['subject', 'skill'])],
        ];

        if ($this->hasFile('document')) {
            $rules['document'] = ['file', 'max:5120']; 
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = $this->input('role');
            $isRepeater = filter_var($this->input('is_repeater'), FILTER_VALIDATE_BOOLEAN);
            
            // Document is required for mentors
            if ($role === 'mentor' && !$this->hasFile('document')) {
                $validator->errors()->add('document', 'Qualification document is required for mentors');
            }
            
            // Document is required for mentee repeaters
            if ($role === 'mentee' && $isRepeater && !$this->hasFile('document')) {
                $validator->errors()->add('document', 'CGPA record or result slip is required for repeaters');
            }
            
            // Validate file type manually if file is present
            if ($this->hasFile('document')) {
                $file = $this->file('document');
                $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                
                // Both extension AND MIME type must be valid (prevent file type spoofing)
                if (!in_array($extension, $allowedExtensions) || !in_array($mimeType, $allowedMimes)) {
                    $validator->errors()->add('document', 'Document must be a PDF, JPG, or PNG file');
                }
                
                // Check for path traversal in original filename
                $originalName = $file->getClientOriginalName();
                if (preg_match('/\\.\\.[\\/\\\\]|[\\/\\\\]\\.\\./', $originalName) || 
                    str_contains($originalName, '..') ||
                    str_contains($originalName, "\0")) {
                    $validator->errors()->add('document', 'Invalid filename');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required',
            'student_id.required' => 'Student ID is required',
            'student_id.unique' => 'This student ID is already registered',
            'course.required' => 'Course is required',
            'faculty.required' => 'Faculty is required',
            'faculty.in' => 'Please select a valid faculty',
            'year_of_study.required' => 'Year of study is required',
            'year_of_study.min' => 'Year of study must be at least 1',
            'year_of_study.max' => 'Year of study cannot exceed 4',
            'cgpa.required' => 'CGPA is required',
            'cgpa.min' => 'CGPA must be at least 0.00',
            'cgpa.max' => 'CGPA cannot exceed 4.00',
            'role.required' => 'Role is required',
            'role.in' => 'Role must be either mentor or mentee',
            'subjects.required' => 'Please select at least one subject',
            'subjects.min' => 'Please select at least one subject',
            'subjects.*.exists' => 'One or more selected subjects are invalid',
            'document.required' => 'Document upload is required',
            'document.mimes' => 'Document must be a PDF, JPG, or PNG file',
            'document.max' => 'Document size must be less than 5MB',
        ];
    }
}