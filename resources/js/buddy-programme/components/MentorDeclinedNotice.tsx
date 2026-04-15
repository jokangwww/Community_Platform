import type { ReactNode } from 'react';
import { AlertCircle, RefreshCw } from 'lucide-react';

interface MentorDeclinedNoticeProps {
  /** All continuation records with each mentor's decision */
  continuations?: Array<{
    id: number;
    mentor_name: string | null;
    mentor_choice: string;
  }>;
  /** Label of the new semester */
  nextSemesterLabel?: string;
  /** Callback for "Register Fresh" button */
  onRegisterFresh: () => void;
  renderReadOnlyDashboard?: () => ReactNode;
}

/**
 * Shown to a mentee whose mentor(s) declined the continuation request.
 * Prompts them to register fresh for the new semester.
 */
export function MentorDeclinedNotice({
  continuations = [],
  nextSemesterLabel,
  onRegisterFresh,
  renderReadOnlyDashboard,
}: MentorDeclinedNoticeProps) {
  const declinedNames = continuations
    .filter((c) => c.mentor_choice === 'decline')
    .map((c) => c.mentor_name ?? 'Your mentor');

  return (
    <div className="space-y-4">
      <div className="max-w-lg mx-auto">
        <div className="bg-white rounded-xl border border-red-200 p-6 text-center">
          <div className="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertCircle className="w-7 h-7 text-red-500" />
          </div>

          <h2 className="text-lg font-semibold text-gray-900 mb-2">
            Your mentor{declinedNames.length > 1 ? 's' : ''} declined the continuation
          </h2>

          {declinedNames.length > 0 && (
            <div className="mb-3 text-sm text-gray-600">
              {declinedNames.join(', ')}{' '}
              {declinedNames.length === 1 ? 'has' : 'have'} declined to continue.
            </div>
          )}

          <div className="bg-red-50 border border-red-100 rounded-lg p-3 mb-5 text-sm text-red-700">
            Unable to continue{nextSemesterLabel ? ` into ${nextSemesterLabel}` : ''} with your existing
            mentor{declinedNames.length > 1 ? 's' : ''}.
          </div>

          <p className="text-gray-600 text-sm mt-3 mb-6">
            You can still view your previous dashboard below. If you want to continue in the new semester,
            register again to be matched with a new mentor.
          </p>

          <button
            onClick={onRegisterFresh}
            className="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer mx-auto"
          >
            <RefreshCw className="w-4 h-4" />
            Register for {nextSemesterLabel ?? 'New Semester'}
          </button>
        </div>
      </div>

      {renderReadOnlyDashboard && <div>{renderReadOnlyDashboard()}</div>}
    </div>
  );
}
