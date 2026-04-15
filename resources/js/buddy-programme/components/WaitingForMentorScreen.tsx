import type { ReactNode } from 'react';
import { Clock, Archive } from 'lucide-react';

interface WaitingForMentorScreenProps {
  /** The semester the mentee is waiting to continue from */
  lastSemesterLabel?: string;
  /** Mentee's last-semester student ID (to render read-only dashboard) */
  studentId: string;
  /** semester_id of the last semester to pass to dashboard for read-only view */
  lastSemesterId: number | null;
  /** Render the actual last-semester dashboard in an iframe-style section */
  renderReadOnlyDashboard?: () => ReactNode;
}

/**
 * Shown to a mentee who chose "continue" but is waiting for their mentor(s) to respond.
 * Displays a banner + a read-only view of the last-semester dashboard below.
 */
export function WaitingForMentorScreen({
  lastSemesterLabel,
  renderReadOnlyDashboard,
}: WaitingForMentorScreenProps) {
  return (
    <div className="space-y-4">
      {/* Banner */}
      <div className="bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start gap-4">
        <div className="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
          <Clock className="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <h3 className="font-semibold text-amber-900">Waiting for Mentor Confirmation</h3>
          <p className="text-sm text-amber-700 mt-1">
            You have chosen to continue in the new semester. Your mentor(s) have been notified and
            need to confirm. Your new dashboard will be available once they respond.
          </p>
          {lastSemesterLabel && (
            <p className="text-xs text-amber-600 flex items-center gap-1 mt-2">
              <Archive className="w-3.5 h-3.5" />
              Viewing {lastSemesterLabel} (read-only)
            </p>
          )}
        </div>
      </div>

      {/* Read-only last-semester dashboard */}
      {renderReadOnlyDashboard && (
        <div className="pointer-events-none opacity-80 select-none">
          {renderReadOnlyDashboard()}
        </div>
      )}
    </div>
  );
}
