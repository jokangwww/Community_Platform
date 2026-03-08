@extends('layouts.user_layout')

@section('title', 'Soft Skill Certificate')

@section('content')
    <style>
        .cert-topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .cert-topbar h2 { margin:0; font-size:24px; }
        .cert-topbar .actions { display:flex; gap:8px; }
        .cert-btn {
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            background: #fff;
            padding: 8px 12px;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            font-size: 14px;
        }
        .certificate {
            margin-top: 18px;
            background: #fff;
            border: 2px solid #2f2f2f;
            padding: 36px 48px 52px;
            max-width: 1080px;
            min-height: 1320px;
            position: relative;
        }
        .cert-page {
            display: grid;
            gap: 18px;
        }
        .cert-uni-ms {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.3px;
            color: #2f2f2f;
            font-family: Georgia, "Times New Roman", serif;
        }
        .cert-brand-wrap {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 18px;
            margin-top: 8px;
        }
        .cert-brand-main {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 22px;
            min-height: 210px;
        }
        .cert-logo-crest {
            width: 150px;
            height: 190px;
            object-fit: contain;
            border: 0;
        }
        .cert-logo-wordmark {
            width: min(500px, 100%);
            height: auto;
            object-fit: contain;
        }
        .cert-cn {
            writing-mode: vertical-rl;
            text-orientation: upright;
            font-family: "KaiTi", "SimSun", serif;
            font-size: 28px;
            color: #111;
            letter-spacing: 4px;
            line-height: 1.4;
            margin-right: 6px;
        }
        .cert-title {
            text-align: center;
            font-size: 40px;
            font-style: italic;
            font-family: "Times New Roman", Georgia, serif;
            margin: 8px 0 0;
            color: #222;
        }
        .cert-body { margin-top: 8px; display:grid; gap:16px; }
        .cert-certified {
            text-align: center;
            font-size: 21px;
            color: #2a2a2a;
            margin-top: 8px;
        }
        .cert-name {
            font-size: 26px;
            font-weight: 700;
            color: #1f1f1f;
            text-align: center;
            margin: 14px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .cert-identity {
            text-align: center;
            font-size: 20px;
            margin-top: -6px;
        }
        .cert-programme {
            text-align: center;
            font-size: 24px;
            margin-top: -4px;
        }
        .cert-qualify {
            text-align: center;
            font-weight: 600;
            border-radius: 999px;
            padding: 6px 12px;
            width: fit-content;
            margin: 0 auto;
            border: 1px solid #d0d0d0;
            background: #f8f8f8;
        }
        .cert-qualify.ok {
            color: #146c2e;
            border-color: #b7e2c1;
            background: #eaf7ee;
        }
        .cert-qualify.no {
            color: #8b1f1f;
            border-color: #efc1c1;
            background: #fdeeee;
        }
        .cert-desc {
            text-align: center;
            font-size: 21px;
            margin-top: 42px;
            color: #222;
        }
        .cert-points-area {
            margin: 8px auto 0;
            width: min(820px, 100%);
            display: grid;
            grid-template-columns: 1fr 210px;
            column-gap: 28px;
            align-items: start;
        }
        .cert-points-header {
            grid-column: 2 / 3;
            text-align: center;
            font-size: 20px;
            text-decoration: underline;
            margin-bottom: 8px;
        }
        .cert-list {
            grid-column: 1 / 3;
            display: grid;
            gap: 9px;
        }
        .cert-point-row {
            display: grid;
            grid-template-columns: 1fr 210px;
            align-items: baseline;
            column-gap: 28px;
            font-size: 18px;
        }
        .cert-point-name {
            display: grid;
            grid-template-columns: 20px 1fr;
            gap: 6px;
            align-items: baseline;
        }
        .cert-point-name .dot {
            text-align: center;
        }
        .cert-point-score {
            text-align: center;
            font-weight: 700;
        }
        .cert-overall {
            margin-top: 14px;
            display: flex;
            justify-content: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
        }
        .cert-footer {
            margin-top: auto;
            display: grid;
            gap: 8px;
            color: #5a5a5a;
            font-size: 13px;
        }
        .cert-sign {
            margin-top: 90px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
        }
        .cert-sign-box {
            text-align: center;
            color: #1f1f1f;
            font-size: 16px;
        }
        .cert-sign-line {
            border-bottom: 1px solid #777;
            height: 26px;
            margin: 0 auto 8px;
            width: 85%;
        }
        .cert-date {
            margin-top: 38px;
            text-align: center;
            font-size: 18px;
            color: #1f1f1f;
        }
        @media print {
            .topbar, .sidebar, .cert-topbar { display: none !important; }
            .layout { display: block !important; }
            .content { padding: 0 !important; }
            .certificate { margin: 0; box-shadow: none; min-height: 0; }
            body { background: #fff !important; }
        }
        @media (max-width: 900px) {
            .cert-topbar { flex-direction: column; align-items: stretch; }
            .cert-topbar .actions { justify-content: flex-start; }
            .certificate { padding: 18px; min-height: 0; }
            .cert-uni-ms { font-size: 18px; }
            .cert-brand-wrap { grid-template-columns: 1fr; }
            .cert-brand-main { flex-direction: column; gap: 10px; min-height: 0; }
            .cert-logo-crest { width: 90px; height: 120px; }
            .cert-logo-wordmark { width: min(340px, 100%); }
            .cert-cn { display: none; }
            .cert-title { font-size: 28px; }
            .cert-certified { font-size: 18px; }
            .cert-programme { font-size: 20px; }
            .cert-desc { font-size: 17px; margin-top: 18px; }
            .cert-points-area { width: 100%; grid-template-columns: 1fr 110px; column-gap: 10px; }
            .cert-points-header { grid-column: 2 / 3; font-size: 16px; }
            .cert-point-row { grid-template-columns: 1fr 110px; column-gap: 10px; font-size: 15px; }
            .cert-sign { grid-template-columns: 1fr; gap: 12px; margin-top: 30px; }
        }
    </style>

    @php
        $totals = $softSkillElementTotals ?? ['cs' => 0, 'ctps' => 0, 'ts' => 0, 'll' => 0, 'kk' => 0, 'em' => 0, 'ls' => 0];
    @endphp

    <div class="cert-topbar">
        <h2>Soft Skill Certificate</h2>
        <div class="actions">
            <a href="{{ route('profile') }}" class="cert-btn">Back to Profile</a>
            <button type="button" class="cert-btn" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="certificate">
        <div class="cert-page">
            <div class="cert-uni-ms">UNIVERSITI PENGURUSAN DAN TEKNOLOGI TUNKU ABDUL RAHMAN</div>

            <div class="cert-brand-wrap">
                <div class="cert-brand-main">
                    <img class="cert-logo-wordmark" src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="TAR UMT wordmark">
                </div>
                <div class="cert-cn" aria-hidden="true">東姑阿都拉曼管理及工藝大學</div>
            </div>

            <h3 class="cert-title">Certificate in Soft Skills</h3>

            <div class="cert-body">
                <div class="cert-certified">It is hereby certified that</div>

                <div class="cert-name">{{ $student->name ?? '-' }}</div>
                <div class="cert-identity">({{ $student->ic_number ?? 'N/A' }})</div>
                <div class="cert-programme">
                    {{ $student->programme ?: 'Student of TAR UMT' }}
                </div>



                <div class="cert-desc">has achieved competencies in the following seven elements of soft skills:</div>

                <div class="cert-points-area">
                    <div class="cert-points-header">Total Points Collected</div>
                    <div class="cert-list">
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Communication Skills</span></div>
                            <div class="cert-point-score">{{ $totals['cs'] ?? 0 }}</div>
                        </div>
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Critical Thinking and Problem Solving</span></div>
                            <div class="cert-point-score">{{ $totals['ctps'] ?? 0 }}</div>
                        </div>
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Teamwork Skills</span></div>
                            <div class="cert-point-score">{{ $totals['ts'] ?? 0 }}</div>
                        </div>
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Lifelong Learning and Information Management</span></div>
                            <div class="cert-point-score">{{ $totals['ll'] ?? 0 }}</div>
                        </div>
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Entrepreneurship Skills</span></div>
                            <div class="cert-point-score">{{ $totals['kk'] ?? 0 }}</div>
                        </div>
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Ethics and Moral Professionalism</span></div>
                            <div class="cert-point-score">{{ $totals['em'] ?? 0 }}</div>
                        </div>
                        <div class="cert-point-row">
                            <div class="cert-point-name"><span class="dot">•</span><span>Leadership Skills</span></div>
                            <div class="cert-point-score">{{ $totals['ls'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="cert-sign">
                    <div class="cert-sign-box">
                        <div class="cert-sign-line"></div>
                        <div>Vice President</div>
                        <div>Student Affairs &amp; Quality Assurance</div>
                    </div>
                    <div class="cert-sign-box">
                        <div class="cert-sign-line"></div>
                        <div>Director</div>
                        <div>Department of Student Affairs</div>
                    </div>
                </div>

                <div class="cert-date">{{ ($generatedAt ?? now())->format('d F Y') }}</div>

            </div>
        </div>
    </div>
@endsection
