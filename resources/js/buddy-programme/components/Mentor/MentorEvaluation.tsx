import { FeedbackRating } from '../FeedbackRating';

interface Mentee {
  id: string;
  name: string;
  studentId: string;
  subject: string;
  isRepeater: boolean;
  attendanceRate: number;
  completedSessions: number;
  totalSessions: number;
}

interface MentorEvaluationProps {
  mentees: Mentee[];
  mentorStudentId: string;
  isReadonly?: boolean;
}

export function MentorEvaluation({ mentees, mentorStudentId, isReadonly }: MentorEvaluationProps) {
  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-gray-900 mb-2">End-of-Semester Evaluation</h3>
        <p className="text-gray-600 mb-6">Provide feedback and ratings for your mentees</p>
      </div>
      
      {mentees.map(mentee => (
        <FeedbackRating
          key={mentee.id}
          userRole="mentor"
          pairName={mentee.name}
          pairId={mentee.studentId}
          studentId={mentorStudentId}
          hasSubmitted={false}
          isReadonly={isReadonly}
        />
      ))}
    </div>
  );
}
