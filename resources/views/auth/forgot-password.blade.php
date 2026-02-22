<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        <h2 style="margin-top:0;">Forgot password</h2>
        <p style="margin-top:0;color:#4a4a4a;">Enter your account email and we will send a reset link.</p>

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

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="Your email address" value="{{ old('email') }}" required>
            </div>
            <button type="submit" class="btn">Send reset link</button>
        </form>

        <div class="link">
            Back to <a href="{{ route('login') }}">Sign In</a>
        </div>
    </div>

    <div class="bottom-bar"></div>
</div>
</body>
</html>
