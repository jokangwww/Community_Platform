import { useState, useEffect } from 'react';
import { Calendar, Clock, Users, Vote, CheckCircle, AlertCircle, Loader2 } from 'lucide-react';

interface TimeSlot {
  id: string;
  day: string;
  startTime: string;
  endTime: string;
  votes: number;
  status: 'pending' | 'voting';
}

interface ScheduledMeeting {
  day: string;
  time: string;
  totalVotes: number;
}

interface ScheduleData {
  hasMatch: boolean;
  matchId?: string;
  timeSlots: TimeSlot[];
  schedule: ScheduledMeeting | null;
  hasVoted: boolean;
  isScheduled: boolean;
  slotsPublished: boolean;
}

const DAYS_OF_WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const TIME_OPTIONS = [
  '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', 
  '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'
];

interface SchedulingPanelProps {
  userRole: 'mentor' | 'mentee';
  studentId: string;
  initialData?: ScheduleData | null;
}

export function SchedulingPanel({ userRole, studentId, initialData }: SchedulingPanelProps) {
  const [loading, setLoading] = useState(!initialData);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  
  const [timeSlots, setTimeSlots] = useState<TimeSlot[]>([]);
  const [newSlot, setNewSlot] = useState({
    day: '',
    startTime: '',
    endTime: ''
  });
  const [selectedSlotIds, setSelectedSlotIds] = useState<string[]>([]);
  const [hasVoted, setHasVoted] = useState(false);
  const [isScheduled, setIsScheduled] = useState(false);
  const [scheduledMeeting, setScheduledMeeting] = useState<ScheduledMeeting | null>(null);
  const [slotsPublished, setSlotsPublished] = useState(false);
  const [hasMatch, setHasMatch] = useState(false);

  // Fetch schedule data from API
  const fetchScheduleData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`/api/buddy/user/schedule?student_id=${encodeURIComponent(studentId)}`);
      const result = await response.json();
      
      if (result.success) {
        const data: ScheduleData = result.data;
        setHasMatch(data.hasMatch);
        setTimeSlots(data.timeSlots);
        setHasVoted(data.hasVoted);
        setIsScheduled(data.isScheduled);
        setScheduledMeeting(data.schedule);
        setSlotsPublished(data.slotsPublished);
      } else {
        setError(result.message || 'Failed to load schedule data');
      }
    } catch (err) {
      setError('Failed to connect to server');
      console.error('Error fetching schedule:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Always fetch fresh schedule data when component mounts or studentId changes
    if (studentId) {
      fetchScheduleData();
    }
  }, [studentId]);

  const handleAddSlot = async () => {
    if (!newSlot.day || !newSlot.startTime || !newSlot.endTime) {
      alert('Please fill in all fields');
      return;
    }

    if (newSlot.startTime >= newSlot.endTime) {
      alert('End time must be after start time');
      return;
    }

    // Helper function to convert 24h time to 12h format for comparison
    const formatTime12h = (time24: string): string => {
      const [hours, minutes] = time24.split(':').map(Number);
      const period = hours >= 12 ? 'PM' : 'AM';
      const hours12 = hours % 12 || 12;
      return `${hours12}:${minutes.toString().padStart(2, '0')} ${period}`;
    };

    // Check for duplicate time slot (compare with formatted times from API)
    const newStartFormatted = formatTime12h(newSlot.startTime);
    const newEndFormatted = formatTime12h(newSlot.endTime);
    const isDuplicate = timeSlots.some(
      slot => slot.day === newSlot.day && 
              slot.startTime === newStartFormatted && 
              slot.endTime === newEndFormatted
    );
    if (isDuplicate) {
      alert('This time slot already exists. Please choose a different day or time.');
      return;
    }

    try {
      setSubmitting(true);
      
      const response = await fetch('/api/buddy/user/schedule/slots', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          student_id: studentId,
          day: newSlot.day,
          start_time: newSlot.startTime,
          end_time: newSlot.endTime,
        }),
      });

      const result = await response.json();
      
      if (result.success) {
        setTimeSlots([...timeSlots, result.data]);
        setNewSlot({ day: '', startTime: '', endTime: '' });
      } else {
        alert(result.message || 'Failed to add time slot');
      }
    } catch (err) {
      alert('Failed to add time slot');
      console.error('Error adding slot:', err);
    } finally {
      setSubmitting(false);
    }
  };

  const handleRemoveSlot = async (slotId: string) => {
    try {
      setSubmitting(true);
      
      const response = await fetch(`/api/buddy/user/schedule/slots/${slotId}?student_id=${encodeURIComponent(studentId)}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const result = await response.json();
      
      if (result.success) {
        setTimeSlots(timeSlots.filter(slot => slot.id !== slotId));
      } else {
        alert(result.message || 'Failed to remove time slot');
      }
    } catch (err) {
      alert('Failed to remove time slot');
      console.error('Error removing slot:', err);
    } finally {
      setSubmitting(false);
    }
  };

  const handlePublishSlots = async () => {
    if (timeSlots.length === 0) {
      alert('Please add at least one time slot before publishing');
      return;
    }

    try {
      setSubmitting(true);
      
      const response = await fetch('/api/buddy/user/schedule/publish', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ student_id: studentId }),
      });

      const result = await response.json();
      
      if (result.success) {
        setSlotsPublished(true);
        setTimeSlots(prev => prev.map(slot => ({ ...slot, status: 'voting' as const })));
        alert('Time slots published successfully! Mentees will now be able to vote.');
      } else {
        alert(result.message || 'Failed to publish time slots');
      }
    } catch (err) {
      alert('Failed to publish time slots');
      console.error('Error publishing slots:', err);
    } finally {
      setSubmitting(false);
    }
  };

  const handleVoteSubmit = async () => {
    if (selectedSlotIds.length === 0) {
      alert('Please select at least one time slot to vote');
      return;
    }

    try {
      setSubmitting(true);
      
      const response = await fetch('/api/buddy/user/schedule/vote', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          student_id: studentId,
          slot_ids: selectedSlotIds.map(id => parseInt(id)),
        }),
      });

      const result = await response.json();
      
      if (result.success) {
        // Update local vote counts for all selected slots
        setTimeSlots(prev => prev.map(slot =>
          selectedSlotIds.includes(slot.id)
            ? { ...slot, votes: slot.votes + 1 }
            : slot
        ));
        setHasVoted(true);
      } else {
        alert(result.message || 'Failed to submit vote');
      }
    } catch (err) {
      alert('Failed to submit vote');
      console.error('Error submitting vote:', err);
    } finally {
      setSubmitting(false);
    }
  };

  const handleResetVotes = async () => {
    if (!confirm('This will clear all current votes and allow mentees to vote again. Continue?')) return;

    try {
      setSubmitting(true);

      const response = await fetch('/api/buddy/user/schedule/reset-votes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ student_id: studentId }),
      });

      const result = await response.json();

      if (result.success) {
        setTimeSlots(prev => prev.map(slot => ({ ...slot, votes: 0 })));
        alert('Votes have been reset. Mentees can now vote again.');
      } else {
        alert(result.message || 'Failed to reset votes');
      }
    } catch (err) {
      alert('Failed to reset votes');
      console.error('Error resetting votes:', err);
    } finally {
      setSubmitting(false);
    }
  };

  const handleConfirmSchedule = async () => {
    // Check if all time slots have 0 votes
    const hasAnyVotes = timeSlots.some(slot => slot.votes > 0);
    if (!hasAnyVotes) {
      alert('Cannot confirm schedule: No mentee has voted yet. Please wait for at least one vote.');
      return;
    }

    try {
      setSubmitting(true);
      
      const response = await fetch('/api/buddy/user/schedule/confirm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ student_id: studentId }),
      });

      const result = await response.json();
      
      if (result.success) {
        setIsScheduled(true);
        setScheduledMeeting(result.data);
      } else {
        alert(result.message || 'Failed to confirm schedule');
      }
    } catch (err) {
      alert('Failed to confirm schedule');
      console.error('Error confirming schedule:', err);
    } finally {
      setSubmitting(false);
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading schedule...</span>
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
          onClick={fetchScheduleData}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 cursor-pointer"
        >
          Retry
        </button>
      </div>
    );
  }

  // No match state
  if (!hasMatch) {
    return (
      <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
        <div className="flex items-center gap-2 text-yellow-700">
          <AlertCircle className="w-5 h-5" />
          <span>You need to be matched with a {userRole === 'mentor' ? 'mentee' : 'mentor'} before scheduling.</span>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3 mb-6">
        <Calendar className="w-6 h-6 text-blue-600" />
        <h2 className="text-xl text-gray-900">Meeting Schedule</h2>
      </div>

      {userRole === 'mentor' ? (
        // Mentor View
        !isScheduled ? (
          <div className="space-y-6">
            {/* Add Time Slot Form */}
            {!slotsPublished && (
              <div className="bg-white rounded-xl border border-gray-200 p-6">
                <h3 className="text-gray-900 mb-4">Add Available Time Slots</h3>
                
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                  <div>
                    <label className="block text-gray-600 mb-1">Day</label>
                    <select
                      value={newSlot.day}
                      onChange={(e) => setNewSlot({ ...newSlot, day: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      disabled={submitting}
                    >
                      <option value="">Select Day</option>
                      {DAYS_OF_WEEK.map(day => (
                        <option key={day} value={day}>{day}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-gray-600 mb-1">Start Time</label>
                    <select
                      value={newSlot.startTime}
                      onChange={(e) => setNewSlot({ ...newSlot, startTime: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      disabled={submitting}
                    >
                      <option value="">Select Time</option>
                      {TIME_OPTIONS.map(time => (
                        <option key={time} value={time}>{time}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-gray-600 mb-1">End Time</label>
                    <select
                      value={newSlot.endTime}
                      onChange={(e) => setNewSlot({ ...newSlot, endTime: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      disabled={submitting}
                    >
                      <option value="">Select Time</option>
                      {TIME_OPTIONS.map(time => (
                        <option key={time} value={time}>{time}</option>
                      ))}
                    </select>
                  </div>

                  <div className="flex items-end">
                    <button
                      onClick={handleAddSlot}
                      disabled={submitting || !newSlot.day || !newSlot.startTime || !newSlot.endTime}
                      className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors cursor-pointer"
                    >
                      {submitting ? 'Adding...' : 'Add Slot'}
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Current Time Slots */}
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              <h3 className="text-gray-900 mb-4">
                {slotsPublished ? 'Published Time Slots' : 'Your Time Slots'}
              </h3>
              
              {timeSlots.length === 0 ? (
                <div className="text-gray-500 text-center py-8">
                  <Clock className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                  <p>No time slots added yet</p>
                  <p className="text-sm">Add your available times above</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {timeSlots.map(slot => (
                    <div 
                      key={slot.id}
                      className="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg"
                    >
                      <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                          <Calendar className="w-4 h-4 text-gray-600" />
                          <span className="text-gray-900">{slot.day}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Clock className="w-4 h-4 text-gray-600" />
                          <span className="text-gray-900">
                            {slot.startTime} - {slot.endTime}
                          </span>
                        </div>
                        {slotsPublished && (
                          <div className="flex items-center gap-1 text-blue-600">
                            <Vote className="w-4 h-4" />
                            <span>{slot.votes} votes</span>
                          </div>
                        )}
                      </div>
                      {!slotsPublished && (
                        <button
                          onClick={() => handleRemoveSlot(slot.id)}
                          disabled={submitting}
                          className="text-red-600 hover:text-red-700 disabled:opacity-50 cursor-pointer"
                        >
                          Remove
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              )}

              {/* Action Buttons */}
              {!slotsPublished && timeSlots.length > 0 && (
                <button
                  onClick={handlePublishSlots}
                  disabled={submitting}
                  className="mt-4 w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors cursor-pointer"
                >
                  {submitting ? 'Publishing...' : 'Publish Slots for Mentee Voting'}
                </button>
              )}

              {slotsPublished && (() => {
                const maxVotes = Math.max(...timeSlots.map(s => s.votes), 0);
                const hasTie = maxVotes > 0 && timeSlots.filter(s => s.votes === maxVotes).length > 1;
                return (
                  <div className="mt-4 space-y-3">
                    {hasTie ? (
                      // Tie: show suggestion box with ONLY a blue Reset button — Confirm is hidden
                      <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div className="flex items-start gap-2">
                          <AlertCircle className="w-5 h-5 text-gray-400 mt-0.5 shrink-0" />
                          <div>
                            <p className="text-gray-600 font-medium">System Suggestion</p>
                            <p className="text-gray-500 mt-1">
                              Multiple time slots have equal votes. We suggest letting mentees vote again or discussing among yourselves to decide.
                            </p>
                          </div>
                        </div>
                        <button
                          onClick={handleResetVotes}
                          disabled={submitting}
                          className="mt-3 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
                        >
                          {submitting ? 'Resetting...' : 'Reset Votes & Reopen Voting'}
                        </button>
                      </div>
                    ) : (
                      // No tie: show Confirm button
                      <button
                        onClick={handleConfirmSchedule}
                        disabled={submitting || !timeSlots.some(slot => slot.votes > 0)}
                        className="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
                      >
                        {submitting ? 'Confirming...' : timeSlots.some(slot => slot.votes > 0) ? 'Confirm Schedule (Select Most Voted)' : 'Waiting for Votes...'}
                      </button>
                    )}
                  </div>
                );
              })()}
            </div>

            {/* Voting Status */}
            {slotsPublished && (
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div className="flex items-center gap-2">
                  <Users className="w-5 h-5 text-blue-600" />
                  <h4 className="text-gray-900">Voting Status</h4>
                </div>
                <p className="text-blue-800 mt-2">
                  Waiting for mentee votes. You can confirm the schedule once voting is complete.
                </p>
              </div>
            )}
          </div>
        ) : (
          // Confirmed Schedule (Mentor)
          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center gap-2 mb-4">
              <CheckCircle className="w-6 h-6 text-green-600" />
              <h3 className="text-gray-900">Weekly Meeting Schedule Confirmed</h3>
            </div>

            <div className="bg-green-50 border border-green-200 rounded-lg p-6">
              <div className="text-center mb-4">
                <p className="text-gray-600 mb-2">Your weekly study group meets on</p>
                <h2 className="text-2xl font-semibold text-gray-900 mb-1">{scheduledMeeting?.day}s</h2>
                <p className="text-lg text-gray-900">{scheduledMeeting?.time}</p>
              </div>

              <div className="flex items-center justify-center gap-2 text-gray-600">
                <Vote className="w-4 h-4" />
                <span>Selected by majority vote ({scheduledMeeting?.totalVotes} votes)</span>
              </div>
            </div>

            <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-blue-800">
                This schedule has been confirmed. Sessions will be created based on this schedule.
              </p>
            </div>
          </div>
        )
      ) : (
        // Mentee View
        !isScheduled ? (
          !slotsPublished ? (
            // Waiting for mentor to publish
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              <div className="flex items-center gap-2 mb-4">
                <Clock className="w-6 h-6 text-yellow-600" />
                <h3 className="text-gray-900">Waiting for Time Slots</h3>
              </div>
              <p className="text-gray-600">
                Your mentor hasn't published available time slots yet. Please check back later.
              </p>
            </div>
          ) : !hasVoted ? (
            // Voting Interface – multi-select checkboxes
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              <div className="flex items-center gap-2 mb-4">
                <Vote className="w-6 h-6 text-blue-600" />
                <h3 className="text-gray-900">Vote for Your Preferred Time</h3>
              </div>

              <p className="text-gray-600 mb-1">
                Select <strong>all time slots that work for you</strong>. Voting for multiple options helps avoid a tie.
              </p>
              <p className="text-gray-500 text-sm mb-6">
                The time slot with the most votes will be chosen as the weekly meeting schedule.
              </p>

              <div className="space-y-3 mb-4">
                {timeSlots.map(slot => (
                  <label
                    key={slot.id}
                    className={`block p-4 border rounded-lg cursor-pointer transition-colors ${
                      selectedSlotIds.includes(slot.id)
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-blue-300'
                    }`}
                  >
                    <div className="flex items-center gap-4">
                      <input
                        type="checkbox"
                        checked={selectedSlotIds.includes(slot.id)}
                        onChange={() => {
                          setSelectedSlotIds(prev =>
                            prev.includes(slot.id)
                              ? prev.filter(id => id !== slot.id)
                              : [...prev, slot.id]
                          );
                        }}
                        className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        disabled={submitting}
                      />
                      <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                          <Calendar className="w-4 h-4 text-gray-600" />
                          <span className="text-gray-900">{slot.day}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Clock className="w-4 h-4 text-gray-600" />
                          <span className="text-gray-900">
                            {slot.startTime} - {slot.endTime}
                          </span>
                        </div>
                      </div>
                    </div>
                  </label>
                ))}
              </div>

              {selectedSlotIds.length > 0 && (
                <p className="text-blue-600 text-sm mb-3">
                  {selectedSlotIds.length} slot{selectedSlotIds.length > 1 ? 's' : ''} selected
                </p>
              )}

              <button
                onClick={handleVoteSubmit}
                disabled={selectedSlotIds.length === 0 || submitting}
                className={`w-full px-6 py-3 rounded-lg transition-colors cursor-pointer ${
                  selectedSlotIds.length > 0 && !submitting
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`}
              >
                {submitting ? 'Submitting...' : `Submit Vote${selectedSlotIds.length > 1 ? 's' : ''}`}
              </button>
            </div>
          ) : (
            // Vote Submitted
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              <div className="flex items-center gap-2 mb-4">
                <CheckCircle className="w-6 h-6 text-green-600" />
                <h3 className="text-gray-900">Vote Submitted Successfully</h3>
              </div>

              <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <p className="text-green-800">
                  Thank you for voting! Waiting for other mentees to submit their votes.
                </p>
              </div>

              <div className="space-y-2">
                <h4 className="text-gray-900">Current Voting Results</h4>
                {timeSlots.map(slot => (
                  <div key={slot.id} className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                    <div className="mb-2">
                      <span className="text-gray-900">
                        {slot.day}, {slot.startTime} - {slot.endTime}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      <div className="flex-1 bg-gray-200 rounded-full h-2">
                        <div
                          className="bg-blue-600 h-2 rounded-full transition-all"
                          style={{ width: `${Math.min((slot.votes / Math.max(...timeSlots.map(s => s.votes), 1)) * 100, 100)}%` }}
                        />
                      </div>
                      <span className="text-sm text-gray-600">{slot.votes} votes</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )
        ) : (
          // Confirmed Schedule (Mentee)
          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center gap-2 mb-4">
              <CheckCircle className="w-6 h-6 text-green-600" />
              <h3 className="text-gray-900">Weekly Meeting Schedule Confirmed</h3>
            </div>

            <div className="bg-green-50 border border-green-200 rounded-lg p-6">
              <div className="text-center mb-4">
                <p className="text-gray-600 mb-2">Your weekly study group meets on</p>
                <h2 className="text-2xl font-semibold text-gray-900 mb-1">{scheduledMeeting?.day}s</h2>
                <p className="text-lg text-gray-900">{scheduledMeeting?.time}</p>
              </div>

              <div className="flex items-center justify-center gap-2 text-gray-600">
                <Vote className="w-4 h-4" />
                <span>Selected by majority vote ({scheduledMeeting?.totalVotes} votes)</span>
              </div>
            </div>

            {/* <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-blue-800">
                This schedule has been added to your calendar. You will receive reminders before each session.
              </p>
            </div> */}
          </div>
        )
      )}
    </div>
  );
}
