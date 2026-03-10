import { Users, UserCheck } from 'lucide-react';

interface RoleSelectionForViewProps {
  /** Called when user picks a role to view their archived record */
  onRoleSelect: (role: 'mentor' | 'mentee') => void;
}

/**
 * Shown to users who have mixed participation history (both mentor and mentee
 * across semesters) with no active semester. They pick a role to view read-only records.
 */
export function RoleSelectionForView({ onRoleSelect }: RoleSelectionForViewProps) {
  return (
    <div className="max-w-md mx-auto">
      <div className="bg-white rounded-xl border border-gray-200 p-6 text-center">
        <h2 className="text-lg font-semibold text-gray-900 mb-2">View Your Past Records</h2>
        <p className="text-sm text-gray-500 mb-6">
          You have participated in different roles. Select a role to view your archived records.
        </p>

        <div className="flex gap-4">
          <button
            onClick={() => onRoleSelect('mentee')}
            className="flex-1 flex flex-col items-center gap-3 p-5 border-2 border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer"
          >
            <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <Users className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p className="font-medium text-gray-900">Mentee</p>
              <p className="text-xs text-gray-500 mt-0.5">View mentee records</p>
            </div>
          </button>

          <button
            onClick={() => onRoleSelect('mentor')}
            className="flex-1 flex flex-col items-center gap-3 p-5 border-2 border-gray-200 rounded-xl hover:border-green-400 hover:bg-green-50 transition-colors cursor-pointer"
          >
            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <UserCheck className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <p className="font-medium text-gray-900">Mentor</p>
              <p className="text-xs text-gray-500 mt-0.5">View mentor records</p>
            </div>
          </button>
        </div>
      </div>
    </div>
  );
}
