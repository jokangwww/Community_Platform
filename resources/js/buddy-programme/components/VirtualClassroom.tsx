import { useState, useEffect, useCallback } from 'react';
import { FileText, Upload, Download, Clock, CheckCircle, AlertCircle, Award, Loader2, X } from 'lucide-react';

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
  totalMarks: number;
  attachments: string[];
  createdDate: string;
}

interface VirtualClassroomProps {
  userName: string;
  matchId?: string;
  studentId?: string;
  onLoad?: () => void;
  onActivityChange?: () => void;
}

export function VirtualClassroom({ userName, matchId, studentId, onLoad, onActivityChange }: VirtualClassroomProps) {
  const [activeTab, setActiveTab] = useState<'materials' | 'quizzes' | 'assignments'>('materials');
  const [materials, setMaterials] = useState<StudyMaterial[]>([]);
  const [quizzes, setQuizzes] = useState<Quiz[]>([]);
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Quiz taking
  const [takingQuiz, setTakingQuiz] = useState<Quiz | null>(null);
  const [quizAnswers, setQuizAnswers] = useState<number[]>([]);
  const [quizResult, setQuizResult] = useState<QuizAttempt | null>(null);
  const [submittingQuiz, setSubmittingQuiz] = useState(false);

  // Assignment submission
  const [showSubmissionModal, setShowSubmissionModal] = useState(false);
  const [selectedAssignment, setSelectedAssignment] = useState<Assignment | null>(null);
  const [submissionFile, setSubmissionFile] = useState<File | null>(null);
  const [submittingAssignment, setSubmittingAssignment] = useState(false);
  const [showAssignmentDetailsModal, setShowAssignmentDetailsModal] = useState(false);
  const [viewingAssignment, setViewingAssignment] = useState<Assignment | null>(null);

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
  const handlePreviewMaterial = async (materialId: string, fileName: string) => {
    try {
      const response = await fetch(`/api/buddy/classroom/${matchId}/materials/${materialId}/download`);
      if (!response.ok) {
        throw new Error('Failed to fetch material');
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
      alert(err instanceof Error ? err.message : 'Failed to preview material');
    }
  };

  // Quiz handlers
  const handleStartQuiz = (quiz: Quiz) => {
    setTakingQuiz(quiz);
    setQuizAnswers(new Array(quiz.questions.length).fill(-1));
    setQuizResult(null);
  };

  const handleSubmitQuiz = async () => {
    if (!takingQuiz) return;

    try {
      setSubmittingQuiz(true);
      const response = await fetch(`/api/buddy/classroom/${matchId}/quizzes/${takingQuiz.id}/submit`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ answers: quizAnswers }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'Failed to submit quiz');
      }

      const data = await response.json();
      setQuizResult(data.result);
      
      // Update takingQuiz with correct answers for review
      if (data.questions) {
        setTakingQuiz({
          ...takingQuiz,
          questions: data.questions
        });
      }
      
      // Update quizzes to mark as attempted
      setQuizzes(quizzes.map(q => 
        q.id === takingQuiz.id ? { ...q, hasAttempted: true } as any : q
      ));
      
      // Notify parent component to refresh
      if (onActivityChange) {
        onActivityChange();
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to submit quiz');
    } finally {
      setSubmittingQuiz(false);
    }
  };

  // Assignment handlers
  const handleSubmitAssignment = async () => {
    if (!submissionFile || !selectedAssignment) return;
    
    try {
      setSubmittingAssignment(true);
      const formData = new FormData();
      formData.append('file', submissionFile);

      const response = await fetch(`/api/buddy/classroom/${matchId}/assignments/${selectedAssignment.id}/submit`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'Failed to submit assignment');
      }

      const data = await response.json();
      
      // Update assignments to mark as submitted
      setAssignments(assignments.map(a => 
        a.id === selectedAssignment.id ? { ...a, hasSubmitted: true, submission: data.submission } as any : a
      ));
      
      setShowSubmissionModal(false);
      setSubmissionFile(null);
      alert('Assignment submitted successfully!');
      
      // Notify parent component to refresh
      if (onActivityChange) {
        onActivityChange();
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to submit assignment');
    } finally {
      setSubmittingAssignment(false);
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
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
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
        <h2 className="text-gray-900 mb-2">Virtual Classroom</h2>
        <p className="text-gray-600">
          Access study materials, quizzes, and assignments for your study group
        </p>
      </div>

      {/* Tab Navigation */}
      <div className="bg-white rounded-xl border border-gray-200 p-2">
        <div className="flex gap-2">
          <button
            onClick={() => setActiveTab('materials')}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors ${
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
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors ${
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
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg transition-colors ${
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
          </div>

          {materials.length === 0 ? (
            <div className="text-center py-12">
              <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No materials available yet</p>
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
                          onClick={() => handlePreviewMaterial(material.id, material.fileName)}
                          className="flex items-center gap-1 text-blue-600 hover:underline"
                        >
                          <FileText className="w-4 h-4" />
                          {material.fileName}
                        </button>
                        <span>{material.fileSize}</span>
                        <span>Uploaded: {material.uploadedDate}</span>
                      </div>
                    </div>
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
          </div>

          {!takingQuiz && !quizResult ? (
            quizzes.length === 0 ? (
              <div className="text-center py-12">
                <Award className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-600">No quizzes available yet</p>
              </div>
            ) : (
              <div className="space-y-3">
                {quizzes.map(quiz => (
                  <div key={quiz.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <h4 className="text-gray-900">{quiz.title}</h4>
                          <span className={`px-3 py-1 rounded-full ${
                            (quiz as any).hasAttempted
                              ? 'bg-blue-100 text-blue-800'
                              : quiz.status === 'open' 
                              ? 'bg-green-100 text-green-800' 
                              : 'bg-red-100 text-red-800'
                          }`}>
                            {(quiz as any).hasAttempted ? 'Completed' : quiz.status === 'open' ? 'Open' : 'Closed'}
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
                        {quiz.status === 'open' && !((quiz as any).hasAttempted) ? (
                          <button
                            onClick={() => handleStartQuiz(quiz)}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                          >
                            Start Quiz
                          </button>
                        ) : (quiz as any).hasAttempted ? (
                          <button
                            onClick={() => {
                              setTakingQuiz(quiz);
                              const attempt = (quiz as any).attempt;
                              if (attempt) {
                                setQuizAnswers(attempt.answers || []);
                                setQuizResult(attempt);
                              }
                            }}
                            className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                          >
                            View Details
                          </button>
                        ) : null}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )
          ) : quizResult ? (
            // Quiz Result View
            <div className="max-w-2xl mx-auto">
              <div className="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
                <div className="text-center">
                  <CheckCircle className="w-16 h-16 text-green-600 mx-auto mb-4" />
                  <h3 className="text-gray-900 mb-2">Quiz Completed!</h3>
                  <div className="mb-4">
                    <span className="text-gray-900">Your Score: {quizResult.score}/{quizResult.totalMarks}</span>
                  </div>
                  <p className="text-gray-600">Percentage: {((quizResult.score / quizResult.totalMarks) * 100).toFixed(1)}%</p>
                </div>
              </div>

              {/* Answer Review */}
              <div className="space-y-4 mb-6">
                <h4 className="text-gray-900">Answer Review</h4>
                {takingQuiz?.questions.map((q, index) => {
                  const isCorrect = quizAnswers[index] === q.correctAnswer;
                  return (
                    <div key={q.id} className={`border rounded-lg p-4 ${
                      isCorrect ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50'
                    }`}>
                      <div className="flex items-start gap-2 mb-3">
                        {isCorrect ? (
                          <CheckCircle className="w-5 h-5 text-green-600 mt-0.5" />
                        ) : (
                          <X className="w-5 h-5 text-red-600 mt-0.5" />
                        )}
                        <div className="flex-1">
                          <p className="text-gray-900 mb-2">{index + 1}. {q.question}</p>
                          <div className="space-y-1">
                            {q.options.map((option, optIndex) => (
                              <div
                                key={optIndex}
                                className={`p-2 rounded ${
                                  optIndex === q.correctAnswer
                                    ? 'bg-green-100 text-green-800'
                                    : optIndex === quizAnswers[index] && !isCorrect
                                      ? 'bg-red-100 text-red-800'
                                      : 'text-gray-700'
                                }`}
                              >
                                {option}
                                {optIndex === q.correctAnswer && ' ✓ (Correct)'}
                                {optIndex === quizAnswers[index] && optIndex !== q.correctAnswer && ' (Your answer)'}
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>

              <button
                onClick={() => {
                  setTakingQuiz(null);
                  setQuizResult(null);
                  setQuizAnswers([]);
                }}
                className="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                Back to Quizzes
              </button>
            </div>
          ) : (
            // Quiz Taking View
            <div className="max-w-2xl mx-auto">
              <div className="mb-6">
                <h3 className="text-gray-900 mb-2">{takingQuiz?.title}</h3>
                <p className="text-gray-600">Answer all questions and submit when ready</p>
              </div>

              <div className="space-y-6 mb-6">
                {takingQuiz?.questions.map((q, index) => (
                  <div key={q.id} className="border border-gray-200 rounded-lg p-4">
                    <p className="text-gray-900 mb-4">{index + 1}. {q.question}</p>
                    <div className="space-y-2">
                      {q.options.map((option, optIndex) => (
                        <label
                          key={optIndex}
                          className={`flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors ${
                            quizAnswers[index] === optIndex
                              ? 'border-blue-500 bg-blue-50'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <input
                            type="radio"
                            name={`question-${index}`}
                            checked={quizAnswers[index] === optIndex}
                            onChange={() => {
                              const newAnswers = [...quizAnswers];
                              newAnswers[index] = optIndex;
                              setQuizAnswers(newAnswers);
                            }}
                            className="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                          />
                          <span className="text-gray-900">{option}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                ))}
              </div>

              <div className="flex gap-3">
                <button
                  onClick={() => {
                    setTakingQuiz(null);
                    setQuizAnswers([]);
                  }}
                  disabled={submittingQuiz}
                  className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                >
                  Cancel
                </button>
                <button
                  onClick={handleSubmitQuiz}
                  disabled={quizAnswers.some(a => a === -1) || submittingQuiz}
                  className={`flex-1 px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2 ${
                    quizAnswers.some(a => a === -1) || submittingQuiz
                      ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      : 'bg-green-600 text-white hover:bg-green-700'
                  }`}
                >
                  {submittingQuiz && <Loader2 className="w-4 h-4 animate-spin" />}
                  {submittingQuiz ? 'Submitting...' : 'Submit Quiz'}
                </button>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Assignments Tab */}
      {activeTab === 'assignments' && (
        <div className="bg-white rounded-xl border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-gray-900">Assignments</h3>
          </div>

          {assignments.length === 0 ? (
            <div className="text-center py-12">
              <CheckCircle className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No assignments available yet</p>
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
                        <div className="flex items-center gap-4 text-gray-600 mb-3">
                          <span className="flex items-center gap-1">
                            <Clock className="w-4 h-4" />
                            Due: {assignment.dueDate}
                          </span>
                          <span>•</span>
                          <span>Total Marks: {assignment.totalMarks}</span>
                          {isPastDue && (
                            <>
                              <span>•</span>
                              <span className="text-red-600">Overdue</span>
                            </>
                          )}
                        </div>
                        {assignment.attachments.length > 0 && (
                          <div className="mb-3">
                            <p className="text-gray-600 mb-2">Attachments:</p>
                            <div className="space-y-1">
                              {assignment.attachments.map((file, index) => (
                                <button 
                                  key={index} 
                                  onClick={() => window.open(`/api/buddy/classroom/${matchId}/assignments/${assignment.id}/attachment/${encodeURIComponent(file)}`, '_blank')}
                                  className="flex items-center gap-2 text-blue-600 hover:underline"
                                >
                                  <Download className="w-4 h-4" />
                                  {file}
                                </button>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                      <div>
                        {!(assignment as any).hasSubmitted ? (
                          <button
                            onClick={() => {
                              setSelectedAssignment(assignment);
                              setShowSubmissionModal(true);
                            }}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                          >
                            Submit Assignment
                          </button>
                        ) : (
                          <button
                            onClick={() => {
                              setViewingAssignment(assignment);
                              setShowAssignmentDetailsModal(true);
                            }}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
                          >
                            <CheckCircle className="w-4 h-4" />
                            View Details
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* Submit Assignment Modal */}
      {showSubmissionModal && selectedAssignment && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setShowSubmissionModal(false)}
        >
          <div
            className="bg-white rounded-xl max-w-md w-full p-6"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-4">Submit Assignment</h3>

            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
              <h4 className="text-gray-900 mb-1">{selectedAssignment.title}</h4>
              <p className="text-gray-600">Due: {selectedAssignment.dueDate}</p>
            </div>

            <div className="mb-6">
              <label className="block text-gray-700 mb-2">Upload Your Work *</label>
              <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <Upload className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                <p className="text-gray-600 mb-2">Click to upload your completed assignment</p>
                <p className="text-gray-500">Supported formats: PDF, DOC, ZIP</p>
                <input
                  type="file"
                  accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                  onChange={(e) => setSubmissionFile(e.target.files?.[0] || null)}
                  className="w-full mt-3 px-4 py-2"
                />
                {submissionFile && (
                  <p className="mt-2 text-sm text-green-600">Selected: {submissionFile.name}</p>
                )}
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowSubmissionModal(false);
                  setSubmissionFile(null);
                }}
                disabled={submittingAssignment}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                onClick={handleSubmitAssignment}
                disabled={!submissionFile || submittingAssignment}
                className={`flex-1 px-4 py-2 rounded-lg transition-colors flex items-center justify-center gap-2 ${
                  submissionFile && !submittingAssignment
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`}
              >
                {submittingAssignment && <Loader2 className="w-4 h-4 animate-spin" />}
                {submittingAssignment ? 'Submitting...' : 'Submit'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Assignment Details Modal (View/Edit/Remove) */}
      {showAssignmentDetailsModal && viewingAssignment && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
          onClick={() => setShowAssignmentDetailsModal(false)}
        >
          <div
            className="bg-white rounded-xl max-w-md w-full p-6"
            onClick={e => e.stopPropagation()}
          >
            <h3 className="text-gray-900 mb-4">Assignment Submission</h3>

            <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
              <div className="flex items-center gap-2 mb-2">
                <CheckCircle className="w-5 h-5 text-green-600" />
                <h4 className="text-gray-900">Submitted</h4>
              </div>
              <p className="text-gray-700 font-medium">{viewingAssignment.title}</p>
              <p className="text-gray-600 text-sm mt-1">
                Submitted: {(viewingAssignment as any).submission?.submittedDate || 'Recently'}
              </p>
              {(viewingAssignment as any).submission?.fileName && (
                <button
                  onClick={async () => {
                    try {
                      const response = await fetch(`/api/buddy/classroom/${matchId}/assignments/${viewingAssignment.id}/submission/download`);
                      if (!response.ok) throw new Error('Failed to fetch submission');
                      const blob = await response.blob();
                      const blobUrl = URL.createObjectURL(blob);
                      const fileName = (viewingAssignment as any).submission.fileName;
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
                      alert('Failed to preview file');
                    }
                  }}
                  className="text-blue-600 hover:underline text-sm mt-1 flex items-center gap-1"
                >
                  <FileText className="w-4 h-4" />
                  {(viewingAssignment as any).submission.fileName}
                </button>
              )}
            </div>

            <div className="space-y-3 mb-6">
              {/* <button
                onClick={async () => {
                  try {
                    const submission = (viewingAssignment as any).submission;
                    if (submission?.filePath) {
                      window.open(`/api/buddy/classroom/${matchId}/assignments/${viewingAssignment.id}/submission/download`, '_blank');
                    }
                  } catch (err) {
                    alert('Failed to download submission');
                  }
                }}
                className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
              >
                <Download className="w-4 h-4" />
                Download Submission
              </button> */}

              <button
                onClick={() => {
                  setShowAssignmentDetailsModal(false);
                  setSelectedAssignment(viewingAssignment);
                  setShowSubmissionModal(true);
                }}
                className="w-full px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors flex items-center justify-center gap-2"
              >
                <Upload className="w-4 h-4" />
                Reupload Submission
              </button>

              <button
                onClick={async () => {
                  if (!confirm('Are you sure you want to remove your submission? This action cannot be undone.')) {
                    return;
                  }
                  
                  try {
                    const response = await fetch(`/api/buddy/classroom/${matchId}/assignments/${viewingAssignment.id}/submission`, {
                      method: 'DELETE',
                      headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        ...(studentId ? { 'X-Student-ID': studentId } : {}),
                      },
                    });

                    if (!response.ok) {
                      throw new Error('Failed to remove submission');
                    }

                    // Update assignments to mark as not submitted
                    setAssignments(assignments.map(a => 
                      a.id === viewingAssignment.id ? { ...a, hasSubmitted: false, submission: null } as any : a
                    ));
                    
                    setShowAssignmentDetailsModal(false);
                    alert('Submission removed successfully');
                  } catch (err) {
                    alert(err instanceof Error ? err.message : 'Failed to remove submission');
                  }
                }}
                className="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2"
              >
                <X className="w-4 h-4" />
                Remove Submission
              </button>
            </div>

            <button
              onClick={() => setShowAssignmentDetailsModal(false)}
              className="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
