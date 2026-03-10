import { useState, useEffect } from 'react';
import { Users, Calendar, BookOpen, Star, Play, Loader2, AlertCircle, RefreshCw } from 'lucide-react';
import { AdminSemesterFilter } from './AdminSemesterFilter';

interface Match {
  id: string;
  mentor: {
    id: number;
    name: string;
    studentId: string;
    faculty: string;
    cgpa: number;
  };
  mentee: {
    id: number;
    name: string;
    studentId: string;
    faculty: string;
    cgpa: number;
    isRepeater: boolean;
  };
  subject: string;
  matchedDate: string;
  status: 'active' | 'completed' | 'cancelled';
  sessions: number;
  totalSessions: number;
}

interface MatchingStats {
  total_mentors: number;
  total_mentees: number;
  matched_mentees: number;
  unmatched_mentees: number;
  available_mentor_slots: number;
  active_matches: number;
}

interface PreviewMatch {
  mentee: {
    id: number;
    name: string;
    priority_tier: string;
    is_repeater: boolean;
  };
  mentor: {
    id: number;
    name: string;
  };
  subject: {
    id: number;
    name: string;
  };
}

interface PreviewResult {
  potential_matches: PreviewMatch[];
  unmatched_mentees: {
    id: number;
    name: string;
    priority_tier: string;
    subject: string;
  }[];
}

