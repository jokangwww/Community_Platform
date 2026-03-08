@extends('layouts.admin_layout')

@section('title', 'Correct User Profile')

@section('content')
    <style>
        .page-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
            max-width: 980px;
        }
        .layout-grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            max-width: 980px;
            align-items: start;
        }
        .panel {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px;
        }
        .panel h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .form-grid {
            display: grid;
            gap: 10px;
        }
        .form-grid label {
            font-size: 13px;
            color: #4a4a4a;
            display: block;
            margin-bottom: 4px;
        }
        .form-grid input,
        .form-grid select,
        .form-grid textarea,
        .form-grid button,
        .form-grid a {
            width: 100%;
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
            box-sizing: border-box;
        }
        .form-grid textarea {
            min-height: 90px;
            resize: vertical;
        }
        .form-grid button {
            cursor: pointer;
            border-color: #1f1f1f;
        }
        .log-item {
            border-top: 1px solid #ececec;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 13px;
            color: #2f2f2f;
        }
        .log-item:first-child {
            border-top: 0;
            margin-top: 0;
            padding-top: 0;
        }
        .log-field {
            margin: 4px 0;
        }
        @media (max-width: 900px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <h2>Correct Profile: {{ $targetUser->name }}</h2>
    </div>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="status" style="border-color:#f5c2c2;background:#ffecec;color:#7f1d1d;">
            <strong>Please fix the following:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                @foreach ($errors->all() as $error)
                    <li style="margin-bottom:4px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="layout-grid">
        <form method="POST" action="{{ route('admin.user-profiles.update', $targetUser) }}" class="panel">
            @csrf
            @method('PUT')
            <h3>Profile Data</h3>
            <div class="form-grid">
                <div>
                    <label>Role</label>
                    <select name="role" required>
                        <option value="student" @selected(old('role', $targetUser->role) === 'student')>Student</option>
                        <option value="staff" @selected(old('role', $targetUser->role) === 'staff')>Staff</option>
                        <option value="club" @selected(old('role', $targetUser->role) === 'club')>Club</option>
                        <option value="admin" @selected(old('role', $targetUser->role) === 'admin')>Admin</option>
                    </select>
                </div>
                <div>
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $targetUser->name) }}" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $targetUser->email) }}" required>
                </div>
                <div id="student-id-row">
                    <label for="student_id">Student/Staff ID</label>
                    <input id="student_id" name="student_id" type="text" value="{{ old('student_id', $targetUser->student_id) }}">
                </div>
                <div id="ic-number-row">
                    <label for="ic_number">IC Number</label>
                    <input id="ic_number" name="ic_number" type="text" value="{{ old('ic_number', $targetUser->ic_number) }}" placeholder="e.g. 000808-14-XXXX">
                </div>
                <div id="programme-row">
                    <label for="programme">Programme</label>
                    <input id="programme" name="programme" type="text" value="{{ old('programme', $targetUser->programme) }}" placeholder="e.g. Diploma in Business Information Systems">
                </div>
                <div>
                    <label for="display_name">Display Name</label>
                    <input id="display_name" name="display_name" type="text" value="{{ old('display_name', $targetUser->display_name) }}">
                </div>
                <div>
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio">{{ old('bio', $targetUser->bio) }}</textarea>
                </div>

                <div id="admin-fields">
                    <div>
                        <label for="position">Position</label>
                        <input id="position" name="position" type="text" value="{{ old('position', $adminMeta->position ?? '') }}">
                    </div>
                    <div>
                        <label for="contact_information">Contact Information</label>
                        <input id="contact_information" name="contact_information" type="text" value="{{ old('contact_information', $adminMeta->contact_information ?? '') }}">
                    </div>
                    <div>
                        <label for="responsibilities">Responsibilities</label>
                        <textarea id="responsibilities" name="responsibilities">{{ old('responsibilities', $adminMeta->responsibilities ?? '') }}</textarea>
                    </div>
                </div>

                <div>
                    <label for="change_note">Change Note (for accountability)</label>
                    <textarea id="change_note" name="change_note" placeholder="Why this correction is needed">{{ old('change_note') }}</textarea>
                </div>

                <button type="submit">Save Correction</button>
                <a href="{{ route('admin.user-profiles.index') }}" style="text-align:center;text-decoration:none;">Back to List</a>
            </div>
        </form>

        <div class="panel">
            <h3>Change History</h3>
            @if ($logs->isEmpty())
                <div style="color:#4a4a4a;font-size:14px;">No correction logs yet.</div>
            @else
                @foreach ($logs as $log)
                    <div class="log-item">
                        <div><strong>By:</strong> {{ $log->admin?->name ?? 'Admin' }}</div>
                        <div><strong>At:</strong> {{ optional($log->created_at)->format('Y-m-d H:i') }}</div>
                        @if ($log->note)
                            <div><strong>Note:</strong> {{ $log->note }}</div>
                        @endif
                        <div style="margin-top:6px;"><strong>Fields:</strong></div>
                        @foreach (($log->changed_fields ?? []) as $field => $change)
                            <div class="log-field">
                                {{ $field }}: "{{ $change['old'] ?? '' }}" -> "{{ $change['new'] ?? '' }}"
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    <script>
        (function () {
            var roleSelect = document.querySelector('select[name="role"]');
            var studentIdRow = document.getElementById('student-id-row');
            var studentIdInput = document.getElementById('student_id');
            var icNumberRow = document.getElementById('ic-number-row');
            var icNumberInput = document.getElementById('ic_number');
            var programmeRow = document.getElementById('programme-row');
            var programmeInput = document.getElementById('programme');
            var adminFields = document.getElementById('admin-fields');
            var adminInputs = adminFields
                ? adminFields.querySelectorAll('input, textarea, select')
                : [];

            function toggleStudentFields() {
                if (!roleSelect || !studentIdRow || !studentIdInput || !icNumberRow || !icNumberInput || !programmeRow || !programmeInput) {
                    return;
                }

                var isClub = roleSelect.value === 'club';
                var isStudent = roleSelect.value === 'student';
                studentIdRow.style.display = isClub ? 'none' : 'block';
                studentIdInput.disabled = isClub;
                icNumberRow.style.display = isStudent ? 'block' : 'none';
                icNumberInput.disabled = !isStudent;
                icNumberInput.required = isStudent;
                programmeRow.style.display = isStudent ? 'block' : 'none';
                programmeInput.disabled = !isStudent;
                programmeInput.required = isStudent;

                if (isClub) {
                    studentIdInput.value = '';
                }

                if (!isStudent) {
                    icNumberInput.value = '';
                    programmeInput.value = '';
                }
            }

            function toggleAdminFields() {
                if (!roleSelect || !adminFields) {
                    return;
                }

                var isAdmin = roleSelect.value === 'admin';
                adminFields.style.display = isAdmin ? 'contents' : 'none';

                adminInputs.forEach(function (input) {
                    input.disabled = !isAdmin;
                });
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleStudentFields);
                roleSelect.addEventListener('change', toggleAdminFields);
                toggleStudentFields();
                toggleAdminFields();
            }
        })();
    </script>
@endsection
