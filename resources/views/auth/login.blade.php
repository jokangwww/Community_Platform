<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
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
            margin-bottom: 36px;
            text-align: center;
        }
        .logo img {
            max-width: 320px;
            width: 100%;
            height: auto;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 22px 20px 24px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }
        .field {
            margin-bottom: 14px;
        }
        .field label {
            display: block;
            font-size: 16px;
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            padding: 12px 10px;
            border-radius: 6px;
            border: 1px solid #d2d2d2;
            font-size: 15px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .field input:focus {
            border-color: #2e63e6;
            box-shadow: 0 0 0 3px rgba(46, 99, 230, 0.15);
        }
        .password-wrap {
            position: relative;
        }
        .password-wrap input {
            padding-right: 46px;
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            border-radius: 6px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #555;
            cursor: pointer;
        }
        .password-toggle:hover {
            background: #f2f2f2;
            color: #1f1f1f;
        }
        .password-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 99, 230, 0.15);
        }
        .password-toggle .icon-eye-off {
            display: none;
        }
        .password-wrap.is-visible .password-toggle .icon-eye {
            display: none;
        }
        .password-wrap.is-visible .password-toggle .icon-eye-off {
            display: inline;
        }
        .btn {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
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
            @if (session('status'))
                <div style="background:#ecfdf3;border:1px solid #9fdcb8;color:#14532d;padding:10px 12px;border-radius:6px;margin-bottom:12px;">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="background:#ffecec;border:1px solid #f5c2c2;color:#7f1d1d;padding:10px 12px;border-radius:6px;margin-bottom:12px;">
                    <strong>Please correct the following:</strong>
                    <ul style="margin:8px 0 0 18px;padding:0;">
                        @foreach ($errors->all() as $error)
                            <li style="margin-bottom:4px;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" placeholder="Your email address" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap" id="password-wrap">
                        <input id="password" name="password" type="password" placeholder="Your password" required>
                        <button type="button" class="password-toggle" id="password-toggle" aria-label="Show password" aria-pressed="false">
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
                <button type="submit" class="btn">Sign In</button>
            </form>

                <div class="link">
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <div class="link">
                    New to the platform? <a href="{{ route('register') }}">Create an account</a>
                </div>
                <div class="link">
                    Vendor only? <a href="{{ route('vendor.register') }}">Register as vendor</a>
                </div>
        </div>

        <div class="bottom-bar"></div>
    </div>
    <script>
        (function () {
            var toggle = document.getElementById('password-toggle');
            var input = document.getElementById('password');
            var wrap = document.getElementById('password-wrap');

            if (!toggle || !input || !wrap) return;

            toggle.addEventListener('click', function () {
                var isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                wrap.classList.toggle('is-visible', isHidden);
                toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                input.focus();
            });
        })();
    </script>
</body>
</html>
