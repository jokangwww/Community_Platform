import { useState, useEffect } from 'react';
import { Users, Calendar, BookOpen, MessageSquare, Award, Loader2, AlertCircle, Clock, Bell, FileText, CheckCircle } from 'lucide-react';
import { MentorMentees } from './MentorMentees';
import { MentorSchedule } from './MentorSchedule';
import { MentorClassroom } from './MentorClassroom';
import { MentorEvaluation } from './MentorEvaluation';
import { MentorTestimonial } from './MentorTestimonial';

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

interface Meeting {
  id: string;
  matchId: string;
  menteeId: string;
  menteeName: string;
  subject: string;
  date: string;
  time: string;
  topic: string;
  status: string;
  mentorCheckIn: string | null;
  menteeCheckIn: string | null;
}

interface Notification {
  id: string;
  message: string;
  timestamp: string;
  type: 'info' | 'warning' | 'success';
  read: boolean;
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

interface MentorData {
  id: number;
  name: string;
  studentId: string;
  role: string;
  status: string;
  rating: number;
  faculty: string;
  course: string;
  totalMentees: number;
  subjects: string[];
}

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

interface WeeklySchedule {
  matchId: string;
  menteeId: string;
  menteeName: string;
  subject: string;
  day: string;
  time: string;
  startTime: string;
  endTime: string;
}

interface DashboardData {
  mentor: MentorData;
  mentees: Mentee[];
  meetings: Meeting[];
  upcomingMeetings: Meeting[];
  weeklySchedules: WeeklySchedule[];
  attendanceRecords: AttendanceRecord[];
  stats: {
    totalMentees: number;
    totalSessions: number;
    completedSessions: number;
    attendanceRate: number;
    upcomingMeetings: number;
  };
}

type TabType = 'overview' | 'mentees' | 'schedule' | 'classroom' | 'evaluation' | 'testimonial';

interface MentorDashboardProps {
  studentId?: string;
}

export function MentorDashboard({ studentId }: MentorDashboardProps) {
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [attendanceRecords, setAttendanceRecords] = useState<AttendanceRecord[]>([]);
  const [scheduleData, setScheduleData] = useState<ScheduleData | null>(null);
  const [evaluationEnabled, setEvaluationEnabled] = useState(false);
  const [testimonialEnabled, setTestimonialEnabled] = useState(false);

  const fetchDashboardData = async () => {
    if (!studentId) {
      setError('Student ID is required');
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      setError(null);

      const [dashboardResponse, scheduleResponse, settingsResponse] = await Promise.all([
        fetch(`/api/buddy/mentor/dashboard?student_id=${studentId}`),
        fetch(`/api/buddy/user/schedule?student_id=${encodeURIComponent(studentId)}`),
        fetch('/api/buddy/admin/settings')
      ]);

      const result = await dashboardResponse.json();
      const scheduleResult = await scheduleResponse.json();
      const settingsResult = await settingsResponse.json();

      if (result.success) {
        setDashboardData(result.data);
        setAttendanceRecords(result.data.attendanceRecords || []);

        if (scheduleResult.success) {
          setScheduleData(scheduleResult.data);
        }

        if (settingsResult.success) {
          const evaluationEnabled = settingsResult.data.evaluation_enabled ?? false;
          const testimonialEnabled = settingsResult.data.testimonial_enabled ?? false;
          
          setEvaluationEnabled(evaluationEnabled);
          setTestimonialEnabled(testimonialEnabled);

          // Generate notifications based on enabled features
          const generatedNotifications: Notification[] = [];
          const currentTimestamp = new Date().toISOString();
          
          if (evaluationEnabled) {
            generatedNotifications.push({
              id: 'eval-reminder',
              message: 'Don\'t forget to complete evaluations for your mentees!',
              timestamp: currentTimestamp,
              type: 'info',
              read: false,
            });
          }

          if (testimonialEnabled) {
            generatedNotifications.push({
              id: 'testimonial-reminder',
              message: 'Your testimonial is ready! View it in the Testimonial tab.',
              timestamp: currentTimestamp,
              type: 'success',
              read: false,
            });
          }

          // Filter out notifications older than 1 week
          const oneWeekAgo = new Date();
          oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
          const recentNotifications = generatedNotifications.filter(
            notif => new Date(notif.timestamp) >= oneWeekAgo
          );

          setNotifications(recentNotifications);
        }
      } else {
        setError(result.message || 'Failed to load dashboard data');
      }
    } catch (err) {
      console.error('Error fetching dashboard data:', err);
      setError('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, [studentId]);

  const markNotificationRead = (notifId: string) => {
    setNotifications(prev => prev.map(notif =>
      notif.id === notifId ? { ...notif, read: true } : notif
    ));
  };

  const handleAttendanceSubmitted = async () => {
    if (!studentId) return;
    
    const dashboardResponse = await fetch(`/api/buddy/mentor/dashboard?student_id=${studentId}`);
    const dashboardResult = await dashboardResponse.json();

    if (dashboardResult.success) {
      setDashboardData(dashboardResult.data);
      setAttendanceRecords(dashboardResult.data.attendanceRecords || []);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-purple-600" />
        <span className="ml-2 text-gray-600">Loading dashboard...</span>
      </div>
    );
  }

  if (error || !dashboardData) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-3" />
        <p className="text-red-700">{error || 'Failed to load dashboard data'}</p>
        <button
          onClick={() => window.location.reload()}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Retry
        </button>
      </div>
    );
  }

  const { mentor, mentees, upcomingMeetings, weeklySchedules, stats } = dashboardData;

  return (
    <div className="space-y-6">
      {/* Navigation Tabs */}
      <div className="bg-white rounded-xl border border-gray-200">
        <div className="bg-white rounded-xl border border-gray-200 p-2">
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setActiveTab('overview')}
              className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors flex-1 min-w-[100px] ${
                activeTab === 'overview'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              <Users className="w-4 h-4" />
              <span className="hidden md:inline">Overview</span>
            </button>
            <button
              onClick={() => setActiveTab('mentees')}
              className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors flex-1 min-w-[100px] ${
                activeTab === 'mentees'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              <Users className="w-4 h-4" />
              <span className="hidden md:inline">Mentees</span>
            </button>
            <button
              onClick={() => setActiveTab('schedule')}
              className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors flex-1 min-w-[100px] ${
                activeTab === 'schedule'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              <Calendar className="w-4 h-4" />
              <span className="hidden md:inline">Schedule</span>
            </button>
            <button
              onClick={() => setActiveTab('classroom')}
              className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors flex-1 min-w-[100px] ${
                activeTab === 'classroom'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              <BookOpen className="w-4 h-4" />
              <span className="hidden md:inline">Classroom</span>
            </button>
            {evaluationEnabled && (
              <button
                onClick={() => setActiveTab('evaluation')}
                className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors flex-1 min-w-[100px] ${
                  activeTab === 'evaluation'
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-700 hover:bg-gray-100'
                }`}
              >
                <MessageSquare className="w-4 h-4" />
                <span className="hidden md:inline">Feedback</span>
              </button>
            )}
            {testimonialEnabled && (
              <button
                onClick={() => setActiveTab('testimonial')}
                className={`flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors flex-1 min-w-[100px] ${
                  activeTab === 'testimonial'
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-700 hover:bg-gray-100'
                }`}
              >
                <Award className="w-4 h-4" />
                <span className="hidden md:inline">Testimonial</span>
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {activeTab === 'overview' && (
            <OverviewContent
              mentor={mentor}
              mentees={mentees}
              upcomingMeetings={upcomingMeetings}
              weeklySchedules={weeklySchedules || []}
              stats={stats}
              notifications={notifications}
              onMarkNotificationRead={markNotificationRead}
            />
          )}

          {activeTab === 'mentees' && (
            <MentorMentees
              mentees={mentees}
              attendanceRecords={attendanceRecords}
              studentId={mentor.studentId}
              onAttendanceSubmitted={handleAttendanceSubmitted}
            />
          )}

          {activeTab === 'schedule' && (
            <MentorSchedule
              studentId={mentor.studentId}
              scheduleData={scheduleData}
            />
          )}

          {activeTab === 'classroom' && (
            <MentorClassroom
              mentorName={mentor.name}
              matchId={weeklySchedules && weeklySchedules.length > 0 ? weeklySchedules[0].matchId : scheduleData?.matchId}
            />
          )}

          {activeTab === 'evaluation' && evaluationEnabled && (
            <MentorEvaluation
              mentees={mentees}
              mentorStudentId={mentor.studentId}
            />
          )}

          {activeTab === 'testimonial' && testimonialEnabled && (
            <MentorTestimonial
              mentor={mentor}
              stats={stats}
              totalMentees={mentor.totalMentees}
              subjects={mentor.subjects}
            />
          )}
        </div>
      </div>
    </div>
  );
}

// Overview Content Component (inline)
interface OverviewContentProps {
  mentor: MentorData;
  mentees: Mentee[];
  upcomingMeetings: Meeting[];
  weeklySchedules: WeeklySchedule[];
  stats: DashboardData['stats'];
  notifications: Notification[];
  onMarkNotificationRead: (notifId: string) => void;
}

function OverviewContent({
  mentor,
  mentees,
  upcomingMeetings,
  weeklySchedules,
  stats,
  notifications,
  onMarkNotificationRead,
}: OverviewContentProps) {
  // Format date to DD-MMM-YYYY
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
  };

  const unreadCount = notifications.filter(n => !n.read).length;
  const avgAttendanceRate = mentees.length > 0 
    ? Math.round(mentees.reduce((sum, mentee) => sum + mentee.attendanceRate, 0) / mentees.length)
    : 0;

  return (
    <>
      {/* Welcome Header */}
      <div className="bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6 text-white">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="mb-2">Welcome back, {mentor.name}!</h2>
            <p className="text-purple-100">
              Student ID: {mentor.studentId} | Role: Mentor
            </p>
            <div className="flex items-center gap-4 mt-2">
              <div className="flex items-center gap-2">
                <BookOpen className="w-4 h-4" />
                <span className="text-purple-100">Subjects: {mentor.subjects.join(', ') || 'N/A'}</span>
              </div>
              <div className="flex items-center gap-2">
                <Users className="w-4 h-4" />
                <span className="text-purple-100">Mentees: {mentor.totalMentees}</span>
              </div>
            </div>
          </div>
          <div className="text-right">
            <p className="text-purple-100">Your Rating</p>
            <div className="flex items-center gap-2">
              <span className="text-white">{mentor.rating.toFixed(1)}</span>
              <div className="flex">
                {[1, 2, 3, 4, 5].map(star => (
                  <span key={star} className={star <= mentor.rating ? 'text-yellow-300' : 'text-purple-300'}>
                    ★
                  </span>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Stats */}
      <div className="grid md:grid-cols-4 gap-4 mt-6">
        <div className="bg-white border border-gray-200 rounded-xl p-4">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Total Mentees</p>
            <Users className="w-5 h-5 text-purple-600" />
          </div>
          <p className="text-gray-900 mb-1">{mentor.totalMentees}</p>
          <p className="text-gray-500">{mentees.filter(m => m.isRepeater).length} Repeaters</p>
        </div>

        <div className="bg-white border border-gray-200 rounded-xl p-4">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Sessions Conducted</p>
            <Calendar className="w-5 h-5 text-blue-600" />
          </div>
          <p className="text-gray-900 mb-1">{stats.completedSessions}</p>
          <p className="text-gray-500">{stats.upcomingMeetings} Upcoming</p>
        </div>

        <div className="bg-white border border-gray-200 rounded-xl p-4">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Total Sessions</p>
            <FileText className="w-5 h-5 text-green-600" />
          </div>
          <p className="text-gray-900 mb-1">{stats.totalSessions}</p>
          <p className="text-gray-500">Scheduled sessions</p>
        </div>

        <div className="bg-white border border-gray-200 rounded-xl p-4">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Attendance Rate</p>
            <CheckCircle className="w-5 h-5 text-amber-600" />
          </div>
          <p className="text-gray-900 mb-1">{avgAttendanceRate}%</p>
          <p className="text-gray-500">Average rate</p>
        </div>
      </div>

      {/* Main Content Grid */}
      <div className="grid lg:grid-cols-3 gap-6 mt-6">
        {/* Meetings Section */}
        <div className="lg:col-span-2 space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-gray-900">Meetings</h3>
          </div>

          {/* Weekly Schedule Cards */}
          {weeklySchedules && weeklySchedules.length > 0 && (
            weeklySchedules.map((schedule, index) => (
              <div
                key={`weekly-${index}`}
                className="border border-green-200 bg-green-50 rounded-lg p-4"
              >
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <h4 className="text-gray-900 mb-2">{schedule.subject}</h4>
                    <div className="flex items-center gap-4 text-gray-600">
                      <div className="flex items-center gap-2">
                        <Calendar className="w-4 h-4 text-green-600" />
                        <span className="font-medium">Every {schedule.day}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <Clock className="w-4 h-4 text-green-600" />
                        <span>{schedule.time}</span>
                      </div>
                    </div>
                  </div>
                  <span className="px-3 py-1 bg-green-600 text-white rounded-full text-sm">
                    Weekly
                  </span>
                </div>
              </div>
            ))
          )}

          {/* Upcoming Meeting Cards */}
          {upcomingMeetings.map(meeting => (
            <div
              key={meeting.id}
              className="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <h4 className="text-gray-900 mb-2">{meeting.topic}</h4>
                  <div className="flex items-center gap-4 text-gray-600">
                    <div className="flex items-center gap-2">
                      <Calendar className="w-4 h-4" />
                      <span>{meeting.date}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Clock className="w-4 h-4" />
                      <span>{meeting.time}</span>
                    </div>
                  </div>
                </div>
                <span className="px-3 py-1 bg-amber-100 text-amber-800 rounded-full">
                  Upcoming
                </span>
              </div>
            </div>
          ))}

          {/* Empty State */}
          {(!weeklySchedules || weeklySchedules.length === 0) && upcomingMeetings.length === 0 && (
            <div className="border border-gray-200 rounded-lg p-6 text-center">
              <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-3" />
              <p className="text-gray-600">No meetings scheduled</p>
            </div>
          )}
        </div>

        {/* Notifications */}
        <div>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <Bell className="w-5 h-5 text-gray-700" />
              <h3 className="text-gray-900">Notifications</h3>
              {unreadCount > 0 && (
                <span className="px-2 py-0.5 bg-red-600 text-white rounded-full">
                  {unreadCount}
                </span>
              )}
            </div>
          </div>

          <div className="space-y-3">
            {notifications.map(notif => (
              <div
                key={notif.id}
                onClick={() => onMarkNotificationRead(notif.id)}
                className={`p-3 rounded-lg border cursor-pointer transition-colors ${
                  notif.read
                    ? 'bg-white border-gray-200 hover:bg-gray-50'
                    : 'bg-blue-50 border-blue-200 hover:bg-blue-100'
                }`}
              >
                <div className="flex items-start gap-2">
                  <div className={`mt-1 ${
                    notif.type === 'warning' ? 'text-amber-600' :
                    notif.type === 'success' ? 'text-green-600' :
                    'text-blue-600'
                  }`}>
                    {notif.type === 'warning' ? (
                      <AlertCircle className="w-4 h-4" />
                    ) : (
                      <Bell className="w-4 h-4" />
                    )}
                  </div>
                  <div className="flex-1">
                    <p className="text-gray-900">{notif.message}</p>
                    <p className="text-gray-500 mt-1">{formatDate(notif.timestamp)}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  );
}
