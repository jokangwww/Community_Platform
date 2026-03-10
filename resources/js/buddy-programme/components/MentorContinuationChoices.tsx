import { useState, useEffect } from 'react';
import { Check, X, Loader2 } from 'lucide-react';

interface ContinuationRequest {
  id: number;
  mentee_name: string;
  mentee_student_id: string;
  subject_name: string | null;
  mentor_choice: 'pending' | 'continue' | 'decline';
  resolved_at: string | null;
}

interface MentorContinuationChoicesProps {
  nextSemesterLabel?: string;
  onAllResolved: () => void;
}

/**
 * Shown to a mentor who has pending continuation requests from their mentees.
 * They can accept or decline each one individually.
 */
export function MentorContinuationChoices({ nextSemesterLabel, onAllResolved }: MentorContinuationChoicesProps) {
  const [requests, setRequests] = useState<ContinuationRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState<number | null>(null);
  const [selfChoice, setSelfChoice] = useState<'continue' | 'decline' | null>(null);
  const [selfSaving, setSelfSaving] = useState(false);

  const getCsrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  useEffect(() => {
    fetch('/api/buddy/continuation/mentor-requests', { headers: { Accept: 'application/json' } })
      .then((r) => r.json())
      .then((reqRes) => {
        if (reqRes.success) setRequests(reqRes.data);
      })
      .finally(() => setIsLoading(false));
  }, []);

  const handleResponse = async (continuationId: number, choice: 'continue' | 'decline') => {
    setActionLoading(continuationId);
    try {
      const res = await fetch('/api/buddy/continuation/mentor-response', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify({
          continuation_id: continuationId,
          choice,
        }),
      });
      const data = await res.json();
      if (data.success) {
        setRequests((prev) =>
          prev.map((r) => (r.id === continuationId ? { ...r, mentor_choice: choice } : r))
        );
        // Check if all resolved
        const remaining = requests.filter(
          (r) => r.id !== continuationId && r.mentor_choice === 'pending'
        );
        if (remaining.length === 0) {
          setTimeout(onAllResolved, 800);
        }
      }
    } finally {
      setActionLoading(null);
    }
  };

  const handleSelfChoice = async (choice: 'continue' | 'decline') => {
    setSelfSaving(true);
    setSelfChoice(choice);
    try {
      await fetch('/api/buddy/continuation/mentor-self-choice', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify({ choice }),
      });
      // Reload the page to reflect new state
      window.location.reload();
    } finally {
      setSelfSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
      </div>
    );
  }

  const pendingRequests = requests.filter((r) => r.mentor_choice === 'pending');

  return (
    <div className="max-w-2xl mx-auto space-y-5">
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h2 className="font-semibold text-gray-900 mb-1" style={{ fontSize: '1.3rem' }}>
          Continuation Requests{nextSemesterLabel ? ` — ${nextSemesterLabel}` : ''}
        </h2>
        <p className="text-sm text-gray-500 mb-6">
          The following mentees would like to continue with you in the new semester. Please respond to each request.
        </p>

        {requests.length === 0 ? (
          <p className="text-gray-500 text-sm text-center py-6">No pending requests.</p>
        ) : (
          <div className="space-y-3">
            {requests.map((req) => (
              <div
                key={req.id}
                className={`flex items-center gap-4 p-4 rounded-lg border ${
                  req.mentor_choice === 'continue'
                    ? 'border-green-200 bg-green-50'
                    : req.mentor_choice === 'decline'
                    ? 'border-red-200 bg-red-50'
                    : 'border-gray-200 bg-gray-50'
                }`}
              >
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-gray-900 truncate">{req.mentee_name}</p>
                  <p className="text-sm text-gray-500">{req.mentee_student_id}</p>
                  {req.subject_name && (
                    <span className="inline-block text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded mt-0.5">
                      {req.subject_name}
                    </span>
                  )}
                </div>

                {req.mentor_choice === 'pending' ? (
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => handleResponse(req.id, 'continue')}
                      disabled={actionLoading === req.id}
                      className="flex items-center gap-1 px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 disabled:opacity-50 cursor-pointer transition-colors"
                    >
                      {actionLoading === req.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Check className="w-3.5 h-3.5" />}
                      Accept
                    </button>
                    <button
                      onClick={() => handleResponse(req.id, 'decline')}
                      disabled={actionLoading === req.id}
                      className="flex items-center gap-1 px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 disabled:opacity-50 cursor-pointer transition-colors"
                    >
                      {actionLoading === req.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <X className="w-3.5 h-3.5" />}
                      Decline
                    </button>
                  </div>
                ) : (
                  <span
                    className={`text-xs font-medium px-2 py-1 rounded ${
                      req.mentor_choice === 'continue'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'
                    }`}
                  >
                    {req.mentor_choice === 'continue' ? 'Accepted' : 'Declined'}
                  </span>
                )}
              </div>
            ))}
          </div>
        )}

        {pendingRequests.length === 0 && requests.length > 0 && (
          <p className="text-sm text-green-600 text-center mt-4">
            All requests resolved. Your new semester dashboard will load shortly.
          </p>
        )}
      </div>

      {/* Mentor's own continuation choice (only shown if no pending requests to resolve first) */}
      {requests.length === 0 && (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <h3 className="font-semibold text-gray-900 mb-2" style={{ fontSize: '1.3rem' }}>Do you wish to continue as a mentor?</h3>
          <p className="text-sm text-gray-500 mb-4">
            No mentees have requested continuation with you. You can choose to re-register for the new semester or stop here.
          </p>
          <div className="flex gap-3">
            <button
              onClick={() => handleSelfChoice('continue')}
              disabled={selfSaving}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 cursor-pointer transition-colors"
            >
              {selfSaving && selfChoice === 'continue' ? <Loader2 className="w-4 h-4 animate-spin" /> : <Check className="w-4 h-4" />}
              Yes, re-register
            </button>
            <button
              onClick={() => handleSelfChoice('decline')}
              disabled={selfSaving}
              className="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 cursor-pointer transition-colors"
            >
              {selfSaving && selfChoice === 'decline' ? <Loader2 className="w-4 h-4 animate-spin" /> : <X className="w-4 h-4" />}
              No thanks
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
