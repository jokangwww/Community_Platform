import { useState, useEffect } from 'react';
import { Check, X, FileText, Download, Eye, Loader2 } from 'lucide-react';

interface PendingMentor {
  id: string;
  fullName: string;
  studentId: string;
  faculty: string;
  course: string;
  yearOfStudy: number;
  cgpa: number;
  subjects: string[];
  documentName: string;
  documentPath?: string;
  registeredDate: string;
}

interface AdminMentorVerificationProps {
  onAnalyticsRefresh?: () => void;
}

export function AdminMentorVerification({ onAnalyticsRefresh }: AdminMentorVerificationProps) {
  const [pendingMentors, setPendingMentors] = useState<PendingMentor[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  const [selectedMentor, setSelectedMentor] = useState<PendingMentor | null>(null);

  const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  };

  const fetchPendingMentors = async () => {
    try {
      const response = await fetch('/api/buddy/admin/pending-mentors', {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setPendingMentors(result.data);
      }
    } catch (error) {
      console.error('Failed to fetch pending mentors:', error);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await fetchPendingMentors();
      setIsLoading(false);
    };
    loadData();
  }, []);

  const handleApprove = async (mentorId: string) => {
    setActionLoading(mentorId);
    try {
      const response = await fetch(`/api/buddy/admin/mentors/${mentorId}/approve`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        }
      });
      const result = await response.json();
      if (result.success) {
        setPendingMentors(prev => prev.filter(m => m.id !== mentorId));
        setSelectedMentor(null);
        // Refresh analytics in parent
        onAnalyticsRefresh?.();
      }
    } catch (error) {
      console.error('Failed to approve mentor:', error);
    } finally {
      setActionLoading(null);
    }
  };

  const handleReject = async (mentorId: string) => {
    setActionLoading(mentorId);
    try {
      const response = await fetch(`/api/buddy/admin/mentors/${mentorId}/reject`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        }
      });
      const result = await response.json();
      if (result.success) {
        setPendingMentors(prev => prev.filter(m => m.id !== mentorId));
        setSelectedMentor(null);
        // Refresh analytics in parent
        onAnalyticsRefresh?.();
      }
    } catch (error) {
      console.error('Failed to reject mentor:', error);
    } finally {
      setActionLoading(null);
    }
  };

  const handleDownloadDocument = (mentorId: string) => {
    window.open(`/api/buddy/admin/documents/${mentorId}`, '_blank');
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-3 text-gray-600">Loading verifications...</span>
      </div>
    );
  }

  return (
    <>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-gray-900">Pending Mentor Verifications</h2>
          <p className="text-gray-600">Review and approve mentor registrations</p>
        </div>
        <div className="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg">
          {pendingMentors.length} Pending
        </div>
      </div>

      {pendingMentors.length === 0 ? (
        <div className="text-center py-12">
          <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Check className="w-8 h-8 text-gray-400" />
          </div>
          <p className="text-gray-600">No pending verifications</p>
        </div>
      ) : (
        <div className="space-y-4">
          {pendingMentors.map(mentor => (
            <div
              key={mentor.id}
              className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-3">
                    <h3 className="text-gray-900">{mentor.fullName}</h3>
                    <span className="px-2 py-1 bg-amber-100 text-amber-800 rounded">
                      Pending Verification
                    </span>
                  </div>

                  <div className="grid md:grid-cols-2 gap-x-6 gap-y-2 text-gray-600 mb-3">
                    <div className="flex gap-2">
                      <span>Student ID:</span>
                      <span className="text-gray-900">{mentor.studentId}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>CGPA:</span>
                      <span className="text-gray-900">{Number(mentor.cgpa).toFixed(2)}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Faculty:</span>
                      <span className="text-gray-900">{mentor.faculty}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Year:</span>
                      <span className="text-gray-900">Year {mentor.yearOfStudy}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Course:</span>
                      <span className="text-gray-900">{mentor.course}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Registered:</span>
                      <span className="text-gray-900">{mentor.registeredDate}</span>
                    </div>
                  </div>

                  <div className="mb-3">
                    <p className="text-gray-600 mb-2">Subjects to Mentor:</p>
                    <div className="flex flex-wrap gap-2">
                      {mentor.subjects.map(subject => (
                        <span
                          key={subject}
                          className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full"
                        >
                          {subject}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>

                <div className="flex flex-col gap-2">
                  <button
                    onClick={() => setSelectedMentor(mentor)}
                    className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <Eye className="w-4 h-4" />
                    View Details
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Detail Modal */}
      {selectedMentor && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setSelectedMentor(null)}
        >
          <div 
            className="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-gray-900">Mentor Application Details</h2>
              <button
                onClick={() => setSelectedMentor(null)}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-4 mb-6">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <p className="text-gray-600 mb-1">Full Name</p>
                  <p className="text-gray-900">{selectedMentor.fullName}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Student ID</p>
                  <p className="text-gray-900">{selectedMentor.studentId}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Faculty</p>
                  <p className="text-gray-900">{selectedMentor.faculty}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Course</p>
                  <p className="text-gray-900">{selectedMentor.course}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Year of Study</p>
                  <p className="text-gray-900">Year {selectedMentor.yearOfStudy}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">CGPA</p>
                  <p className="text-gray-900">{Number(selectedMentor.cgpa).toFixed(2)}</p>
                </div>
              </div>

              <div>
                <p className="text-gray-600 mb-2">Subjects to Mentor</p>
                <div className="flex flex-wrap gap-2">
                  {selectedMentor.subjects.map(subject => (
                    <span
                      key={subject}
                      className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full"
                    >
                      {subject}
                    </span>
                  ))}
                </div>
              </div>

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-gray-600 mb-3">Uploaded Document</p>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                      <FileText className="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                      <p className="text-gray-900">{selectedMentor.documentName}</p>
                      <p className="text-gray-500">Qualification Certificate</p>
                    </div>
                  </div>
                  <button 
                    onClick={() => handleDownloadDocument(selectedMentor.id)}
                    className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <Download className="w-4 h-4" />
                    Download
                  </button>
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => handleApprove(selectedMentor.id)}
                disabled={actionLoading === selectedMentor.id}
                className="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
              >
                {actionLoading === selectedMentor.id ? (
                  <Loader2 className="w-5 h-5 animate-spin" />
                ) : (
                  <Check className="w-5 h-5" />
                )}
                Approve Application
              </button>
              <button
                onClick={() => handleReject(selectedMentor.id)}
                disabled={actionLoading === selectedMentor.id}
                className="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
              >
                {actionLoading === selectedMentor.id ? (
                  <Loader2 className="w-5 h-5 animate-spin" />
                ) : (
                  <X className="w-5 h-5" />
                )}
                Reject Application
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
