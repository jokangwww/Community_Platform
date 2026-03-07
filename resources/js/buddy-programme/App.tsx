import { useState, useEffect, lazy, Suspense } from 'react';
import { Users, UserCheck, Clock, Settings, Loader2, CheckCircle, XCircle, Calendar } from 'lucide-react';

// Lazy load heavy components
const RoleSelection = lazy(() => import('./components/RoleSelection').then(m => ({ default: m.RoleSelection })));
const RegistrationForm = lazy(() => import('./components/RegistrationForm').then(m => ({ default: m.RegistrationForm })));
const AdminDashboard = lazy(() => import('./components/Admin/AdminDashboard').then(m => ({ default: m.AdminDashboard })));
const MatchingDashboard = lazy(() => import('./components/Admin/MatchingDashboard').then(m => ({ default: m.MatchingDashboard })));
const WaitingList = lazy(() => import('./components/Admin/WaitingList').then(m => ({ default: m.WaitingList })));
const MenteeDashboard = lazy(() => import('./components/MenteeDashboard').then(m => ({ default: m.MenteeDashboard })));
const MentorDashboard = lazy(() => import('./components/Mentor/MentorDashboard').then(m => ({ default: m.MentorDashboard })));

// Loading fallback component
const LoadingSpinner = () => (
  <div className="flex items-center justify-center min-h-100">
    <div className="text-center">
      <Loader2 className="w-12 h-12 text-blue-600 mx-auto mb-4 animate-spin" />
      <p className="text-gray-600">Loading...</p>
    </div>
  </div>
);

type View = 'register' | 'admin' | 'matching' | 'waitlist';
type Role = 'mentor' | 'mentee' | null;

// Auth user from Laravel
interface SemesterSetting {
  academic_year: string;
  semester: number;
  start_date: string;
  end_date: string;
}

interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: string;
  student_id: string | null;
  is_admin: boolean;
}

declare global {
  interface Window {
    authUser: AuthUser | null;
  }
}

interface RegistrationStatus {
  registered: boolean;
  id?: number;
  full_name?: string;
  student_id?: string;
  role?: 'mentor' | 'mentee';
  status?: string;
  priority_tier?: string;
  is_repeater?: boolean;
  has_active_match?: boolean;
  match_id?: number;
  waitlist_position?: number;
  subject?: {
    id: number;
    name: string;
    type: string;
  };
}

