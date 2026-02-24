@extends('layouts.vendor')

@section('title', 'Vendor Home')

@section('content')
    <div style="padding:16px 0;border-bottom:2px solid #b8b8b8;">
        <h2 style="margin:0;font-size:24px;">Vendor Portal</h2>
        <div style="margin-top:6px;color:#555;">Browse events with booth rental opportunities and submit applications.</div>
    </div>

    <div style="margin-top:16px;border:1px solid #d6d6d6;border-radius:10px;padding:16px;background:#fff;max-width:800px;">
        <div style="font-size:16px;margin-bottom:10px;">Quick Actions</div>
        <a href="{{ route('vendor.booth-applications.index') }}" style="display:inline-block;border:1px solid #1f1f1f;border-radius:8px;padding:10px 12px;text-decoration:none;color:#1f1f1f;background:#fff;">
            Browse Booth Rental & Apply
        </a>
    </div>
@endsection

