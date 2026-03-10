import { useState, useEffect } from 'react';
import { Award, CheckCircle, XCircle, Clock, Eye, Download, Loader2, AlertCircle } from 'lucide-react';

interface PendingTestimonial {
  id: string;
  mentorName: string;
  mentorId: string;
  faculty: string;
  programme: string;
  totalSessions: number;
  totalMentees: number;
  skillsTaught: string[];
  avgFeedbackScore: number;
  attendanceRate: number;
  semesterYear: string;
  requestDate: string;
  status: 'pending' | 'approved' | 'rejected';
}

interface Stats {
  pendingCount: number;
  approvedCount: number;
  rejectedCount: number;
}

export function AdminTestimonialManagement() {
  const [testimonials, setTestimonials] = useState<PendingTestimonial[]>([]);
  const [stats, setStats] = useState<Stats>({ pendingCount: 0, approvedCount: 0, rejectedCount: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedTestimonial, setSelectedTestimonial] = useState<PendingTestimonial | null>(null);
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    fetchTestimonials();
  }, []);

  const fetchTestimonials = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch('/api/buddy/testimonials');
      const result = await response.json();

      if (result.success) {
        setTestimonials(result.data.testimonials);
        setStats(result.data.stats);
      } else {
        setError(result.message || 'Failed to fetch testimonials');
      }
    } catch (err) {
      setError('Failed to load testimonials. Please try again.');
      console.error('Error fetching testimonials:', err);
    } finally {
      setLoading(false);
    }
  };

  const approveTestimonial = async (id: string) => {
    try {
      setProcessing(true);
      const response = await fetch(`/api/buddy/testimonials/${id}/approve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      const result = await response.json();

      if (result.success) {
        setTestimonials(testimonials.map(t => 
          t.id === id ? { ...t, status: 'approved' as const } : t
        ));
        setStats(prev => ({
          ...prev,
          pendingCount: prev.pendingCount - 1,
          approvedCount: prev.approvedCount + 1,
        }));
        alert('Testimonial approved successfully!');
        setSelectedTestimonial(null);
      } else {
        alert(result.message || 'Failed to approve testimonial');
      }
    } catch (err) {
      console.error('Error approving testimonial:', err);
      alert('Failed to approve testimonial. Please try again.');
    } finally {
      setProcessing(false);
    }
  };

  const rejectTestimonial = async (id: string) => {
    const reason = prompt('Enter reason for rejection (optional):');
    
    try {
      setProcessing(true);
      const response = await fetch(`/api/buddy/testimonials/${id}/reject`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ reason }),
      });

      const result = await response.json();

      if (result.success) {
        setTestimonials(testimonials.map(t => 
          t.id === id ? { ...t, status: 'rejected' as const } : t
        ));
        setStats(prev => ({
          ...prev,
          pendingCount: prev.pendingCount - 1,
          rejectedCount: prev.rejectedCount + 1,
        }));
        alert('Testimonial rejected.');
        setSelectedTestimonial(null);
      } else {
        alert(result.message || 'Failed to reject testimonial');
      }
    } catch (err) {
      console.error('Error rejecting testimonial:', err);
      alert('Failed to reject testimonial. Please try again.');
    } finally {
      setProcessing(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading testimonials...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <AlertCircle className="w-8 h-8 text-red-600 mx-auto mb-2" />
        <p className="text-red-800">{error}</p>
        <button
          onClick={fetchTestimonials}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors cursor-pointer"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Statistics */}
      <div className="grid md:grid-cols-3 gap-4">
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Pending Review</p>
            <Clock className="w-5 h-5 text-amber-600" />
          </div>
          <p className="text-gray-900">{stats.pendingCount}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Approved</p>
            <CheckCircle className="w-5 h-5 text-green-600" />
          </div>
          <p className="text-gray-900">{stats.approvedCount}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Rejected</p>
            <XCircle className="w-5 h-5 text-red-600" />
          </div>
          <p className="text-gray-900">{stats.rejectedCount}</p>
        </div>
      </div>

      {/* Testimonial List */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h3 className="text-gray-900 mb-4">Testimonial Requests</h3>
        
        {testimonials.length === 0 ? (
          <div className="text-center py-8 text-gray-500">
            No testimonial requests found
          </div>
        ) : (
        <div className="space-y-3">
          {testimonials.map(testimonial => (
            <div
              key={testimonial.id}
              className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <Award className="w-5 h-5 text-blue-600" />
                    <div>
                      <h4 className="text-gray-900">{testimonial.mentorName}</h4>
                      <p className="text-gray-600">{testimonial.mentorId}</p>
                    </div>
                    <span className={`px-3 py-1 rounded cursor-pointer ${
                      testimonial.status === 'approved' 
                        ? 'bg-green-100 text-green-800'
                        : testimonial.status === 'rejected'
                        ? 'bg-red-100 text-red-800'
                        : 'bg-amber-100 text-amber-800'
                    }`}>
                      {testimonial.status.charAt(0).toUpperCase() + testimonial.status.slice(1)}
                    </span>
                  </div>

                  <div className="grid md:grid-cols-4 gap-4 text-gray-700 mb-3">
                    <div>
                      <p className="text-gray-600">Faculty</p>
                      <p className="text-gray-900">{testimonial.faculty}</p>
                    </div>
                    <div>
                      <p className="text-gray-600">Sessions</p>
                      <p className="text-gray-900">{testimonial.totalSessions}</p>
                    </div>
                    <div>
                      <p className="text-gray-600">Mentees</p>
                      <p className="text-gray-900">{testimonial.totalMentees}</p>
                    </div>
                    <div>
                      <p className="text-gray-600">Avg. Rating</p>
                      <p className="text-gray-900">{testimonial.avgFeedbackScore.toFixed(1)}/5.0</p>
                    </div>
                  </div>

                  <p className="text-gray-600">Requested: {testimonial.requestDate}</p>
                </div>

                <div className="flex gap-2">
                  <button
                    onClick={() => setSelectedTestimonial(testimonial)}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2 cursor-pointer"
                  >
                    <Eye className="w-4 h-4" />
                    Review
                  </button>
                  {testimonial.status === 'pending' && (
                    <>
                      <button
                        onClick={() => approveTestimonial(testimonial.id)}
                        className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors cursor-pointer"
                      >
                        <CheckCircle className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => rejectTestimonial(testimonial.id)}
                        className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors cursor-pointer"
                      >
                        <XCircle className="w-4 h-4" />
                      </button>
                    </>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
        )}
      </div>

      {/* Preview Modal */}
      {selectedTestimonial && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setSelectedTestimonial(null)}
        >
          <div
            className="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
            onClick={e => e.stopPropagation()}
          >
            <div className="sticky top-0 bg-white border-b border-gray-200 p-6 flex items-center justify-between">
              <h3 className="text-gray-900">Testimonial Preview</h3>
              <button
                onClick={() => setSelectedTestimonial(null)}
                className="text-gray-600 hover:text-gray-900 cursor-pointer"
              >
                <XCircle className="w-6 h-6" />
              </button>
            </div>

            <div className="p-8">
              {/* Testimonial Preview Content */}
              <div className="border-2 border-gray-300 rounded-xl p-8 md:p-12">
                <div className="text-center mb-8 pb-6 border-b-2 border-gray-200">
                  <div className="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Award className="w-12 h-12 text-white" />
                  </div>
                  <h2 className="text-gray-900 mb-2">Certificate of Contribution</h2>
                  <p className="text-gray-700">TARUMT Buddy Programme</p>
                  <p className="text-gray-600">{selectedTestimonial.semesterYear}</p>
                </div>

                <div className="space-y-6 mb-8">
                  <p className="text-center text-gray-700">This is to certify that</p>
                  
                  <div className="text-center">
                    <p className="text-gray-900 mb-1">{selectedTestimonial.mentorName}</p>
                    <p className="text-gray-600">Student ID: {selectedTestimonial.mentorId}</p>
                    <p className="text-gray-600">{selectedTestimonial.programme}, {selectedTestimonial.faculty}</p>
                  </div>

                  <p className="text-center text-gray-700">
                    has successfully served as a <span className="text-gray-900">Mentor</span> in the TARUMT Buddy Programme
                  </p>

                  {/* Contribution Summary */}
                  <div className="bg-gray-50 rounded-lg p-6">
                    <p className="text-gray-900 text-center mb-4">Contribution Summary:</p>
                    
                    <div className="grid md:grid-cols-2 gap-4">
                      <div className="text-center">
                        <p className="text-gray-600">Sessions Conducted</p>
                        <p className="text-gray-900">{selectedTestimonial.totalSessions} weeks</p>
                      </div>
                      <div className="text-center">
                        <p className="text-gray-600">Mentees Guided</p>
                        <p className="text-gray-900">{selectedTestimonial.totalMentees} students</p>
                      </div>
                      <div className="text-center">
                        <p className="text-gray-600">Skills Taught</p>
                        <p className="text-gray-900">{selectedTestimonial.skillsTaught.join(', ')}</p>
                      </div>
                      <div className="text-center">
                        <p className="text-gray-600">Average Rating</p>
                        <p className="text-gray-900">{selectedTestimonial.avgFeedbackScore.toFixed(1)}/5.0</p>
                      </div>
                    </div>
                  </div>

                  <p className="text-center text-gray-700">
                    This certificate acknowledges their dedication, leadership, and commitment to supporting fellow students throughout the semester.
                  </p>
                </div>


                <div className="pt-6 border-t-2 border-gray-200 text-center text-gray-600">
                  <p>Verification Code: TARUMT-BP-{selectedTestimonial.mentorId}-2025</p>
                </div>
              </div>

              {selectedTestimonial.status === 'pending' && (
                <div className="flex justify-center gap-3 mt-6">
                  <button
                    onClick={() => rejectTestimonial(selectedTestimonial.id)}
                    disabled={processing}
                    className="px-6 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                  >
                    {processing ? 'Processing...' : 'Reject'}
                  </button>
                  <button
                    onClick={() => approveTestimonial(selectedTestimonial.id)}
                    disabled={processing}
                    className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                  >
                    {processing ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <CheckCircle className="w-4 h-4" />
                    )}
                    {processing ? 'Processing...' : 'Approve & Release'}
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}