export default function App() {
  // Get authenticated user from Laravel
  const authUser = window.authUser;
  const isAdmin = authUser?.is_admin ?? false;

  // If admin, default to admin view; otherwise register view
  const [currentView, setCurrentView] = useState<View>(isAdmin ? 'admin' : 'register');
  const [selectedRole, setSelectedRole] = useState<Role>(null);
  const [showRegistration, setShowRegistration] = useState(false);
  
  // Registration status check
  const [isCheckingStatus, setIsCheckingStatus] = useState(false);
  const [registrationStatus, setRegistrationStatus] = useState<RegistrationStatus | null>(null);
  const [hasCheckedStatus, setHasCheckedStatus] = useState(false);

  // Registration settings
  const [isRegistrationEnabled, setIsRegistrationEnabled] = useState<boolean | null>(null);
  const [isLoadingSettings, setIsLoadingSettings] = useState(true);
  const [isEvaluationEnabled, setIsEvaluationEnabled] = useState(false);
  const [semesterInfo, setSemesterInfo] = useState<SemesterSetting | null>(null);

  // Fetch settings on load (always — header needs semester info for all roles)
  useEffect(() => {
    fetchSettings();
  }, [isAdmin]);

  // Auto-check status for all authenticated non-admin users (resolved from auth session in middleware)
  useEffect(() => {
    if (!isAdmin && !hasCheckedStatus) {
      checkRegistrationStatus();
    }
  }, [isAdmin]);

  const fetchSettings = async () => {
    try {
      setIsLoadingSettings(true);
      const [settingsResponse, semesterResponse] = await Promise.all([
        fetch('/api/buddy/admin/settings'),
        fetch('/api/buddy/semester-info'),  // auth-only, no admin required
      ]);
      const result = await settingsResponse.json();
      const semResult = await semesterResponse.json();
      
      if (result.success && result.data) {
        // The API returns settings as an object with registration_open key
        const registrationOpen = result.data.registration_open;
        setIsRegistrationEnabled(registrationOpen === true || registrationOpen === 1);
        
        // Also store evaluation_enabled setting
        const evaluationEnabled = result.data.evaluation_enabled;
        setIsEvaluationEnabled(evaluationEnabled === true || evaluationEnabled === 1);
      }
      
      if (semResult.success && semResult.data) {
        setSemesterInfo(semResult.data);
      }
    } catch (err) {
      console.error('Failed to fetch settings:', err);
      setIsRegistrationEnabled(true); // Default to enabled if fetch fails
    } finally {
      setIsLoadingSettings(false);
    }
  };

  const checkRegistrationStatus = async () => {
    try {
      setIsCheckingStatus(true);
      const response = await fetch('/api/buddy/status');
      const result = await response.json();
      
      if (result.success) {
        setRegistrationStatus(result.data);
      }
      // Always mark checked regardless of outcome so user isn't stuck on loading
      setHasCheckedStatus(true);
    } catch (err) {
      console.error('Failed to check registration status:', err);
      setHasCheckedStatus(true);
    } finally {
      setIsCheckingStatus(false);
    }
  };

  const handleRoleSelect = (role: Role) => {
    setSelectedRole(role);
    setShowRegistration(true);
  };

  const handleBackToSelection = () => {
    setShowRegistration(false);
    setSelectedRole(null);
    setCurrentView('register');
  };

  // Render the registration section based on status
  const renderRegisterContent = () => {
    // Show loading while checking settings
    if (isLoadingSettings) {
      return (
        <div className="max-w-md mx-auto">
          <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <Loader2 className="w-12 h-12 text-blue-600 mx-auto mb-4 animate-spin" />
            <p className="text-gray-600">Loading...</p>
          </div>
        </div>
      );
    }

    // Step 1: Show loading while auto-checking status
    if (!hasCheckedStatus) {
      return (
        <div className="max-w-md mx-auto">
          <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <Loader2 className="w-12 h-12 text-blue-600 mx-auto mb-4 animate-spin" />
            <p className="text-gray-600">{isCheckingStatus ? 'Checking your registration status...' : 'Loading...'}</p>
          </div>
        </div>
      );
    }

    // Step 2: If registered and approved with active match, go to dashboard
    if (registrationStatus?.registered && registrationStatus.status === 'active' && registrationStatus.has_active_match) {
      // Mentors go to MentorDashboard, mentees go to MenteeDashboard
      if (registrationStatus.role === 'mentor') {
        return (
          <Suspense fallback={<LoadingSpinner />}>
            <MentorDashboard 
              studentId={registrationStatus.student_id || authUser?.student_id || ''} 
            />
          </Suspense>
        );
      } else if (registrationStatus.role === 'mentee') {
        return (
          <Suspense fallback={<LoadingSpinner />}>
            <MenteeDashboard 
              studentId={registrationStatus.student_id || authUser?.student_id || ''}
            />
          </Suspense>
        );
      }
    }

    // Step 2.5: If registered and approved but NO active match, show waiting page
    if (registrationStatus?.registered && registrationStatus.status === 'active' && !registrationStatus.has_active_match) {
      return (
        <div className="max-w-2xl mx-auto">
          <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Clock className="w-8 h-8 text-blue-600" />
            </div>
            
            <h2 className="text-xl font-semibold text-gray-900 mb-3">Waiting for Match</h2>
            
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <p className="text-blue-800">
                Your registration has been approved, and you are currently in the waiting list for matching.
              </p>
            </div>
            
            <div className="text-gray-600 space-y-3">
              <p>
                {registrationStatus.role === 'mentor' 
                  ? 'We are working on finding suitable mentees for you based on your expertise and availability. Please check back regularly for updates.'
                  : 'We are working on finding a suitable mentor for you based on your needs and subject. Please check back regularly for updates.'}
              </p>
            </div>

            <div className="mt-6 p-4 bg-gray-50 rounded-lg text-left">
              <p className="text-gray-700 font-medium mb-2">Your Profile:</p>
              <ul className="space-y-1 text-gray-600">
                <li>Name: <span className="font-medium">{registrationStatus.full_name}</span></li>
                <li>Student ID: <span className="font-medium">{registrationStatus.student_id}</span></li>
                <li>Role: <span className="capitalize font-medium">{registrationStatus.role}</span></li>
                <li>Status: <span className="capitalize font-medium text-green-600">{registrationStatus.status}</span></li>
              </ul>
            </div>

            <div className="mt-6">
              <button
                onClick={() => window.location.href = '/home'}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
              >
                Back to Home
              </button>
            </div>
          </div>
        </div>
      );
    }

    // Step 3: Show registration status if already registered but not approved yet
    if (registrationStatus?.registered) {
      return (
        <div className="max-w-2xl mx-auto">
          <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle className="w-8 h-8 text-green-600" />
            </div>
            
            <h2 className="text-xl font-semibold text-gray-900 mb-3">Registration Submitted!</h2>
            
            {registrationStatus.role === 'mentor' ? (
              <div className="space-y-3">
                <p className="text-gray-600">
                  Your mentor registration has been submitted for verification.
                </p>
                <div className={`border rounded-lg p-4 ${
                  registrationStatus.status === 'pending' 
                    ? 'bg-amber-50 border-amber-200' 
                    : registrationStatus.status === 'active'
                      ? 'bg-green-50 border-green-200'
                      : 'bg-red-50 border-red-200'
                }`}>
                  <p className={`${
                    registrationStatus.status === 'pending' 
                      ? 'text-amber-800' 
                      : registrationStatus.status === 'active'
                        ? 'text-green-800'
                        : 'text-red-800'
                  }`}>
                    Your profile is currently <strong className="capitalize">{registrationStatus.status}</strong>. 
                    {registrationStatus.status === 'pending' && ' An admin will review your qualification documents and approve your registration.'}
                    {registrationStatus.status === 'active' && ' You can now be matched with mentees.'}
                    {registrationStatus.status === 'rejected' && ' Please contact the admin for more information.'}
                  </p>
                </div>
              </div>
            ) : (
              <div className="space-y-3">
                <p className="text-gray-600">
                  Your mentee registration has been successfully processed.
                </p>
                {registrationStatus.is_repeater ? (
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p className="text-blue-800">
                      As a verified repeater, you have been given <strong>priority allocation</strong> ({registrationStatus.priority_tier} priority) in the matching process.
                    </p>
                  </div>
                ) : (
                  <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p className="text-green-800">
                      Your profile is now <strong className="capitalize">{registrationStatus.status}</strong>. You will be matched with a mentor on a first-come, first-served basis.
                    </p>
                  </div>
                )}
              </div>
            )}

            <div className="mt-6 p-4 bg-gray-50 rounded-lg text-left">
              <p className="text-gray-700 font-medium mb-2">Registration Summary:</p>
              <ul className="space-y-1 text-gray-600">
                <li>Name: <span className="font-medium">{registrationStatus.full_name}</span></li>
                <li>Student ID: <span className="font-medium">{registrationStatus.student_id}</span></li>
                <li>Role: <span className="capitalize font-medium">{registrationStatus.role}</span></li>
                <li>Status: <span className="capitalize font-medium">{registrationStatus.status}</span></li>
                {registrationStatus.is_repeater && (
                  <li>Priority Tier: <span className="capitalize font-medium">{registrationStatus.priority_tier}</span> (Repeater)</li>
                )}
              </ul>
            </div>

            <div className="mt-6 flex gap-3 justify-center">
              <button
                onClick={() => window.location.href = '/home'}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
              >
                Back to Home
              </button>
            </div>
          </div>
        </div>
      );
    }

    // Step 4: Show registration closed page if registration is disabled
    if (!isRegistrationEnabled && !showRegistration) {
      return (
        <div className="max-w-2xl mx-auto">
          <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <XCircle className="w-10 h-10 text-red-500" />
            </div>
            
            <h2 className="text-2xl font-semibold text-gray-900 mb-3">Registration Phase Ended</h2>
            
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
              <div className="flex items-center justify-center gap-2 text-amber-800">
                <Calendar className="w-5 h-5" />
                <p className="font-medium">The registration period for the Buddy Programme has closed.</p>
              </div>
            </div>
            
            <div className="text-gray-600 space-y-3">
              <p>
                Thank you for your interest in the Buddy Programme. Unfortunately, the registration phase has ended and we are no longer accepting new registrations at this time.
              </p>
              <p>
                If you have already registered, you can check your registration status by entering your Student ID.
              </p>
            </div>

            <div className="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-blue-800 text-sm">
                <strong>Note:</strong> If you believe you should have access or have any questions, please contact the programme administrator.
              </p>
            </div>

            <div className="mt-6">
              <button
                onClick={() => window.location.href = '/home'}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
              >
                Back to Home
              </button>
            </div>
          </div>
        </div>
      );
    }

    // Step 5: Show role selection if not registered and registration is enabled
    if (!showRegistration) {
      return (
        <Suspense fallback={<LoadingSpinner />}>
          <RoleSelection onRoleSelect={handleRoleSelect} />
        </Suspense>
      );
    }

    // Step 6: Show registration form
    return (
      <Suspense fallback={<LoadingSpinner />}>
        <RegistrationForm 
          role={selectedRole} 
          onBack={handleBackToSelection}
        />
      </Suspense>
    );
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 style={{ fontSize: '1.5rem', fontWeight: 500, lineHeight: '1.2', color: '#111827' }}>Buddy Programme</h1>
              <p className="text-sm text-gray-600">Mentor-Mentee Matching System</p>
            </div>
            
            <div className="flex items-center gap-4">
              {/* View Switcher */}
              <div className="flex gap-2">
                {/* Show Register button only for non-admin users not yet on the dashboard */}
                {!isAdmin && !(registrationStatus?.status === 'active' && registrationStatus?.has_active_match) && (
                  <button
                    onClick={() => {
                      setCurrentView('register');
                      setShowRegistration(false);
                      setSelectedRole(null);
                      // Reset status check when navigating to register
                      setRegistrationStatus(null);
                      setHasCheckedStatus(false);
                    }}
                    className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
                      currentView === 'register'
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-300'
                    }`}
                  >
                    <Users className="w-4 h-4" />
                    Register
                  </button>
                )}

                {/* Semester info */}
                {!isAdmin && registrationStatus?.status === 'active' && registrationStatus?.has_active_match && semesterInfo && (
                  <div className="flex flex-col px-4 py-2">
                    <h1 style={{ fontSize: '1.5rem', fontWeight: 500, lineHeight: '1.2', color: '#111827' }}>{semesterInfo.academic_year} &nbsp; Semester {semesterInfo.semester}</h1>
                    <p className="text-sm text-gray-600">{semesterInfo.start_date} &mdash; {semesterInfo.end_date}</p>
                  </div>
                )}

                {/* Admin-only navigation buttons */}
                {isAdmin && (
                  <>
                    <button
                      onClick={() => setCurrentView('matching')}
                      className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
                        currentView === 'matching'
                          ? 'bg-blue-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-300 cursor-pointer'
                      }`}
                    >
                      <UserCheck className="w-4 h-4" />
                      Matches
                    </button>
                    <button
                      onClick={() => setCurrentView('waitlist')}
                      className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
                        currentView === 'waitlist'
                          ? 'bg-blue-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-300 cursor-pointer'
                      }`}
                    >
                      <Clock className="w-4 h-4" />
                      Waiting List
                    </button>
                    <button
                      onClick={() => setCurrentView('admin')}
                      className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
                        currentView === 'admin'
                          ? 'bg-blue-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-300 cursor-pointer'
                      }`}
                    >
                      <Settings className="w-4 h-4" />
                      Admin
                    </button>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {currentView === 'register' && renderRegisterContent()}

        {currentView === 'admin' && (
          <Suspense fallback={<LoadingSpinner />}>
            <AdminDashboard />
          </Suspense>
        )}
        {currentView === 'matching' && (
          <Suspense fallback={<LoadingSpinner />}>
            <MatchingDashboard />
          </Suspense>
        )}
        {currentView === 'waitlist' && (
          <Suspense fallback={<LoadingSpinner />}>
            <WaitingList />
          </Suspense>
        )}
      </main>
    </div>
  );
}