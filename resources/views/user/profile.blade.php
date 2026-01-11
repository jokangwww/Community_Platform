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
                <div class="form-row" style="margin-top: 8px;">
                    <label for="old_password">Old Password</label>
                    <input id="old_password" type="password" placeholder="Type your old password">
                </div>
                <div class="form-row" style="margin-top: 10px;">
                    <label for="new_password">New Password</label>
                    <input id="new_password" type="password" placeholder="Type your new password">
                </div>
                <button class="action-btn" style="margin-top: 12px;">Change Password</button>
            </div>
        </div>
        <div class="profile-panel">
            <div class="section-title">Profile Information</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div class="form-row">
                    <label>Username</label>
                    <input type="text" value="{{ $user?->email ?? '' }}" placeholder="username" readonly>
                </div>
                <div class="form-row">
                    <label>First Name</label>
                    <input type="text" value="{{ $user?->name ? explode(' ', $user->name)[0] : '' }}" placeholder="First name" readonly>
                </div>
                <div class="form-row">
                    <label>Nickname</label>
                    <input type="text" placeholder="Nickname" readonly>
                </div>
                <div class="form-row">
                    <label>Role</label>
                    <select disabled>
                        <option>Subscriber</option>
                        <option>Student</option>
                        <option>Staff</option>
                        <option>Alumni</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Last Name</label>
                    <input type="text" value="{{ $user?->name ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '' }}" placeholder="Last name" readonly>
                </div>
                <div class="form-row">
                    <label>Display Name Publicly as</label>
                    <input type="text" value="{{ $user?->name ?? '' }}" placeholder="Display name" readonly>
                </div>
            </div>
            <div class="section-title" style="margin-top: 16px;">Contact Info</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div class="form-row">
                    <label>Email (required)</label>
                    <input type="email" value="{{ $user?->email ?? '' }}" placeholder="email@example.com" readonly>
                </div>
                <div class="form-row">
                    <label>WhatsApp</label>
                    <input type="text" placeholder="@handle" readonly>
                </div>
                <div class="form-row">
                    <label>Website</label>
                    <input type="text" placeholder="https://example.com" readonly>
                </div>
                <div class="form-row">
                    <label>Telegram</label>
                    <input type="text" placeholder="@handle" readonly>
                </div>
            </div>
            <div class="section-title" style="margin-top: 16px;">About the User</div>
            <div class="form-row full" style="margin-top: 10px;">
                <label>Biographical Info</label>
                <textarea placeholder="Tell us about yourself..." readonly></textarea>
            </div>
        </div>
    </div>
@endsection
