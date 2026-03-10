import { useState, useEffect } from 'react';
import { Star, Send, CheckCircle, AlertCircle, MessageSquare, Award, Loader2 } from 'lucide-react';

interface FeedbackRatingProps {
  userRole: 'mentor' | 'mentee';
  pairName: string;
  pairId: string;
  studentId: string;
  hasSubmitted?: boolean;
  isReadonly?: boolean;
}

export function FeedbackRating({ userRole, pairName, pairId, studentId, hasSubmitted: initialHasSubmitted = false, isReadonly }: FeedbackRatingProps) {
  const [rating, setRating] = useState<number>(0);
  const [hoverRating, setHoverRating] = useState<number>(0);
  const [feedback, setFeedback] = useState('');
  const [submitted, setSubmitted] = useState(initialHasSubmitted);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [checkingSubmission, setCheckingSubmission] = useState(true);

  // Check if user has already submitted evaluation
  useEffect(() => {
    const checkSubmission = async () => {
      try {
        setCheckingSubmission(true);
        const response = await fetch(`/api/buddy/evaluations/check?student_id=${encodeURIComponent(studentId)}&pair_student_id=${encodeURIComponent(pairId)}`);
        const result = await response.json();
        if (result.success && result.data.hasSubmitted) {
          setSubmitted(true);
        }
      } catch (err) {
        console.error('Error checking submission status:', err);
      } finally {
        setCheckingSubmission(false);
      }
    };

    if (studentId && pairId) {
      checkSubmission();
    } else {
      setCheckingSubmission(false);
    }
  }, [studentId, pairId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    
    if (rating === 0) {
      setError('Please select a rating');
      return;
    }
    
    if (feedback.trim().length < 10) {
      setError('Please provide at least 10 characters of feedback');
      return;
    }

    setIsSubmitting(true);
    
    try {
      const response = await fetch('/api/buddy/evaluations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          student_id: studentId,
          pair_student_id: pairId,
          rating,
          feedback: feedback.trim(),
        }),
      });

      const result = await response.json();

      if (result.success) {
        setSubmitted(true);
      } else {
        setError(result.message || 'Failed to submit evaluation');
      }
    } catch (err) {
      console.error('Error submitting evaluation:', err);
      setError('Failed to submit evaluation. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const renderStars = () => {
    return (
      <div className="flex gap-2">
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            type="button"
            onClick={() => setRating(star)}
            onMouseEnter={() => setHoverRating(star)}
            onMouseLeave={() => setHoverRating(0)}
            className="focus:outline-none transition-transform hover:scale-110 cursor-pointer"
          >
            <Star
              className={`w-8 h-8 cursor-pointer ${
                star <= (hoverRating || rating)
                  ? 'fill-amber-400 text-amber-400'
                  : 'text-gray-300'
              }`}
            />
          </button>
        ))}
      </div>
    );
  };

  const getRatingLabel = (rating: number) => {
    const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    return labels[rating];
  };

  if (checkingSubmission) {
    return (
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex items-center justify-center py-8">
          <Loader2 className="w-6 h-6 animate-spin text-blue-600" />
          <span className="ml-2 text-gray-600">Checking submission status...</span>
        </div>
      </div>
    );
  }

  if (submitted) {
    return (
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="text-center py-8">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle className="w-8 h-8 text-green-600" />
          </div>
          <h3 className="text-gray-900 mb-2">Feedback Submitted Successfully</h3>
          <p className="text-gray-600 mb-4">
            Thank you for providing your feedback on {pairName}
          </p>
        </div>
      </div>
    );
  }

  if (isReadonly) {
    return (
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="text-center py-8">
          <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <MessageSquare className="w-8 h-8 text-gray-400" />
          </div>
          <h3 className="text-gray-900 mb-2">Evaluation (Read-only)</h3>
          <p className="text-gray-600">
            This is a past semester. Evaluation submissions are disabled.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6">
      <div className="mb-6">
        <h3 className="text-gray-900 mb-2">End-of-Semester Evaluation</h3>
        <p className="text-gray-600">
          Rate and provide feedback for your {userRole === 'mentor' ? 'mentee' : 'mentor'}: <span className="text-gray-900">{pairName}</span>
        </p>
      </div>

      {error && (
        <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-800">
          <AlertCircle className="w-5 h-5 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Rating Section */}
        <div>
          <label className="block text-gray-700 mb-3">
            Performance Rating <span className="text-red-500">*</span>
          </label>
          <div className="flex items-center gap-4">
            {renderStars()}
            {(hoverRating || rating) > 0 && (
              <span className="text-gray-700">
                {getRatingLabel(hoverRating || rating)}
              </span>
            )}
          </div>
        </div>

        {/* Feedback Section */}
        <div>
          <label className="block text-gray-700 mb-2">
            Written Feedback <span className="text-red-500">*</span>
          </label>
          <p className="text-gray-600 mb-3">
            Describe your experience, strengths, and areas for improvement
          </p>
          <textarea
            value={feedback}
            onChange={(e) => setFeedback(e.target.value)}
            placeholder="Share your detailed feedback here (minimum 10 characters)..."
            rows={6}
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
            required
            minLength={10}
          />
          <p className="text-gray-500 mt-2">
            {feedback.length} characters
          </p>
        </div>

        {/* Prompts */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p className="text-gray-700 mb-2">Consider including:</p>
          <ul className="space-y-1 text-gray-600">
            <li className="flex items-start gap-2">
              <span className="text-blue-600 mt-1">•</span>
              <span>Communication effectiveness and responsiveness</span>
            </li>
            <li className="flex items-start gap-2">
              <span className="text-blue-600 mt-1">•</span>
              <span>Knowledge sharing and teaching ability</span>
            </li>
            <li className="flex items-start gap-2">
              <span className="text-blue-600 mt-1">•</span>
              <span>Commitment and attendance consistency</span>
            </li>
            <li className="flex items-start gap-2">
              <span className="text-blue-600 mt-1">•</span>
              <span>Areas where they excelled or could improve</span>
            </li>
          </ul>
        </div>

        {/* Submit Button */}
        <div className="flex justify-end gap-3">
          <button
            type="button"
            onClick={() => {
              setRating(0);
              setFeedback('');
            }}
            className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
          >
            Reset
          </button>
          <button
            type="submit"
            disabled={isSubmitting || rating === 0 || feedback.trim().length < 10}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 cursor-pointer"
          >
            {isSubmitting ? (
              <>
                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Submitting...
              </>
            ) : (
              <>
                <Send className="w-4 h-4" />
                Submit Feedback
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}
