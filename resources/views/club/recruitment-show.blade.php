@extends('layouts.club')

@section('title', 'Recruitment Details')

@section('content')
    <style>
        .recruitment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .recruitment-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .recruitment-actions {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 8px 14px;
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
            background: #fff;
            font-size: 14px;
        }
        .detail-card {
            margin-top: 16px;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .detail-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .detail-card p {
            margin: 0 0 6px;
            color: #4a4a4a;
        }
        .section-title {
            margin-top: 22px;
            font-size: 20px;
        }
        .filter-bar {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            margin-top: 10px;
            align-items: end;
        }
        .filter-bar input {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
        }
        .filter-bar button {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .applicant-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }
        .applicant-card {
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 14px;
            background: #fff;
        }
        .applicant-card h4 {
            margin: 0 0 6px;
            font-size: 17px;
        }
        .answer-list {
            margin: 8px 0 0;
            padding-left: 18px;
            color: #4a4a4a;
        }
        .answer-list li {
            margin-bottom: 4px;
        }
    </style>

    <div class="recruitment-header">
        <h2>{{ $recruitment->title }}</h2>
        <div class="recruitment-actions">
            <a class="action-btn" href="{{ route('club.recruitment.edit', $recruitment) }}">Edit</a>
            <form method="POST" action="{{ route('club.recruitment.destroy', $recruitment) }}">
                @csrf
                @method('DELETE')
                <button class="action-btn" type="submit" onclick="return confirm('Delete this recruitment?')">Delete</button>
            </form>
            <a class="action-btn" href="{{ route('club.recruitment.mine') }}">Back</a>
        </div>
    </div>

    <div class="detail-card">
        <h3>Details</h3>
        <p><strong>Event:</strong> {{ $recruitment->event->name ?? 'Event' }}</p>
        <p><strong>Description:</strong> {{ $recruitment->description }}</p>
        <p><strong>Requirements:</strong> {{ $recruitment->requirements ?: 'Not set' }}</p>
        <p><strong>Required skills:</strong> {{ $recruitment->required_skills ?: 'Not set' }}</p>
        <p><strong>Interests:</strong> {{ $recruitment->interests ?: 'Not set' }}</p>
        @if ($recruitment->questions->isNotEmpty())
            <p><strong>Questions:</strong></p>
            <ul class="answer-list">
                @foreach ($recruitment->questions as $question)
                    <li>{{ $question->question }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="section-title">Applicants</div>
    <form class="filter-bar" method="GET" action="{{ route('club.recruitment.show', $recruitment) }}">
        <div>
            <label for="skills">Skill filter</label>
            <input id="skills" name="skills" type="text" value="{{ $filters['skills'] ?? '' }}" placeholder="e.g. Design">
        </div>
        <div>
            <label for="experience">Experience filter</label>
            <input id="experience" name="experience" type="text" value="{{ $filters['experience'] ?? '' }}" placeholder="e.g. Leadership">
        </div>
        <button type="submit">Filter</button>
    </form>

    <div class="applicant-list">
        @if ($applications->isEmpty())
            <div class="applicant-card">No applicants yet.</div>
        @else
            @foreach ($applications as $application)
                <div class="applicant-card">
                    <h4>{{ $application->student->name ?? 'Student' }}</h4>
                    <div><strong>Student ID:</strong> {{ $application->student->student_id ?? '-' }}</div>
                    <div><strong>Skills:</strong> {{ $application->skills ?: 'Not provided' }}</div>
                    <div><strong>Experience:</strong> {{ $application->experience ?: 'Not provided' }}</div>
                    @if ($application->answers->isNotEmpty())
                        <ul class="answer-list">
                            @foreach ($application->answers as $answer)
                                <li>
                                    <strong>{{ $recruitment->questions->firstWhere('id', $answer->recruitment_question_id)->question ?? 'Question' }}:</strong>
                                    {{ $answer->answer }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
@endsection
