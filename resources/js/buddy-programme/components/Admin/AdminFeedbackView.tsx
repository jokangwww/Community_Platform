import { useState, useEffect } from 'react';
import { Star, MessageSquare, User, TrendingUp, Filter, Download, Loader2, AlertCircle } from 'lucide-react';

interface FeedbackRecord {
  id: string;
  fromName: string;
  fromId: string;
  fromRole: 'mentor' | 'mentee';
  toName: string;
  toId: string;
  toRole: 'mentor' | 'mentee';
  rating: number;
  feedback: string;
  submittedDate: string;
}

interface Stats {
  totalSubmissions: number;
  avgRating: number;
  mentorFeedback: number;
  menteeFeedback: number;
}

export function AdminFeedbackView() {
  const [feedbackRecords, setFeedbackRecords] = useState<FeedbackRecord[]>([]);
  const [stats, setStats] = useState<Stats>({
    totalSubmissions: 0,
    avgRating: 0,
    mentorFeedback: 0,
    menteeFeedback: 0
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filterRole, setFilterRole] = useState<'all' | 'mentor' | 'mentee'>('all');
  const [filterRating, setFilterRating] = useState<number>(0);
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    fetchEvaluations();
  }, []);

  const fetchEvaluations = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch('/api/buddy/evaluations');
      const result = await response.json();

      if (result.success) {
        setFeedbackRecords(result.data.evaluations);
        setStats(result.data.stats);
      } else {
        setError(result.message || 'Failed to fetch evaluations');
      }
    } catch (err) {
      setError('Failed to load evaluations. Please try again.');
      console.error('Error fetching evaluations:', err);
    } finally {
      setLoading(false);
    }
  };

  const filteredFeedback = feedbackRecords.filter(record => {
    if (filterRole !== 'all' && record.toRole !== filterRole) return false;
    if (filterRating > 0 && record.rating !== filterRating) return false;
    return true;
  });

  const exportReport = async () => {
    try {
      setExporting(true);
      const response = await fetch('/api/buddy/evaluations/export');
      
      if (!response.ok) {
        throw new Error('Export failed');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `buddy_evaluations_${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      console.error('Export error:', err);
      alert('Failed to export report. Please try again.');
    } finally {
      setExporting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading evaluations...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <AlertCircle className="w-8 h-8 text-red-600 mx-auto mb-2" />
        <p className="text-red-800">{error}</p>
        <button
          onClick={fetchEvaluations}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Total Submissions</p>
            <MessageSquare className="w-5 h-5 text-blue-600" />
          </div>
          <p className="text-gray-900">{stats.totalSubmissions}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Average Rating</p>
            <Star className="w-5 h-5 text-amber-600" />
          </div>
          <div className="flex items-center gap-2">
            <p className="text-gray-900">{stats.avgRating.toFixed(2)}</p>
            <span className="text-gray-600">/ 5.0</span>
          </div>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Mentor Feedback</p>
            <User className="w-5 h-5 text-green-600" />
          </div>
          <p className="text-gray-900">{stats.mentorFeedback}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Mentee Feedback</p>
            <User className="w-5 h-5 text-purple-600" />
          </div>
          <p className="text-gray-900">{stats.menteeFeedback}</p>
        </div>
      </div>

      {/* Filters and Export */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex flex-wrap items-center gap-4">
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5 text-gray-600" />
              <span className="text-gray-700">Filters:</span>
            </div>
            
            <select
              value={filterRole}
              onChange={(e) => setFilterRole(e.target.value as any)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Roles</option>
              <option value="mentor">Mentors Only</option>
              <option value="mentee">Mentees Only</option>
            </select>

            <select
              value={filterRating}
              onChange={(e) => setFilterRating(Number(e.target.value))}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value={0}>All Ratings</option>
              <option value={5}>5 Stars</option>
              <option value={4}>4 Stars</option>
              <option value={3}>3 Stars</option>
              <option value={2}>2 Stars</option>
              <option value={1}>1 Star</option>
            </select>
          </div>

          <button
            onClick={exportReport}
            disabled={exporting || feedbackRecords.length === 0}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {exporting ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Download className="w-4 h-4" />
            )}
            {exporting ? 'Exporting...' : 'Export CSV'}
          </button>
        </div>
      </div>

      {/* Feedback Records */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-gray-900 mb-4">Feedback Records</h3>
        
        <div className="space-y-4">
          {filteredFeedback.map(record => (
            <div key={record.id} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <span className="text-gray-900">{record.fromName}</span>
                    <span className="text-gray-600">({record.fromId})</span>
                    <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                      {record.fromRole}
                    </span>
                    <span className="text-gray-500">→</span>
                    <span className="text-gray-900">{record.toName}</span>
                    <span className="text-gray-600">({record.toId})</span>
                    <span className="px-2 py-1 bg-purple-100 text-purple-800 rounded">
                      {record.toRole}
                    </span>
                  </div>
                  <p className="text-gray-600">Submitted: {record.submittedDate}</p>
                </div>
                
                <div className="flex items-center gap-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <Star
                      key={star}
                      className={`w-4 h-4 ${
                        star <= record.rating
                          ? 'fill-amber-400 text-amber-400'
                          : 'text-gray-300'
                      }`}
                    />
                  ))}
                  <span className="ml-2 text-gray-900">{record.rating}.0</span>
                </div>
              </div>
              
              <div className="bg-gray-50 rounded-lg p-3">
                <p className="text-gray-700">{record.feedback}</p>
              </div>
            </div>
          ))}
        </div>

        {filteredFeedback.length === 0 && (
          <div className="text-center py-8 text-gray-600">
            No feedback records found matching the selected filters.
          </div>
        )}
      </div>
    </div>
  );
}