@extends('layouts.user_layout')

@section('title', 'Buddy Programme - Benefits')
@section('welcome_text', 'Buddy Programme')

@section('content')
    <style>
        .bp-hero {
            margin-top: 16px;
            border-radius: 18px;
            padding: 32px 28px;
            background: linear-gradient(135deg, #0e5ec6 0%, #4f46e5 50%, #7c3aed 100%);
            color: #fff;
            box-shadow: 0 20px 40px -30px rgba(11, 43, 84, 0.72);
            text-align: center;
        }

        .bp-hero h1 {
            margin: 0 0 8px;
            font-size: clamp(24px, 3vw, 36px);
            color: #fff;
        }

        .bp-hero p {
            margin: 0 auto;
            max-width: 620px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            line-height: 1.7;
        }

        .bp-hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 12px 28px;
            background: #fff;
            color: #0e5ec6;
            font-weight: 700;
            font-size: 15px;
            border-radius: 12px;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .bp-hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .bp-section-title {
            text-align: center;
            margin: 32px 0 6px;
            font-size: 24px;
            color: #0f172a;
        }

        .bp-section-subtitle {
            text-align: center;
            margin: 0 auto 20px;
            max-width: 520px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .bp-benefits-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 640px) {
            .bp-benefits-grid { grid-template-columns: 1fr; }
        }

        .bp-role-section {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #dbe4f0;
            background: #fff;
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.85);
        }

        .bp-role-header {
            padding: 20px 20px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bp-role-header.mentor {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        }

        .bp-role-header.mentee {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }

        .bp-role-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .bp-role-icon.mentor { background: #065f46; color: #fff; }
        .bp-role-icon.mentee { background: #1e40af; color: #fff; }

        .bp-role-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .bp-role-header.mentor h2 { color: #065f46; }
        .bp-role-header.mentee h2 { color: #1e40af; }

        .bp-role-header small {
            display: block;
            font-weight: 400;
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }

        .bp-benefit-list {
            padding: 16px 20px 20px;
            margin: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .bp-benefit-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .bp-benefit-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .bp-benefit-item.mentor .bp-benefit-icon {
            background: #ecfdf5;
        }
        .bp-benefit-item.mentee .bp-benefit-icon {
            background: #eff6ff;
        }

        .bp-benefit-item strong {
            display: block;
            font-size: 14px;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .bp-benefit-item p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        .bp-how-it-works {
            margin-top: 32px;
            border-radius: 16px;
            border: 1px solid #dbe4f0;
            background: #fff;
            padding: 24px;
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.85);
        }

        .bp-how-it-works h2 {
            margin: 0 0 20px;
            font-size: 22px;
            text-align: center;
            color: #0f172a;
        }

        .bp-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .bp-step {
            text-align: center;
            padding: 16px 12px;
            border-radius: 12px;
            background: #f8fbff;
            border: 1px solid #e8eef8;
        }

        .bp-step-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0e5ec6, #4f46e5);
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .bp-step h3 {
            margin: 0 0 4px;
            font-size: 15px;
            color: #0f172a;
        }

        .bp-step p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }

        .bp-testimonial-section {
            margin-top: 32px;
            border-radius: 16px;
            border: 1px solid #dbe4f0;
            background: linear-gradient(135deg, #fff7ed, #fef3c7, #fdf2f8);
            padding: 24px;
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.85);
        }

        .bp-testimonial-section h2 {
            margin: 0 0 16px;
            font-size: 22px;
            text-align: center;
            color: #0f172a;
        }

        .bp-testimonials {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .bp-testimonial {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(0,0,0,0.06);
        }

        .bp-testimonial p {
            margin: 0 0 10px;
            font-size: 14px;
            color: #334155;
            line-height: 1.6;
            font-style: italic;
        }

        .bp-testimonial cite {
            font-style: normal;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }

        .bp-bottom-cta {
            margin-top: 32px;
            border-radius: 16px;
            padding: 32px;
            background: linear-gradient(135deg, #0e5ec6 0%, #4f46e5 100%);
            text-align: center;
            box-shadow: 0 20px 40px -30px rgba(11, 43, 84, 0.72);
        }

        .bp-bottom-cta h2 {
            margin: 0 0 8px;
            font-size: 24px;
            color: #fff;
        }

        .bp-bottom-cta p {
            margin: 0 auto 20px;
            max-width: 480px;
            font-size: 14px;
            color: rgba(255,255,255,0.85);
            line-height: 1.6;
        }

        .bp-bottom-cta a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: #fff;
            color: #0e5ec6;
            font-weight: 700;
            font-size: 16px;
            border-radius: 12px;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .bp-bottom-cta a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
    </style>

    <div class="tabs">
        <div class="tab">Buddy Programme</div>
        <div class="actions">
            <a href="{{ route('home') }}" class="action-icon" title="Back to Home" aria-label="Home">&#127968;</a>
        </div>
    </div>

    {{-- Hero Section --}}
    <section class="bp-hero">
        <h1>&#129309; Buddy Programme</h1>
        <p>A peer mentoring initiative that pairs experienced students with those looking for academic guidance, skill development, and campus integration support.</p>
        <a href="{{ route('buddy-programme') }}" class="bp-hero-cta">
            Register Now &rarr;
        </a>
    </section>

    {{-- Benefits Section --}}
    <h2 class="bp-section-title">Why Join?</h2>
    <p class="bp-section-subtitle">Whether you're sharing knowledge or seeking guidance, the Buddy Programme offers real benefits for your university journey.</p>

    <div class="bp-benefits-grid">
        {{-- Mentor Benefits --}}
        <div class="bp-role-section">
            <div class="bp-role-header mentor">
                <div class="bp-role-icon mentor">&#128640;</div>
                <div>
                    <h2>Become a Mentor</h2>
                    <small>Guide, lead &amp; grow</small>
                </div>
            </div>
            <ul class="bp-benefit-list">
                <li class="bp-benefit-item mentor">
                    <div class="bp-benefit-icon">&#127942;</div>
                    <div>
                        <strong>Earn GAP Points</strong>
                        <p>Accumulate Graduate Attribute Points recognised by the university for your mentoring contributions.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentor">
                    <div class="bp-benefit-icon">&#128188;</div>
                    <div>
                        <strong>Build Your Portfolio</strong>
                        <p>Add verified mentoring experience and testimonials to your CV and professional profile.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentor">
                    <div class="bp-benefit-icon">&#127793;</div>
                    <div>
                        <strong>Develop Leadership Skills</strong>
                        <p>Enhance communication, time management, and mentoring skills valued by employers.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentor">
                    <div class="bp-benefit-icon">&#128172;</div>
                    <div>
                        <strong>Enhance Soft Skills</strong>
                        <p>Practice active listening, empathy, conflict resolution, and presentation skills through mentoring sessions.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentor">
                    <div class="bp-benefit-icon">&#128101;</div>
                    <div>
                        <strong>Expand Your Network</strong>
                        <p>Connect with fellow mentors, faculty advisors, and programme coordinators across departments.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentor">
                    <div class="bp-benefit-icon">&#128220;</div>
                    <div>
                        <strong>Receive a Certificate</strong>
                        <p>Get an official certificate and testimonial letter recognising your commitment and impact.</p>
                    </div>
                </li>
            </ul>
        </div>

        {{-- Mentee Benefits --}}
        <div class="bp-role-section">
            <div class="bp-role-header mentee">
                <div class="bp-role-icon mentee">&#128218;</div>
                <div>
                    <h2>Become a Mentee</h2>
                    <small>Learn, grow &amp; succeed</small>
                </div>
            </div>
            <ul class="bp-benefit-list">
                <li class="bp-benefit-item mentee">
                    <div class="bp-benefit-icon">&#128300;</div>
                    <div>
                        <strong>Learn New Skills</strong>
                        <p>Get hands-on guidance in subjects you find challenging, from someone who's been there.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentee">
                    <div class="bp-benefit-icon">&#128200;</div>
                    <div>
                        <strong>Improve Academically</strong>
                        <p>Receive study tips, revision strategies, and assignment help tailored to your course.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentee">
                    <div class="bp-benefit-icon">&#128170;</div>
                    <div>
                        <strong>Build Confidence</strong>
                        <p>Gain the confidence to tackle difficult topics and participate actively in class.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentee">
                    <div class="bp-benefit-icon">&#129504;</div>
                    <div>
                        <strong>Develop Self-Improvement Habits</strong>
                        <p>Learn time management, goal setting, and effective study habits from your mentor.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentee">
                    <div class="bp-benefit-icon">&#127760;</div>
                    <div>
                        <strong>Campus Integration</strong>
                        <p>Navigate university life more easily with a buddy who knows the ropes.</p>
                    </div>
                </li>
                <li class="bp-benefit-item mentee">
                    <div class="bp-benefit-icon">&#129309;</div>
                    <div>
                        <strong>Personal Support System</strong>
                        <p>Have a go-to person for academic and personal guidance throughout the semester.</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    {{-- How It Works --}}
    <div class="bp-how-it-works">
        <h2>How It Works</h2>
        <div class="bp-steps">
            <div class="bp-step">
                <div class="bp-step-number">1</div>
                <h3>Register</h3>
                <p>Sign up as a Mentor or Mentee through the Buddy Programme page.</p>
            </div>
            <div class="bp-step">
                <div class="bp-step-number">2</div>
                <h3>Get Matched</h3>
                <p>Admin matches you with a compatible buddy based on your subjects and preferences.</p>
            </div>
            <div class="bp-step">
                <div class="bp-step-number">3</div>
                <h3>Meet &amp; Learn</h3>
                <p>Schedule sessions, share knowledge, and track your progress together.</p>
            </div>
            <div class="bp-step">
                <div class="bp-step-number">4</div>
                <h3>Grow Together</h3>
                <p>Complete the semester, earn points, receive certificates, and build lasting connections.</p>
            </div>
        </div>
    </div>

    {{-- Testimonials --}}
    <div class="bp-testimonial-section">
        <h2>&#128172; What Students Say</h2>
        <div class="bp-testimonials">
            <div class="bp-testimonial">
                <p>"Being a mentor helped me improve my own understanding while earning GAP points. It's a win-win!"</p>
                <cite>&mdash; Former Mentor</cite>
            </div>
            <div class="bp-testimonial">
                <p>"My mentor helped me go from struggling in calculus to getting an A-. I couldn't have done it alone."</p>
                <cite>&mdash; Former Mentee</cite>
            </div>
            <div class="bp-testimonial">
                <p>"The programme taught me leadership skills I now use in my internship. Highly recommend!"</p>
                <cite>&mdash; Former Mentor</cite>
            </div>
        </div>
    </div>

    {{-- Bottom CTA --}}
    <section class="bp-bottom-cta">
        <h2>Ready to Get Started?</h2>
        <p>Join hundreds of students who have benefited from the Buddy Programme. Registration is open now!</p>
        <a href="{{ route('buddy-programme') }}">
            Join the Buddy Programme &rarr;
        </a>
    </section>
@endsection
