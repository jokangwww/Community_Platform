import { useState, useEffect } from 'react';
import { Clock, Star, TrendingUp, AlertCircle, Loader2 } from 'lucide-react';

interface WaitingListEntry {
  id: string;
  name: string;
  studentId: string;
  faculty: string;
  course: string;
  cgpa: number;
  subject: string;
  registeredDate: string;
  position: number;
  isRepeater: boolean;
  rating: number;
  priorityTier: 'high' | 'normal' | 'low';
}

export function WaitingList() {
  const [waitingList, setWaitingList] = useState<WaitingListEntry[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchWaitingList();
  }, []);

  const fetchWaitingList = async () => {
    try {
      setIsLoading(true);
      const response = await fetch('/api/buddy/admin/waiting-list');
      const data = await response.json();
      
      if (data.success) {
        setWaitingList(data.data);
      } else {
        setError('Failed to load waiting list');
      }
    } catch (err) {
      setError('Failed to connect to server');
      console.error('Error fetching waiting list:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const highPriority = waitingList.filter(e => e.priorityTier === 'high');
  const normalPriority = waitingList.filter(e => e.priorityTier === 'normal');
  const lowPriority = waitingList.filter(e => e.priorityTier === 'low');

  const getPriorityBadge = (tier: string) => {
    switch (tier) {
      case 'high':
        return <span className="px-2 py-1 bg-amber-100 text-amber-800 rounded">High</span>;
      case 'normal':
        return <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">Normal</span>;
      case 'low':
        return <span className="px-2 py-1 bg-gray-100 text-gray-800 rounded">Low</span>;
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-2 text-gray-600">Loading waiting list...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <AlertCircle className="w-8 h-8 text-red-600 mx-auto mb-2" />
        <p className="text-red-800">{error}</p>
        <button
          onClick={fetchWaitingList}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 cursor-pointer"
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
            <p className="text-gray-600">Total Waiting</p>
            <Clock className="w-5 h-5 text-gray-600" />
          </div>
          <p className="text-gray-900">{waitingList.length}</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">High Priority</p>
            <Star className="w-5 h-5 text-amber-600" />
          </div>
          <p className="text-gray-900">{highPriority.length} (Repeaters)</p>
        </div>

        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-2">
            <p className="text-gray-600">Deprioritized</p>
            <TrendingUp className="w-5 h-5 text-red-600" />
          </div>
          <p className="text-gray-900">{lowPriority.length} (Rating &lt; 3.0)</p>
        </div>
      </div>

      {/* Waiting List */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <div className="mb-6">
          <h2 className="text-gray-900 mb-2">Mentee Waiting List</h2>
          <p className="text-gray-600">
            Students waiting to be matched with mentors, ordered by priority and registration time
          </p>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-3 px-4 text-gray-700">#</th>
                <th className="text-left py-3 px-4 text-gray-700">Name</th>
                <th className="text-left py-3 px-4 text-gray-700">Student ID</th>
                <th className="text-left py-3 px-4 text-gray-700">Priority</th>
                <th className="text-left py-3 px-4 text-gray-700">Faculty</th>
                <th className="text-left py-3 px-4 text-gray-700">Course</th>
                <th className="text-left py-3 px-4 text-gray-700">Subject</th>
                <th className="text-left py-3 px-4 text-gray-700">CGPA</th>
                <th className="text-left py-3 px-4 text-gray-700">Rating</th>
                <th className="text-left py-3 px-4 text-gray-700">Status</th>
                <th className="text-left py-3 px-4 text-gray-700">Registered</th>
              </tr>
            </thead>
            <tbody>
              {highPriority.map(entry => (
                <tr key={entry.id} className="border-b border-gray-100 hover:bg-amber-50">
                  <td className="py-3 px-4">
                    <div className="w-8 h-8 bg-[rgb(255,255,255)] text-[rgb(17,17,17)] rounded-full flex items-center justify-center">
                      {entry.position}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-gray-900">{entry.name}</td>
                  <td className="py-3 px-4 text-gray-600">{entry.studentId}</td>
                  <td className="py-3 px-4">{getPriorityBadge(entry.priorityTier)}</td>
                  <td className="py-3 px-4 text-gray-900">{entry.faculty}</td>
                  <td className="py-3 px-4 text-gray-900">{entry.course}</td>
                  <td className="py-3 px-4">
                    <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">{entry.subject}</span>
                  </td>
                  <td className="py-3 px-4 text-gray-900">{Number(entry.cgpa).toFixed(2)}</td>
                  <td className="py-3 px-4 text-gray-900">{Number(entry.rating).toFixed(1)}</td>
                  <td className="py-3 px-4">
                    {entry.isRepeater && (
                      <span className="px-2 py-1 bg-amber-200 text-amber-900 rounded flex items-center gap-1 w-fit">
                        <Star className="w-3 h-3" />
                        Repeater
                      </span>
                    )}
                  </td>
                  <td className="py-3 px-4 text-gray-600">{entry.registeredDate}</td>
                </tr>
              ))}
              {normalPriority.map(entry => (
                <tr key={entry.id} className="border-b border-gray-100 hover:bg-blue-50">
                  <td className="py-3 px-4">
                    <div className="w-8 h-8 bg-[rgba(47,47,47,0)] text-[rgb(6,6,6)] rounded-full flex items-center justify-center">
                      {entry.position}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-gray-900">{entry.name}</td>
                  <td className="py-3 px-4 text-gray-600">{entry.studentId}</td>
                  <td className="py-3 px-4">{getPriorityBadge(entry.priorityTier)}</td>
                  <td className="py-3 px-4 text-gray-900">{entry.faculty}</td>
                  <td className="py-3 px-4 text-gray-900">{entry.course}</td>
                  <td className="py-3 px-4">
                    <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">{entry.subject}</span>
                  </td>
                  <td className="py-3 px-4 text-gray-900">{Number(entry.cgpa).toFixed(2)}</td>
                  <td className="py-3 px-4 text-gray-900">{Number(entry.rating).toFixed(1)}</td>
                  <td className="py-3 px-4">-</td>
                  <td className="py-3 px-4 text-gray-600">{entry.registeredDate}</td>
                </tr>
              ))}
              {lowPriority.map(entry => (
                <tr key={entry.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="py-3 px-4">
                    <div className="w-8 h-8 bg-[rgba(74,85,101,0)] text-[rgb(0,0,0)] rounded-full flex items-center justify-center">
                      {entry.position}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-gray-900">{entry.name}</td>
                  <td className="py-3 px-4 text-gray-600">{entry.studentId}</td>
                  <td className="py-3 px-4">{getPriorityBadge(entry.priorityTier)}</td>
                  <td className="py-3 px-4 text-gray-900">{entry.faculty}</td>
                  <td className="py-3 px-4 text-gray-900">{entry.course}</td>
                  <td className="py-3 px-4">
                    <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded">{entry.subject}</span>
                  </td>
                  <td className="py-3 px-4 text-gray-900">{Number(entry.cgpa).toFixed(2)}</td>
                  <td className="py-3 px-4 text-gray-900">{Number(entry.rating).toFixed(1)}</td>
                  <td className="py-3 px-4">
                    <span className="px-2 py-1 bg-red-100 text-red-800 rounded flex items-center gap-1 w-fit">
                      <AlertCircle className="w-3 h-3" />
                      Low Rating
                    </span>
                  </td>
                  <td className="py-3 px-4 text-gray-600">{entry.registeredDate}</td>
                </tr>
              ))}
              {waitingList.length === 0 && (
                <tr>
                  <td colSpan={11} className="py-8 text-center text-gray-500">
                    No mentees in waiting list
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Info Panel */}
      {/* <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 className="text-gray-900 mb-3">Waiting List Management</h3>
        <div className="space-y-2 text-gray-700">
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <p>Unmatched mentees are automatically placed in waiting list when mentor capacity is exceeded</p>
          </div>
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <p>List is ordered by priority status (repeater quota) and registration timestamp</p>
          </div>
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <p>System continuously monitors mentor availability and re-runs matching from top of list</p>
          </div>
          <div className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <p>Students receive notifications when matched from waiting list</p>
          </div>
        </div>
      </div> */}
    </div>
  );
}