<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Account Resubmission</title>
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
            padding: 22px 20px 24px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }
        .field {
            margin-bottom: 14px;
        }
        .field label {
            display: block;
            font-size: 15px;
            margin-bottom: 6px;
        }
        .field input,
        .field textarea {
            width: 100%;
            padding: 12px 10px;
            border-radius: 6px;
            border: 1px solid #d2d2d2;
            font-size: 15px;
            outline: none;
        }
        .field textarea {
            resize: vertical;
            min-height: 110px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #2f2f2f;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        .btn:hover { background: #1f1f1f; }
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
        <h2 style="margin-top:0;">Club Account Resubmission</h2>
        <p style="margin-top:0;color:#4a4a4a;">Upload a new attachment and provide your remark for admin review.</p>

        @if ($club->club_rejection_reason)
            <div style="background:#fff4e5;border:1px solid #f2cf9c;color:#7a4b00;padding:10px 12px;border-radius:6px;margin-bottom:12px;">
                <strong>Previous rejection reason:</strong> {{ $club->club_rejection_reason }}
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

        <form method="POST" action="{{ route('club.resubmission.submit') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="field">
                <label>Email</label>
                <input type="email" value="{{ $email }}" readonly>
            </div>

            <div class="field">
                <label for="club_attachment">New Supporting Attachment</label>
                <input id="club_attachment" name="club_attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" required>
                <div style="font-size:12px;color:#6a6a6a;margin-top:6px;">Allowed: PDF/JPG/PNG, max 5MB.</div>
            </div>

            <div class="field">
                <label for="resubmission_remark">Remark to Admin</label>
                <textarea id="resubmission_remark" name="resubmission_remark" placeholder="Explain the update you made..." maxlength="1000" required>{{ old('resubmission_remark') }}</textarea>
            </div>

            <button type="submit" class="btn">Submit Resubmission</button>
        </form>
    </div>

    <div class="bottom-bar"></div>
</div>
</body>
</html>
