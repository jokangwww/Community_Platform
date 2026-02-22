@extends('layouts.user_layout')

@section('title', 'Account Appeal')

@section('content')
    <div class="tabs">
        <div class="tab">Account Appeal</div>
    </div>

    <div style="max-width:760px;margin-top:20px;border:1px solid #d6d6d6;border-radius:10px;padding:16px;background:#fff;">
        <h2 style="margin:0 0 10px;font-size:24px;">Your account is banned</h2>
        <p style="margin:0 0 12px;color:#4a4a4a;">You cannot access other pages until admin approves your appeal.</p>

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

        <div style="margin-bottom:12px;color:#1f1f1f;font-size:14px;line-height:1.7;">
            <div><strong>Ban reason:</strong> {{ $user->ban_reason ?: 'Not provided' }}</div>
            <div><strong>Appeal status:</strong> {{ ucfirst($user->appeal_status ?? 'not submitted') }}</div>
            <div><strong>Reviewed note:</strong> {{ $user->appeal_review_note ?: 'N/A' }}</div>
        </div>

        @if (($user->appeal_status ?? '') !== 'pending')
            <form method="POST" action="{{ route('student.appeal.submit') }}">
                @csrf
                <div style="margin-bottom:10px;">
                    <label for="appeal_message" style="display:block;margin-bottom:6px;">Appeal message</label>
                    <textarea id="appeal_message" name="appeal_message" rows="6" style="width:100%;padding:10px;border:1px solid #c2c2c2;border-radius:6px;resize:vertical;" placeholder="Explain why your account should be reactivated." required>{{ old('appeal_message') }}</textarea>
                </div>
                <button type="submit" style="padding:10px 14px;border:1px solid #1f1f1f;border-radius:6px;background:#fff;cursor:pointer;">Submit appeal</button>
            </form>
        @else
            <div style="padding:10px 12px;border:1px solid #c2c2c2;border-radius:6px;background:#f7f7f7;">
                Your appeal is under review by admin.
            </div>
        @endif
    </div>
@endsection
