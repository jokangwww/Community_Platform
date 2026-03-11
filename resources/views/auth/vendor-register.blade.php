<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Register</title>
    <style>
        body { margin:0; font-family:Arial,sans-serif; background:#f3f5f9; min-height:100vh; display:grid; place-items:center; padding:20px; }
        .card { width:min(520px, 100%); background:#fff; border:1px solid #d6d6d6; border-radius:12px; padding:20px; }
        h2 { margin:0 0 8px; }
        p { margin:0 0 14px; color:#555; }
        form { display:grid; gap:10px; }
        label { display:grid; gap:6px; font-size:14px; }
        input, button, a { border:1px solid #c7c7c7; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; }
        button { cursor:pointer; background:#fff;
            color: #1f1f1f; }
        .msg { margin-bottom:10px; padding:10px; border:1px solid #cfcfcf; border-radius:8px; background:#f7f7f7; font-size:14px; }
        .links { margin-top:10px; display:flex; gap:8px; justify-content:space-between; align-items:center; }
        .links a { text-decoration:none; color:#1f1f1f; background:#fff; }
        .strength { margin-top: 8px; display: flex; flex-direction: column; gap: 6px; }
        .strength-label { font-size: 12px; color: #6a6a6a; }
        .strength-bar { width: 100%; height: 6px; border-radius: 999px; background: #e6e6e6; overflow: hidden; }
        .strength-bar span { display: block; height: 100%; width: 0%; background: #d14b4b; transition: width 0.2s ease, background 0.2s ease; }
        .strength-hints { margin: 0; padding-left: 18px; font-size: 12px; color: #6a6a6a; }
        .strength-hints li { margin: 2px 0; }
        .strength-hints li.ok { color: #2f8f4e; }
        .password-wrap { position: relative; width: 100%; max-width: 100%; }
        .password-wrap input { padding-right: 42px; width: 100%; }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            padding: 0;
            color: #555;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .password-toggle:hover { color: #222; }
        .password-toggle svg { width: 18px; height: 18px; display: block; }
        .password-toggle .icon-eye-off { display: none; }
        .password-wrap.is-visible .password-toggle .icon-eye { display: none; }
        .password-wrap.is-visible .password-toggle .icon-eye-off { display: inline; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Vendor Registration</h2>
        <p>Register as vendor using only name, email and phone number (plus password for login).</p>

        @if ($errors->any())
            <div class="msg">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('vendor.register.store') }}">
            @csrf
            <label>
                Name
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
            <label>
                Phone Number
                <input type="text" name="contact_information" value="{{ old('contact_information') }}" required>
            </label>
            <label>
                Password
                <div class="password-wrap">
                    <input id="password" type="password" name="password" required>
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
                <div class="strength">
                    <div class="strength-label">Strength: <span id="strength-text">Too weak</span></div>
                    <div class="strength-bar"><span id="strength-bar"></span></div>
                    <ul class="strength-hints" id="strength-hints">
                        <li data-rule="length">At least 8 characters</li>
                        <li data-rule="upper">One uppercase letter</li>
                        <li data-rule="number">One number</li>
                        <li data-rule="symbol">One special character</li>
                    </ul>
                </div>
            </label>
            <label>
                Confirm Password
                <div class="password-wrap">
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
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
            </label>
            <button type="submit">Create Vendor Account</button>
        </form>

        <div class="links">
            <a href="{{ route('login') }}">Back to Login</a>
            <span style="color:#666;font-size:13px;">Vendor role only for booth applications</span>
        </div>
    </div>
    <script>
        (function () {
            var input = document.getElementById('password');
            var bar = document.getElementById('strength-bar');
            var text = document.getElementById('strength-text');
            var hints = document.getElementById('strength-hints');
            var form = document.querySelector('form[action="{{ route('vendor.register.store') }}"]');

            if (!input || !bar || !text || !hints) return;

            function scorePassword(value) {
                var score = 0;
                if (value.length >= 8) score += 1;
                if (value.length >= 12) score += 1;
                if (/[A-Z]/.test(value)) score += 1;
                if (/[0-9]/.test(value)) score += 1;
                if (/[^A-Za-z0-9]/.test(value)) score += 1;
                return score;
            }

            function updateStrength() {
                var value = input.value || '';
                var score = scorePassword(value);
                var levels = [
                    { label: 'Too weak', color: '#d14b4b', width: 10 },
                    { label: 'Weak', color: '#e06b3c', width: 30 },
                    { label: 'Fair', color: '#d9a63a', width: 50 },
                    { label: 'Good', color: '#5fa66a', width: 70 },
                    { label: 'Strong', color: '#2f8f4e', width: 90 },
                    { label: 'Very strong', color: '#1f7a3f', width: 100 }
                ];
                var level = levels[Math.min(score, levels.length - 1)];

                bar.style.width = value.length ? level.width + '%' : '0%';
                bar.style.background = level.color;
                text.textContent = value.length ? level.label : 'Too weak';

                var rules = {
                    length: value.length >= 8,
                    upper: /[A-Z]/.test(value),
                    number: /[0-9]/.test(value),
                    symbol: /[^A-Za-z0-9]/.test(value)
                };
                Object.keys(rules).forEach(function (key) {
                    var item = hints.querySelector('[data-rule="' + key + '"]');
                    if (!item) return;
                    item.classList.toggle('ok', rules[key]);
                });

                var meetsRequirement = value.length >= 8
                    && /[A-Z]/.test(value)
                    && /[0-9]/.test(value)
                    && /[^A-Za-z0-9]/.test(value);

                input.setCustomValidity(
                    meetsRequirement || value.length === 0
                        ? ''
                        : 'Password must have at least 8 characters, one uppercase letter, one number, and one special character.'
                );
            }

            input.addEventListener('input', updateStrength);
            updateStrength();

            if (form) {
                form.addEventListener('submit', function (event) {
                    updateStrength();
                    if (!input.checkValidity()) {
                        event.preventDefault();
                        input.reportValidity();
                    }
                });
            }

            document.querySelectorAll('[data-toggle-password]').forEach(function (toggleButton) {
                toggleButton.addEventListener('click', function () {
                    var wrap = toggleButton.closest('.password-wrap');
                    var field = wrap ? wrap.querySelector('input[type="password"], input[type="text"]') : null;
                    if (!wrap || !field) return;

                    var isHidden = field.type === 'password';
                    field.type = isHidden ? 'text' : 'password';
                    wrap.classList.toggle('is-visible', isHidden);
                    toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                    toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                });
            });
        })();
    </script>
</body>
</html>
