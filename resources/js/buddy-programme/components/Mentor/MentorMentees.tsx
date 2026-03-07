import { useState } from 'react';
import { CheckCircle, AlertCircle, Calendar, Clock, ChevronDown } from 'lucide-react';

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

interface AttendanceRecord {
  id: string;
  date: string;
  topic: string;
  menteeId: string;
  menteeName: string;
  status: 'present' | 'absent';
  mentorCheckedIn: boolean;
}

interface SessionOption {
  id: string;
  date: string;
  time: string;
  topic: string;
  status: string;
  description?: string;
}

interface MentorMenteesProps {
  mentees: Mentee[];
  attendanceRecords: AttendanceRecord[];
  studentId: string;
  sessions: SessionOption[];
  onAttendanceSubmitted: () => void;
}

export function MentorMentees({
  mentees,
  attendanceRecords,
  studentId,
  sessions,
  onAttendanceSubmitted,
}: MentorMenteesProps) {
  const [showCheckInModal, setShowCheckInModal] = useState(false);
  const [showMenteeDetailsModal, setShowMenteeDetailsModal] = useState(false);
  const [selectedMenteeId, setSelectedMenteeId] = useState<string | null>(null);
  const [selectedSessionId, setSelectedSessionId] = useState('');
  const [sessionTopic, setSessionTopic] = useState('');
  const [sessionDescription, setSessionDescription] = useState('');
  const [menteeAttendance, setMenteeAttendance] = useState<Record<string, 'present' | 'absent'>>({});
  const [submitting, setSubmitting] = useState(false);

  // Filter to only pending sessions that haven't been checked in yet, sorted ascending for ordinal labels
  const pendingSessions = [...sessions.filter(s => s.status === 'pending' && s.date)]
    .sort((a, b) => a.date.localeCompare(b.date));

  const openAttendanceModal = () => {
    setShowCheckInModal(true);
    setSelectedSessionId('');
    setSessionTopic('');
    setSessionDescription('');
    const initialAttendance: Record<string, 'present' | 'absent'> = {};
    mentees.forEach(mentee => {
      initialAttendance[mentee.id] = 'absent';
    });
    setMenteeAttendance(initialAttendance);
  };

  const handleSessionSelect = (sessionId: string) => {
    setSelectedSessionId(sessionId);
    const session = sessions.find(s => s.id === sessionId);
    if (session) {
      setSessionTopic(session.topic || '');
      setSessionDescription(session.description || '');
    }
  };

  const toggleMenteeAttendance = (menteeId: string) => {
    setMenteeAttendance(prev => ({
      ...prev,
      [menteeId]: prev[menteeId] === 'present' ? 'absent' : 'present'
    }));
  };

  const submitAttendance = async () => {
    if (!selectedSessionId || !studentId) return;

    try {
      setSubmitting(true);
      
      const response = await fetch('/api/buddy/mentor/attendance', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          student_id: studentId,
          session_id: parseInt(selectedSessionId),
          session_topic: sessionTopic || null,
          session_description: sessionDescription || null,
          attendance: menteeAttendance,
        }),
      });

      const result = await response.json();

      if (result.success) {
        setShowCheckInModal(false);
        setSelectedSessionId('');
        setSessionTopic('');
        setSessionDescription('');
        setMenteeAttendance({});
        onAttendanceSubmitted();
      } else {
        alert(result.message || 'Failed to submit attendance');
      }
    } catch (err) {
      console.error('Error submitting attendance:', err);
      alert('Failed to submit attendance');
    } finally {
      setSubmitting(false);
    }
  };

  const selectedMentee = mentees.find(m => m.id === selectedMenteeId);
  const menteeRecords = selectedMenteeId 
    ? attendanceRecords.filter(r => r.menteeId === selectedMenteeId)
    : [];

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-gray-900 mb-2">My Mentees</h3>
          <p className="text-gray-600">View your mentees and their attendance records</p>
        </div>
        <button
          onClick={openAttendanceModal}
          className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2 cursor-pointer"
        >
          <CheckCircle className="w-4 h-4" />
          Check In
        </button>
      </div>

      <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table className="w-full">
          <thead className="bg-gray-50 border-b border-gray-200">
            <tr>
              <th className="px-6 py-3 text-left text-gray-700">Name</th>
              <th className="px-6 py-3 text-left text-gray-700">Student ID</th>
              <th className="px-6 py-3 text-left text-gray-700">Subject</th>
              <th className="px-6 py-3 text-left text-gray-700">Status</th>
              <th className="px-6 py-3 text-left text-gray-700">Attendance</th>
              <th className="px-6 py-3 text-left text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {mentees.map(mentee => (
              <tr key={mentee.id} className="hover:bg-gray-50 transition-colors">
                <td className="px-6 py-4">
                  <p className="text-gray-900">{mentee.name}</p>
                </td>
                <td className="px-6 py-4">
                  <p className="text-gray-700">{mentee.studentId}</p>
                </td>
                <td className="px-6 py-4">
                  <p className="text-gray-700">{mentee.subject}</p>
                </td>
                <td className="px-6 py-4">
                  <p className="text-gray-700">
                    {mentee.isRepeater ? 'Repeater' : 'Regular'}
                  </p>
                </td>
                <td className="px-6 py-4">
                  <span className="text-gray-900">{mentee.attendanceRate}%</span>
                </td>
                <td className="px-6 py-4">
                  <button
                    onClick={() => {
                      setSelectedMenteeId(mentee.id);
                      setShowMenteeDetailsModal(true);
                    }}
                    className="px-3 py-1 text-purple-600 hover:bg-purple-50 border border-purple-300 rounded transition-colors cursor-pointer"
                  >
                    View Details
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Attendance Modal */}
      {showCheckInModal && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setShowCheckInModal(false)}
        >
          <div 
            className="bg-white rounded-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-4">Mark Session Attendance</h3>
            
            <div className="space-y-4 mb-6">
              <div>
                <label className="block text-gray-700 mb-2">Select Session</label>
                {pendingSessions.length === 0 ? (
                  <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div className="flex items-center gap-2 text-yellow-700">
                      <AlertCircle className="w-4 h-4" />
                      <span>No pending sessions available. Sessions are auto-created when you confirm a schedule.</span>
                    </div>
                  </div>
                ) : (
                  <div className="relative">
                    <select
                      value={selectedSessionId}
                      onChange={(e) => handleSessionSelect(e.target.value)}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 appearance-none bg-white pr-10 cursor-pointer"
                    >
                      <option value="">-- Choose a session --</option>
                      {pendingSessions.map((session, index) => (
                        <option key={session.id} value={session.id}>
                          Session {index + 1} — {session.date} {session.time}
                        </option>
                      ))}
                    </select>
                  </div>
                )}
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Session Name <span className="text-gray-400 text-sm">(optional, overrides default name)</span></label>
                <input
                  type="text"
                  value={sessionTopic}
                  onChange={(e) => setSessionTopic(e.target.value)}
                  placeholder="e.g., Calculus - Integration Techniques"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Description <span className="text-gray-400 text-sm">(optional)</span></label>
                <textarea
                  value={sessionDescription}
                  onChange={(e) => setSessionDescription(e.target.value)}
                  placeholder="Add any notes about this session..."
                  rows={2}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Mark Attendance for Mentees</label>
                <div className="space-y-2">
                  {mentees.map(mentee => (
                    <div
                      key={mentee.id}
                      className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div>
                        <p className="text-gray-900">{mentee.name}</p>
                        <p className="text-gray-600">{mentee.studentId}</p>
                      </div>
                      <button
                        onClick={() => toggleMenteeAttendance(mentee.id)}
                        className={`px-4 py-2 rounded-lg transition-colors flex items-center gap-2 cursor-pointer ${
                          menteeAttendance[mentee.id] === 'present'
                            ? 'bg-green-600 text-white hover:bg-green-700'
                            : 'bg-red-600 text-white hover:bg-red-700'
                        }`}
                      >
                        {menteeAttendance[mentee.id] === 'present' ? (
                          <>
                            <CheckCircle className="w-4 h-4" />
                            Present
                          </>
                        ) : (
                          <>
                            <AlertCircle className="w-4 h-4" />
                            Absent
                          </>
                        )}
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowCheckInModal(false);
                  setSelectedSessionId('');
                  setSessionTopic('');
                  setSessionDescription('');
                  setMenteeAttendance({});
                }}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={submitAttendance}
                disabled={!selectedSessionId || submitting}
                className={`flex-1 px-4 py-2 rounded-lg transition-colors cursor-pointer ${
                  selectedSessionId && !submitting
                    ? 'bg-purple-600 text-white hover:bg-purple-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`}
              >
                {submitting ? 'Submitting...' : 'Submit Attendance'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Mentee Details Modal */}
      {showMenteeDetailsModal && selectedMentee && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => {
            setShowMenteeDetailsModal(false);
            setSelectedMenteeId(null);
          }}
        >
          <div 
            className="bg-white rounded-xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-gray-900 mb-1">Attendance Details: {selectedMentee.name}</h3>
                <p className="text-gray-600">{selectedMentee.studentId} | {selectedMentee.subject}</p>
              </div>
              <button
                onClick={() => {
                  setShowMenteeDetailsModal(false);
                  setSelectedMenteeId(null);
                }}
                className="text-gray-500 hover:text-gray-700 cursor-pointer"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            {/* Summary Stats */}
            <div className="grid grid-cols-3 gap-4 mb-6">
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-gray-600 mb-1">Attendance Rate</p>
                <p className={`text-gray-900 cursor-pointer ${
                  selectedMentee.attendanceRate >= 80 ? 'text-green-700' :
                  selectedMentee.attendanceRate >= 60 ? 'text-amber-700' :
                  'text-red-700'
                }`}>
                  {selectedMentee.attendanceRate}%
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-gray-600 mb-1">Sessions Attended</p>
                <p className="text-gray-900">
                  {menteeRecords.filter(r => r.status === 'present').length} / {menteeRecords.length}
                </p>
              </div>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p className="text-gray-600 mb-1">Student Status</p>
                <span className="px-2 py-1 bg-gray-100 text-gray-800 rounded">
                  {selectedMentee.isRepeater ? 'Repeater' : 'Regular'}
                </span>
              </div>
            </div>

            {/* Attendance Table */}
            <div>
              <h4 className="text-gray-900 mb-4">Session History</h4>
              {menteeRecords.length === 0 ? (
                <div className="text-center py-8 bg-gray-50 rounded-lg">
                  <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-gray-600">No attendance records yet</p>
                </div>
              ) : (
                <div className="border border-gray-200 rounded-lg overflow-hidden">
                  <table className="w-full">
                    <thead className="bg-gray-50 border-b border-gray-200">
                      <tr>
                        <th className="px-6 py-3 text-left text-gray-700">Date</th>
                        <th className="px-6 py-3 text-left text-gray-700">Topic</th>
                        <th className="px-6 py-3 text-left text-gray-700">Status</th>
                        <th className="px-6 py-3 text-left text-gray-700">Mentor Check-in</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {menteeRecords.map(record => (
                        <tr key={record.id} className="hover:bg-gray-50 transition-colors">
                          <td className="px-6 py-4">
                            <div className="flex items-center gap-2 text-gray-700">
                              <Calendar className="w-4 h-4" />
                              <span>{record.date}</span>
                            </div>
                          </td>
                          <td className="px-6 py-4">
                            <p className="text-gray-900">{record.topic}</p>
                          </td>
                          <td className="px-6 py-4">
                            {record.status === 'present' ? (
                              <span className="flex items-center gap-2 px-3 py-1 bg-green-100 text-green-800 rounded-full w-fit">
                                <CheckCircle className="w-4 h-4" />
                                Present
                              </span>
                            ) : (
                              <span className="flex items-center gap-2 px-3 py-1 bg-red-100 text-red-800 rounded-full w-fit">
                                <AlertCircle className="w-4 h-4" />
                                Absent
                              </span>
                            )}
                          </td>
                          <td className="px-6 py-4">
                            {record.mentorCheckedIn ? (
                              <span className="flex items-center gap-2 text-green-700">
                                <CheckCircle className="w-4 h-4" />
                                Confirmed
                              </span>
                            ) : (
                              <span className="flex items-center gap-2 text-gray-500">
                                <Clock className="w-4 h-4" />
                                Pending
                              </span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

            <div className="mt-6 flex justify-end">
              <button
                onClick={() => {
                  setShowMenteeDetailsModal(false);
                  setSelectedMenteeId(null);
                }}
                className="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
