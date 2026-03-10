import { useState, useEffect } from 'react';
import { Check, X, FileText, Download, Eye, Loader2 } from 'lucide-react';
import { AdminSemesterFilter } from './AdminSemesterFilter';

interface PendingRepeater {
  id: string;
  fullName: string;
  studentId: string;
  faculty: string;
  course: string;
  yearOfStudy: number;
  cgpa: number;
  subject: string;
  documentName: string;
  documentPath?: string;
  registeredDate: string;
}

interface AdminRepeaterVerificationProps {
  onAnalyticsRefresh?: () => void;
}

export function AdminRepeaterVerification({ onAnalyticsRefresh }: AdminRepeaterVerificationProps) {
  const [pendingRepeaters, setPendingRepeaters] = useState<PendingRepeater[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  const [selectedRepeater, setSelectedRepeater] = useState<PendingRepeater | null>(null);
  const [selectedSemesterId, setSelectedSemesterId] = useState<number | null>(null);

  const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  };

  const fetchPendingRepeaters = async (semId: number | null = null) => {
    try {
      const semParam = semId ? `?semester_id=${semId}` : '';
      const response = await fetch(`/api/buddy/admin/pending-repeaters${semParam}`, {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setPendingRepeaters(result.data);
      }
    } catch (error) {
      console.error('Failed to fetch pending repeaters:', error);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await fetchPendingRepeaters(selectedSemesterId);
      setIsLoading(false);
    };
    loadData();
  }, [selectedSemesterId]);

  const handleApprove = async (repeaterId: string) => {
    setActionLoading(repeaterId);
    try {
      const response = await fetch(`/api/buddy/admin/repeaters/${repeaterId}/approve`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        }
      });
      const result = await response.json();
      if (result.success) {
        setPendingRepeaters(prev => prev.filter(r => r.id !== repeaterId));
        setSelectedRepeater(null);
        onAnalyticsRefresh?.();
      }
    } catch (error) {
      console.error('Failed to approve repeater:', error);
    } finally {
      setActionLoading(null);
    }
  };

  const handleReject = async (repeaterId: string) => {
    setActionLoading(repeaterId);
    try {
      const response = await fetch(`/api/buddy/admin/repeaters/${repeaterId}/reject`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        }
      });
      const result = await response.json();
      if (result.success) {
        setPendingRepeaters(prev => prev.filter(r => r.id !== repeaterId));
        setSelectedRepeater(null);
        onAnalyticsRefresh?.();
      }
    } catch (error) {
      console.error('Failed to reject repeater:', error);
    } finally {
      setActionLoading(null);
    }
  };

  const handleDownloadDocument = (repeaterId: string) => {
    window.open(`/api/buddy/admin/documents/${repeaterId}`, '_blank');
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
          <h2 className="text-gray-900">Pending Repeater Verifications</h2>
          <p className="text-gray-600">Review and approve repeater document submissions</p>
        </div>
        <div className="flex items-center gap-3">
          <AdminSemesterFilter selectedSemesterId={selectedSemesterId} onSelect={setSelectedSemesterId} />
          <div className="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg">
            {pendingRepeaters.length} Pending
          </div>
        </div>
      </div>

      {pendingRepeaters.length === 0 ? (
        <div className="text-center py-12">
          <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Check className="w-8 h-8 text-gray-400" />
          </div>
          <p className="text-gray-600">No pending repeater verifications</p>
        </div>
      ) : (
        <div className="space-y-4">
          {pendingRepeaters.map(repeater => (
            <div
              key={repeater.id}
              className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-3">
                    <h3 className="text-gray-900">{repeater.fullName}</h3>
                    <span className="px-2 py-1 bg-amber-100 text-amber-800 rounded">
                      Pending Verification
                    </span>
                    <span className="px-2 py-1 bg-red-100 text-red-800 rounded">
                      Repeater
                    </span>
                  </div>

                  <div className="grid md:grid-cols-2 gap-x-6 gap-y-2 text-gray-600 mb-3">
                    <div className="flex gap-2">
                      <span>Student ID:</span>
                      <span className="text-gray-900">{repeater.studentId}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>CGPA:</span>
                      <span className="text-gray-900">{Number(repeater.cgpa).toFixed(2)}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Faculty:</span>
                      <span className="text-gray-900">{repeater.faculty}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Year:</span>
                      <span className="text-gray-900">Year {repeater.yearOfStudy}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Course:</span>
                      <span className="text-gray-900">{repeater.course}</span>
                    </div>
                    <div className="flex gap-2">
                      <span>Registered:</span>
                      <span className="text-gray-900">{repeater.registeredDate}</span>
                    </div>
                  </div>

                  <div className="mb-3">
                    <p className="text-gray-600 mb-2">Subject to Repeat:</p>
                    <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                      {repeater.subject}
                    </span>
                  </div>
                </div>

                <div className="flex flex-col gap-2">
                  <button
                    onClick={() => setSelectedRepeater(repeater)}
                    className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
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
      {selectedRepeater && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setSelectedRepeater(null)}
        >
          <div 
            className="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-gray-900">Repeater Application Details</h2>
              <button
                onClick={() => setSelectedRepeater(null)}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-4 mb-6">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <p className="text-gray-600 mb-1">Full Name</p>
                  <p className="text-gray-900">{selectedRepeater.fullName}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Student ID</p>
                  <p className="text-gray-900">{selectedRepeater.studentId}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Faculty</p>
                  <p className="text-gray-900">{selectedRepeater.faculty}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Course</p>
                  <p className="text-gray-900">{selectedRepeater.course}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">Year of Study</p>
                  <p className="text-gray-900">Year {selectedRepeater.yearOfStudy}</p>
                </div>
                <div>
                  <p className="text-gray-600 mb-1">CGPA</p>
                  <p className="text-gray-900">{Number(selectedRepeater.cgpa).toFixed(2)}</p>
                </div>
              </div>

              <div>
                <p className="text-gray-600 mb-2">Subject to Repeat</p>
                <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                  {selectedRepeater.subject}
                </span>
              </div>

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-gray-600 mb-3">Uploaded Document</p>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                      <FileText className="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                      <p className="text-gray-900">{selectedRepeater.documentName}</p>
                      <p className="text-gray-500">Repeater Proof Document</p>
                    </div>
                  </div>
                  <button 
                    onClick={() => handleDownloadDocument(selectedRepeater.id)}
                    className="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                  >
                    <Download className="w-4 h-4" />
                    Download
                  </button>
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => handleApprove(selectedRepeater.id)}
                disabled={actionLoading === selectedRepeater.id}
                className="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 cursor-pointer"
              >
                {actionLoading === selectedRepeater.id ? (
                  <Loader2 className="w-5 h-5 animate-spin" />
                ) : (
                  <Check className="w-5 h-5" />
                )}
                Approve Application
              </button>
              <button
                onClick={() => handleReject(selectedRepeater.id)}
                disabled={actionLoading === selectedRepeater.id}
                className="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 cursor-pointer"
              >
                {actionLoading === selectedRepeater.id ? (
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
