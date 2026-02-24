import { GraduationCap, BookOpen } from 'lucide-react';

interface RoleSelectionProps {
  onRoleSelect: (role: 'mentor' | 'mentee') => void;
}

export function RoleSelection({ onRoleSelect }: RoleSelectionProps) {
  return (
    <div className="max-w-4xl mx-auto">
      <div className="text-center mb-12">
        <h2 className="text-gray-900 mb-3">Select Your Role</h2>
        <p className="text-gray-600">
          Choose whether you want to register as a Mentor or Mentee
        </p>
      </div>

      <div className="grid md:grid-cols-2 gap-6">
        {/* Mentor Card */}
        <button
          onClick={() => onRoleSelect('mentor')}
          className="bg-white rounded-xl border-2 border-gray-200 p-8 hover:border-blue-500 hover:shadow-lg transition-all text-left group"
        >
          <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6 group-hover:bg-blue-500 transition-colors">
            <GraduationCap className="w-8 h-8 text-blue-600 group-hover:text-white" />
          </div>
          
          <h3 className="text-gray-900 mb-3">Register as Mentor</h3>
          
          <p className="text-gray-600 mb-6">
            Share your knowledge and guide fellow students in their academic journey
          </p>

          <div className="space-y-2">
            <div className="flex items-start gap-2">
              <div className="w-1.5 h-1.5 bg-blue-600 rounded-full mt-2" />
              <p className="text-gray-700">Help students excel in subjects you master</p>
            </div>
            <div className="flex items-start gap-2">
              <div className="w-1.5 h-1.5 bg-blue-600 rounded-full mt-2" />
              <p className="text-gray-700">Build leadership and mentoring skills</p>
            </div>
            <div className="flex items-start gap-2">
              <div className="w-1.5 h-1.5 bg-blue-600 rounded-full mt-2" />
              <p className="text-gray-700">Requires verification of qualifications</p>
            </div>
          </div>
        </button>

        {/* Mentee Card */}
        <button
          onClick={() => onRoleSelect('mentee')}
          className="bg-white rounded-xl border-2 border-gray-200 p-8 hover:border-green-500 hover:shadow-lg transition-all text-left group"
        >
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6 group-hover:bg-green-500 transition-colors">
            <BookOpen className="w-8 h-8 text-green-600 group-hover:text-white" />
          </div>
          
          <h3 className="text-gray-900 mb-3">Register as Mentee</h3>
          
          <p className="text-gray-600 mb-6">
            Get personalized guidance and support to improve your academic performance
          </p>

          <div className="space-y-2">
            <div className="flex items-start gap-2">
              <div className="w-1.5 h-1.5 bg-green-600 rounded-full mt-2" />
              <p className="text-gray-700">Receive help in challenging subjects</p>
            </div>
            <div className="flex items-start gap-2">
              <div className="w-1.5 h-1.5 bg-green-600 rounded-full mt-2" />
              <p className="text-gray-700">Priority allocation for repeaters</p>
            </div>
            <div className="flex items-start gap-2">
              <div className="w-1.5 h-1.5 bg-green-600 rounded-full mt-2" />
              <p className="text-gray-700">Immediate activation upon registration</p>
            </div>
          </div>
        </button>
      </div>

      <div className="mt-8 bg-blue-50 rounded-lg p-6 border border-blue-200">
        <h4 className="text-gray-900 mb-2">Important Information</h4>
        <ul className="space-y-2 text-gray-700">
          <li className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <span>All registered students can choose to register as either a Mentor or Mentee</span>
          </li>
          <li className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <span>You cannot register for the same subject in both roles</span>
          </li>
          <li className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <span>Mentors require admin verification before being matched</span>
          </li>
          <li className="flex items-start gap-2">
            <span className="text-blue-600 mt-1">•</span>
            <span>Matching is done on a first-come, first-served basis</span>
          </li>
        </ul>
      </div>
    </div>
  );
}
