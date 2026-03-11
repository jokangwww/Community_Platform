@extends('layouts.club')

@section('title', 'Club Profile')

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
        .avatar-card {
            border: 1px solid var(--panel-border);
            background: #f0f4ff;
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
        .profile-panel .action-btn {
            display: block;
            padding: 10px 16px;
            border: 1px solid var(--field-border);
            background: #fff !important;
            color: #333 !important;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            border-radius: 6px;
            font: inherit;
            box-shadow: none !important;
            filter: none !important;
        }
        .profile-panel .action-btn:hover {
            background: #f2f6ff !important;
            color: #1f1f1f !important;
            box-shadow: none !important;
            filter: none !important;
            transform: none !important;
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
        .form-row textarea {
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            width: 100%;
        }
        .form-row textarea {
            resize: vertical;
            min-height: 120px;
        }
        .password-wrap {
            width: 100%;
            position: relative;
        }
        .password-wrap input {
            padding-right: 46px;
        }
        .profile-panel .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: none !important;
            background: transparent !important;
            border-radius: 6px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #555 !important;
            cursor: pointer;
            box-shadow: none !important;
        }
        .profile-panel .password-toggle svg {
            width: 18px;
            height: 18px;
            display: block;
            pointer-events: none;
        }
        .profile-panel .password-toggle:hover {
            background: #f2f2f2 !important;
            color: #1f1f1f !important;
            transform: translateY(-50%) !important;
        }
        .profile-panel .password-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 99, 230, 0.15) !important;
        }
        .profile-panel .password-toggle .icon-eye-off {
            display: none;
        }
        .password-wrap.is-visible .password-toggle .icon-eye {
            display: none;
        }
        .password-wrap.is-visible .password-toggle .icon-eye-off {
            display: inline;
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

    <div class="profile-header">Club Account</div>
    <div class="profile-layout">
        <div>
            <div class="avatar-card">
                @if ($user?->profile_photo_path)
                    <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="Profile photo">
                @else
                    <div class="avatar-fallback">
                        {{ $user?->name ? strtoupper(substr($user->name, 0, 1)) : 'C' }}
                    </div>
                @endif
            </div>
            @if (session('status'))
                <div class="status-text">{{ session('status') }}</div>
            @endif
            <form action="{{ route('club.profile.photo') }}" method="POST" enctype="multipart/form-data" class="profile-upload">
                @csrf
                <input type="file" name="profile_photo" accept="image/*" required>
                @error('profile_photo')
                    <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                @enderror
                <button type="submit" class="action-btn" style="margin-top: 6px; font: inherit;">Upload Photo</button>
            </form>
            <div class="profile-panel" style="margin-top: 16px;">
                <div class="section-title">Change Password</div>
                <form action="{{ route('club.profile.password') }}" method="POST" style="margin-top: 8px;">
                    @csrf
                    <div class="form-row">
                        <label for="current_password">Old Password</label>
                        <div class="password-wrap">
                            <input id="current_password" name="current_password" type="password" placeholder="Type your old password" required>
                            <button type="button" class="password-toggle" data-toggle-password aria-label="Show password" aria-pressed="false">
                                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M1 12C2.73 7.61 6.96 4.5 12 4.5C17.04 4.5 21.27 7.61 23 12C21.27 16.39 17.04 19.5 12 19.5C6.96 19.5 2.73 16.39 1 12Z" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M10.58 10.58C10.21 10.95 10 11.46 10 12C10 13.1 10.9 14 12 14C12.54 14 13.05 13.79 13.42 13.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9.88 5.09C10.57 4.89 11.28 4.79 12 4.79C16.6 4.79 20.48 7.57 22 12C21.41 13.73 20.45 15.27 19.21 16.53" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M6.23 6.23C4.2 7.52 2.62 9.52 2 12C3.52 16.43 7.4 19.21 12 19.21C13.58 19.21 15.09 18.88 16.45 18.28" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-row" style="margin-top: 10px;">
                        <label for="password">New Password</label>
                        <div class="password-wrap">
                            <input id="password" name="password" type="password" placeholder="Type your new password" required>
                            <button type="button" class="password-toggle" data-toggle-password aria-label="Show password" aria-pressed="false">
                                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M1 12C2.73 7.61 6.96 4.5 12 4.5C17.04 4.5 21.27 7.61 23 12C21.27 16.39 17.04 19.5 12 19.5C6.96 19.5 2.73 16.39 1 12Z" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M10.58 10.58C10.21 10.95 10 11.46 10 12C10 13.1 10.9 14 12 14C12.54 14 13.05 13.79 13.42 13.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9.88 5.09C10.57 4.89 11.28 4.79 12 4.79C16.6 4.79 20.48 7.57 22 12C21.41 13.73 20.45 15.27 19.21 16.53" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M6.23 6.23C4.2 7.52 2.62 9.52 2 12C3.52 16.43 7.4 19.21 12 19.21C13.58 19.21 15.09 18.88 16.45 18.28" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-row" style="margin-top: 10px;">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="password-wrap">
                            <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter your new password" required>
                            <button type="button" class="password-toggle" data-toggle-password aria-label="Show password" aria-pressed="false">
                                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M1 12C2.73 7.61 6.96 4.5 12 4.5C17.04 4.5 21.27 7.61 23 12C21.27 16.39 17.04 19.5 12 19.5C6.96 19.5 2.73 16.39 1 12Z" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M10.58 10.58C10.21 10.95 10 11.46 10 12C10 13.1 10.9 14 12 14C12.54 14 13.05 13.79 13.42 13.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9.88 5.09C10.57 4.89 11.28 4.79 12 4.79C16.6 4.79 20.48 7.57 22 12C21.41 13.73 20.45 15.27 19.21 16.53" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M6.23 6.23C4.2 7.52 2.62 9.52 2 12C3.52 16.43 7.4 19.21 12 19.21C13.58 19.21 15.09 18.88 16.45 18.28" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
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
                || $errors->has('email')
                || $errors->has('bio');
        @endphp
        <form id="profile-form" class="profile-panel" method="POST" action="{{ route('club.profile.update') }}" data-start-edit="{{ $hasProfileErrors ? 'true' : 'false' }}">
            @csrf
            @method('PUT')
            <div class="section-title">Club Information</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div class="form-row">
                    <label>Username</label>
                    <input type="text" value="{{ $user?->email ?? '' }}" placeholder="username" readonly>
                </div>
                <div class="form-row">
                    <label for="name">Club Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user?->name ?? '') }}" placeholder="Club name" readonly>
                    @error('name')
                        <div class="status-text" style="color: #b00020;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-row">
                    <label>Role</label>
                    <input type="text" value="Club" readonly>
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
            <div class="section-title" style="margin-top: 16px;">About the Club</div>
            <div class="form-row full" style="margin-top: 10px;">
                <label for="bio">Club Description</label>
                <textarea id="bio" name="bio" placeholder="Tell us about your club..." readonly>{{ old('bio', $user?->bio ?? '') }}</textarea>
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

            const fields = Array.from(form.querySelectorAll('input[name], textarea[name]'));

            const setEditable = (isEditable) => {
                fields.forEach((field) => {
                    if (field.dataset.originalValue === undefined) {
                        field.dataset.originalValue = field.value;
                        field.dataset.originalReadonly = field.hasAttribute('readonly') ? 'true' : 'false';
                    }

                    if (isEditable) {
                        field.removeAttribute('readonly');
                    } else {
                        field.value = field.dataset.originalValue;
                        if (field.dataset.originalReadonly === 'true') {
                            field.setAttribute('readonly', 'readonly');
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

            document.querySelectorAll('[data-toggle-password]').forEach((toggleButton) => {
                toggleButton.addEventListener('click', () => {
                    const wrap = toggleButton.closest('.password-wrap');
                    const passwordInput = wrap ? wrap.querySelector('input[type="password"], input[type="text"]') : null;
                    if (!wrap || !passwordInput) return;
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    wrap.classList.toggle('is-visible', isHidden);
                    toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                    toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                    passwordInput.focus();
                });
            });
        })();
    </script>
@endsection