export function MatchingDashboard() {
  const [matches, setMatches] = useState<Match[]>([]);
  const [stats, setStats] = useState<MatchingStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRunningMatch, setIsRunningMatch] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [previewData, setPreviewData] = useState<PreviewResult | null>(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [selectedSemesterId, setSelectedSemesterId] = useState<number | null>(null);

  const isViewingArchived = selectedSemesterId !== null;

  const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  };

  const semParam = (prefix: '?' | '&' = '?') => selectedSemesterId ? `${prefix}semester_id=${selectedSemesterId}` : '';

  const fetchMatches = async () => {
    try {
      const response = await fetch(`/api/buddy/matching${semParam('?')}`, {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setMatches(result.data);
      }
    } catch (err) {
      console.error('Failed to fetch matches:', err);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await fetch(`/api/buddy/matching/stats${semParam('?')}`, {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setStats(result.data);
      }
    } catch (err) {
      console.error('Failed to fetch stats:', err);
    }
  };

  const fetchPreview = async () => {
    setPreviewLoading(true);
    try {
      const response = await fetch(`/api/buddy/matching/preview${semParam('?')}`, {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setPreviewData(result.data);
        setShowPreview(true);
      }
    } catch (err) {
      console.error('Failed to fetch preview:', err);
      setError('Failed to load preview');
    } finally {
      setPreviewLoading(false);
    }
  };

  const runAutoMatch = async () => {
    setIsRunningMatch(true);
    setError(null);
    setSuccessMessage(null);
    
    try {
      const response = await fetch('/api/buddy/matching/auto', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        }
      });
      
      const result = await response.json();
      
      if (result.success) {
        setSuccessMessage(result.message);
        setShowPreview(false);
        // Refresh data
        await Promise.all([fetchMatches(), fetchStats()]);
      } else {
        setError(result.message || 'Failed to run auto-match');
      }
    } catch (err) {
      console.error('Failed to run auto-match:', err);
      setError('Failed to run auto-match');
    } finally {
      setIsRunningMatch(false);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      setShowPreview(false);
      setPreviewData(null);
      setError(null);
      setSuccessMessage(null);
      await Promise.all([fetchMatches(), fetchStats()]);
      setIsLoading(false);
    };
    loadData();
  }, [selectedSemesterId]);

  const activeMatches = matches.filter(m => m.status === 'active');
  const repeaterMatches = activeMatches.filter(m => m.mentee.isRepeater);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading matches...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Semester Filter */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-gray-900">Mentor-Mentee Matching</h2>
          <p className="text-gray-600 text-sm">Manage and review mentor-mentee pairings</p>
        </div>
        <AdminSemesterFilter selectedSemesterId={selectedSemesterId} onSelect={setSelectedSemesterId} />
      </div>

      {/* Archived notice */}
      {isViewingArchived && (
        <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center gap-2">
          <AlertCircle className="w-5 h-5 text-amber-600" />
          <span className="text-amber-800 text-sm">You are viewing an archived semester. Auto-match and preview actions are disabled.</span>
        </div>
      )}

      {/* Messages */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-2">
          <AlertCircle className="w-5 h-5 text-red-600" />
          <span className="text-red-800">{error}</span>
        </div>
      )}
      {successMessage && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
          <span className="text-green-800">{successMessage}</span>
        </div>
      )}

      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Total Matches</p>
            <Users className="w-5 h-5 text-blue-600" />
          </div>
          <p className="text-gray-900">{stats?.active_matches ?? activeMatches.length}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Repeater Priority</p>
            <Star className="w-5 h-5 text-amber-600" />
          </div>
          <p className="text-gray-900">{repeaterMatches.length}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Unmatched Mentees</p>
            <BookOpen className="w-5 h-5 text-red-600" />
          </div>
          <p className="text-gray-900">{stats?.unmatched_mentees ?? 0}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Available Slots</p>
            <Calendar className="w-5 h-5 text-green-600" />
          </div>
          <p className="text-gray-900">{stats?.available_mentor_slots ?? 0}</p>
        </div>
      </div>

      {/* Auto-Match Controls */}
      {!isViewingArchived && (
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h2 className="text-gray-900 mb-1">Auto-Matching</h2>
            <p className="text-gray-600">
              Run the automatic matching algorithm to pair mentees with available mentors
            </p>
          </div>
          <div className="flex gap-3">
            <button
              onClick={fetchPreview}
              disabled={previewLoading || isRunningMatch}
              className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 cursor-pointer"
            >
              {previewLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <RefreshCw className="w-4 h-4" />
              )}
              Preview Matches
            </button>
            <button
              onClick={runAutoMatch}
              disabled={isRunningMatch}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 cursor-pointer"
            >
              {isRunningMatch ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <Play className="w-4 h-4" />
              )}
              Run Auto-Match
            </button>
          </div>
        </div>

        {/* Preview Results */}
        {showPreview && previewData && (
          <div className="border-t border-gray-200 pt-4 mt-4">
            <h3 className="text-gray-900 mb-3">Match Preview</h3>
            
            {previewData.potential_matches.length > 0 ? (
              <div className="mb-4">
                <p className="text-gray-600 mb-2">
                  {previewData.potential_matches.length} potential matches found:
                </p>
                <div className="space-y-2 max-h-60 overflow-y-auto">
                  {previewData.potential_matches.map((match, idx) => (
                    <div key={idx} className="flex items-center gap-4 p-3 bg-green-50 rounded-lg">
                      <div className="flex-1">
                        <span className="text-gray-900">{match.mentee.name}</span>
                        {match.mentee.is_repeater && (
                          <span className="ml-2 px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-xs">Repeater</span>
                        )}
                      </div>
                      <span className="text-gray-400">→</span>
                      <div className="flex-1 text-gray-900">{match.mentor.name}</div>
                      <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">{match.subject.name}</span>
                    </div>
                  ))}
                </div>
              </div>
            ) : (
              <p className="text-gray-600 mb-4">No matches can be created at this time.</p>
            )}

            {previewData.unmatched_mentees.length > 0 && (
              <div>
                <p className="text-gray-600 mb-2">
                  {previewData.unmatched_mentees.length} mentees cannot be matched:
                </p>
                <div className="space-y-2 max-h-40 overflow-y-auto">
                  {previewData.unmatched_mentees.map((mentee, idx) => (
                    <div key={idx} className="p-3 bg-gray-50 rounded-lg text-gray-700">
                      {mentee.name} - No mentor available for: {mentee.subject || 'No subject'}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </div>
      )}

      {/* Matches List */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="mb-6">
          <h2 className="text-gray-900 mb-2">Active Mentor-Mentee Matches</h2>
          <p className="text-gray-600">
            Automatically matched based on subject preferences and priority allocation
          </p>
        </div>

        {activeMatches.length === 0 ? (
          <div className="text-center py-12">
            <Users className="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <p className="text-gray-600">No active matches yet</p>
            <p className="text-gray-500 text-sm">Run the auto-match algorithm to create matches</p>
          </div>
        ) : (
          <div className="space-y-4">
            {activeMatches.map(match => (
            <div
              key={match.id}
              className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
            >
              <div className="flex items-start justify-between gap-4 mb-4">
                <div className="flex items-center gap-3">
                  <div className="px-3 py-1 bg-blue-100 text-blue-800 rounded-lg">
                    {match.subject}
                  </div>
                  {match.mentee.isRepeater && (
                    <div className="px-3 py-1 bg-amber-100 text-amber-800 rounded-lg flex items-center gap-1">
                      <Star className="w-4 h-4" />
                      Priority (Repeater)
                    </div>
                  )}
                  <div className="px-3 py-1 bg-green-100 text-green-800 rounded-lg">
                    Active
                  </div>
                </div>
                <div className="text-right text-gray-600">
                  <p>Matched: {match.matchedDate}</p>
                  <p>{match.sessions} sessions completed</p>
                </div>
              </div>

              <div className="grid md:grid-cols-2 gap-6">
                {/* Mentor Info */}
                <div className="bg-blue-50 rounded-lg p-4">
                  <div className="flex items-center gap-2 mb-3">
                    <div className="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">
                      M
                    </div>
                    <div>
                      <p className="text-gray-900">Mentor</p>
                    </div>
                  </div>
                  <div className="space-y-1 text-gray-700">
                    <p><strong>{match.mentor.name}</strong></p>
                    <p>ID: {match.mentor.studentId}</p>
                    <p>{match.mentor.faculty}</p>
                    <p>CGPA: {Number(match.mentor.cgpa).toFixed(2)}</p>
                  </div>
                </div>

                {/* Mentee Info */}
                <div className="bg-green-50 rounded-lg p-4">
                  <div className="flex items-center gap-2 mb-3">
                    <div className="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center">
                      M
                    </div>
                    <div>
                      <p className="text-gray-900">Mentee</p>
                    </div>
                  </div>
                  <div className="space-y-1 text-gray-700">
                    <p><strong>{match.mentee.name}</strong></p>
                    <p>ID: {match.mentee.studentId}</p>
                    <p>{match.mentee.faculty}</p>
                    <p>CGPA: {Number(match.mentee.cgpa).toFixed(2)}</p>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
        )}
      </div>

      {/* Info Panel */}
      {/* <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 className="text-gray-900 mb-3">Matching Process</h3>
        <div className="space-y-2 text-gray-700">
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">1.</span>
            <p>System compares skills/subjects selected by mentors and mentees during registration</p>
          </div>
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">2.</span>
            <p>Matching conducted on first-come, first-served basis with priority for verified repeaters</p>
          </div>
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">3.</span>
            <p>Both mentor and mentee receive notifications upon successful match</p>
          </div>
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">4.</span>
            <p>Students with ratings below 3.0 are deprioritized in allocation list (when priority allocation enabled)</p>
          </div>
        </div>
      </div> */}
    </div>
  );
}