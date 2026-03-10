import { useState, useEffect, useCallback } from 'react';
import { FileText, Upload, Download, Trash2, Plus, X, Clock, CheckCircle, AlertCircle, Award, Users, Loader2, Edit, Star } from 'lucide-react';

interface StudyMaterial {
  id: string;
  name: string;
  description: string;
  fileName: string;
  fileSize: string;
  uploadedDate: string;
  uploadedBy: string;
}

interface Quiz {
  id: string;
  title: string;
  totalMarks: number;
  dueDate: string;
  status: 'open' | 'closed';
  questions: QuizQuestion[];
  createdDate: string;
}

interface QuizQuestion {
  id: string;
  question: string;
  options: string[];
  correctAnswer: number;
}

interface QuizAttempt {
  quizId: string;
  score: number;
  totalMarks: number;
  completedDate: string;
  answers: number[];
}

interface Assignment {
  id: string;
  title: string;
  description: string;
  dueDate: string;
  totalMarks?: number;
  attachments: string[];
  createdDate: string;
}

interface AssignmentSubmission {
  id: string;
  assignmentId: string;
  fileName: string;
  submittedDate: string;
  status: 'on-time' | 'late' | 'missing';
  marks?: number;
  feedback?: string;
  studentName?: string;
  studentId?: string;
}

interface Mentee {
  id: string;
  name: string;
  studentId: string;
}

interface QuizResultWithStudent extends QuizAttempt {
  studentName: string;
  studentId: string;
}

interface MentorClassroomProps {
  mentorName: string;
  matchId?: string;
  isReadonly?: boolean;
}

