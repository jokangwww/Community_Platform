import { useState, useEffect } from 'react';
import { CheckCircle, Clock, Calendar, Users, Bell, AlertCircle, FileText, Vote, BookOpen, Loader2, MessageSquare, Award, Archive } from 'lucide-react';
import { SchedulingPanel } from './SchedulingPanel';
import { AttendancePanel } from './AttendancePanel';
import { VirtualClassroom } from './VirtualClassroom';
import { FeedbackRating } from './FeedbackRating';
import { SemesterSelector } from './SemesterSelector';

interface User {
  id: number;
  name: string;
  studentId: string;
  role: 'mentor' | 'mentee';
  registrationStatus: string;
  rating: number;
  faculty: string;
  course: string;
  subject: {
    id: number;
    name: string;
    type: string;
  } | null;
}

interface Pairing {
  id: string;
  partnerName: string;
  partnerStudentId: string;
  subject: string;
  matchedDate: string;
  progressPercentage: number;
  totalSessions: number;
  completedSessions: number;
}

interface Meeting {
  id: string;
  date: string;
  time: string;
  topic: string;
  status: 'pending' | 'completed' | 'missed';
  attendanceSubmitted: boolean;
}

interface Notification {
  id: string;
  message: string;
  timestamp: string;
  type: 'info' | 'warning' | 'success';
  read: boolean;
}

interface Activity {
  id: string;
  type: 'evaluation' | 'material' | 'quiz' | 'assignment' | 'vote';
  title: string;
  description: string;
  timestamp: string;
  icon: 'MessageSquare' | 'FileText' | 'Award' | 'CheckCircle' | 'Vote';
  color: string;
}

interface WeeklySchedule {
  day: string;
  time: string;
  startTime: string;
  endTime: string;
}

interface MenteeDashboardProps {
  studentId?: string;
  selectedSemesterId?: number | null;
  onSemesterChange?: (id: number | null, role?: string) => void;
}

