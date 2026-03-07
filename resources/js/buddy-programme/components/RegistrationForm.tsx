import { useState, useEffect, useRef } from 'react';
import { ArrowLeft, Upload, X, AlertCircle, CheckCircle, Loader2, Search, Plus } from 'lucide-react';

interface Subject {
  id: number;
  name: string;
  code: string | null;
  type: 'subject' | 'skill';
  display_name: string;
}

interface Skill {
  id: number;
  name: string;
  type: 'skill';
}

interface RegistrationFormProps {
  role: 'mentor' | 'mentee' | null;
  onBack: () => void;
}

const FACULTIES = [
  'Faculty of Accountancy, Finance and Business',
  'Faculty of Applied Sciences',
  'Faculty of Computing and Information Technology',
  'Faculty of Built Environment',
  'Faculty of Engineering and Technology',
  'Faculty of Communication and Creative Industries',
  'Faculty of Social Science and Humanities'
];

export function RegistrationForm({ role, onBack }: RegistrationFormProps) {
  const [formData, setFormData] = useState({
    fullName: '',
    studentId: '',
    course: '',
    faculty: '',
    yearOfStudy: '',
    cgpa: '',
    isRepeater: false,
  });

  // Subject/Skill selection state
  const [selectionType, setSelectionType] = useState<'subject' | 'skill'>('subject');
  const [selectedSubject, setSelectedSubject] = useState<Subject | null>(null);
  const [selectedSkill, setSelectedSkill] = useState<Skill | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<Subject[]>([]);
  const [skills, setSkills] = useState<Skill[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [showDropdown, setShowDropdown] = useState(false);
  const [showAddNew, setShowAddNew] = useState(false);
  const [newSubjectName, setNewSubjectName] = useState('');
  const [newSubjectCode, setNewSubjectCode] = useState('');
  const [isCreating, setIsCreating] = useState(false);
  const [skillSearch, setSkillSearch] = useState('');
  const [showSkillDropdown, setShowSkillDropdown] = useState(false);
  const [showAddNewSkill, setShowAddNewSkill] = useState(false);
  const [newSkillName, setNewSkillName] = useState('');
  const [isCreatingSkill, setIsCreatingSkill] = useState(false);
  const searchRef = useRef<HTMLDivElement>(null);
  const skillSearchRef = useRef<HTMLDivElement>(null);

  const [uploadedFile, setUploadedFile] = useState<File | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitted, setSubmitted] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [apiError, setApiError] = useState<string | null>(null);

  // Fetch skills on mount
  useEffect(() => {
    const fetchSkills = async () => {
      try {
        const response = await fetch('/api/buddy/skills');
        if (response.ok) {
          const data = await response.json();
          setSkills(data.data || []);
        }
      } catch (error) {
        console.error('Failed to fetch skills:', error);
      }
    };
    fetchSkills();
  }, []);

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setShowDropdown(false);
      }
      if (skillSearchRef.current && !skillSearchRef.current.contains(event.target as Node)) {
        setShowSkillDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Search subjects with debounce
  useEffect(() => {
    if (searchQuery.length < 2) {
      setSearchResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setIsSearching(true);
      try {
        const response = await fetch(`/api/buddy/subjects/search?q=${encodeURIComponent(searchQuery)}&type=subject`);
        if (response.ok) {
          const data = await response.json();
          setSearchResults(data.data || []);
          setShowDropdown(true);
        }
      } catch (error) {
        console.error('Search failed:', error);
      } finally {
        setIsSearching(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  const handleInputChange = (field: string, value: string | boolean) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    // Clear error for this field
    if (errors[field]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  const handleSelectSubject = (subject: Subject) => {
    setSelectedSubject(subject);
    setSearchQuery(subject.display_name);
    setShowDropdown(false);
    if (errors.subject) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors.subject;
        return newErrors;
      });
    }
  };

  const handleSelectSkill = (skill: Skill) => {
    setSelectedSkill(skill);
    if (errors.subject) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors.subject;
        return newErrors;
      });
    }
  };

  const handleAddNewSubject = () => {
    setShowAddNew(true);
    setNewSubjectName(searchQuery);
    setNewSubjectCode('');
    setShowDropdown(false);
  };

  const handleCreateNewSubject = async () => {
    if (!newSubjectName.trim()) return;
    
    setIsCreating(true);
    try {
      const response = await fetch('/api/buddy/subjects', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          name: newSubjectName.trim(),
          code: newSubjectCode.trim() || null,
          type: 'subject',
        }),
      });

      if (response.ok) {
        const result = await response.json();
        const newSubject = result.data || result;
        setSelectedSubject(newSubject);
        setSearchQuery(newSubject.display_name || newSubject.name);
        setShowAddNew(false);
        setNewSubjectName('');
        setNewSubjectCode('');
      } else {
        const error = await response.json();
        setApiError(error.message || 'Failed to create subject');
      }
    } catch (error) {
      setApiError('Failed to create subject');
    } finally {
      setIsCreating(false);
    }
  };

  const handleCreateNewSkill = async () => {
    if (!newSkillName.trim()) return;

    setIsCreatingSkill(true);
    try {
      const response = await fetch('/api/buddy/subjects', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          name: newSkillName.trim(),
          code: null,
          type: 'skill',
        }),
      });

      if (response.ok) {
        const result = await response.json();
        const newSkill: Skill = {
          id: (result.data || result).id,
          name: newSkillName.trim(),
          type: 'skill',
        };
        setSelectedSkill(newSkill);
        setSkillSearch(newSkillName.trim());
        setSkills(prev => [...prev, newSkill]);
        setShowAddNewSkill(false);
        setNewSkillName('');
      } else {
        const error = await response.json();
        setApiError(error.message || 'Failed to create skill');
      }
    } catch (error) {
      setApiError('Failed to create skill');
    } finally {
      setIsCreatingSkill(false);
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
      const maxSize = 5 * 1024 * 1024;

      if (!validTypes.includes(file.type)) {
        setErrors(prev => ({ ...prev, file: 'Only PDF, JPG, or PNG files are allowed' }));
        return;
      }

      if (file.size > maxSize) {
        setErrors(prev => ({ ...prev, file: 'File size must be less than 5MB' }));
        return;
      }

      setUploadedFile(file);
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors.file;
        return newErrors;
      });
    }
  };

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.fullName.trim()) newErrors.fullName = 'Full name is required';
    if (!formData.studentId.trim()) newErrors.studentId = 'Student ID is required';
    if (!formData.course.trim()) newErrors.course = 'Course is required';
    if (!formData.faculty) newErrors.faculty = 'Faculty is required';
    if (!formData.yearOfStudy) newErrors.yearOfStudy = 'Year of study is required';
    if (!formData.cgpa) {
      newErrors.cgpa = 'CGPA is required';
    } else {
      const cgpa = parseFloat(formData.cgpa);
      if (isNaN(cgpa) || cgpa < 0 || cgpa > 4.0) {
        newErrors.cgpa = 'CGPA must be between 0.00 and 4.00';
      }
    }

    // Validate subject or skill selection
    if (selectionType === 'subject' && !selectedSubject) {
      newErrors.subject = 'Please select or add a subject';
    } else if (selectionType === 'skill' && !selectedSkill) {
      newErrors.subject = 'Please select a skill';
    }

    if (role === 'mentor' && !uploadedFile) {
      newErrors.file = 'Qualification document is required for mentors';
    }

    if (role === 'mentee' && formData.isRepeater && !uploadedFile) {
      newErrors.file = 'CGPA record or result slip is required for repeaters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setApiError(null);
    
    if (validateForm()) {
      setIsLoading(true);
      
      try {
        // Create FormData for file upload
        const submitData = new FormData();
        submitData.append('full_name', formData.fullName);
        submitData.append('student_id', formData.studentId);
        submitData.append('course', formData.course);
        submitData.append('faculty', formData.faculty);
        submitData.append('year_of_study', formData.yearOfStudy);
        submitData.append('cgpa', formData.cgpa);
        submitData.append('role', role || '');
        submitData.append('is_repeater', formData.isRepeater ? '1' : '0');
        
        // Send subject_id for existing subject/skill
        if (selectionType === 'subject' && selectedSubject) {
          submitData.append('subject_id', selectedSubject.id.toString());
        } else if (selectionType === 'skill' && selectedSkill) {
          submitData.append('subject_id', selectedSkill.id.toString());
        }
        
        if (uploadedFile) {
          submitData.append('document', uploadedFile);
        }

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/buddy/register', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
          },
          body: submitData,
        });

        const result = await response.json();

        if (response.ok && result.success) {
          setSubmitted(true);
        } else {
          if (result.errors) {
            const serverErrors: Record<string, string> = {};
            Object.keys(result.errors).forEach(key => {serverErrors[key] = result.errors[key][0];});
            setErrors(serverErrors);
          } else {
            setApiError(result.message || 'Registration failed. Please try again.');
          }
        }
      } catch (error) {
        setApiError('An error occurred in Server. Please try again.');
      } finally {
        setIsLoading(false);
      }
    }
  };

  if (submitted) {
    return (
      <div className="max-w-2xl mx-auto">
        <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle className="w-8 h-8 text-green-600" />
          </div>
          
          <h2 className="text-gray-900 mb-3">Registration Submitted!</h2>
          
          {role === 'mentor' ? (
            <div className="space-y-3">
              <p className="text-gray-600">
                Your mentor registration has been submitted for verification.
              </p>
              <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <p className="text-amber-800">
                  Your profile is currently <strong>Pending Verification</strong>. 
                  An admin will review your qualification documents and approve your registration.
                </p>
              </div>
            </div>
          ) : (
            <div className="space-y-3">
              <p className="text-gray-600">
                Your mentee registration has been successfully processed.
              </p>
              {formData.isRepeater ? (
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <p className="text-blue-800">
                    As a verified repeater, you have been given <strong>priority allocation</strong> in the matching process.
                  </p>
                </div>
              ) : (
                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                  <p className="text-green-800">
                    Your profile is now <strong>Active</strong>. You will be matched with a mentor on a first-come, first-served basis.
                  </p>
                </div>
              )}
            </div>
          )}

          <div className="mt-6 p-4 bg-gray-50 rounded-lg text-left">
            <p className="text-gray-700 mb-2">Registration Summary:</p>
            <ul className="space-y-1 text-gray-600">
              <li>Role: <span className="capitalize">{role}</span></li>
              <li>Student ID: {formData.studentId}</li>
              <li>
                {selectionType === 'subject' ? 'Subject' : 'Skill'}: {
                  selectionType === 'subject' 
                    ? selectedSubject?.display_name 
                    : selectedSkill?.name
                }
              </li>
              {formData.isRepeater && <li>Priority: Repeater (20% reserved slots)</li>}
            </ul>
          </div>

          {/* <button
            onClick={onBack}
            className="mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
          >
            Back to Role Selection
          </button> */}
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto">
      <button
        onClick={onBack}
        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors cursor-pointer"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Role Selection
      </button>

      <div className="bg-white rounded-xl border border-gray-200 p-8">
        <div className="mb-6">
          <h2 className="text-gray-900 mb-2 capitalize">{role} Registration</h2>
          <p className="text-gray-600">
            Please fill in all required fields to complete your registration
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="space-y-4">
            <h3 className="text-gray-900">Personal Information</h3>
            
            <div>
              <label className="block text-gray-700 mb-2">
                Full Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.fullName}
                onChange={(e) => handleInputChange('fullName', e.target.value)}
                className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer ${
                  errors.fullName ? 'border-red-500' : 'border-gray-300'
                }`}
                placeholder="Enter your full name"
              />
              {errors.fullName && (
                <p className="mt-1 text-red-600 flex items-center gap-1">
                  <AlertCircle className="w-4 h-4" />
                  {errors.fullName}
                </p>
              )}
            </div>

            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="block text-gray-700 mb-2">
                  Student ID <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.studentId}
                  onChange={(e) => handleInputChange('studentId', e.target.value)}
                  className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer ${
                    errors.studentId ? 'border-red-500' : 'border-gray-300'
                  }`}
                  placeholder="e.g., 24WMR00123"
                />
                {errors.studentId && (
                  <p className="mt-1 text-red-600 flex items-center gap-1">
                    <AlertCircle className="w-4 h-4" />
                    {errors.studentId}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-gray-700 mb-2">
                  CGPA <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="4.0"
                  value={formData.cgpa}
                  onChange={(e) => handleInputChange('cgpa', e.target.value)}
                  className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer ${
                    errors.cgpa ? 'border-red-500' : 'border-gray-300'
                  }`}
                  placeholder="e.g., 4.00"
                />
                {errors.cgpa && (
                  <p className="mt-1 text-red-600 flex items-center gap-1">
                    <AlertCircle className="w-4 h-4" />
                    {errors.cgpa}
                  </p>
                )}
              </div>
            </div>
          </div>

          <div className="space-y-4">
            <h3 className="text-gray-900">Academic Information</h3>
            
            <div>
              <label className="block text-gray-700 mb-2">
                Faculty <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.faculty}
                onChange={(e) => handleInputChange('faculty', e.target.value)}
                className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer ${
                  errors.faculty ? 'border-red-500' : 'border-gray-300'
                }`}
              >
                <option value="">Select Faculty</option>
                {FACULTIES.map(faculty => (
                  <option key={faculty} value={faculty}>{faculty}</option>
                ))}
              </select>
              {errors.faculty && (
                <p className="mt-1 text-red-600 flex items-center gap-1">
                  <AlertCircle className="w-4 h-4" />
                  {errors.faculty}
                </p>
              )}
            </div>

            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="block text-gray-700 mb-2">
                  Course <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.course}
                  onChange={(e) => handleInputChange('course', e.target.value)}
                  className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer ${
                    errors.course ? 'border-red-500' : 'border-gray-300'
                  }`}
                  placeholder="e.g., Computer Science"
                />
                {errors.course && (
                  <p className="mt-1 text-red-600 flex items-center gap-1">
                    <AlertCircle className="w-4 h-4" />
                    {errors.course}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-gray-700 mb-2">
                  Year of Study <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.yearOfStudy}
                  onChange={(e) => handleInputChange('yearOfStudy', e.target.value)}
                  className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer ${
                    errors.yearOfStudy ? 'border-red-500' : 'border-gray-300'
                  }`}
                >
                  <option value="">Select Year</option>
                  <option value="1">Year 1</option>
                  <option value="2">Year 2</option>
                  <option value="3">Year 3</option>
                  <option value="4">Year 4</option>
                </select>
                {errors.yearOfStudy && (
                  <p className="mt-1 text-red-600 flex items-center gap-1">
                    <AlertCircle className="w-4 h-4" />
                    {errors.yearOfStudy}
                  </p>
                )}
              </div>
            </div>
          </div>

          {role === 'mentee' && (
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={formData.isRepeater}
                  onChange={(e) => handleInputChange('isRepeater', e.target.checked)}
                  className="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <div>
                  <span className="text-gray-900">I am a Repeater Student</span>
                  <p className="text-gray-600 mt-1">
                    Select this if you are retaking subjects. Up to 20% of mentee slots are reserved for repeaters (priority allocation).
                  </p>
                </div>
              </label>
            </div>
          )}

          <div className="space-y-4">
            <div>
              <h3 className="text-gray-900 mb-2">
                Select Subject or Skill <span className="text-red-500">*</span>
              </h3>
              <p className="text-gray-600 mb-4">
                Choose either ONE subject or skill you want to {role === 'mentor' ? 'teach' : 'learn'}
              </p>
            </div>

            {/* Selection Type Tabs */}
            <div className="flex border-b border-gray-200">
              <button
                type="button"
                onClick={() => {
                  setSelectionType('subject');
                  setSelectedSkill(null);
                  setErrors(prev => { const e = {...prev}; delete e.subject; return e; });
                }}
                className={`px-4 py-2 font-medium border-b-2 transition-colors cursor-pointer ${
                  selectionType === 'subject'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Subject
              </button>
              <button
                type="button"
                onClick={() => {
                  setSelectionType('skill');
                  setSelectedSubject(null);
                  setSearchQuery('');
                  setSkillSearch('');
                  setErrors(prev => { const e = {...prev}; delete e.subject; return e; });
                }}
                className={`px-4 py-2 font-medium border-b-2 transition-colors cursor-pointer ${
                  selectionType === 'skill'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Skill
              </button>
            </div>

            {/* Subject Search (Autocomplete) */}
            {selectionType === 'subject' && (
              <div ref={searchRef} style={{ position: 'relative' }}>
                <div style={{ position: 'relative' }}>
                  <div style={{ position: 'relative' }}>
                    <Search style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', pointerEvents: 'none', color: '#9ca3af', width: '16px', height: '16px' }} />
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={(e) => {
                        setSearchQuery(e.target.value);
                        setSelectedSubject(null);
                      }}
                      onFocus={() => (searchQuery?.length ?? 0) >= 2 && setShowDropdown(true)}
                    className={`w-full h-11 pr-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                      errors.subject ? 'border-red-500' : 'border-gray-300'
                    }`}
                    style={{ paddingLeft: '2.5rem' }}
                      placeholder="Search by subject name or code (e.g., BMCS2203 Data Structures)"
                    />
                    {isSearching && (
                      <Loader2 style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', color: '#9ca3af', width: '16px', height: '16px' }} className="animate-spin" />
                    )}
                  </div>
                </div>

                {/* Search Results Dropdown */}
                {showDropdown && (
                  <div style={{ position: 'absolute', zIndex: 10, top: '100%', left: 0, right: 0, marginTop: '4px' }} className="w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    {searchResults.length > 0 ? (
                      <>
                        {searchResults.map((subject) => (
                          <button
                            key={subject.id}
                            type="button"
                            onClick={() => handleSelectSubject(subject)}
                            className="w-full px-4 py-2 text-left hover:bg-blue-50 flex items-center justify-between cursor-pointer"
                          >
                            <span>{subject.display_name}</span>
                            {subject.code && (
                              <span className="text-gray-500 text-sm">{subject.code}</span>
                            )}
                          </button>
                        ))}
                        <button
                          type="button"
                          onClick={handleAddNewSubject}
                          className="w-full px-4 py-2 text-left hover:bg-green-50 text-green-600 border-t flex items-center gap-2 cursor-pointer"
                        >
                          <Plus className="w-4 h-4" />
                          Add "{searchQuery}" as new subject
                        </button>
                      </>
                    ) : searchQuery.length >= 2 && !isSearching ? (
                      <button
                        type="button"
                        onClick={handleAddNewSubject}
                        className="w-full px-4 py-2 text-left hover:bg-green-50 text-green-600 flex items-center gap-2 cursor-pointer"
                      >
                        <Plus className="w-4 h-4" />
                        Add "{searchQuery}" as new subject
                      </button>
                    ) : null}
                  </div>
                )}

                {/* Selected Subject Display */}
                {selectedSubject && (
                  <div className="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
                    <div>
                      <span className="font-medium text-blue-900">{selectedSubject.name}</span>
                      {selectedSubject.code && (
                        <span className="ml-2 text-blue-700 text-sm">({selectedSubject.code})</span>
                      )}
                    </div>
                    <button
                      type="button"
                      onClick={() => {
                        setSelectedSubject(null);
                        setSearchQuery('');
                      }}
                      className="text-blue-600 hover:text-blue-800 cursor-pointer"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  </div>
                )}

                {/* Add New Subject Form */}
                {showAddNew && (
                  <div className="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h4 className="font-medium text-gray-900 mb-3">Add New Subject</h4>
                    <div className="space-y-3">
                      <div>
                        <label className="block text-gray-700 text-sm mb-1">Subject Name *</label>
                        <input
                          type="text"
                          value={newSubjectName}
                          onChange={(e) => setNewSubjectName(e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="e.g., Data Structures and Algorithms"
                        />
                      </div>
                      <div>
                        <label className="block text-gray-700 text-sm mb-1">Subject Code (Optional)</label>
                        <input
                          type="text"
                          value={newSubjectCode}
                          onChange={(e) => setNewSubjectCode(e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="e.g., BMCS2203"
                        />
                      </div>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={handleCreateNewSubject}
                          disabled={isCreating || !newSubjectName.trim()}
                          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                        >
                          {isCreating ? (
                            <Loader2 className="w-4 h-4 animate-spin" />
                          ) : (
                            <Plus className="w-4 h-4" />
                          )}
                          Add Subject
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            setShowAddNew(false);
                            setNewSubjectName('');
                            setNewSubjectCode('');
                          }}
                          className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 cursor-pointer"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Skill Selection (Autocomplete — mirrors Subject search) */}
            {selectionType === 'skill' && (
              <div ref={skillSearchRef} style={{ position: 'relative' }}>
                <div style={{ position: 'relative' }}>
                  <Search style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', pointerEvents: 'none', color: '#9ca3af', width: '16px', height: '16px' }} />
                  <input
                    type="text"
                    value={skillSearch}
                    onChange={(e) => {
                      setSkillSearch(e.target.value);
                      setSelectedSkill(null);
                      setShowSkillDropdown(e.target.value.length >= 2);
                    }}
                    onFocus={() => skillSearch.length >= 2 && setShowSkillDropdown(true)}
                    className={`w-full h-11 pr-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                      errors.subject ? 'border-red-500' : 'border-gray-300'
                    }`}
                    style={{ paddingLeft: '2.5rem' }}
                    placeholder="Search by skill name (e.g., Time Management)"
                  />
                </div>

                {/* Skill Dropdown */}
                {showSkillDropdown && (
                  <div style={{ position: 'absolute', zIndex: 10, top: '100%', left: 0, right: 0, marginTop: '4px' }} className="w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    {skills.filter(s => s.name.toLowerCase().includes(skillSearch.toLowerCase())).length > 0 ? (
                      <>
                        {skills
                          .filter(s => s.name.toLowerCase().includes(skillSearch.toLowerCase()))
                          .map(skill => (
                            <button
                              key={skill.id}
                              type="button"
                              onClick={() => {
                                handleSelectSkill(skill);
                                setSkillSearch(skill.name);
                                setShowSkillDropdown(false);
                              }}
                              className="w-full px-4 py-2 text-left hover:bg-blue-50 cursor-pointer"
                            >
                              {skill.name}
                            </button>
                          ))
                        }
                        <button
                          type="button"
                          onClick={() => {
                            setShowAddNewSkill(true);
                            setNewSkillName(skillSearch);
                            setShowSkillDropdown(false);
                          }}
                          className="w-full px-4 py-2 text-left hover:bg-green-50 text-green-600 border-t flex items-center gap-2 cursor-pointer"
                        >
                          <Plus className="w-4 h-4" />
                          Add "{skillSearch}" as new skill
                        </button>
                      </>
                    ) : (
                      <button
                        type="button"
                        onClick={() => {
                          setShowAddNewSkill(true);
                          setNewSkillName(skillSearch);
                          setShowSkillDropdown(false);
                        }}
                        className="w-full px-4 py-2 text-left hover:bg-green-50 text-green-600 flex items-center gap-2 cursor-pointer"
                      >
                        <Plus className="w-4 h-4" />
                        Add "{skillSearch}" as new skill
                      </button>
                    )}
                  </div>
                )}

                {/* Selected Skill Chip */}
                {selectedSkill && (
                  <div className="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
                    <span className="font-medium text-blue-900">{selectedSkill.name}</span>
                    <button
                      type="button"
                      onClick={() => {
                        setSelectedSkill(null);
                        setSkillSearch('');
                      }}
                      className="text-blue-600 hover:text-blue-800 cursor-pointer"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  </div>
                )}

                {/* Add New Skill Form */}
                {showAddNewSkill && (
                  <div className="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h4 className="font-medium text-gray-900 mb-3">Add New Skill</h4>
                    <div className="space-y-3">
                      <div>
                        <label className="block text-gray-700 text-sm mb-1">Skill Name *</label>
                        <input
                          type="text"
                          value={newSkillName}
                          onChange={(e) => setNewSkillName(e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="e.g., Communication Skills"
                        />
                      </div>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={handleCreateNewSkill}
                          disabled={isCreatingSkill || !newSkillName.trim()}
                          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                        >
                          {isCreatingSkill ? (
                            <Loader2 className="w-4 h-4 animate-spin" />
                          ) : (
                            <Plus className="w-4 h-4" />
                          )}
                          Add Skill
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            setShowAddNewSkill(false);
                            setNewSkillName('');
                          }}
                          className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 cursor-pointer"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}
            
            {errors.subject && (
              <p className="mt-2 text-red-600 flex items-center gap-1">
                <AlertCircle className="w-4 h-4" />
                {errors.subject}
              </p>
            )}
          </div>

          <div className="space-y-4">
            <div>
              <h3 className="text-gray-900 mb-2">
                {role === 'mentor' 
                  ? 'Upload Qualification Document'
                  : formData.isRepeater 
                    ? 'Upload CGPA Record/Result Slip'
                    : 'Upload Document (Optional)'}
                {(role === 'mentor' || formData.isRepeater) && (
                  <span className="text-red-500"> *</span>
                )}
              </h3>
              <p className="text-gray-600 mb-4">
                {role === 'mentor'
                  ? 'Upload your result slip or qualification certificate (PDF, JPG, or PNG, max 5MB)'
                  : formData.isRepeater
                    ? 'Upload proof of repeater status (PDF, JPG, or PNG, max 5MB)'
                    : 'Optional: Upload supporting documents (PDF, JPG, or PNG, max 5MB)'}
              </p>
            </div>

            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
              {!uploadedFile ? (
                <label className="flex flex-col items-center cursor-pointer">
                  <Upload className="w-12 h-12 text-gray-400 mb-3" />
                  <span className="text-gray-700 mb-1">Click to upload document</span>
                  <span className="text-gray-500">PDF, JPG, or PNG (max 5MB)</span>
                  <input
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    onChange={handleFileUpload}
                    className="hidden"
                  />
                </label>
              ) : (
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                      <Upload className="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                      <p className="text-gray-900">{uploadedFile.name}</p>
                      <p className="text-gray-500">
                        {(uploadedFile.size / 1024 / 1024).toFixed(2)} MB
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => setUploadedFile(null)}
                    className="p-2 text-gray-400 hover:text-red-600 transition-colors cursor-pointer"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
              )}
            </div>

            {errors.file && (
              <p className="mt-2 text-red-600 flex items-center gap-1">
                <AlertCircle className="w-4 h-4" />
                {errors.file}
              </p>
            )}
          </div>

          {/* API Error Display */}
          {apiError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
              <p className="text-red-800 flex items-center gap-2">
                <AlertCircle className="w-5 h-5" />
                {apiError}
              </p>
            </div>
          )}

          <div className="flex gap-4 pt-4">
            <button
              type="button"
              onClick={onBack}
              disabled={isLoading}
              className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
            >
              {isLoading ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  Submitting...
                </>
              ) : (
                'Submit Registration'
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
