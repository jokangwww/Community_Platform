import { useState, useEffect } from 'react';
import { AlertTriangle, Loader2 } from 'lucide-react';

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

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await fetchSettings();
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
        {/* Priority Allocation System */}
        <div className="border border-gray-200 rounded-lg p-4">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <h3 className="text-gray-900">Priority Allocation System</h3>
                <span className={`px-2 py-1 rounded text-white ${
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
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 ${
                priorityAllocationEnabled ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={priorityAllocationEnabled}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform ${
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
                <span className={`px-2 py-1 rounded text-white ${
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
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 ${
                registrationOpen ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={registrationOpen}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform ${
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
                <span className={`px-2 py-1 rounded text-white ${
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
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 ${
                evaluationEnabled ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={evaluationEnabled}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform ${
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
                <span className={`px-2 py-1 rounded text-white ${
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
              className={`relative inline-flex h-10 w-20 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 ${
                testimonialEnabled ? 'bg-green-600' : 'bg-red-600'
              }`}
              role="switch"
              aria-checked={testimonialEnabled}
            >
              <span
                className={`inline-block h-8 w-8 transform rounded-full bg-white transition-transform ${
                  testimonialEnabled ? 'translate-x-11' : 'translate-x-1'
                }`}
              />
            </button>
          </div>
        </div>
      </div>

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
