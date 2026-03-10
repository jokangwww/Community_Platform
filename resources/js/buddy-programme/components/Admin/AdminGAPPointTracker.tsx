import { useState, useEffect } from 'react';
import { Award, Download, CheckCircle, XCircle, TrendingUp, FileText, Users, Loader2, AlertCircle } from 'lucide-react';
import { AdminSemesterFilter } from './AdminSemesterFilter';

interface StudentAttendance {
  id: string;
  name: string;
  studentId: string;
  role: 'mentor' | 'mentee';
  faculty: string;
  programme: string;
  totalSessions: number;
  attendedSessions: number;
  attendanceRate: number;
  isEligible: boolean;
}

interface Stats {
  totalStudents: number;
  eligibleCount: number;
  notEligibleCount: number;
  avgAttendance: number;
}

export function AdminGAPPointTracker() {
  const [attendanceData, setAttendanceData] = useState<StudentAttendance[]>([]);
  const [stats, setStats] = useState<Stats>({
    totalStudents: 0,
    eligibleCount: 0,
    notEligibleCount: 0,
    avgAttendance: 0
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedRole, setSelectedRole] = useState<'all' | 'mentor' | 'mentee'>('all');
  const [exporting, setExporting] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [selectedSemesterId, setSelectedSemesterId] = useState<number | null>(null);

  useEffect(() => {
    fetchAttendanceData();
  }, [selectedSemesterId]);

  const fetchAttendanceData = async () => {
    try {
      setLoading(true);
      setError(null);
      const semParam = selectedSemesterId ? `?semester_id=${selectedSemesterId}` : '';
      const response = await fetch(`/api/buddy/gap-points${semParam}`);
      const result = await response.json();

      if (result.success) {
        setAttendanceData(result.data.students);
        setStats(result.data.stats);
      } else {
        setError(result.message || 'Failed to fetch attendance data');
      }
    } catch (err) {
      setError('Failed to load attendance data. Please try again.');
      console.error('Error fetching attendance data:', err);
    } finally {
      setLoading(false);
    }
  };

  const filteredData = attendanceData.filter(student => 
    selectedRole === 'all' || student.role === selectedRole
  );

  const eligibleStudents = filteredData.filter(s => s.isEligible);

  const downloadEligibilityReport = async () => {
    try {
      setExporting(true);
      const response = await fetch('/api/buddy/gap-points/export');
      
      if (!response.ok) {
        throw new Error('Export failed');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `gap_point_eligibility_report_${new Date().toISOString().split('T')[0]}.csv`;
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
        <span className="ml-2 text-gray-600">Loading attendance data...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <AlertCircle className="w-8 h-8 text-red-600 mx-auto mb-2" />
        <p className="text-red-800">{error}</p>
        <button
          onClick={fetchAttendanceData}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors cursor-pointer"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-gray-900">GAP Point Tracker</h2>
          <p className="text-gray-600">Track attendance eligibility for GAP points</p>
        </div>
        <AdminSemesterFilter selectedSemesterId={selectedSemesterId} onSelect={setSelectedSemesterId} />
      </div>

      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Total Students</p>
            <Users className="w-5 h-5 text-blue-600" />
          </div>
          <p className="text-gray-900">{stats.totalStudents}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Eligible for GAP</p>
            <CheckCircle className="w-5 h-5 text-green-600" />
          </div>
          <p className="text-gray-900">{stats.eligibleCount}</p>
          <p className="text-gray-600">
            ({stats.totalStudents > 0 ? ((stats.eligibleCount / stats.totalStudents) * 100).toFixed(0) : 0}%)
          </p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Not Eligible</p>
            <XCircle className="w-5 h-5 text-red-600" />
          </div>
          <p className="text-gray-900">{stats.notEligibleCount}</p>
          <p className="text-gray-600">&lt; 80% attendance</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Avg. Attendance</p>
            <TrendingUp className="w-5 h-5 text-purple-600" />
          </div>
          <p className="text-gray-900">{stats.avgAttendance.toFixed(1)}%</p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <span className="text-gray-700">Filter by Role:</span>
            <select
              value={selectedRole}
              onChange={(e) => setSelectedRole(e.target.value as any)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Students</option>
              <option value="mentor">Mentors Only</option>
              <option value="mentee">Mentees Only</option>
            </select>
          </div>

          <div className="flex gap-2">
            <button
              onClick={() => setShowPreview(true)}
              disabled={attendanceData.length === 0}
              className={`px-4 py-2 rounded-lg transition-colors flex items-center gap-2 cursor-pointer ${
                attendanceData.length === 0 
                  ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                  : 'bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed'
              }`}
            >
              <FileText className="w-4 h-4" />
              Preview Report
            </button>
            <button
              onClick={downloadEligibilityReport}
              disabled={exporting || attendanceData.length === 0}
              className={`px-4 py-2 rounded-lg transition-colors flex items-center gap-2 cursor-pointer ${
                attendanceData.length === 0 
                  ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                  : 'bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed'
              }`}
            >
              {exporting ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <Download className="w-4 h-4" />
              )}
              {exporting ? 'Exporting...' : 'Download GAP Eligibility Report'}
            </button>
          </div>
        </div>
      </div>

      {/* Attendance Table */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-gray-900 mb-4">Attendance & GAP Point Eligibility</h3>
        
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-3 px-4 text-gray-700">Name</th>
                <th className="text-left py-3 px-4 text-gray-700">Student ID</th>
                <th className="text-left py-3 px-4 text-gray-700">Role</th>
                <th className="text-left py-3 px-4 text-gray-700">Faculty</th>
                <th className="text-left py-3 px-4 text-gray-700">Programme</th>
                <th className="text-left py-3 px-4 text-gray-700">Attended</th>
                <th className="text-left py-3 px-4 text-gray-700">Attendance %</th>
                <th className="text-left py-3 px-4 text-gray-700">GAP Eligible</th>
              </tr>
            </thead>
            <tbody>
              {filteredData.length === 0 ? (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-gray-500">
                    No attendance data available
                  </td>
                </tr>
              ) : (
                filteredData.map(student => (
                  <tr key={student.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="py-3 px-4 text-gray-900">{student.name}</td>
                    <td className="py-3 px-4 text-gray-600">{student.studentId}</td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-1 rounded cursor-pointer ${
                        student.role === 'mentor' 
                          ? 'bg-blue-100 text-blue-800' 
                          : 'bg-purple-100 text-purple-800'
                      }`}>
                        {student.role}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-gray-700">{student.faculty}</td>
                    <td className="py-3 px-4 text-gray-700">{student.programme}</td>
                    <td className="py-3 px-4 text-gray-900">
                      {student.attendedSessions} / {student.totalSessions}
                    </td>
                    <td className="py-3 px-4">
                      <span className={`px-3 py-1 rounded cursor-pointer ${
                        student.attendanceRate >= 80
                          ? 'bg-green-100 text-green-800'
                          : student.attendanceRate >= 70
                          ? 'bg-amber-100 text-amber-800'
                          : 'bg-red-100 text-red-800'
                      }`}>
                        {student.attendanceRate.toFixed(1)}%
                      </span>
                    </td>
                    <td className="py-3 px-4">
                      {student.isEligible ? (
                        <div className="flex items-center gap-2 text-green-700">
                          <CheckCircle className="w-5 h-5" />
                          <span>Yes</span>
                        </div>
                      ) : (
                        <div className="flex items-center gap-2 text-red-700">
                          <XCircle className="w-5 h-5" />
                          <span>No</span>
                        </div>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Report Preview Modal */}
      {showPreview && attendanceData.length > 0 && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setShowPreview(false)}
        >
          <div
            className="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
            onClick={e => e.stopPropagation()}
          >
            <div className="flex items-center justify-between mb-6 p-6 border-b border-gray-200 sticky top-0 bg-white rounded-t-xl">
              <h3 className="text-gray-900">GAP Point Submission Report Preview</h3>
              <button
                onClick={() => setShowPreview(false)}
                className="text-gray-600 hover:text-gray-900 cursor-pointer"
              >
                <XCircle className="w-6 h-6" />
              </button>
            </div>
            <div className="p-6">
              <div className="bg-gray-50 rounded-lg p-6 space-y-4">
                <div className="text-center pb-4 border-b border-gray-200">
                  <h4 className="text-gray-900 mb-1">TARUMT Buddy Programme</h4>
                  <p className="text-gray-600">GAP Point Eligibility Report - Semester 2, 2024/2025</p>
                </div>

                <div>
                  <p className="text-gray-700 mb-3">Eligible Students (≥80% Attendance):</p>
                  <div className="space-y-2">
                    {eligibleStudents.length === 0 ? (
                      <p className="text-gray-500 text-center py-4">No eligible students found</p>
                    ) : (
                      eligibleStudents.map((student, index) => (
                        <div key={student.id} className="flex items-center justify-between bg-white rounded p-3 border border-gray-200">
                          <div className="flex items-center gap-3">
                            <span className="text-gray-600">{index + 1}.</span>
                            <div>
                              <p className="text-gray-900">{student.name}</p>
                              <p className="text-gray-600">{student.studentId}</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="text-gray-900">{student.attendanceRate.toFixed(1)}% attendance</p>
                            <p className="text-gray-600">{student.role}</p>
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </div>

                <div className="pt-4 border-t border-gray-200 text-gray-600">
                  <p>Total Eligible: <span className="text-gray-900">{eligibleStudents.length}</span> students</p>
                  <p className="mt-2">Generated: {new Date().toLocaleDateString()}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
