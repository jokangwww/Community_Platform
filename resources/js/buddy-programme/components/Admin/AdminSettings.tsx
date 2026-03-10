import { useState, useEffect } from 'react';
import { AlertTriangle, Loader2, Calendar, BookOpen, Clock } from 'lucide-react';

interface SemesterSetting {
  id: number;
  academic_year: string;
  semester: number;
  duration_type: string;
  total_weeks: number;
  start_date: string;
  end_date: string;
  is_active: boolean;
}

export function AdminSettings() {
  const [settingsLoading, setSettingsLoading] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [priorityAllocationEnabled, setPriorityAllocationEnabled] = useState(true);
  const [registrationOpen, setRegistrationOpen] = useState(true);
  const [evaluationEnabled, setEvaluationEnabled] = useState(false);
  const [testimonialEnabled, setTestimonialEnabled] = useState(false);
  const [confirmAction, setConfirmAction] = useState<{
    type: 'priority' | 'registration' | 'evaluation' | 'testimonial';
    newValue: boolean;
  } | null>(null);

  // Semester setting states
  const [semesterSetting, setSemesterSetting] = useState<SemesterSetting | null>(null);
  const [semAcademicYear, setSemAcademicYear] = useState('');
  const [semSemester, setSemSemester] = useState<number>(1);
  const [semDurationType, setSemDurationType] = useState<'long' | 'short'>('long');
  const [semStartDate, setSemStartDate] = useState('');
  const [semEndDate, setSemEndDate] = useState('');
  const [semesterSaving, setSemesterSaving] = useState(false);
  const [semesterUpdating, setSemesterUpdating] = useState(false);
  const [showNewSemesterConfirm, setShowNewSemesterConfirm] = useState(false);
  const [semesterMessage, setSemesterMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  };

  const fetchSettings = async () => {
    try {
      const response = await fetch('/api/buddy/admin/settings', {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setPriorityAllocationEnabled(result.data.priority_allocation_enabled ?? true);
        setRegistrationOpen(result.data.registration_open ?? true);
        setEvaluationEnabled(result.data.evaluation_enabled ?? false);
        setTestimonialEnabled(result.data.testimonial_enabled ?? false);
      }
    } catch (error) {
      console.error('Failed to fetch settings:', error);
    }
  };

  const fetchSemesterSetting = async () => {
    try {
      const response = await fetch('/api/buddy/admin/semester-setting', {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success && result.data) {
        setSemesterSetting(result.data);
        setSemAcademicYear(result.data.academic_year);
        setSemSemester(result.data.semester);
        setSemDurationType(result.data.duration_type as 'long' | 'short');
        setSemStartDate(result.data.start_date);
        setSemEndDate(result.data.end_date);
      }
    } catch (error) {
      console.error('Failed to fetch semester setting:', error);
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await Promise.all([fetchSettings(), fetchSemesterSetting()]);
      setIsLoading(false);
    };
    loadData();
  }, []);

  // Update a setting
  const updateSetting = async (key: string, value: boolean) => {
    setSettingsLoading(key);
    try {
      const response = await fetch('/api/buddy/admin/settings', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ key, value: value ? 'true' : 'false' })
      });
      
      // Check for non-OK response (like 419 CSRF error)
      if (!response.ok) {
        console.error('HTTP error:', response.status, response.statusText);
        // Revert the state on failure
        if (key === 'priority_allocation_enabled') {
          setPriorityAllocationEnabled(!value);
        } else if (key === 'registration_open') {
          setRegistrationOpen(!value);
        } else if (key === 'evaluation_enabled') {
          setEvaluationEnabled(!value);
        } else if (key === 'testimonial_enabled') {
          setTestimonialEnabled(!value);
        }
        return;
      }
      
      const result = await response.json();
      if (!result.success) {
        console.error('Failed to update setting:', result.message);
        // Revert the state on failure
        if (key === 'priority_allocation_enabled') {
          setPriorityAllocationEnabled(!value);
        } else if (key === 'registration_open') {
          setRegistrationOpen(!value);
        } else if (key === 'evaluation_enabled') {
          setEvaluationEnabled(!value);
        } else if (key === 'testimonial_enabled') {
          setTestimonialEnabled(!value);
        }
      }
    } catch (error) {
      console.error('Failed to update setting:', error);
      // Revert the state on failure
      if (key === 'priority_allocation_enabled') {
        setPriorityAllocationEnabled(!value);
      } else if (key === 'registration_open') {
        setRegistrationOpen(!value);
      } else if (key === 'evaluation_enabled') {
        setEvaluationEnabled(!value);
      } else if (key === 'testimonial_enabled') {
        setTestimonialEnabled(!value);
      }
    } finally {
      setSettingsLoading(null);
    }
  };

  // Handle toggle for priority allocation - show confirmation first
  const handlePriorityAllocationToggle = () => {
    setConfirmAction({
      type: 'priority',
      newValue: !priorityAllocationEnabled
    });
  };

  // Handle toggle for registration - show confirmation first
  const handleRegistrationToggle = () => {
    setConfirmAction({
      type: 'registration',
      newValue: !registrationOpen
    });
  };

  // Handle toggle for evaluation - show confirmation first
  const handleEvaluationToggle = () => {
    setConfirmAction({
      type: 'evaluation',
      newValue: !evaluationEnabled
    });
  };

  // Handle toggle for testimonial - show confirmation first
  const handleTestimonialToggle = () => {
    setConfirmAction({
      type: 'testimonial',
      newValue: !testimonialEnabled
    });
  };

  // Confirm and execute the toggle action
  const confirmToggle = () => {
    if (!confirmAction) return;
    
    if (confirmAction.type === 'priority') {
      setPriorityAllocationEnabled(confirmAction.newValue);
      updateSetting('priority_allocation_enabled', confirmAction.newValue);
    } else if (confirmAction.type === 'registration') {
      setRegistrationOpen(confirmAction.newValue);
      updateSetting('registration_open', confirmAction.newValue);
    } else if (confirmAction.type === 'evaluation') {
      setEvaluationEnabled(confirmAction.newValue);
      updateSetting('evaluation_enabled', confirmAction.newValue);
    } else if (confirmAction.type === 'testimonial') {
      setTestimonialEnabled(confirmAction.newValue);
      updateSetting('testimonial_enabled', confirmAction.newValue);
    }
    
    setConfirmAction(null);
  };

  const updateCurrentSemester = async () => {
    if (!semAcademicYear || !semStartDate || !semEndDate) {
      setSemesterMessage({ type: 'error', text: 'Please fill in all required fields.' });
      return;
    }
    setSemesterUpdating(true);
    setSemesterMessage(null);
    try {
      const response = await fetch('/api/buddy/admin/semester-setting', {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          academic_year: semAcademicYear,
          semester: semSemester,
          duration_type: semDurationType,
          start_date: semStartDate,
          end_date: semEndDate,
        }),
      });
      const result = await response.json();
      if (result.success) {
        setSemesterSetting(result.data);
        setSemesterMessage({ type: 'success', text: 'Semester updated successfully.' });
      } else {
        setSemesterMessage({ type: 'error', text: result.message || 'Failed to update semester.' });
      }
    } catch (error) {
      console.error('Failed to update semester:', error);
      setSemesterMessage({ type: 'error', text: 'Failed to update semester.' });
    } finally {
      setSemesterUpdating(false);
    }
  };

  const startNewSemester = async () => {
    if (!semAcademicYear || !semStartDate || !semEndDate) {
      setSemesterMessage({ type: 'error', text: 'Please fill in all required fields.' });
      return;
    }
    setSemesterSaving(true);
    setSemesterMessage(null);
    setShowNewSemesterConfirm(false);
    try {
      const response = await fetch('/api/buddy/admin/semester-setting', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          academic_year: semAcademicYear,
          semester: semSemester,
          duration_type: semDurationType,
          start_date: semStartDate,
          end_date: semEndDate,
        }),
      });
      const result = await response.json();
      if (result.success) {
        setSemesterSetting(result.data);
        setSemesterMessage({ type: 'success', text: 'New semester started successfully.' });
      } else {
        setSemesterMessage({ type: 'error', text: result.message || 'Failed to start new semester.' });
      }
    } catch (error) {
      console.error('Failed to start new semester:', error);
      setSemesterMessage({ type: 'error', text: 'Failed to start new semester.' });
    } finally {
      setSemesterSaving(false);
    }
  };

  // Generate academic year options (current year -1 to +2)
  const currentYear = new Date().getFullYear();
  const academicYearOptions = Array.from({ length: 4 }, (_, i) => {
    const y = currentYear - 1 + i;
    return `${y}/${y + 1}`;
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
        <span className="ml-3 text-gray-600">Loading settings...</span>
      </div>
    );
  }

  return (
    <>
      <div className="space-y-6">
        {/* Semester Setting */}
        <div className="border border-blue-200 bg-blue-50 rounded-lg p-6">
          <div className="flex items-center gap-2 mb-4">
            <Calendar className="w-5 h-5 text-blue-600" />
            <h3 className="text-gray-900">Semester Setting</h3>
          </div>

          {semesterSetting && (
            <div className="bg-white rounded-lg p-4 mb-4 border border-blue-200">
              <p className="text-gray-600 mb-1">
                <span className="font-medium text-gray-900">Current:</span>{' '}
                {semesterSetting.academic_year} — Semester {semesterSetting.semester} ({semesterSetting.duration_type === 'long' ? 'Long' : 'Short'} Sem, {semesterSetting.total_weeks} weeks)
              </p>
              <p className="text-gray-500 text-sm">
                {semesterSetting.start_date} to {semesterSetting.end_date}
              </p>
            </div>
          )}

          <div className="grid grid-cols-4 gap-4">
            <div className="col-span-2">
              <label className="block text-gray-700 mb-1">Academic Year</label>
              <select
                value={semAcademicYear}
                onChange={(e) => setSemAcademicYear(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white cursor-pointer"
              >
                <option value="">-- Select Year --</option>
                {academicYearOptions.map((y) => (
                  <option key={y} value={y}>{y}</option>
                ))}
              </select>
            </div>

            <div className="col-span-2">
              <label className="block text-gray-700 mb-1">Semester</label>
              <select
                value={semSemester}
                onChange={(e) => setSemSemester(Number(e.target.value))}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white cursor-pointer"
              >
                <option value={1}>Semester 1</option>
                <option value={2}>Semester 2</option>
                <option value={3}>Semester 3</option>
              </select>
            </div>

            <div className="col-span-4">
              <label className="block text-gray-700 mb-1">Duration</label>
              <select
                value={semDurationType}
                onChange={(e) => setSemDurationType(e.target.value as 'long' | 'short')}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white cursor-pointer"
              >
                <option value="long">Long Semester (14 weeks)</option>
                <option value="short">Short Semester (7 weeks)</option>
              </select>
            </div>

            <div className="col-span-2">
              <label className="block text-gray-700 mb-1">Start Date</label>
              <input
                type="date"
                value={semStartDate}
                onChange={(e) => setSemStartDate(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div className="col-span-2">
              <label className="block text-gray-700 mb-1">End Date</label>
              <input
                type="date"
                value={semEndDate}
                onChange={(e) => setSemEndDate(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          {semesterMessage && (
            <div className={`mt-3 p-3 rounded-lg text-sm ${
              semesterMessage.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'
            }`}>
              {semesterMessage.text}
            </div>
          )}

          <div className="mt-4 flex items-center gap-3">
            <button
              onClick={updateCurrentSemester}
              disabled={semesterUpdating || semesterSaving}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
            >
              {semesterUpdating ? 'Updating...' : 'Update Current Semester'}
            </button>
            <button
              onClick={() => setShowNewSemesterConfirm(true)}
              disabled={semesterSaving || semesterUpdating}
              className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
            >
              {semesterSaving ? 'Starting...' : '+ Start New Semester'}
            </button>
          </div>
        </div>

        {/* Priority Allocation System */}
        <div className="border border-gray-200 rounded-lg p-4">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <h3 className="text-gray-900">Priority Allocation System</h3>
                <span className={`px-2 py-1 rounded text-white cursor-pointer ${
                  priorityAllocationEnabled ? 'bg-green-600' : 'bg-red-600'
                }`}>
                  {priorityAllocationEnabled ? 'Enabled' : 'Disabled'}
                </span>
              </div>
              <p className="text-gray-600 mb-3">
                When enabled, the system will reserve up to 20% of mentee slots for verified repeaters 
                and deprioritize students with ratings below 3.0 in the allocation list.
              </p>
              <ul className="space-y-1 text-gray-600">
                <li className="flex items-start gap-2">
                  <span className="text-blue-600 mt-1">•</span>
                  <span>20% slot reservation for repeater students</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-blue-600 mt-1">•</span>
                  <span>Rating-based deprioritization (&lt; 3.0)</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-blue-600 mt-1">•</span>
                  <span>First-come, first-served matching within priority tiers</span>
                </li>
              </ul>
            </div>
            
            <button
              onClick={handlePriorityAllocationToggle}
              disabled={settingsLoading === 'priority_allocation_enabled'}
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 cursor-pointer ${
                priorityAllocationEnabled ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={priorityAllocationEnabled}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform cursor-pointer ${
                  priorityAllocationEnabled ? 'translate-x-11' : 'translate-x-1'
                }`}
              />
            </button>
          </div>
        </div>

        {/* Registration Status */}
        <div className="border border-gray-200 rounded-lg p-4">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <h3 className="text-gray-900">Registration Phase</h3>
                <span className={`px-2 py-1 rounded text-white cursor-pointer ${
                  registrationOpen ? 'bg-green-600' : 'bg-red-600'
                }`}>
                  {registrationOpen ? 'Open' : 'Closed'}
                </span>
              </div>
              <p className="text-gray-600">
                Control whether students can register for the Buddy Programme this semester
              </p>
            </div>
            
            <button
              onClick={handleRegistrationToggle}
              disabled={settingsLoading === 'registration_open'}
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 cursor-pointer ${
                registrationOpen ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={registrationOpen}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform cursor-pointer ${
                  registrationOpen ? 'translate-x-11' : 'translate-x-1'
                }`}
              />
            </button>
          </div>
        </div>

        {/* Evaluation Phase */}
        <div className="border border-gray-200 rounded-lg p-4">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <h3 className="text-gray-900">Evaluation Phase</h3>
                <span className={`px-2 py-1 rounded text-white cursor-pointer ${
                  evaluationEnabled ? 'bg-green-600' : 'bg-red-600'
                }`}>
                  {evaluationEnabled ? 'Enabled' : 'Disabled'}
                </span>
              </div>
              <p className="text-gray-600">
                Allow mentors to access the Feedback tab for end-of-semester evaluations
              </p>
            </div>
            
            <button
              onClick={handleEvaluationToggle}
              disabled={settingsLoading === 'evaluation_enabled'}
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 cursor-pointer ${
                evaluationEnabled ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={evaluationEnabled}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform cursor-pointer ${
                  evaluationEnabled ? 'translate-x-11' : 'translate-x-1'
                }`}
              />
            </button>
          </div>
        </div>

        {/* Testimonial Phase */}
        <div className="border border-gray-200 rounded-lg p-4">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <h3 className="text-gray-900">Testimonial Phase</h3>
                <span className={`px-2 py-1 rounded text-white cursor-pointer ${
                  testimonialEnabled ? 'bg-green-600' : 'bg-red-600'
                }`}>
                  {testimonialEnabled ? 'Enabled' : 'Disabled'}
                </span>
              </div>
              <p className="text-gray-600">
                Allow mentors to access the Testimonial tab to download their certificates
              </p>
            </div>
            
            <button
              onClick={handleTestimonialToggle}
              disabled={settingsLoading === 'testimonial_enabled'}
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 cursor-pointer ${
                testimonialEnabled ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={testimonialEnabled}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform cursor-pointer ${
                  testimonialEnabled ? 'translate-x-11' : 'translate-x-1'
                }`}
              />
            </button>
          </div>
        </div>
      </div>

      {/* Confirmation Modal for Start New Semester */}
      {showNewSemesterConfirm && (
        <div className="fixed inset-0 flex items-center justify-center p-4 z-50" style={{ backdropFilter: 'blur(8px)', WebkitBackdropFilter: 'blur(8px)', backgroundColor: 'rgba(255, 255, 255, 0.3)' }}>
          <div className="bg-white rounded-2xl max-w-md w-full p-8 text-center" style={{ boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)' }}>
            <div className="w-16 h-16 mx-auto mb-6 rounded-full flex items-center justify-center" style={{ border: '4px solid #dc2626' }}>
              <AlertTriangle className="w-8 h-8 text-red-600" />
            </div>
            <h3 className="text-2xl font-bold mb-3 text-red-600">Start New Semester?</h3>
            <p className="text-gray-500 mb-4">
              This will archive the current semester and create a new active semester with the details you entered.
            </p>
            <p className="text-gray-700 font-medium mb-8">
              {semAcademicYear} — Semester {semSemester} ({semDurationType === 'long' ? 'Long' : 'Short'} Sem)<br />
              <span className="text-gray-500 text-sm">{semStartDate} to {semEndDate}</span>
            </p>
            <p className="text-red-600 text-sm mb-8">This action cannot be undone.</p>
            <div className="flex gap-3 justify-center">
              <button
                onClick={() => setShowNewSemesterConfirm(false)}
                style={{ padding: '10px 24px', border: '1px solid #d1d5db', borderRadius: '9999px', color: '#374151', backgroundColor: 'white', fontWeight: 500, cursor: 'pointer' }}
              >
                Cancel
              </button>
              <button
                onClick={startNewSemester}
                style={{ padding: '10px 24px', borderRadius: '9999px', color: 'white', backgroundColor: '#dc2626', fontWeight: 500, cursor: 'pointer', border: 'none' }}
              >
                Yes, Start New Semester
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Confirmation Modal for Settings Toggle */}
      {confirmAction && (
        <div className="fixed inset-0 flex items-center justify-center p-4 z-50" style={{ backdropFilter: 'blur(8px)', WebkitBackdropFilter: 'blur(8px)', backgroundColor: 'rgba(255, 255, 255, 0.3)' }}>
          <div className="bg-white rounded-2xl max-w-md w-full p-8 text-center" style={{ boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)' }}>
            <div 
              className="w-16 h-16 mx-auto mb-6 rounded-full flex items-center justify-center"
              style={{ border: confirmAction.newValue ? '4px solid #4ade80' : '4px solid #dc2626' }}
            >
              <AlertTriangle className="w-8 h-8" style={{ color: confirmAction.newValue ? '#22c55e' : '#dc2626' }} />
            </div>
            
            <h3 className="text-2xl font-bold mb-3" style={{ color: confirmAction.newValue ? '#111827' : '#dc2626' }}>
              {confirmAction.type === 'priority' 
                ? (confirmAction.newValue ? 'Enable Priority Allocation?' : 'Disable Priority Allocation?')
                : confirmAction.type === 'registration'
                ? (confirmAction.newValue ? 'Open Registration?' : 'Close Registration?')
                : confirmAction.type === 'evaluation'
                ? (confirmAction.newValue ? 'Enable Evaluation Phase?' : 'Disable Evaluation Phase?')
                : (confirmAction.newValue ? 'Enable Testimonial Phase?' : 'Disable Testimonial Phase?')
              }
            </h3>
            
            <p className="text-gray-500 mb-8">
              {confirmAction.type === 'priority' 
                ? confirmAction.newValue
                  ? 'This will reserve 20% of slots for repeaters and deprioritize low-rated students.'
                  : 'All students will be matched on a first-come, first-served basis only.'
                : confirmAction.type === 'registration'
                ? confirmAction.newValue
                  ? 'Students will be able to register for the Buddy Programme.'
                  : 'Students will no longer be able to register for the Buddy Programme.'
                : confirmAction.type === 'evaluation'
                ? confirmAction.newValue
                  ? 'Mentors will be able to access the Feedback tab for end-of-semester evaluations.'
                  : 'Mentors will no longer be able to access the Feedback tab.'
                : confirmAction.newValue
                  ? 'Mentors will be able to access the Testimonial tab to download their certificates.'
                  : 'Mentors will no longer be able to access the Testimonial tab.'
              }
            </p>
            
            <div className="flex gap-3 justify-center">
              <button
                onClick={() => setConfirmAction(null)}
                style={{ 
                  padding: '10px 24px', 
                  border: '1px solid #d1d5db', 
                  borderRadius: '9999px',
                  color: '#374151',
                  backgroundColor: 'white',
                  fontWeight: 500,
                  cursor: 'pointer'
                }}
              >
                Cancel
              </button>
              <button
                onClick={confirmToggle}
                style={{ 
                  padding: '10px 24px', 
                  borderRadius: '9999px',
                  color: 'white',
                  backgroundColor: confirmAction.newValue ? '#38bdf8' : '#ef4444',
                  fontWeight: 500,
                  cursor: 'pointer',
                  border: 'none'
                }}
              >
                Yes
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
