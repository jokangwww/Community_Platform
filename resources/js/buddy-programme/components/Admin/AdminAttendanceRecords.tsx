import { useState, useEffect } from 'react';
import { Calendar, Loader2 } from 'lucide-react';

interface CheckInRecord {
  id: string;
  sessionDate: string;
  sessionTopic: string;
  mentorName: string;
  menteeName: string;
  mentorCheckInTime: string;
  menteeCheckInTime: string;
  groupSubject: string;
  status: 'present' | 'partial' | 'absent';
}

export function AdminAttendanceRecords() {
  const [checkInRecords, setCheckInRecords] = useState<CheckInRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchCheckInRecords = async () => {
    try {
      const response = await fetch('/api/buddy/admin/check-in-records', {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setCheckInRecords(result.data);
      }
    } catch (error) {
      console.error('Failed to fetch check-in records:', error);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await fetchCheckInRecords();
      setIsLoading(false);
    };
    loadData();
  }, []);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-3 text-gray-600">Loading attendance records...</span>
      </div>
    );
  }

  return (
    <>
      <div className="mb-6">
        <h2 className="text-gray-900">Session Check-In Records</h2>
        <p className="text-gray-600">View all mentor and mentee check-ins with timestamps</p>
      </div>

      <div className="space-y-3">
        {checkInRecords.length === 0 ? (
          <div className="text-center py-12">
            <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Calendar className="w-8 h-8 text-gray-400" />
            </div>
            <p className="text-gray-600">No check-in records found</p>
          </div>
        ) : checkInRecords.map(record => (
          <div
            key={record.id}
            className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
          >
            <div className="flex items-start justify-between gap-4 mb-3">
              <div className="flex-1">
                <div className="flex items-center gap-3 mb-2">
                  <h4 className="text-gray-900">{record.sessionTopic}</h4>
                  <span className={`px-2 py-1 rounded text-white ${
                    record.status === 'present'
                      ? 'bg-green-600'
                      : record.status === 'partial'
                        ? 'bg-amber-600'
                        : 'bg-red-600'
                  }`}>
                    {record.status === 'present' ? 'Both Checked In' : record.status === 'partial' ? 'Partial' : 'Absent'}
                  </span>
                  <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                    {record.groupSubject}
                  </span>
                </div>
                <div className="flex items-center gap-2 text-gray-600 mb-3">
                  <Calendar className="w-4 h-4" />
                  <span>{record.sessionDate}</span>
                </div>
              </div>
            </div>

            <div className="grid md:grid-cols-2 gap-3">
              {/* Mentor Check-in */}
              <div className={`p-3 rounded-lg border ${
                record.mentorCheckInTime
                  ? 'bg-green-50 border-green-200'
                  : 'bg-gray-50 border-gray-200'
              }`}>
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">
                    M
                  </div>
                  <div>
                    <p className="text-gray-900">Mentor</p>
                    <p className="text-gray-600">{record.mentorName}</p>
                  </div>
                </div>
                {record.mentorCheckInTime ? (
                  <p className="text-gray-600 ml-10">
                    Checked in: {record.mentorCheckInTime}
                  </p>
                ) : (
                  <p className="text-gray-500 ml-10">Not checked in</p>
                )}
              </div>

              {/* Mentee Check-in */}
              <div className={`p-3 rounded-lg border ${
                record.menteeCheckInTime
                  ? 'bg-green-50 border-green-200'
                  : 'bg-gray-50 border-gray-200'
              }`}>
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center">
                    M
                  </div>
                  <div>
                    <p className="text-gray-900">Mentee</p>
                    <p className="text-gray-600">{record.menteeName}</p>
                  </div>
                </div>
                {record.menteeCheckInTime ? (
                  <p className="text-gray-600 ml-10">
                    Checked in: {record.menteeCheckInTime}
                  </p>
                ) : (
                  <p className="text-gray-500 ml-10">Not checked in</p>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>
    </>
  );
}
