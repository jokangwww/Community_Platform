import { AlertCircle } from 'lucide-react';
import { SchedulingPanel } from '../SchedulingPanel';

interface ScheduleData {
  hasMatch: boolean;
  matchId?: string;
  timeSlots: Array<{
    id: string;
    day: string;
    startTime: string;
    endTime: string;
    votes: number;
    status: 'pending' | 'voting';
  }>;
  schedule: { day: string; time: string; totalVotes: number } | null;
  hasVoted: boolean;
  isScheduled: boolean;
  slotsPublished: boolean;
}

interface MentorScheduleProps {
  studentId: string;
  scheduleData: ScheduleData | null;
}

export function MentorSchedule({ studentId, scheduleData }: MentorScheduleProps) {
  if (!scheduleData?.hasMatch) {
    return (
      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <AlertCircle className="w-8 h-8 text-yellow-600 mx-auto mb-2" />
        <p className="text-yellow-800">You need to be matched with a mentee before you can manage schedules.</p>
      </div>
    );
  }

  return (
    <SchedulingPanel 
      userRole="mentor" 
      studentId={studentId} 
      initialData={scheduleData} 
    />
  );
}
