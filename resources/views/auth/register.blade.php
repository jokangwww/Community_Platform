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
            <p>Join the community platform to access student services, events, and more.</p>

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

            <form method="POST" action="{{ route('register.submit') }}">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input id="first_name" name="first_name" type="text" placeholder="e.g. Aisyah" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input id="last_name" name="last_name" type="text" placeholder="e.g. Lee" value="{{ old('last_name') }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="student_id">Student / Staff ID</label>
                    <input id="student_id" name="student_id" type="text" placeholder="e.g. 21WMR12345" value="{{ old('student_id') }}">
                </div>

                <div class="grid">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" placeholder="Your TAR UMT email" value="{{ old('email') }}" required>
                    </div>
                    <div class="field">
                        <label for="phone">Phone number (optional)</label>
                        <input id="phone" name="phone" type="tel" placeholder="01X-XXXXXXX" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="grid">
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Create a strong password" required>
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
                        <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="club" {{ old('role') === 'club' ? 'selected' : '' }}>Club</option>
                    </select>
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
</body>
</html>
