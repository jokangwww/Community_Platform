import { useState, useEffect } from 'react';
import { Download, TrendingUp, Users, BarChart3, Loader2 } from 'lucide-react';
import { AdminFeedbackView } from './AdminFeedbackView';
import { AdminTestimonialManagement } from './AdminTestimonialManagement';
import { AdminGAPPointTracker } from './AdminGAPPointTracker';
import { AdminMentorVerification } from './AdminMentorVerification';
import { AdminAttendanceRecords } from './AdminAttendanceRecords';
import { AdminSettings } from './AdminSettings';
import { AdminAnalyticReport } from './AdminAnalyticReport';

interface Analytics {
  mentors: { total: number; active: number; pending: number };
  mentees: { total: number; matched: number; waiting: number };
  repeaters: { total: number; matched: number; waiting: number };
  match_rate: number;
}

export function AdminDashboard() {
  const [analytics, setAnalytics] = useState<Analytics | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'verifications' | 'attendance' | 'feedback' | 'testimonials' | 'gap' | 'settings'>('verifications');
  const [showReportPreview, setShowReportPreview] = useState(false);

  const fetchAnalytics = async () => {
    try {
      const response = await fetch('/api/buddy/admin/analytics', {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setAnalytics(result.data);
      }
    } catch (error) {
      console.error('Failed to fetch analytics:', error);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await fetchAnalytics();
      setIsLoading(false);
    };
    loadData();
  }, []);

  const handleDownloadReport = () => {
    setShowReportPreview(true);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-3 text-gray-600">Loading dashboard...</span>
      </div>
    );
  };

  return (
    <div className="space-y-8">
      {/* Analytics Overview */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center gap-3">
            <BarChart3 className="w-6 h-6 text-blue-600" />
            <div>
              <h2 className="text-gray-900">Analytics Overview</h2>
              <p className="text-gray-600">Complete programme statistics and insights</p>
            </div>
          </div>
          <button
            onClick={handleDownloadReport}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
          >
            <Download className="w-4 h-4" />
            Download PDF Report
          </button>
        </div>

        <div className="grid md:grid-cols-4 gap-4">
          <div className="bg-linear-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-700">Total Mentors</p>
              <Users className="w-5 h-5 text-blue-600" />
            </div>
            <p className="text-gray-900">{analytics?.mentors.total ?? 0}</p>
            <p className="text-blue-700 mt-1">{analytics?.mentors.active ?? 0} Active, {analytics?.mentors.pending ?? 0} Pending</p>
          </div>

          <div className="bg-linear-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-700">Total Mentees</p>
              <Users className="w-5 h-5 text-green-600" />
            </div>
            <p className="text-gray-900">{analytics?.mentees.total ?? 0}</p>
            <p className="text-green-700 mt-1">{analytics?.mentees.matched ?? 0} Matched, {analytics?.mentees.waiting ?? 0} Waiting</p>
          </div>

          <div className="bg-linear-to-br from-amber-50 to-amber-100 rounded-lg p-4 border border-amber-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-700">Repeater Priority</p>
              <TrendingUp className="w-5 h-5 text-amber-600" />
            </div>
            <p className="text-gray-900">{analytics?.repeaters.total ?? 0}</p>
            <p className="text-amber-700 mt-1">{analytics?.repeaters.matched ?? 0} Matched, {analytics?.repeaters.waiting ?? 0} Waiting</p>
          </div>

          <div className="bg-linear-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
            <div className="flex items-center justify-between mb-2">
              <p className="text-gray-700">Match Rate</p>
              <BarChart3 className="w-5 h-5 text-purple-600" />
            </div>
            <p className="text-gray-900">{analytics?.match_rate ?? 0}%</p>
            <p className="text-purple-700 mt-1">{analytics?.mentees.matched ?? 0} of {analytics?.mentees.total ?? 0} mentees</p>
          </div>
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="bg-white rounded-xl border border-gray-200">
        <div className="border-b border-gray-200 p-2">
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setActiveTab('verifications')}
              className={`flex items-center justify-center px-4 py-3 rounded-lg transition-colors flex-1 min-w-25 cursor-pointer ${
                activeTab === 'verifications'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              Mentor Verifications
            </button>
            <button
              onClick={() => setActiveTab('attendance')}
              className={`flex items-center justify-center px-4 py-3 rounded-lg transition-colors flex-1 min-w-25 cursor-pointer ${
                activeTab === 'attendance'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              Attendance Records
            </button>
            <button
              onClick={() => setActiveTab('feedback')}
              className={`flex items-center justify-center px-4 py-3 rounded-lg transition-colors flex-1 min-w-25 cursor-pointer ${
                activeTab === 'feedback'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              Feedback
            </button>
            <button
              onClick={() => setActiveTab('testimonials')}
              className={`flex items-center justify-center px-4 py-3 rounded-lg transition-colors flex-1 min-w-25 cursor-pointer ${
                activeTab === 'testimonials'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              Testimonials
            </button>
            <button
              onClick={() => setActiveTab('gap')}
              className={`flex items-center justify-center px-4 py-3 rounded-lg transition-colors flex-1 min-w-25 cursor-pointer ${
                activeTab === 'gap'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              GAP Points
            </button>
            <button
              onClick={() => setActiveTab('settings')}
              className={`flex items-center justify-center px-4 py-3 rounded-lg transition-colors flex-1 min-w-25 cursor-pointer ${
                activeTab === 'settings'
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              Settings
            </button>
          </div>
        </div>

        <div className="p-6">
          {activeTab === 'verifications' ? (
            <AdminMentorVerification onAnalyticsRefresh={fetchAnalytics} />
          ) : activeTab === 'attendance' ? (
            <AdminAttendanceRecords />
          ) : activeTab === 'feedback' ? (
            <AdminFeedbackView />
          ) : activeTab === 'testimonials' ? (
            <AdminTestimonialManagement />
          ) : activeTab === 'gap' ? (
            <AdminGAPPointTracker />
          ) : activeTab === 'settings' ? (
            <AdminSettings />
          ) : null}
        </div>
      </div>

      <AdminAnalyticReport 
        isOpen={showReportPreview} 
        onClose={() => setShowReportPreview(false)} 
      />
    </div>
  );
}