export function MentorClassroom({ mentorName, matchId: initialMatchId, isReadonly }: MentorClassroomProps) {
  const [selectedMatchId, setSelectedMatchId] = useState<string | undefined>(initialMatchId);
  const matchId = selectedMatchId || initialMatchId;
  const [activeTab, setActiveTab] = useState<'materials' | 'quizzes' | 'assignments'>('materials');
  const [materials, setMaterials] = useState<StudyMaterial[]>([]);
  const [quizzes, setQuizzes] = useState<Quiz[]>([]);
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [mentees, setMentees] = useState<Mentee[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  // Material upload modal
  const [showMaterialModal, setShowMaterialModal] = useState(false);
  const [editingMaterial, setEditingMaterial] = useState<StudyMaterial | null>(null);
  const [newMaterial, setNewMaterial] = useState({
    name: '',
    description: '',
    file: null as File | null
  });
  const [uploadingMaterial, setUploadingMaterial] = useState(false);

  // Quiz creation modal
  const [showQuizModal, setShowQuizModal] = useState(false);
  const [editingQuiz, setEditingQuiz] = useState<Quiz | null>(null);
  const [newQuiz, setNewQuiz] = useState({
    title: '',
    totalMarks: 10,
    dueDate: '',
    questions: [] as QuizQuestion[]
  });
  const [currentQuestion, setCurrentQuestion] = useState({
    question: '',
    options: ['', '', '', ''],
    correctAnswer: 0
  });
  const [creatingQuiz, setCreatingQuiz] = useState(false);
  
  // Quiz results viewing (for mentors)
  const [viewingQuizResults, setViewingQuizResults] = useState<Quiz | null>(null);
  const [quizAttempts, setQuizAttempts] = useState<QuizResultWithStudent[]>([]);
  const [loadingQuizResults, setLoadingQuizResults] = useState(false);

  // Assignment modal
  const [showAssignmentModal, setShowAssignmentModal] = useState(false);
  const [editingAssignment, setEditingAssignment] = useState<Assignment | null>(null);
  const [newAssignment, setNewAssignment] = useState({
    title: '',
    description: '',
    dueDate: '',
    attachments: [] as File[]
  });
  const [creatingAssignment, setCreatingAssignment] = useState(false);
  
  // Mentor - View submissions
  const [showSubmissionsViewer, setShowSubmissionsViewer] = useState(false);
  const [viewingAssignment, setViewingAssignment] = useState<Assignment | null>(null);
  const [assignmentSubmissions, setAssignmentSubmissions] = useState<AssignmentSubmission[]>([]);
  const [loadingSubmissions, setLoadingSubmissions] = useState(false);
  const [currentAssignmentTotalMarks, setCurrentAssignmentTotalMarks] = useState<number>(0);

  // Grading state
  const [gradingSubmission, setGradingSubmission] = useState<AssignmentSubmission | null>(null);
  const [gradeMarks, setGradeMarks] = useState<number>(0);
  const [gradeFeedback, setGradeFeedback] = useState<string>('');
  const [savingGrade, setSavingGrade] = useState(false);

  // Fetch classroom data
  const fetchClassroomData = useCallback(async () => {
    if (!matchId) {
      setLoading(false);
      setError('No match selected. Please select an active buddy match to access the classroom.');
      return;
    }
    
    try {
      setLoading(true);
      setError(null);
      const response = await fetch(`/api/buddy/classroom/${matchId}`);
      if (!response.ok) {
        throw new Error('Failed to fetch classroom data');
      }
      const data = await response.json();
      setMaterials(data.materials || []);
      setQuizzes(data.quizzes || []);
      setAssignments(data.assignments || []);
      setMentees(data.mentees || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred');
    } finally {
      setLoading(false);
    }
  }, [matchId]);

  useEffect(() => {
    fetchClassroomData();
  }, [fetchClassroomData]);

  // Material handlers
  const handleOpenEditMaterial = (material: StudyMaterial) => {
    setEditingMaterial(material);
    setNewMaterial({
      name: material.name,
      description: material.description || '',
      file: null
    });
    setShowMaterialModal(true);
  };

  const handleUploadMaterial = async () => {
    if (!newMaterial.name) return;
    
    try {
      setUploadingMaterial(true);
      const formData = new FormData();
      formData.append('name', newMaterial.name);
      formData.append('description', newMaterial.description);
      if (newMaterial.file) {
        formData.append('file', newMaterial.file);
      }

      const url = editingMaterial 
        ? `/api/buddy/classroom/${matchId}/materials/${editingMaterial.id}`
        : `/api/buddy/classroom/${matchId}/materials`;
      
      // Use POST with _method=PUT for edits (PHP can't parse multipart form data on PUT requests)
      if (editingMaterial) {
        formData.append('_method', 'PUT');
      }

      const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(editingMaterial ? 'Failed to update material' : 'Failed to upload material');
      }

      const data = await response.json();
      
      if (editingMaterial) {
        setMaterials(materials.map(m => m.id === editingMaterial.id ? data.material : m));
      } else {
        setMaterials([data.material, ...materials]);
      }
      
      setNewMaterial({ name: '', description: '', file: null });
      setEditingMaterial(null);
      setShowMaterialModal(false);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to save material');
    } finally {
      setUploadingMaterial(false);
    }
  };

  const handleDeleteMaterial = async (materialId: string) => {
    if (!confirm('Are you sure you want to delete this material?')) return;
    
    try {
      const response = await fetch(`/api/buddy/classroom/${matchId}/materials/${materialId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to delete material');
      }

      setMaterials(materials.filter(m => m.id !== materialId));
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to delete material');
    }
  };

  const handleDownloadMaterial = async (materialId: string, fileName: string) => {
    try {
      const response = await fetch(`/api/buddy/classroom/${matchId}/materials/${materialId}/download`);
      if (!response.ok) {
        throw new Error('Failed to download material');
      }
      const blob = await response.blob();
      const blobUrl = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = blobUrl;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(blobUrl);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to download material');
    }
  };

  // Assignment file preview
  const handlePreviewAssignment = async (assignmentId: string, submissionId: string, fileName: string) => {
    try {
      const response = await fetch(`/api/buddy/classroom/${matchId}/assignments/${assignmentId}/submissions/${submissionId}/download`);
      if (!response.ok) {
        throw new Error('Failed to fetch assignment file');
      }
      const blob = await response.blob();
      const blobUrl = URL.createObjectURL(blob);
      const previewWindow = window.open('', '_blank');
      if (previewWindow) {
        previewWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>${fileName}</title>
            <style>
              * { margin: 0; padding: 0; box-sizing: border-box; }
              body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
              .preview-container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
              .preview-header { display: flex; align-items: center; padding-bottom: 15px; border-bottom: 2px solid #e5e7eb; margin-bottom: 20px; }
              .preview-title { font-size: 20px; font-weight: 600; color: #1f2937; }
              iframe { width: 100%; height: 800px; border: 1px solid #e5e7eb; border-radius: 4px; }
              @media print { body { background: white; padding: 0; } .preview-container { box-shadow: none; padding: 0; } .preview-header { display: none; } iframe { height: auto; border: none; } }
            </style>
          </head>
          <body>
            <div class="preview-container">
              <div class="preview-header">
                <div class="preview-title">${fileName}</div>
              </div>
              <iframe src="${blobUrl}" type="${blob.type}"></iframe>
            </div>
          </body>
          </html>
        `);
        previewWindow.document.close();
      } else {
        alert('Please allow pop-ups to preview the file');
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to preview assignment file');
    }
  };

  // Quiz handlers
  const handleOpenEditQuiz = (quiz: Quiz) => {
    setEditingQuiz(quiz);
    setNewQuiz({
      title: quiz.title,
      totalMarks: quiz.totalMarks,
      dueDate: quiz.dueDate || '',
      questions: quiz.questions.map((q, idx) => ({
        id: q.id,
        question: q.question,
        options: [...q.options],
        correctAnswer: q.correctAnswer
      }))
    });
    setShowQuizModal(true);
  };

  const handleAddQuestion = () => {
    if (currentQuestion.question && currentQuestion.options.every(o => o.trim())) {
      setNewQuiz({
        ...newQuiz,
        questions: [...newQuiz.questions, {
          id: `q${newQuiz.questions.length + 1}`,
          ...currentQuestion
        }]
      });
      setCurrentQuestion({
        question: '',
        options: ['', '', '', ''],
        correctAnswer: 0
      });
    }
  };

  const handleCreateQuiz = async () => {
    if (!newQuiz.title || newQuiz.questions.length === 0) return;
    
    try {
      setCreatingQuiz(true);
      const url = editingQuiz 
        ? `/api/buddy/classroom/${matchId}/quizzes/${editingQuiz.id}`
        : `/api/buddy/classroom/${matchId}/quizzes`;
      
      const method = editingQuiz ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          title: newQuiz.title,
          totalMarks: newQuiz.totalMarks,
          dueDate: newQuiz.dueDate || null,
          questions: newQuiz.questions.map(q => ({
            question: q.question,
            options: q.options,
            correctAnswer: q.correctAnswer
          }))
        }),
      });

      if (!response.ok) {
        throw new Error(editingQuiz ? 'Failed to update quiz' : 'Failed to create quiz');
      }

      const data = await response.json();
      
      if (editingQuiz) {
        setQuizzes(quizzes.map(q => q.id === editingQuiz.id ? data.quiz : q));
      } else {
        setQuizzes([data.quiz, ...quizzes]);
      }
      
      setNewQuiz({ title: '', totalMarks: 10, dueDate: '', questions: [] });
      setEditingQuiz(null);
      setShowQuizModal(false);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to save quiz');
    } finally {
      setCreatingQuiz(false);
    }
  };

  const handleViewQuizResults = async (quiz: Quiz) => {
    setViewingQuizResults(quiz);
    setLoadingQuizResults(true);
    
    try {
      const response = await fetch(`/api/buddy/classroom/${matchId}/quizzes/${quiz.id}/results`);
      if (!response.ok) {
        throw new Error('Failed to fetch quiz results');
      }
      const data = await response.json();
      setQuizAttempts(data.attempts || []);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to fetch quiz results');
    } finally {
      setLoadingQuizResults(false);
    }
  };

  // Assignment handlers
  const handleOpenEditAssignment = (assignment: Assignment) => {
    setEditingAssignment(assignment);
    setNewAssignment({
      title: assignment.title,
      description: assignment.description,
      dueDate: assignment.dueDate,
      attachments: []
    });
    setShowAssignmentModal(true);
  };

  const handleCreateAssignment = async () => {
    if (!newAssignment.title || !newAssignment.description || !newAssignment.dueDate) return;
    
    try {
      setCreatingAssignment(true);
      const formData = new FormData();
      formData.append('title', newAssignment.title);
      formData.append('description', newAssignment.description);
      formData.append('dueDate', newAssignment.dueDate);
      
      newAssignment.attachments.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
      });

      const url = editingAssignment 
        ? `/api/buddy/classroom/${matchId}/assignments/${editingAssignment.id}`
        : `/api/buddy/classroom/${matchId}/assignments`;
      
      // Use POST with _method=PUT for edits (PHP can't parse multipart form data on PUT requests)
      if (editingAssignment) {
        formData.append('_method', 'PUT');
      }

      const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(editingAssignment ? 'Failed to update assignment' : 'Failed to create assignment');
      }

      const data = await response.json();
      
      if (editingAssignment) {
        setAssignments(assignments.map(a => a.id === editingAssignment.id ? data.assignment : a));
      } else {
        setAssignments([data.assignment, ...assignments]);
      }
      
      setNewAssignment({ title: '', description: '', dueDate: '', attachments: [] });
      setEditingAssignment(null);
      setShowAssignmentModal(false);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to save assignment');
    } finally {
      setCreatingAssignment(false);
    }
  };

  const handleViewSubmissions = async (assignment: Assignment) => {
    setViewingAssignment(assignment);
    setShowSubmissionsViewer(true);
    setLoadingSubmissions(true);
    
    try {
      if (!matchId) {
        throw new Error('No match selected');
      }
      const response = await fetch(`/api/buddy/classroom/${matchId}/assignments/${assignment.id}/submissions`);
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'Failed to fetch submissions');
      }
      const data = await response.json();
      setAssignmentSubmissions(data.submissions || []);
      if (data.assignment?.totalMarks !== undefined) {
        setCurrentAssignmentTotalMarks(data.assignment.totalMarks);
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to fetch submissions');
    } finally {
      setLoadingSubmissions(false);
    }
  };

  const handleGradeSubmission = async () => {
    if (!gradingSubmission || !viewingAssignment) return;
    try {
      setSavingGrade(true);
      const response = await fetch(
        `/api/buddy/classroom/${matchId}/assignments/${viewingAssignment.id}/submissions/${gradingSubmission.id}/grade`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify({ marks: gradeMarks, feedback: gradeFeedback }),
        }
      );
      if (!response.ok) {
        const err = await response.json().catch(() => ({}));
        throw new Error(err.error || 'Failed to save grade');
      }
      const data = await response.json();
      // Update in-place
      setAssignmentSubmissions(prev =>
        prev.map(s =>
          s.id === gradingSubmission.id
            ? { ...s, marks: data.submission.marks, feedback: data.submission.feedback }
            : s
        )
      );
      setGradingSubmission(null);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to save grade');
    } finally {
      setSavingGrade(false);
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-8 h-8 text-blue-600 animate-spin" />
        <span className="ml-2 text-gray-600">Loading classroom data...</span>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <AlertCircle className="w-12 h-12 text-red-600 mx-auto mb-4" />
        <p className="text-red-800">{error}</p>
        <button
          onClick={fetchClassroomData}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors cursor-pointer"
        >
          Try Again
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-xl border border-gray-200 p-6">
        <h2 className="text-gray-900 mb-2">Virtual Classroom - Mentor View</h2>
        <p className="text-gray-600">
          Manage study materials, quizzes, and assignments for your mentees
        </p>
    
        {mentees.length === 0 && (
          <div className="mt-4 text-sm text-amber-600">
            No mentees found for this match. Materials, quizzes, and assignments will be shared across all your mentees.
          </div>
        )}
      </div>

      {/* Tab Navigation */}
      <div className="bg-white rounded-xl border border-gray-200 p-2">
        <div className="flex gap-2">
          <button
            onClick={() => setActiveTab('materials')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'materials'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <FileText className="w-4 h-4" />
            Study Materials
          </button>
          <button
            onClick={() => setActiveTab('quizzes')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'quizzes'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <Award className="w-4 h-4" />
            Quizzes
          </button>
          <button
            onClick={() => setActiveTab('assignments')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors cursor-pointer ${
              activeTab === 'assignments'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            }`}
          >
            <CheckCircle className="w-4 h-4" />
            Assignments
          </button>
        </div>
      </div>

      {/* Materials Tab */}
      {activeTab === 'materials' && (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-gray-900">Study Materials</h3>
            {!isReadonly && (
              <button
                onClick={() => { setEditingMaterial(null); setNewMaterial({ name: '', description: '', file: null }); setShowMaterialModal(true); }}
                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
              >
                <Plus className="w-4 h-4" />
                Upload Material
              </button>
            )}
          </div>

          {materials.length === 0 ? (
            <div className="text-center py-12">
              <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No materials uploaded yet</p>
            </div>
          ) : (
            <div className="space-y-3">
              {materials.map(material => (
                <div key={material.id} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                      <h4 className="text-gray-900 mb-2">{material.name}</h4>
                      {material.description && (
                        <p className="text-gray-600 mb-3">{material.description}</p>
                      )}
                      <div className="flex items-center gap-4 text-gray-500">
                        <button
                          onClick={() => handleDownloadMaterial(material.id, material.fileName)}
                          className="flex items-center gap-1 text-blue-600 hover:underline cursor-pointer"
                        >
                          <FileText className="w-4 h-4" />
                          {material.fileName}
                        </button>
                        <span>{material.fileSize}</span>
                        <span>Uploaded: {material.uploadedDate}</span>
                      </div>
                    </div>
                    {!isReadonly && (
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => handleOpenEditMaterial(material)}
                          className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                          title="Edit"
                      >
                        <Edit className="w-5 h-5" />
                      </button>
                      <button
                        onClick={() => handleDeleteMaterial(material.id)}
                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                        title="Delete"
                      >
                        <Trash2 className="w-5 h-5" />
                      </button>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Quizzes Tab */}
      {activeTab === 'quizzes' && (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-gray-900">Skill Assessment Quizzes</h3>
            {!isReadonly && (
              <button
                onClick={() => { setEditingQuiz(null); setNewQuiz({ title: '', totalMarks: 10, dueDate: '', questions: [] }); setShowQuizModal(true); }}
                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
              >
                <Plus className="w-4 h-4" />
                Create Quiz
              </button>
            )}
          </div>

          {quizzes.length === 0 ? (
            <div className="text-center py-12">
              <Award className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No quizzes created yet</p>
            </div>
          ) : (
            <div className="space-y-3">
              {quizzes.map(quiz => (
                <div key={quiz.id} className="border border-gray-200 rounded-lg p-4">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h4 className="text-gray-900">{quiz.title}</h4>
                        <span className={`px-3 py-1 rounded-full cursor-pointer ${
                          quiz.status === 'open' 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                        }`}>
                          {quiz.status === 'open' ? 'Open' : 'Closed'}
                        </span>
                      </div>
                      <div className="flex items-center gap-4 text-gray-600 mb-3">
                        <span>{quiz.questions.length} questions</span>
                        <span>•</span>
                        <span>Total Marks: {quiz.totalMarks}</span>
                        <span>•</span>
                        <span className="flex items-center gap-1">
                          <Clock className="w-4 h-4" />
                          Due: {quiz.dueDate}
                        </span>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      {!isReadonly && (
                        <button
                          onClick={() => handleOpenEditQuiz(quiz)}
                          className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                        >
                          <Edit className="w-4 h-4 inline mr-1" />
                          Edit
                        </button>
                      )}
                      <button
                        onClick={() => handleViewQuizResults(quiz)}
                        className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                      >
                        View Results
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Assignments Tab */}
      {activeTab === 'assignments' && (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-gray-900">Assignments</h3>
            {!isReadonly && (
              <button
                onClick={() => { setEditingAssignment(null); setNewAssignment({ title: '', description: '', dueDate: '', attachments: [] }); setShowAssignmentModal(true); }}
                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
              >
                <Plus className="w-4 h-4" />
                Create Assignment
              </button>
            )}
          </div>

          {assignments.length === 0 ? (
            <div className="text-center py-12">
              <CheckCircle className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No assignments posted yet</p>
            </div>
          ) : (
            <div className="space-y-4">
              {assignments.map(assignment => {
                const isPastDue = new Date(assignment.dueDate) < new Date();
                return (
                  <div key={assignment.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1">
                        <h4 className="text-gray-900 mb-2">{assignment.title}</h4>
                        <p className="text-gray-600 mb-3">{assignment.description}</p>
                        <div className="flex items-center gap-4 text-gray-600">
                          <span className="flex items-center gap-1">
                            <Clock className="w-4 h-4" />
                            Due: {assignment.dueDate}
                          </span>
                          {isPastDue && (
                            <>
                              <span>•</span>
                              <span className="text-red-600">Overdue</span>
                            </>
                          )}
                        </div>
                        {assignment.attachments.length > 0 && (
                          <div className="mt-3">
                            <p className="text-gray-600 mb-2">Attachments:</p>
                            <div className="space-y-1">
                              {assignment.attachments.map((file, index) => (
                                <button 
                                  key={index} 
                                  onClick={() => window.open(`api/buddy/classroom/${matchId}/assignments/${assignment.id}/attachment/${encodeURIComponent(file)}`, '_blank')}
                                  className="flex items-center gap-2 text-blue-600 hover:underline cursor-pointer"
                                >
                                  <Download className="w-4 h-4" />
                                  {file}
                                </button>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                      <div className="flex items-center gap-2">
                        {!isReadonly && (
                          <button
                            onClick={() => handleOpenEditAssignment(assignment)}
                            className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                          >
                            <Edit className="w-4 h-4 inline mr-1" />
                            Edit
                          </button>
                        )}
                        <button
                          onClick={() => handleViewSubmissions(assignment)}
                          className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                        >
                          View Submissions
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* Upload Material Modal */}
      {showMaterialModal && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => {
            setShowMaterialModal(false);
            setNewMaterial({ name: '', description: '', file: null });
            setEditingMaterial(null);
          }}
        >
          <div
            className="bg-white rounded-xl max-w-md w-full p-6"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-4">{editingMaterial ? 'Edit Study Material' : 'Upload Study Material'}</h3>

            <div className="space-y-4 mb-6">
              <div>
                <label className="block text-gray-700 mb-2">Material Name *</label>
                <input
                  type="text"
                  value={newMaterial.name}
                  onChange={(e) => setNewMaterial({ ...newMaterial, name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., Chapter 5 Notes"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Description (Optional)</label>
                <textarea
                  value={newMaterial.description}
                  onChange={(e) => setNewMaterial({ ...newMaterial, description: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  rows={3}
                  placeholder="Brief description of the material"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">
                  {editingMaterial ? 'Replace File (Optional)' : 'Upload File *'}
                </label>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                  <Upload className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p className="text-gray-600 mb-2">Click to upload or drag and drop</p>
                  <p className="text-gray-500">PDF, DOC, PPT, TXT, ZIP (Max 10MB)</p>
                  <input
                    type="file"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip"
                    onChange={(e) => setNewMaterial({ ...newMaterial, file: e.target.files?.[0] || null })}
                    className="w-full mt-3 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  {newMaterial.file && (
                    <p className="mt-2 text-sm text-green-600">Selected: {newMaterial.file.name}</p>
                  )}
                  {editingMaterial && !newMaterial.file && (
                    <p className="mt-2 text-sm text-gray-600">Current file: {editingMaterial.fileName}</p>
                  )}
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowMaterialModal(false);
                  setNewMaterial({ name: '', description: '', file: null });
                  setEditingMaterial(null);
                }}
                disabled={uploadingMaterial}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={handleUploadMaterial}
                disabled={!newMaterial.name || (!editingMaterial && !newMaterial.file) || uploadingMaterial}
                className={`flex-1 px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2 cursor-pointer ${
                  newMaterial.name && (editingMaterial || newMaterial.file) && !uploadingMaterial
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`}
              >
                {uploadingMaterial && <Loader2 className="w-4 h-4 animate-spin" />}
                {uploadingMaterial ? (editingMaterial ? 'Updating...' : 'Uploading...') : (editingMaterial ? 'Update' : 'Upload')}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Create/Edit Quiz Modal */}
      {showQuizModal && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto"
          onClick={() => {
            setShowQuizModal(false);
            setEditingQuiz(null);
            setNewQuiz({ title: '', totalMarks: 10, dueDate: '', questions: [] });
          }}
        >
          <div
            className="bg-white rounded-xl max-w-2xl w-full p-6 my-8"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-4">{editingQuiz ? 'Edit Quiz' : 'Create Quiz'}</h3>

            {(() => {
              const hasAttempts = editingQuiz && (editingQuiz as any).attemptsCount > 0;
              return (
            <div className="space-y-4 mb-6">
              {hasAttempts && (
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-amber-800 text-sm">
                  Mentees have already attempted this quiz. You can only edit the Quiz Title and Due Date.
                </div>
              )}
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-gray-700 mb-2">Quiz Title *</label>
                  <input
                    type="text"
                    value={newQuiz.title}
                    onChange={(e) => setNewQuiz({ ...newQuiz, title: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., Integration Fundamentals"
                  />
                </div>
                <div>
                  <label className="block text-gray-700 mb-2">Total Marks *</label>
                  <input
                    type="number"
                    value={newQuiz.totalMarks}
                    onChange={(e) => setNewQuiz({ ...newQuiz, totalMarks: parseInt(e.target.value) })}
                    disabled={!!hasAttempts}
                    className={`w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${hasAttempts ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                  />
                </div>
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Due Date (Optional)</label>
                <input
                  type="date"
                  value={newQuiz.dueDate}
                  min={new Date().toISOString().split('T')[0]}
                  onChange={(e) => setNewQuiz({ ...newQuiz, dueDate: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              {/* Questions List */}
              {newQuiz.questions.length > 0 && (
                <div className="border border-gray-200 rounded-lg p-4">
                  <h4 className="text-gray-900 mb-3">Questions {hasAttempts ? '(Locked - mentees have attempted)' : `(${newQuiz.questions.length})`}</h4>
                  <div className="space-y-2">
                    {newQuiz.questions.map((q, index) => (
                      <div key={q.id} className="p-3 bg-gray-50 rounded">
                        <div className="flex items-start justify-between mb-2">
                          <span className="text-gray-900 font-medium">{index + 1}. {q.question}</span>
                          {!hasAttempts && (
                            <div className="flex gap-2">
                              <button
                                onClick={() => {
                                  // Load question into edit form
                                  setCurrentQuestion({
                                    question: q.question,
                                    options: [...q.options],
                                    correctAnswer: q.correctAnswer
                                  });
                                  // Remove from list so it can be re-added after editing
                                  setNewQuiz({
                                    ...newQuiz,
                                    questions: newQuiz.questions.filter((_, i) => i !== index)
                                  });
                                }}
                                className="text-blue-600 hover:bg-blue-50 p-1 rounded cursor-pointer"
                                title="Edit"
                              >
                                <Edit className="w-4 h-4" />
                              </button>
                              <button
                                onClick={() => setNewQuiz({
                                  ...newQuiz,
                                  questions: newQuiz.questions.filter((_, i) => i !== index)
                                })}
                                className="text-red-600 hover:bg-red-50 p-1 rounded cursor-pointer"
                                title="Delete"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            </div>
                          )}
                        </div>
                        <div className="text-sm text-gray-600 ml-4">
                          {q.options.map((opt, optIdx) => (
                            <div key={optIdx} className="flex items-center gap-2">
                              {optIdx === q.correctAnswer ? (
                                <CheckCircle className="w-4 h-4 text-green-600" />
                              ) : (
                                <span className="w-4 h-4" />
                              )}
                              <span>{opt}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Add Question Form - Only show when no attempts exist */}
              {!hasAttempts && (
                <div className="border border-blue-200 bg-blue-50 rounded-lg p-4">
                  <h4 className="text-gray-900 mb-3">Add Question</h4>
                <div className="space-y-3">
                  <input
                    type="text"
                    value={currentQuestion.question}
                    onChange={(e) => setCurrentQuestion({ ...currentQuestion, question: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter question"
                  />
                  {currentQuestion.options.map((option, index) => (
                    <div key={index} className="flex items-center gap-2">
                      <input
                        type="radio"
                        name="correctAnswer"
                        checked={currentQuestion.correctAnswer === index}
                        onChange={() => setCurrentQuestion({ ...currentQuestion, correctAnswer: index })}
                        className="w-4 h-4 text-green-600"
                      />
                      <input
                        type="text"
                        value={option}
                        onChange={(e) => {
                          const newOptions = [...currentQuestion.options];
                          newOptions[index] = e.target.value;
                          setCurrentQuestion({ ...currentQuestion, options: newOptions });
                        }}
                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder={`Option ${index + 1}`}
                      />
                    </div>
                  ))}
                  <p className="text-gray-600">Select the radio button for the correct answer</p>
                  <button
                    onClick={handleAddQuestion}
                    className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer"
                  >
                    Add Question
                  </button>
                </div>
              </div>
              )}
            </div>
              );
            })()}

            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowQuizModal(false);
                  setNewQuiz({ title: '', totalMarks: 10, dueDate: '', questions: [] });
                  setEditingQuiz(null);
                }}
                disabled={creatingQuiz}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={handleCreateQuiz}
                disabled={!newQuiz.title || newQuiz.questions.length === 0 || creatingQuiz}
                className={`flex-1 px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2 cursor-pointer ${
                  newQuiz.title && newQuiz.questions.length > 0 && !creatingQuiz
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`}
              >
                {creatingQuiz && <Loader2 className="w-4 h-4 animate-spin" />}
                {creatingQuiz ? (editingQuiz ? 'Updating...' : 'Creating...') : (editingQuiz ? 'Update Quiz' : 'Create Quiz')}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Create/Edit Assignment Modal */}
      {showAssignmentModal && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => {
            setShowAssignmentModal(false);
            setEditingAssignment(null);
            setNewAssignment({ title: '', description: '', dueDate: '', attachments: [] });
          }}
        >
          <div
            className="bg-white rounded-xl max-w-md w-full p-6"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-4">{editingAssignment ? 'Edit Assignment' : 'Create Assignment'}</h3>

            <div className="space-y-4 mb-6">
              <div>
                <label className="block text-gray-700 mb-2">Assignment Title *</label>
                <input
                  type="text"
                  value={newAssignment.title}
                  onChange={(e) => setNewAssignment({ ...newAssignment, title: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="e.g., Problem Set 1"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Description *</label>
                <textarea
                  value={newAssignment.description}
                  onChange={(e) => setNewAssignment({ ...newAssignment, description: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  rows={4}
                  placeholder="Detailed instructions for the assignment"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Due Date *</label>
                <input
                  type="date"
                  value={newAssignment.dueDate}
                  min={new Date().toISOString().split('T')[0]}
                  onChange={(e) => setNewAssignment({ ...newAssignment, dueDate: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-2">Attachments (Optional)</label>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                  <Upload className="w-6 h-6 text-gray-400 mx-auto mb-2" />
                  <p className="text-gray-600 mb-2">Click to attach files</p>
                  <input
                    type="file"
                    multiple
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                    onChange={(e) => setNewAssignment({ 
                      ...newAssignment, 
                      attachments: e.target.files ? Array.from(e.target.files) : [] 
                    })}
                    className="w-full px-4 py-2"
                  />
                  {newAssignment.attachments.length > 0 && (
                    <p className="mt-2 text-sm text-green-600">
                      {newAssignment.attachments.length} file(s) selected
                    </p>
                  )}
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowAssignmentModal(false);
                  setNewAssignment({ title: '', description: '', dueDate: '', attachments: [] });
                  setEditingAssignment(null);
                }}
                disabled={creatingAssignment}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={handleCreateAssignment}
                disabled={!newAssignment.title || !newAssignment.description || !newAssignment.dueDate || creatingAssignment}
                className={`flex-1 px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2 cursor-pointer ${
                  newAssignment.title && newAssignment.description && newAssignment.dueDate && !creatingAssignment
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`}
              >
                {creatingAssignment && <Loader2 className="w-4 h-4 animate-spin" />}
                {creatingAssignment ? (editingAssignment ? 'Updating...' : 'Creating...') : (editingAssignment ? 'Update' : 'Create')}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* View Submissions Modal */}
      {showSubmissionsViewer && viewingAssignment && (() => {
        const totalMentees = mentees.length;
        const submittedCount = assignmentSubmissions.length;
        const notSubmittedCount = totalMentees - submittedCount;
        const submittedMenteeIds = assignmentSubmissions.map(sub => sub.studentId);
        const notSubmittedMentees = mentees.filter(m => !submittedMenteeIds.includes(m.studentId));

        return (
          <div
            className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto"
            onClick={() => setShowSubmissionsViewer(false)}
          >
            <div
              className="bg-white rounded-xl max-w-3xl w-full p-6 my-8"
              onClick={e => e.stopPropagation()}
            >
              <h3 className="text-gray-900 mb-6">Submissions for {viewingAssignment.title}</h3>

              {loadingSubmissions ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="w-8 h-8 text-blue-600 animate-spin" />
                  <span className="ml-2 text-gray-600">Loading submissions...</span>
                </div>
              ) : (
                <>
              {/* Submission Statistics */}
              <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-2">
                    <p className="text-gray-700">Total Assigned</p>
                    <Users className="w-5 h-5 text-blue-600" />
                  </div>
                  <p className="text-blue-900">{totalMentees} mentees</p>
                </div>
                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-2">
                    <p className="text-gray-700">Submitted</p>
                    <CheckCircle className="w-5 h-5 text-green-600" />
                  </div>
                  <p className="text-green-900">{submittedCount} mentees</p>
                </div>
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-2">
                    <p className="text-gray-700">Not Submitted</p>
                    <AlertCircle className="w-5 h-5 text-amber-600" />
                  </div>
                  <p className="text-amber-900">{notSubmittedCount} mentees</p>
                </div>
              </div>

              {/* Mentees Who Haven't Submitted */}
              {notSubmittedMentees.length > 0 && (
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                  <div className="flex items-start gap-2">
                    <AlertCircle className="w-5 h-5 text-amber-600 mt-0.5" />
                    <div className="flex-1">
                      <p className="text-amber-900 mb-2">Pending Submissions:</p>
                      <div className="flex flex-wrap gap-2">
                        {notSubmittedMentees.map((mentee) => (
                          <span key={mentee.id} className="px-3 py-1 bg-white border border-amber-300 rounded-full text-amber-900">
                            {mentee.name} ({mentee.studentId})
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Submitted Assignments List */}
              <div>
                <h4 className="text-gray-900 mb-4">Submitted Assignments ({assignmentSubmissions.length})</h4>
                <div className="space-y-3">
                  {assignmentSubmissions.map((submission) => (
                    <div key={submission.id} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                      <div className="flex items-start justify-between gap-4">
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-2">
                            <h5 className="text-gray-900">{submission.studentName}</h5>
                            <span className="text-gray-600">({submission.studentId})</span>
                            <span className={`px-3 py-1 rounded-full cursor-pointer ${
                              submission.status === 'on-time' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-red-100 text-red-800'
                            }`}>
                              {submission.status === 'on-time' ? 'On Time' : 'Late'}
                            </span>
                          </div>
                          <div className="flex items-center gap-4 text-gray-600 mb-2">
                            <span className="flex items-center gap-1">
                              <Download className="w-4 h-4" />
                              <button
                                onClick={() => handlePreviewAssignment(viewingAssignment.id, submission.id, submission.fileName)}
                                className="text-blue-600 hover:underline cursor-pointer"
                              >
                                {submission.fileName}
                              </button>
                            </span>
                            <span>•</span>
                            <span>Submitted: {submission.submittedDate}</span>
                          </div>
                          {submission.marks !== null && submission.marks !== undefined ? (
                            <div className="mt-2 flex items-center gap-3">
                              <span className="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full font-medium">
                                <Star className="w-4 h-4" />
                                {submission.marks} / {currentAssignmentTotalMarks} marks
                              </span>
                              {submission.feedback && (
                                <span className="text-gray-600 text-sm italic">"{submission.feedback}"</span>
                              )}
                            </div>
                          ) : (
                            <div className="mt-2">
                              <span className="inline-block px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm">Not marked yet</span>
                            </div>
                          )}
                        </div>
                        <div className="flex-shrink-0">
                          {!isReadonly && (
                            <button
                              onClick={() => {
                                setGradingSubmission(submission);
                                setGradeMarks(submission.marks ?? 0);
                                setGradeFeedback(submission.feedback ?? '');
                                setShowSubmissionsViewer(false);
                              }}
                              className="flex items-center gap-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors cursor-pointer text-sm"
                            >
                              <Star className="w-4 h-4" />
                              {submission.marks !== null && submission.marks !== undefined ? 'Edit Grade' : 'Grade'}
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
              </>
              )}

              <div className="flex gap-3 mt-6">
                <button
                  onClick={() => {
                    setShowSubmissionsViewer(false);
                    setViewingAssignment(null);
                    setAssignmentSubmissions([]);
                  }}
                  className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        );
      })()}

      {/* Grading Modal */}
      {gradingSubmission && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[60]"
          onClick={() => !savingGrade && setGradingSubmission(null)}
        >
          <div
            className="bg-white rounded-xl max-w-md w-full p-6"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-1">Mark Submission</h3>
            <p className="text-gray-600 text-sm mb-4">
              {gradingSubmission.studentName} &mdash; {gradingSubmission.studentId}
            </p>

            <div className="bg-gray-50 rounded-lg p-3 mb-4 flex items-center gap-2">
              <FileText className="w-4 h-4 text-gray-500" />
              <span className="text-gray-700 text-sm">{gradingSubmission.fileName}</span>
              <button
                onClick={() => handlePreviewAssignment(viewingAssignment!.id, gradingSubmission.id, gradingSubmission.fileName)}
                className="ml-auto text-blue-600 text-sm hover:underline cursor-pointer"
              >
                Preview
              </button>
            </div>

            <div className="space-y-4 mb-6">
              <div>
                <label className="block text-gray-700 mb-1">Marks (out of {currentAssignmentTotalMarks}) *</label>
                <input
                  type="number"
                  min={0}
                  max={currentAssignmentTotalMarks}
                  value={gradeMarks}
                  onChange={e => setGradeMarks(Math.min(currentAssignmentTotalMarks, Math.max(0, Number(e.target.value))))}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-gray-700 mb-1">Feedback (Optional)</label>
                <textarea
                  value={gradeFeedback}
                  onChange={e => setGradeFeedback(e.target.value)}
                  rows={3}
                  placeholder="Write feedback for the mentee..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                />
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => setGradingSubmission(null)}
                disabled={savingGrade}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 cursor-pointer"
              >
                Cancel
              </button>
              <button
                onClick={handleGradeSubmission}
                disabled={savingGrade}
                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
              >
                {savingGrade && <Loader2 className="w-4 h-4 animate-spin" />}
                {savingGrade ? 'Saving...' : 'Save Grade'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* View Quiz Results Modal */}
      {viewingQuizResults && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 overflow-y-auto"
          onClick={() => {
            setViewingQuizResults(null);
            setQuizAttempts([]);
          }}
        >
          <div
            className="bg-white rounded-xl max-w-3xl w-full p-6 my-8"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-6">Results for {viewingQuizResults.title}</h3>

            {loadingQuizResults ? (
              <div className="flex items-center justify-center py-12">
                <Loader2 className="w-8 h-8 text-blue-600 animate-spin" />
                <span className="ml-2 text-gray-600">Loading results...</span>
              </div>
            ) : (
              <>
            {/* Submission Statistics */}
            <div className="grid grid-cols-3 gap-4 mb-6">
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <p className="text-gray-700">Total Assigned</p>
                  <Users className="w-5 h-5 text-blue-600" />
                </div>
                <p className="text-blue-900">{mentees.length} mentees</p>
              </div>
              <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <p className="text-gray-700">Completed</p>
                  <CheckCircle className="w-5 h-5 text-green-600" />
                </div>
                <p className="text-green-900">{quizAttempts.length} mentees</p>
              </div>
              <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-2">
                  <p className="text-gray-700">Not Attempted</p>
                  <AlertCircle className="w-5 h-5 text-amber-600" />
                </div>
                <p className="text-amber-900">{mentees.length - quizAttempts.length} mentees</p>
              </div>
            </div>

            {/* Mentees Who Haven't Submitted */}
            {(() => {
              const submittedStudentIds = quizAttempts.map(a => a.studentId);
              const notSubmittedMentees = mentees.filter(m => !submittedStudentIds.includes(m.studentId));
              return notSubmittedMentees.length > 0 && (
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                  <div className="flex items-start gap-2">
                    <AlertCircle className="w-5 h-5 text-amber-600 mt-0.5" />
                    <div className="flex-1">
                      <p className="text-amber-900 mb-2">Pending Attempts:</p>
                      <div className="flex flex-wrap gap-2">
                        {notSubmittedMentees.map((mentee) => (
                          <span key={mentee.id} className="px-3 py-1 bg-white border border-amber-300 rounded-full text-amber-900">
                            {mentee.name} ({mentee.studentId})
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })()}

            {/* Submitted Quizzes List */}
            <div>
              <h4 className="text-gray-900 mb-4">Quiz Submissions ({quizAttempts.length})</h4>
              <div className="space-y-3">
                {quizAttempts.map((attempt) => (
                  <div key={`${attempt.studentId}-${attempt.quizId}`} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <h5 className="text-gray-900">{attempt.studentName}</h5>
                          <span className="text-gray-600">({attempt.studentId})</span>
                        </div>
                        <div className="flex items-center gap-4 text-gray-600 mb-2">
                          <span className="text-gray-900">Score: {attempt.score}/{attempt.totalMarks}</span>
                          <span>•</span>
                          <span>Percentage: {((attempt.score / attempt.totalMarks) * 100).toFixed(1)}%</span>
                          <span>•</span>
                          <span>Completed: {attempt.completedDate}</span>
                        </div>
                        <div className={`inline-block px-3 py-1 rounded-full cursor-pointer ${
                          (attempt.score / attempt.totalMarks) >= 0.7 
                            ? 'bg-green-100 text-green-800' 
                            : (attempt.score / attempt.totalMarks) >= 0.5
                              ? 'bg-amber-100 text-amber-800'
                              : 'bg-red-100 text-red-800'
                        }`}>
                          {(attempt.score / attempt.totalMarks) >= 0.7 ? 'Excellent' : 
                           (attempt.score / attempt.totalMarks) >= 0.5 ? 'Good' : 'Needs Improvement'}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
            </>
            )}

            <div className="flex gap-3 mt-6">
              <button
                onClick={() => {
                  setViewingQuizResults(null);
                  setQuizAttempts([]);
                }}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
