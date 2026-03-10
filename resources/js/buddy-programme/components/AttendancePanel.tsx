import { useState, useEffect } from 'react';
import { CheckCircle, XCircle, Clock, Calendar, AlertCircle, Loader2 } from 'lucide-react';

interface Session {
  id: string;
  date: string;
  time: string;
  endTime?: string;
  topic: string;
  description?: string;
  status: 'scheduled' | 'completed' | 'cancelled' | 'missed' | 'pending';
  mentorCheckIn: string | null;
  menteeCheckIn: string | null;
}

interface AttendancePanelProps {
  userRole: 'mentor' | 'mentee';
  userName: string;
  studentId: string;
  isReadonly?: boolean;
  semesterId?: number | null;
}

export function AttendancePanel({ userRole, userName, studentId, isReadonly, semesterId }: AttendancePanelProps) {
  const [sessions, setSessions] = useState<Session[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [selectedSessionId, setSelectedSessionId] = useState<string | null>(null);

  // Fetch sessions from API
  const fetchSessions = async () => {
    try {
      setLoading(true);
      setError(null);

      const semParam = semesterId ? `&semester_id=${semesterId}` : '';
      const response = await fetch(`/api/buddy/user/sessions?student_id=${encodeURIComponent(studentId)}${semParam}`);
      const result = await response.json();

      if (result.success) {
        setSessions(result.data);
      } else {
        setError(result.message || 'Failed to load sessions');
      }
    } catch (err) {
      setError('Failed to connect to server');
      console.error('Error fetching sessions:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (studentId) {
      fetchSessions();
    }
  }, [studentId]);

  const handleCheckIn = async (sessionId: string) => {
    if (!sessionId) return;

    try {
      setSubmitting(true);
      setSelectedSessionId(sessionId);

      const response = await fetch(`/api/buddy/user/sessions/${sessionId}/check-in`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ student_id: studentId }),
      });

      const result = await response.json();

      if (result.success) {
        // Refresh sessions to get updated data
        await fetchSessions();
        setSelectedSessionId(null);
      } else {
        alert(result.message || 'Failed to check in');
      }
    } catch (err) {
      alert('Failed to check in');
      console.error('Error checking in:', err);
    } finally {
      setSubmitting(false);
      setSelectedSessionId(null);
    }
  };

  // Helper to determine display status
  const getDisplayStatus = (session: Session): 'present' | 'absent' | 'pending' => {
    if (session.status === 'completed' || (session.mentorCheckIn && session.menteeCheckIn)) {
      return 'present';
    }
    if (session.status === 'missed' || session.status === 'cancelled') {
      return 'absent';
    }
    
    // Check if session time has passed and no check-in occurred
    if (session.date && session.time) {
      const now = new Date();
      const sessionDateTime = new Date(session.date + ' ' + session.time);
      
      // Add 2 hours buffer to session time for check-in window
      const sessionEndTime = new Date(sessionDateTime.getTime() + (2 * 60 * 60 * 1000));
      
      // If current time is past session end and no check-ins, mark as absent
      if (now > sessionEndTime && !session.mentorCheckIn && !session.menteeCheckIn) {
        return 'absent';
      }
    }
    
    return 'pending';
  };

  // Helper to check if current time is within session time range
  const isWithinSessionTime = (session: Session): boolean => {
    if (!session.date || !session.time) return false;
    
    const now = new Date();
    const sessionDate = new Date(session.date);
    
    // Check if today matches session date
    const today = new Date();
    if (sessionDate.toDateString() !== today.toDateString()) {
      return false;
    }
    
    // Parse session start time (format: "HH:MM:SS" or "HH:MM")
    const [startHour, startMin] = session.time.split(':').map(Number);
    const sessionStart = new Date(sessionDate);
    sessionStart.setHours(startHour, startMin, 0, 0);
    
    // Parse session end time if available, otherwise default to 1 hour after start
    let sessionEnd: Date;
    if (session.endTime) {
      const [endHour, endMin] = session.endTime.split(':').map(Number);
      sessionEnd = new Date(sessionDate);
      sessionEnd.setHours(endHour, endMin, 0, 0);
    } else {
      sessionEnd = new Date(sessionStart);
      sessionEnd.setHours(sessionEnd.getHours() + 1);
    }
    
    return now >= sessionStart && now <= sessionEnd;
  };

  // Find the latest pending session (most recent by date)
  const getLatestPendingSession = (): Session | null => {
    const pendingSessions = sessions.filter(s => 
      getDisplayStatus(s) === 'pending' && !s.menteeCheckIn
    );
    
    if (pendingSessions.length === 0) return null;
    
    // Sort by date descending (latest first)
    return pendingSessions.sort((a, b) => 
      new Date(b.date).getTime() - new Date(a.date).getTime()
    )[0];
  };

  const latestPendingSession = getLatestPendingSession();

  const getStatusBadge = (session: Session) => {
    const displayStatus = getDisplayStatus(session);
    
    switch (displayStatus) {
      case 'present':
        return (
          <span className="flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full">
            <CheckCircle className="w-4 h-4" />
            Present
          </span>
        );
      case 'absent':
        return (
          <span className="flex items-center gap-1 px-3 py-1 bg-red-100 text-red-800 rounded-full">
            <XCircle className="w-4 h-4" />
            {session.status === 'cancelled' ? 'Cancelled' : 'Absent'}
          </span>
        );
      case 'pending':
        return (
          <span className="flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 rounded-full">
            <Clock className="w-4 h-4" />
            Pending Check-in
          </span>
        );
    }
  };

  // Calculate statistics
  const presentSessions = sessions.filter(s => getDisplayStatus(s) === 'present').length;
  const absentSessions = sessions.filter(s => getDisplayStatus(s) === 'absent').length;
  const totalSessions = sessions.length;
  const attendanceRate = totalSessions > 0 ? (presentSessions / totalSessions) * 100 : 0;

  // Loading state
  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading attendance...</span>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-xl p-6">
        <div className="flex items-center gap-2 text-red-700">
          <AlertCircle className="w-5 h-5" />
          <span>{error}</span>
        </div>
        <button
          onClick={fetchSessions}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 cursor-pointer"
        >
          Retry
        </button>
      </div>
    );
  }

  // No sessions state
  if (sessions.length === 0) {
    return (
      <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
        <div className="flex items-center gap-2 text-yellow-700">
          <AlertCircle className="w-5 h-5" />
          <span>No sessions found. Sessions will appear here once they are scheduled.</span>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Attendance Statistics */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-gray-900 mb-4">Attendance Overview</h3>
        
        <div className="grid md:grid-cols-3 gap-4">
          <div className="bg-green-50 border border-green-200 rounded-lg p-4">
            <p className="text-gray-600 mb-1">Present</p>
            <p className="text-gray-900">{presentSessions} sessions</p>
          </div>

          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <p className="text-gray-600 mb-1">Absent</p>
            <p className="text-gray-900">{absentSessions} sessions</p>
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-gray-600 mb-1">Attendance Rate</p>
            <p className="text-gray-900">{attendanceRate.toFixed(1)}%</p>
          </div>
        </div>
      </div>

      {/* Session List */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-gray-900">Session Attendance</h3>
        </div>

        <div className="space-y-3">
          {sessions.map(session => (
            <div
              key={session.id}
              className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
            >
              <div className="flex items-start justify-between gap-4 mb-3">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <h4 className="text-gray-900">{session.topic}</h4>
                    {getStatusBadge(session)}
                  </div>
                  <div className="flex items-center gap-4 text-gray-600">
                    <div className="flex items-center gap-2">
                      <Calendar className="w-4 h-4" />
                      <span>{session.date}</span>
                    </div>
                    {session.time && (
                      <div className="flex items-center gap-2">
                        <Clock className="w-4 h-4" />
                        <span>{session.time}</span>
                      </div>
                    )}
                  </div>
                </div>
                {/* Check-in button for mentees - show only for latest pending session and within session time */}
                {!isReadonly && userRole === 'mentee' && 
                 latestPendingSession?.id === session.id && 
                 !session.menteeCheckIn && 
                 getDisplayStatus(session) === 'pending' && (
                  isWithinSessionTime(session) ? (
                    <button
                      onClick={() => handleCheckIn(session.id)}
                      disabled={submitting && selectedSessionId === session.id}
                      className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 cursor-pointer"
                    >
                      {submitting && selectedSessionId === session.id ? (
                        <><Loader2 className="w-4 h-4 animate-spin" /> Checking in...</>
                      ) : (
                        <><CheckCircle className="w-4 h-4" /> Check In</>
                      )}
                    </button>
                  ) : (
                    <span className="px-3 py-2 bg-gray-100 text-gray-500 rounded-lg text-sm flex items-center gap-2">
                      <Clock className="w-4 h-4" />
                      Check-in available during session time
                    </span>
                  )
                )}
              </div>

              <div className="grid md:grid-cols-2 gap-3">
                {/* Mentor Check-in Status */}
                <div className={`p-3 rounded-lg border cursor-pointer ${
                  session.mentorCheckIn 
                    ? 'bg-green-50 border-green-200' 
                    : 'bg-gray-50 border-gray-200'
                }`}>
                  <div className="flex items-center gap-2 mb-1">
                    {session.mentorCheckIn ? (
                      <CheckCircle className="w-4 h-4 text-green-600" />
                    ) : (
                      <XCircle className="w-4 h-4 text-gray-400" />
                    )}
                    <span className="text-gray-900">Mentor Check-in</span>
                  </div>
                  {session.mentorCheckIn && (
                    <p className="text-gray-600 ml-6">
                      {session.mentorCheckIn}
                    </p>
                  )}
                  {!session.mentorCheckIn && getDisplayStatus(session) !== 'absent' && (
                    <p className="text-gray-500 ml-6">Not checked in</p>
                  )}
                </div>

                {/* Mentee Check-in Status */}
                <div className={`p-3 rounded-lg border cursor-pointer ${
                  session.menteeCheckIn 
                    ? 'bg-green-50 border-green-200' 
                    : 'bg-gray-50 border-gray-200'
                }`}>
                  <div className="flex items-center gap-2 mb-1">
                    {session.menteeCheckIn ? (
                      <CheckCircle className="w-4 h-4 text-green-600" />
                    ) : (
                      <XCircle className="w-4 h-4 text-gray-400" />
                    )}
                    <span className="text-gray-900">Mentee Check-in</span>
                  </div>
                  {session.menteeCheckIn && (
                    <p className="text-gray-600 ml-6">
                      {session.menteeCheckIn}
                    </p>
                  )}
                  {!session.menteeCheckIn && getDisplayStatus(session) !== 'absent' && (
                    <p className="text-gray-500 ml-6">Not checked in</p>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Info Panel */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex items-start gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 mt-0.5" />
          <div>
            <h4 className="text-gray-900 mb-2">How Attendance Works</h4>
            <ul className="space-y-1 text-gray-700">
              <li className="flex items-start gap-2">
                <span className="text-blue-600">•</span>
                <span>Both mentor and mentee must check in after each session</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-blue-600">•</span>
                <span>Select the session date from the list and confirm your attendance</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-blue-600">•</span>
                <span>Status is marked as &quot;Present&quot; only when both parties check in</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-blue-600">•</span>
                <span>Sessions remain &quot;Pending&quot; until both check-ins are completed</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  );
}
