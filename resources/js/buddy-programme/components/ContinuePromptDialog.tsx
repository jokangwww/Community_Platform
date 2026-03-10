import { useState } from 'react';
import { ArrowRight, X, Users, Archive } from 'lucide-react';

interface ContinuePromptDialogProps {
  /** Label of the semester that just ended */
  lastSemesterLabel: string;
  /** Label of the new semester to continue into */
  nextSemesterLabel?: string;
  onContinue: () => void;
  onDecline: () => void;
  isSaving?: boolean;
}

/**
 * Non-dismissable modal that asks the mentee whether they want to continue
 * into the next semester with the same mentor(s).
 */
export function ContinuePromptDialog({
  lastSemesterLabel,
  nextSemesterLabel,
  onContinue,
  onDecline,
  isSaving = false,
}: ContinuePromptDialogProps) {
  const [choice, setChoice] = useState<'continue' | 'decline' | null>(null);

  const handleConfirm = () => {
    if (!choice) return;
    if (choice === 'continue') onContinue();
    else onDecline();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div className="bg-white rounded-xl border border-gray-200 shadow-xl max-w-md w-full mx-4 p-6">
        <div className="flex items-center gap-3 mb-4">
          <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
            <Users className="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <h2 className="text-lg font-semibold text-gray-900">New Semester — Continue?</h2>
            <p className="text-sm text-gray-500">{lastSemesterLabel} has ended</p>
          </div>
        </div>

        <p className="text-gray-600 mb-6">
          Would you like to continue in{' '}
          <strong>{nextSemesterLabel ?? 'the new semester'}</strong> with your existing mentor?
          Your mentor will be notified to confirm as well.
        </p>

        <div className="flex flex-col gap-3 mb-5">
          <button
            onClick={() => setChoice('continue')}
            className={`flex items-center gap-3 w-full px-4 py-3 rounded-lg border-2 transition-colors cursor-pointer ${
              choice === 'continue'
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-200 text-gray-700 hover:border-blue-300 hover:bg-blue-50'
            }`}
          >
            <ArrowRight className="w-4 h-4" />
            <div className="text-left">
              <p className="font-medium">Yes, continue with my mentor</p>
              <p className="text-xs text-gray-500 mt-0.5">
                Your mentor will be asked to confirm the continuation.
              </p>
            </div>
          </button>

          <button
            onClick={() => setChoice('decline')}
            className={`flex items-center gap-3 w-full px-4 py-3 mb-4 rounded-lg border-2 transition-colors cursor-pointer ${
              choice === 'decline'
                ? 'border-red-400 bg-red-50 text-red-700'
                : 'border-gray-200 text-gray-700 hover:border-red-300 hover:bg-red-50'
            }`}
          >
            <X className="w-4 h-4" />
            <div className="text-left">
              <p className="font-medium">No, I'll stop here</p>
              <p className="text-xs text-gray-500 mt-0.5">
                You can still view your records from {lastSemesterLabel}.
              </p>
            </div>
          </button>
        </div>

        <button
          onClick={handleConfirm}
          disabled={!choice || isSaving}
          className="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
        >
          {isSaving ? 'Saving…' : 'Confirm Selection'}
        </button>

        <p className="text-xs text-gray-400 text-center mt-3">
          This action cannot be undone. Please choose carefully.
        </p>
      </div>
    </div>
  );
}
