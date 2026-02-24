import { useState, useEffect } from 'react';
import { TestimonialGenerator } from '../TestimonialGenerator';
import { Loader2, AlertCircle } from 'lucide-react';

interface MentorData {
  name: string;
  studentId: string;
  course: string;
  faculty: string;
  rating: number;
}

interface Stats {
  totalSessions: number;
  attendanceRate: number;
}

interface TestimonialData {
  id: string;
  totalSessions: number;
  totalMentees: number;
  skillsTaught: string[];
  avgFeedbackScore: number;
  attendanceRate: number;
  semesterYear: string;
  status: 'pending' | 'approved' | 'rejected';
}

interface MentorTestimonialProps {
  mentor: MentorData;
  stats: Stats;
  totalMentees: number;
  subjects: string[];
}

export function MentorTestimonial({ mentor, stats, totalMentees, subjects }: MentorTestimonialProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [testimonialData, setTestimonialData] = useState<TestimonialData | null>(null);

  // Check eligibility: minimum 80% attendance and 10 sessions
  const isEligible = stats.attendanceRate >= 80 && stats.totalSessions >= 10;

  useEffect(() => {
    fetchTestimonialData();
  }, [mentor.studentId]);

  const fetchTestimonialData = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(`/api/buddy/testimonials/check?student_id=${encodeURIComponent(mentor.studentId)}`);
      const result = await response.json();

      if (result.success && result.data.hasRequested) {
        setTestimonialData(result.data.testimonial);
      }
    } catch (err) {
      console.error('Error fetching testimonial data:', err);
      setError('Failed to load testimonial data');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading testimonial...</span>
      </div>
    );
  }

  // Use data from API if available and approved, otherwise fall back to props
  const displayStats = testimonialData && testimonialData.status === 'approved' ? {
    totalSessions: testimonialData.totalSessions,
    totalMentees: testimonialData.totalMentees,
    skillsTaught: testimonialData.skillsTaught,
    avgFeedbackScore: testimonialData.avgFeedbackScore,
    attendanceRate: testimonialData.attendanceRate,
    semesterYear: testimonialData.semesterYear,
  } : {
    totalSessions: stats.totalSessions,
    totalMentees: totalMentees,
    skillsTaught: subjects,
    avgFeedbackScore: mentor.rating,
    attendanceRate: stats.attendanceRate,
    semesterYear: 'Semester 2, 2024/2025',
  };

  const isApproved = testimonialData?.status === 'approved';

  return (
    <div>
      <div className="mb-6">
        <h3 className="text-gray-900 mb-2">Contribution Testimonial</h3>
        <p className="text-gray-600">
          {isApproved 
            ? 'Download your official certificate of contribution for the Buddy Programme'
            : 'Your contribution summary for the Buddy Programme'}
        </p>
      </div>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
          <p className="text-red-700">{error}</p>
        </div>
      )}
      
      <TestimonialGenerator
        mentorStats={{
          name: mentor.name,
          studentId: mentor.studentId,
          programme: mentor.course || 'N/A',
          faculty: mentor.faculty || 'N/A',
          totalSessions: displayStats.totalSessions,
          totalMentees: displayStats.totalMentees,
          skillsTaught: displayStats.skillsTaught,
          avgFeedbackScore: displayStats.avgFeedbackScore,
          attendanceRate: displayStats.attendanceRate,
          semesterYear: displayStats.semesterYear
        }}
        isEligible={isEligible}
        isApproved={isApproved}
      />
    </div>
  );
}
