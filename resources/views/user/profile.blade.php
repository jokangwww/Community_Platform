@extends('layouts.user_layout')

@section('title', 'Profile')

@section('content')
    <style>
        :root {
            --panel-bg: #ffffff;
            --panel-border: #e1e1e1;
            --muted: #6b6b6b;
            --field-bg: #fbfbfb;
            --field-border: #d9d9d9;
        }
        .profile-header {
            font-size: 20px;
            font-weight: 600;
            margin: 8px 0 16px;
            color: #2f2f2f;
        }
        .profile-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 28px;
            align-items: start;
        }
        .profile-panel {
            border: 1px solid var(--panel-border);
            background: var(--panel-bg);
            padding: 16px;
            border-radius: 8px;
        }
        .panel-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .avatar-card {
            border: 1px solid var(--panel-border);
            background: #f6e6df;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-card img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .avatar-card .avatar-fallback {
            font-size: 42px;
            color: #4a4a4a;
        }
        .avatar-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 0;
            background: rgba(0, 0, 0, 0.4);
            color: #fff;
            font-size: 20px;
            line-height: 32px;
            cursor: pointer;
        }
        .profile-upload {
            margin-top: 12px;
        }
        .profile-upload input[type="file"] {
            width: 100%;
            margin-bottom: 10px;
        }
        .status-text {
            margin: 6px 0 10px;
            font-size: 13px;
            color: #1f7a1f;
        }
        .action-btn {
            display: block;
            padding: 10px 16px;
            border: 1px solid var(--field-border);
            background: #fff;
            color: #333;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            border-radius: 6px;
        }
        .action-btn:hover {
            background: #f6f6f6;
        }
        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #3a3a3a;
            margin: 10px 0 6px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-row label {
            font-size: 13px;
            color: var(--muted);
        }
        .form-row input,
        .form-row select,
        .form-row textarea {
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
        }
        .form-row textarea {
            resize: vertical;
            min-height: 120px;
        }
        .full {
            grid-column: 1 / -1;
        }
        .profile-actions {
            display: none;
            gap: 12px;
            margin-top: 16px;
        }
        .profile-actions.is-visible {
            display: flex;
        }
        @media (max-width: 800px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $user = auth()->user();
    @endphp

    <div class="profile-header">Account Management</div>
    <div class="profile-layout">
        <div>
            <div class="avatar-card">
                @if ($user?->profile_photo_path)
                    <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="Profile photo">
                @else
                    <div class="avatar-fallback">
                        {{ $user?->name ? strtoupper(substr($user->name, 0, 1)) : 'U' }}
                    </div>
                @endif
            </div>
            @if (session('status'))
                <div class="status-text">{{ session('status') }}</div>
            @endif
            <form action="{{ route('profile.photo') }}" method="POST" enctype="multipart/form-data" class="profile-upload">
                @csrf
                <input type="file" name="profile_photo" accept="image/*" required>
                @error('profile_photo')
                    <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                @enderror
                <button type="submit" class="action-btn" style="margin-top: 6px; font: inherit;">Upload Photo</button>
            </form>
            <div class="profile-panel" style="margin-top: 16px;">
                <div class="section-title">Change Password</div>
                <form action="{{ route('profile.password') }}" method="POST" style="margin-top: 8px;">
                    @csrf
                    <div class="form-row">
                        <label for="current_password">Old Password</label>
                        <input id="current_password" name="current_password" type="password" placeholder="Type your old password" required>
                        @error('current_password')
                            <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-row" style="margin-top: 10px;">
                        <label for="password">New Password</label>
                        <input id="password" name="password" type="password" placeholder="Type your new password" required>
                        @error('password')
                            <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-row" style="margin-top: 10px;">
                        <label for="password_confirmation">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter your new password" required>
                    </div>
                    <button type="submit" class="action-btn" style="margin-top: 12px;">Change Password</button>
                    @if (session('password_status'))
                        <div class="status-text">{{ session('password_status') }}</div>
                    @endif
                </form>
                <button id="edit-profile-btn" type="button" class="action-btn" style="margin-top: 10px;">Update Profile</button>
            </div>
        </div>
        @php
            $hasProfileErrors = $errors->has('name')
                || $errors->has('display_name')
                || $errors->has('role')
                || $errors->has('email')
                || $errors->has('bio');
        @endphp
        <form id="profile-form" class="profile-panel" method="POST" action="{{ route('profile.update') }}" data-start-edit="{{ $hasProfileErrors ? 'true' : 'false' }}">
            @csrf
            @method('PUT')
            <div class="section-title">Profile Information</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div class="form-row">
                    <label>Username</label>
                    <input type="text" value="{{ $user?->email ?? '' }}" placeholder="username" readonly>
                </div>
                <div class="form-row">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user?->name ?? '') }}" placeholder="Full name" readonly>
                    @error('name')
                        <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-row">
                    <label for="role">Role</label>
                    <select id="role" name="role" disabled>
                        @php
                            $roleValue = old('role', $user?->role ?? 'subscriber');
                        @endphp
                        <option value="subscriber" @selected($roleValue === 'subscriber')>Subscriber</option>
                        <option value="student" @selected($roleValue === 'student')>Student</option>
                        <option value="staff" @selected($roleValue === 'staff')>Staff</option>
                        <option value="alumni" @selected($roleValue === 'alumni')>Alumni</option>
                    </select>
                    @error('role')
                        <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-row">
                    <label for="display_name">Display Name Publicly as</label>
                    <input id="display_name" name="display_name" type="text" value="{{ old('display_name', $user?->display_name ?? $user?->name ?? '') }}" placeholder="Display name" readonly>
                    @error('display_name')
                        <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="section-title" style="margin-top: 16px;">Contact Info</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div class="form-row">
                    <label for="email">Email (required)</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user?->email ?? '') }}" placeholder="email@example.com" readonly>
                    @error('email')
                        <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="section-title" style="margin-top: 16px;">About the User</div>
            <div class="form-row full" style="margin-top: 10px;">
                <label for="bio">Biographical Info</label>
                <textarea id="bio" name="bio" placeholder="Tell us about yourself..." readonly>{{ old('bio', $user?->bio ?? '') }}</textarea>
                @error('bio')
                    <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                @enderror
            </div>
            <div id="profile-actions" class="profile-actions">
                <button type="submit" class="action-btn" style="width: 160px;">Update</button>
                <button id="profile-cancel" type="button" class="action-btn" style="width: 160px;">Cancel</button>
            </div>
            @if (session('profile_status'))
                <div class="status-text" style="margin-top: 10px;">{{ session('profile_status') }}</div>
            @endif
        </form>
    </div>
    <script>
        (function () {
            const editButton = document.getElementById('edit-profile-btn');
            const form = document.getElementById('profile-form');
            const actionBar = document.getElementById('profile-actions');
            const cancelButton = document.getElementById('profile-cancel');
            if (!editButton || !form || !actionBar || !cancelButton) return;

            const fields = Array.from(form.querySelectorAll('input[name], select[name], textarea[name]'));

            const setEditable = (isEditable) => {
                fields.forEach((field) => {
                    if (field.dataset.originalValue === undefined) {
                        field.dataset.originalValue = field.value;
                        field.dataset.originalReadonly = field.hasAttribute('readonly') ? 'true' : 'false';
                        field.dataset.originalDisabled = field.hasAttribute('disabled') ? 'true' : 'false';
                    }

                    if (isEditable) {
                        field.removeAttribute('readonly');
                        field.removeAttribute('disabled');
                    } else {
                        field.value = field.dataset.originalValue;
                        if (field.dataset.originalReadonly === 'true') {
                            field.setAttribute('readonly', 'readonly');
                        }
                        if (field.dataset.originalDisabled === 'true') {
                            field.setAttribute('disabled', 'disabled');
                        }
                    }
                });
                actionBar.classList.toggle('is-visible', isEditable);
            };

            editButton.addEventListener('click', () => setEditable(true));
            cancelButton.addEventListener('click', () => setEditable(false));

            if (form.dataset.startEdit === 'true') {
                setEditable(true);
            }
        })();
    </script>
@endsection
