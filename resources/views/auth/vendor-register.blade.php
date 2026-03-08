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
                <input type="password" name="password" required>
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
</body>
</html>


