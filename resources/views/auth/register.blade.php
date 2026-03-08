<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8f9fb;
            color: #1f1f1f;
        }
        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 16px 80px;
            position: relative;
        }
        .logo {
            margin-bottom: 28px;
            text-align: center;
        }
        .logo img {
            max-width: 320px;
            width: 100%;
            height: auto;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 22px 20px 26px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }
        .card h1 {
            margin: 0 0 6px;
            font-size: 24px;
        }
        .card p {
            margin: 0 0 18px;
            color: #4a4a4a;
        }
        .field {
            margin-bottom: 14px;
        }
        .field label {
            display: block;
            font-size: 15px;
            margin-bottom: 6px;
        }
        .field input, .field select {
            width: 100%;
            padding: 12px 10px;
            border-radius: 6px;
            border: 1px solid #d2d2d2;
            font-size: 15px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            background: #fff;
        }
        .field input:focus,
        .field select:focus {
            border-color: #2e63e6;
            box-shadow: 0 0 0 3px rgba(46, 99, 230, 0.15);
        }
        .strength {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .strength-label {
            font-size: 12px;
            color: #6a6a6a;
        }
        .strength-bar {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: #e6e6e6;
            overflow: hidden;
        }
        .strength-bar span {
            display: block;
            height: 100%;
            width: 0%;
            background: #d14b4b;
            transition: width 0.2s ease, background 0.2s ease;
        }
        .strength-hints {
            margin: 0;
            padding-left: 18px;
            font-size: 12px;
            color: #6a6a6a;
        }
        .strength-hints li {
            margin: 2px 0;
        }
        .strength-hints li.ok {
            color: #2f8f4e;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 6px;
        }
        .check {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #4a4a4a;
            margin: 8px 0 4px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border: none;
            border-radius: 6px;
            background: #2f2f2f;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn:hover { background: #1f1f1f; }
        .link {
            margin-top: 14px;
            font-size: 14px;
            text-align: center;
        }
        .link a {
            color: #1f1f1f;
        }
        .bottom-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: #2e63e6;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="logo">
            <img src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="TAR UMT Logo">
        </div>

        <div class="card">
            <h1>Create your account</h1>
            <p>Join the community platform to access student services, events, and more. We will send an OTP to verify your email after registration.</p>

            {{-- Display validation errors returned by the registration controller. --}}
            @if ($errors->any())
                <div style="background:#ffecec;border:1px solid #f5c2c2;color:#7f1d1d;padding:10px 12px;border-radius:6px;margin-bottom:12px;">
                    <strong>Please fix the following:</strong>
                    <ul style="margin:8px 0 0 18px;padding:0;">
                        @foreach ($errors->all() as $error)
                            <li style="margin-bottom:4px;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.submit') }}" enctype="multipart/form-data">
                @csrf
                {{-- Core identity fields shared by student/admin/club registration. --}}
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" placeholder="e.g. Aisyah Lee" value="{{ old('name') }}" required>
                </div>

                <div class="field" id="student-id-field">
                    <label for="student_id">Student / Staff ID</label>
                    <input id="student_id" name="student_id" type="text" placeholder="e.g. 21WMR12345" value="{{ old('student_id') }}">
                </div>

                <div class="field" id="ic-number-field" style="display:none;">
                    <label for="ic_number">IC Number</label>
                    <input id="ic_number" name="ic_number" type="text" placeholder="e.g. 000808-14-XXXX" value="{{ old('ic_number') }}">
                </div>

                <div class="field" id="programme-field" style="display:none;">
                    <label for="programme">Programme</label>
                    <input id="programme" name="programme" type="text" placeholder="e.g. Diploma in Business Information Systems" value="{{ old('programme') }}">
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" placeholder="Your TAR UMT email" value="{{ old('email') }}" required>
                </div>

                {{-- Password setup section with client-side strength indicator. --}}
                <div class="grid">
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Create a strong password" required>
                        <div class="strength" id="password-strength">
                            <div class="strength-label">Strength: <span id="strength-text">Too weak</span></div>
                            <div class="strength-bar"><span id="strength-bar"></span></div>
                            <ul class="strength-hints" id="strength-hints">
                                <li data-rule="length">At least 8 characters</li>
                                <li data-rule="upper">One uppercase letter</li>
                                <li data-rule="number">One number</li>
                                <li data-rule="symbol">One special character</li>
                            </ul>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter your password" required>
                    </div>
                </div>

                <div class="field">
                    <label for="role">Account type</label>
                    <select id="role" name="role" required>
                        <option value="" selected disabled>Select one</option>
                        <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="club" {{ old('role') === 'club' ? 'selected' : '' }}>Club</option>
                    </select>
                </div>

                {{-- Club-only supporting document for admin account approval workflow. --}}
                <div class="field" id="club-attachment-field" style="display:none;">
                    <label for="club_attachment">Official club supporting document</label>
                    <input id="club_attachment" name="club_attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
                    <div style="font-size:12px;color:#6a6a6a;margin-top:6px;">For Official Clubs only. Allowed: PDF/JPG/PNG, max 5MB.</div>
                </div>

                <label class="check">
                    <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                    I agree to the platform terms and privacy notice.
                </label>

                <button type="submit" class="btn">Create account</button>
                <div class="link">
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                </div>
            </form>
        </div>

        <div class="bottom-bar"></div>
    </div>
    <script>
        (function () {
            // Client-side UI behavior: password strength meter + role-based field visibility toggles.
            var input = document.getElementById('password');
            var bar = document.getElementById('strength-bar');
            var text = document.getElementById('strength-text');
            var hints = document.getElementById('strength-hints');
            var roleSelect = document.getElementById('role');
            var studentIdField = document.getElementById('student-id-field');
            var studentIdInput = document.getElementById('student_id');
            var icNumberField = document.getElementById('ic-number-field');
            var icNumberInput = document.getElementById('ic_number');
            var programmeField = document.getElementById('programme-field');
            var programmeInput = document.getElementById('programme');
            var clubAttachmentField = document.getElementById('club-attachment-field');
            var clubAttachmentInput = document.getElementById('club_attachment');
            var form = document.querySelector('form[action="{{ route('register.submit') }}"]');

            function scorePassword(value) {
                // Display the current scoring for the password strength
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

                if (hints) {
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
                }

                var meetsRequirement = value.length >= 8
                    && /[A-Z]/.test(value)
                    && /[0-9]/.test(value)
                    && /[^A-Za-z0-9]/.test(value);

                if (input) {
                    input.setCustomValidity(
                        meetsRequirement || value.length === 0
                            ? ''
                            : 'Password must have at least 8 characters, one uppercase letter, one number, and one special character.'
                    );
                }
            }

            input.addEventListener('input', updateStrength);
            updateStrength();

            function toggleRoleFields() {
                // Show/hide and enforce only the fields required for the selected account type.
                var role = roleSelect ? roleSelect.value : '';
                var isClub = role === 'club';
                var isStudent = role === 'student';

                if (studentIdField) {
                    studentIdField.style.display = isClub ? 'none' : 'block';
                }

                if (studentIdInput) {
                    studentIdInput.required = false;
                    if (isClub) {
                        studentIdInput.value = '';
                    }
                }

                if (icNumberField) {
                    icNumberField.style.display = isStudent ? 'block' : 'none';
                }

                if (icNumberInput) {
                    icNumberInput.required = isStudent;
                    if (!isStudent) {
                        icNumberInput.value = '';
                    }
                }

                if (programmeField) {
                    programmeField.style.display = isStudent ? 'block' : 'none';
                }

                if (programmeInput) {
                    programmeInput.required = isStudent;
                    if (!isStudent) {
                        programmeInput.value = '';
                    }
                }

                if (clubAttachmentField) {
                    clubAttachmentField.style.display = isClub ? 'block' : 'none';
                }

                if (clubAttachmentInput) {
                    clubAttachmentInput.required = isClub;
                }
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleRoleFields);
                toggleRoleFields();
            }

            if (form) {
                form.addEventListener('submit', function (event) {
                    updateStrength();
                    if (input && !input.checkValidity()) {
                        event.preventDefault();
                        input.reportValidity();
                    }
                });
            }
        })();
    </script>
</body>
</html>
