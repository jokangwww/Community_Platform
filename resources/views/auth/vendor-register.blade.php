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
        input, button, a { border:1px solid #c7c7c7; border-radius:8px; padding:10px 12px; font-size:14px; }
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
                <input id="password" type="password" name="password" required>
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
                <input type="password" name="password_confirmation" required>
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
        })();
    </script>
</body>
</html>