export function MenteeDashboard({ studentId: propStudentId, selectedSemesterId = null, onSemesterChange }: MenteeDashboardProps) {
  const [studentId, setStudentId] = useState(propStudentId || '');
  const [isReadonly, setIsReadonly] = useState(false);
  const [user, setUser] = useState<User | null>(null);

  // Format date to DD-MMM-YYYY
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
  };
  const [pairing, setPairing] = useState<Pairing | null>(null);
  const [meetings, setMeetings] = useState<Meeting[]>([]);
  const [weeklySchedule, setWeeklySchedule] = useState<WeeklySchedule | null>(null);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedMeeting, setSelectedMeeting] = useState<Meeting | null>(null);
  const [activeTab, setActiveTab] = useState<'overview' | 'schedule' | 'attendance' | 'classroom' | 'evaluation'>('overview');
  const [evaluationEnabled, setEvaluationEnabled] = useState(false);
  const [pendingQuizCount, setPendingQuizCount] = useState(0);
  const [pendingAssignmentCount, setPendingAssignmentCount] = useState(0);
  const [matchId, setMatchId] = useState<string | null>(null);
  const [activities, setActivities] = useState<Activity[]>([]);
  const [attendanceRate, setAttendanceRate] = useState<number>(0);

  useEffect(() => {
    if (studentId) {
      fetchDashboardData();
    }
  }, [studentId, selectedSemesterId]);

  const fetchDashboardData = async () => {
    try {
      setIsLoading(true);
      setError(null);

      const semParam = selectedSemesterId ? `&semester_id=${selectedSemesterId}` : '';
      const [response, settingsResponse, scheduleResponse] = await Promise.all([
        fetch(`/api/buddy/user/dashboard?student_id=${encodeURIComponent(studentId)}${semParam}`),
        fetch('/api/buddy/admin/settings'),
        fetch(`/api/buddy/user/schedule?student_id=${encodeURIComponent(studentId)}${semParam}`),
      ]);
      const result = await response.json();
      const settingsResult = await settingsResponse.json();
      const scheduleResult = await scheduleResponse.json();

      if (result.success) {
        const data = result.data;
        setIsReadonly(data.is_readonly ?? false);
        
        setUser(data.user);
        setPairing(data.pairing);
        setMeetings(data.meetings || []);
        setWeeklySchedule(data.weeklySchedule || null);
        setAttendanceRate(data.stats?.attendanceRate ?? 0);
        
        // Store matchId if pairing exists
        if (data.pairing?.id) {
          setMatchId(data.pairing.id);
          // Fetch classroom data to get pending quizzes/assignments
          const slotData = scheduleResult?.success ? scheduleResult.data : null;
          fetchClassroomStats(
            data.pairing.id,
            slotData?.slotsPublished === true,
            slotData?.hasVoted === true,
            slotData?.isScheduled === true,
          );
        }
        
        // Set settings if successful and handle evaluation status
        if (settingsResult.success) {
          const evalEnabled = settingsResult.data.evaluation_enabled ?? false;
          setEvaluationEnabled(evalEnabled);
          
          // Add evaluation activity notification if enabled and user has a pairing
          if (evalEnabled && data.pairing?.id) {
            setActivities(prev => [{
              id: 'evaluation-open',
              type: 'evaluation',
              title: 'Evaluation Now Open',
              description: 'Please take a moment to evaluate your buddy experience',
              timestamp: new Date().toISOString(),
              icon: 'MessageSquare',
              color: 'amber'
            }, ...prev]);
          }
        }
        
        // Generate notifications based on data
        const generatedNotifications: Notification[] = [];
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
        
        const pendingMeetings = (data.meetings || []).filter((m: Meeting) => m.status === 'pending');
        if (pendingMeetings.length > 0) {
          generatedNotifications.push({
            id: 'notif-1',
            message: `You have ${pendingMeetings.length} upcoming session(s)`,
            timestamp: new Date().toISOString(),
            type: 'info',
            read: false
          });
        }
        if (data.weeklySchedule) {
          generatedNotifications.push({
            id: 'notif-schedule',
            message: `Weekly meeting: Every ${data.weeklySchedule.day} at ${data.weeklySchedule.time}`,
            timestamp: new Date().toISOString(),
            type: 'success',
            read: false
          });
        }
        if (data.pairing) {
          generatedNotifications.push({
            id: 'notif-2',
            message: `Session progress: ${data.pairing.completedSessions}/${data.pairing.totalSessions} completed`,
            timestamp: new Date().toISOString(),
            type: 'success',
            read: true
          });
        }
        
        // Filter out notifications older than 1 week
        const recentNotifications = generatedNotifications.filter(
          notif => new Date(notif.timestamp) >= oneWeekAgo
        );
        setNotifications(recentNotifications);
        
      } else {
        setError(result.message || 'Failed to load dashboard data');
      }
    } catch (err) {
      setError('Failed to connect to server');
      console.error('Error fetching dashboard:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleAttendanceSubmit = async (meetingId: string) => {
    if (!user) return;
    
    try {
      const response = await fetch(`/api/buddy/user/sessions/${meetingId}/check-in`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Student-ID': user.studentId,
        },
        body: JSON.stringify({ student_id: user.studentId }),
      });

      const result = await response.json();

      if (result.success) {
        alert('Attendance submitted successfully!');
        setSelectedMeeting(null);
        fetchDashboardData(); // Refresh data
      } else {
        alert(result.message || 'Failed to submit attendance');
      }
    } catch (err) {
      alert('Failed to submit attendance');
    }
  };

  const fetchClassroomStats = async (
    matchId: string,
    slotsPublished = false,
    hasVoted = false,
    isScheduled = false,
  ) => {
    try {
      const response = await fetch(`/api/buddy/classroom/${matchId}`, {
        headers: studentId ? { 'X-Student-ID': studentId } : {},
      });
      if (response.ok) {
        const data = await response.json();
        
        // Count pending quizzes (open and not attempted)
        const pendingQuizzes = (data.quizzes || []).filter(
          (q: any) => q.status === 'open' && !q.hasAttempted
        ).length;
        setPendingQuizCount(pendingQuizzes);
        
        // Count pending assignments (not submitted)
        const pendingAssignments = (data.assignments || []).filter(
          (a: any) => !a.hasSubmitted
        ).length;
        setPendingAssignmentCount(pendingAssignments);
        
        // Generate activities from classroom data
        const newActivities: Activity[] = [];
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);

        // Add recent materials (only within 1 week)
        (data.materials || [])
          .filter((m: any) => new Date(m.uploadedDate) >= oneWeekAgo)
          .slice(0, 3)
          .forEach((material: any) => {
            newActivities.push({
              id: `material-${material.id}`,
              type: 'material',
              title: 'New Study Material',
              description: material.name,
              timestamp: material.uploadedDate,
              icon: 'FileText',
              color: 'blue'
            });
          });

        // Add quizzes assigned within 1 week (regardless of attempt status)
        (data.quizzes || [])
          .filter((q: any) => new Date(q.createdDate) >= oneWeekAgo)
          .slice(0, 3)
          .forEach((quiz: any) => {
            newActivities.push({
              id: `quiz-${quiz.id}`,
              type: 'quiz',
              title: 'New Quiz Assigned',
              description: quiz.title,
              timestamp: quiz.createdDate,
              icon: 'Award',
              color: 'purple'
            });
          });

        // Add recent assignments (only those not submitted and within 1 week)
        (data.assignments || [])
          .filter((a: any) => !a.hasSubmitted)
          .filter((a: any) => new Date(a.createdDate) >= oneWeekAgo)
          .slice(0, 3)
          .forEach((assignment: any) => {
            newActivities.push({
              id: `assignment-${assignment.id}`,
              type: 'assignment',
              title: 'New Assignment',
              description: assignment.title,
              timestamp: assignment.createdDate,
              icon: 'CheckCircle',
              color: 'green'
            });
          });

        // Add graded assignment notifications (only within 1 week of submission)
        (data.assignments || [])
          .filter((a: any) => a.hasSubmitted && a.submission?.marks !== null && a.submission?.marks !== undefined)
          .filter((a: any) => new Date(a.submission?.submittedDate ?? a.createdDate) >= oneWeekAgo)
          .slice(0, 3)
          .forEach((assignment: any) => {
            newActivities.push({
              id: `graded-${assignment.id}`,
              type: 'assignment',
              title: 'Assignment Graded',
              description: `${assignment.title}: ${assignment.submission.marks}/${assignment.totalMarks} marks`,
              timestamp: assignment.submission?.submittedDate ?? assignment.createdDate,
              icon: 'Award',
              color: 'blue'
            });
          });

        // Sort by timestamp (most recent first)
        newActivities.sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());

        // Prepend voting notification if slots are published and mentee hasn't voted yet
        if (slotsPublished && !hasVoted && !isScheduled) {
          newActivities.unshift({
            id: 'voting-open',
            type: 'vote',
            title: 'Time Slots Available for Voting',
            description: 'Your mentor has published meeting time slots. Go to Schedule Meeting to vote.',
            timestamp: new Date().toISOString(),
            icon: 'Vote',
            color: 'purple',
          });
        }

        setActivities(newActivities.slice(0, 6));
      }
    } catch (err) {
      console.error('Failed to fetch classroom stats:', err);
    }
  };

  const unreadNotifications = notifications.filter(n => !n.read).length;

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading dashboard...</span>
      </div>
    );
  }

  if (error || !user) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <AlertCircle className="w-8 h-8 text-red-600 mx-auto mb-2" />
        <p className="text-red-800">{error || 'User not found'}</p>
        <div className="flex gap-3 justify-center mt-4">
          <button
            onClick={fetchDashboardData}
            className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 cursor-pointer"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Welcome Header */}
      {activeTab === 'overview' && (
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="mb-2">Welcome back, {user.name}!</h2>
              <p className="text-blue-100">
                Student ID: {user.studentId} | Role: {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
              </p>
              {isReadonly && (
                <span className="inline-flex items-center gap-1 mt-2 px-2 py-0.5 bg-white/20 text-white rounded text-xs">
                  <Archive className="w-3 h-3" />
                  Read-only — archived view
                </span>
              )}
            </div>
            <div className="text-right flex flex-col items-end gap-3">
              {onSemesterChange && (
                <div className="bg-white/10 rounded-lg p-1">
                  <SemesterSelector
                    selectedSemesterId={selectedSemesterId ?? null}
                    onSelect={onSemesterChange}
                  />
                </div>
              )}
              <div>
                <p className="text-blue-100">Your Rating</p>
                <div className="flex items-center gap-2">
                  <span className="text-white">{user.rating.toFixed(1)}</span>
                  <div className="flex">
                    {[1, 2, 3, 4, 5].map(star => (
                      <span key={star} className={star <= user.rating ? 'text-yellow-300' : 'text-blue-300'}>
                        ★
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Quick Stats */}
      {activeTab === 'overview' && (
        <div className="grid md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-600">Attendance Rate</p>
              <CheckCircle className="w-5 h-5 text-green-600" />
            </div>
            <p className="text-gray-900">{attendanceRate}%</p>
            <div className="mt-2 bg-gray-200 rounded-full h-2">
              <div 
                className="bg-green-600 h-2 rounded-full" 
                style={{ width: `${attendanceRate}%` }}
              />
            </div>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-600">Sessions</p>
              <Calendar className="w-5 h-5 text-blue-600" />
            </div>
            <p className="text-gray-900">{pairing?.completedSessions ?? 0}/{pairing?.totalSessions ?? 0}</p>
            <p className="text-gray-500">Completed</p>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-600">Pending Quizzes</p>
              <Award className="w-5 h-5 text-purple-600" />
            </div>
            <p className="text-gray-900">{pendingQuizCount}</p>
            <p className="text-gray-500">To Complete</p>
          </div>

          <div className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-600">Pending Assignments</p>
              <FileText className="w-5 h-5 text-amber-600" />
            </div>
            <p className="text-gray-900">{pendingAssignmentCount}</p>
            <p className="text-gray-500">To Submit</p>
          </div>
        </div>
      )}

      {/* Tab Navigation */}
      <div className="bg-white rounded-xl border border-gray-200 p-2">
        <div className="flex gap-2">
          <button
            onClick={() => setActiveTab('overview')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'overview'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <Users className="w-4 h-4" />
            Overview
          </button>
          <button
            onClick={() => setActiveTab('schedule')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'schedule'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <Vote className="w-4 h-4" />
            Schedule Meeting
          </button>
          <button
            onClick={() => setActiveTab('attendance')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'attendance'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <CheckCircle className="w-4 h-4" />
            Attendance
          </button>
          <button
            onClick={() => setActiveTab('classroom')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'classroom'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <BookOpen className="w-4 h-4" />
            Virtual Classroom
          </button>
          {evaluationEnabled && (
            <button
              onClick={() => setActiveTab('evaluation')}
              className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
                activeTab === 'evaluation'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              <MessageSquare className="w-4 h-4" />
              Feedback
            </button>
          )}
        </div>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <div className="grid lg:grid-cols-3 gap-6">
          {/* Left Column - Pairing & Schedule */}
          <div className="lg:col-span-2 space-y-6">
            {/* Current Pairing */}
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              <div className="flex items-center gap-2 mb-4">
                <Users className="w-5 h-5 text-blue-600" />
                <h3 className="text-gray-900">Your {user.role === 'mentee' ? 'Mentor' : 'Mentee'}</h3>
              </div>

              {pairing ? (
                <div className="bg-blue-50 rounded-lg p-4 mb-4">
                  <div className="flex items-center justify-between mb-3">
                    <div>
                      <h4 className="text-gray-900">{pairing.partnerName}</h4>
                      <p className="text-gray-600">{pairing.partnerStudentId}</p>
                    </div>
                    <span className="px-3 py-1 bg-blue-600 text-white rounded-lg">
                      {pairing.subject}
                    </span>
                  </div>
                  <div className="flex items-center gap-4 text-gray-600">
                    <span>Paired since: {pairing.matchedDate}</span>
                    <span>•</span>
                    <span>{pairing.completedSessions} sessions completed</span>
                  </div>
                </div>
              ) : (
                <div className="bg-gray-50 rounded-lg p-4 text-center">
                  <p className="text-gray-600">You haven't been matched with a {user.role === 'mentee' ? 'mentor' : 'mentee'} yet.</p>
                  <p className="text-gray-500 mt-1">Please wait for the admin to process matching.</p>
                </div>
              )}
            </div>

            {/* Weekly Schedule (Confirmed) */}
            {weeklySchedule && (
              <div className="bg-white rounded-xl border border-green-200 p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-2">
                    <Calendar className="w-5 h-5 text-green-600" />
                    <h3 className="text-gray-900">Weekly Meeting Schedule</h3>
                  </div>
                </div>
                <div className="bg-green-50 rounded-lg p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="text-gray-900 font-medium mb-2">
                        Every {weeklySchedule.day}
                      </h4>
                      <div className="flex items-center gap-2 text-gray-600">
                        <Clock className="w-4 h-4 text-green-600" />
                        <span>{weeklySchedule.time}</span>
                      </div>
                      {pairing && (
                        <div className="flex items-center gap-2 mt-2 text-gray-600">
                          <Users className="w-4 h-4 text-gray-500" />
                          <span>With {pairing.partnerName}</span>
                        </div>
                      )}
                    </div>
                    <span className="px-3 py-1 bg-green-600 text-white rounded-full text-sm">
                      Weekly
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* Meetings Schedule - commented out
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              ...
            </div>
            */}
          </div>

          {/* Right Column - Activity Feed */}
          <div className="space-y-6">
            <div className="bg-white rounded-xl border border-gray-200 p-6">
              <div className="flex items-center gap-2 mb-4">
                <Bell className="w-5 h-5 text-purple-600" />
                <h3 className="text-gray-900">Activity Feed</h3>
                {activities.length > 0 && (
                  <span className="px-2 py-1 bg-blue-600 text-white rounded-full text-xs">
                    {activities.length}
                  </span>
                )}
              </div>

              <div className="space-y-3">
                {activities.length > 0 ? activities.map(activity => {
                  const iconColorClass = `text-${activity.color}-600`;
                  const bgColorClass = `bg-${activity.color}-50`;
                  const borderColorClass = `border-${activity.color}-200`;
                  
                  return (
                    <div
                      key={activity.id}
                      className={`p-4 rounded-lg border transition-all hover:shadow-sm ${borderColorClass} ${bgColorClass}`}
                    >
                      <div className="flex items-start gap-3">
                        <div className={`p-2 rounded-lg ${activity.color === 'blue' ? 'bg-blue-100' : activity.color === 'purple' ? 'bg-purple-100' : activity.color === 'green' ? 'bg-green-100' : 'bg-amber-100'}`}>
                          {activity.icon === 'FileText' && <FileText className={`w-5 h-5 ${activity.color === 'blue' ? 'text-blue-600' : 'text-gray-600'}`} />}
                          {activity.icon === 'Award' && <Award className={`w-5 h-5 ${activity.color === 'purple' ? 'text-purple-600' : 'text-gray-600'}`} />}
                          {activity.icon === 'CheckCircle' && <CheckCircle className={`w-5 h-5 ${activity.color === 'green' ? 'text-green-600' : 'text-gray-600'}`} />}
                          {activity.icon === 'Vote' && <Vote className={`w-5 h-5 ${activity.color === 'purple' ? 'text-purple-600' : 'text-gray-600'}`} />}
                          {activity.icon === 'MessageSquare' && <MessageSquare className={`w-5 h-5 ${activity.color === 'amber' ? 'text-amber-600' : 'text-gray-600'}`} />}
                        </div>
                        <div className="flex-1">
                          <h4 className="text-gray-900 font-medium">{activity.title}</h4>
                          <p className="text-gray-700 mt-1">{activity.description}</p>
                          <p className="text-gray-500 mt-2 text-xs">{formatDate(activity.timestamp)}</p>
                        </div>
                      </div>
                    </div>
                  );
                }) : (
                  <div className="text-center py-8">
                    <Bell className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500">No recent activities</p>
                    <p className="text-gray-400 text-sm mt-1">Check back later for updates</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'schedule' && (
        <SchedulingPanel 
          userRole={user.role} 
          studentId={user.studentId}
          isReadonly={isReadonly}
          semesterId={selectedSemesterId}
        />
      )}

      {activeTab === 'attendance' && (
        <AttendancePanel 
          userRole={user.role}
          userName={user.name}
          studentId={user.studentId}
          isReadonly={isReadonly}
          semesterId={selectedSemesterId}
        />
      )}

      {activeTab === 'classroom' && pairing && (
        <VirtualClassroom 
          userName={user.name}
          matchId={pairing.id}
          studentId={user.studentId}
          onActivityChange={() => fetchClassroomStats(pairing.id)}
          isReadonly={isReadonly}
        />
      )}

      {activeTab === 'classroom' && !pairing && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
          <AlertCircle className="w-8 h-8 text-yellow-600 mx-auto mb-2" />
          <p className="text-yellow-800">You need to be matched with a mentor before you can access the virtual classroom.</p>
        </div>
      )}

      {activeTab === 'evaluation' && evaluationEnabled && pairing && (
        <div className="space-y-6">
          <div>
            <h3 className="text-gray-900 mb-2">End-of-Semester Evaluation</h3>
            <p className="text-gray-600 mb-6">Provide feedback and ratings for your mentor</p>
          </div>
          
          <FeedbackRating
            userRole="mentee"
            pairName={pairing.partnerName}
            pairId={pairing.partnerStudentId}
            studentId={user.studentId}
            hasSubmitted={false}
            isReadonly={isReadonly}
          />
        </div>
      )}

      {activeTab === 'evaluation' && evaluationEnabled && !pairing && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
          <AlertCircle className="w-8 h-8 text-yellow-600 mx-auto mb-2" />
          <p className="text-yellow-800">You need to be matched with a mentor before you can submit feedback.</p>
        </div>
      )}

      {/* Attendance Modal */}
      {selectedMeeting && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-gray-900 mb-4">Verify Attendance</h3>
            
            <div className="space-y-3 mb-6">
              <div>
                <p className="text-gray-600">Meeting Topic</p>
                <p className="text-gray-900">{selectedMeeting.topic}</p>
              </div>
              <div>
                <p className="text-gray-600">Date & Time</p>
                <p className="text-gray-900">{selectedMeeting.date} at {selectedMeeting.time}</p>
              </div>
            </div>

            <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
              <p className="text-amber-800">
                Please confirm that your mentee attended this session and actively participated.
              </p>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => setSelectedMeeting(null)}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={() => handleAttendanceSubmit(selectedMeeting.id)}
                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors cursor-pointer"
              >
                Verify Attendance
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
