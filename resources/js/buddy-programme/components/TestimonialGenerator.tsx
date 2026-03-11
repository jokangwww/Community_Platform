import { Award, Download, CheckCircle, AlertCircle, Calendar, Users, BookOpen, Star } from 'lucide-react';
import { formatDate } from '../../shared/utils/date';

interface MentorStats {
  name: string;
  studentId: string;
  programme: string;
  faculty: string;
  totalSessions: number;
  totalMentees: number;
  skillsTaught: string[];
  avgFeedbackScore: number;
  attendanceRate: number;
  semesterYear: string;
}

interface TestimonialGeneratorProps {
  mentorStats: MentorStats;
  isEligible: boolean;
  isApproved?: boolean;
}

export function TestimonialGenerator({ mentorStats, isEligible, isApproved = false }: TestimonialGeneratorProps) {
  const verificationCode = `TARUMT-BP-${mentorStats.studentId}-${new Date().getFullYear()}`;

  const downloadTestimonial = () => {
    // Create a printable version of the certificate
    const printContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>Certificate of Contribution - ${mentorStats.name}</title>
        <style>
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body { 
            font-family: 'Georgia', serif; 
            padding: 40px; 
            max-width: 800px; 
            margin: 0 auto;
            color: #1f2937;
          }
          .certificate { 
            border: 3px solid #1e40af; 
            padding: 40px; 
            background: white;
          }
          .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 2px solid #e5e7eb;
          }
          .award-icon {
            width: 80px;
            height: 80px;
            background: #1e40af;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
          }
          .header h1 { font-size: 28px; margin-bottom: 10px; color: #1f2937; }
          .header p { color: #4b5563; }
          .body { text-align: center; margin-bottom: 30px; }
          .body p { margin: 15px 0; color: #374151; }
          .name { font-size: 24px; font-weight: bold; color: #1f2937; }
          .details { color: #6b7280; font-size: 14px; }
          .summary { 
            background: #f9fafb; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0;
            text-align: left;
          }
          .summary h3 { text-align: center; margin-bottom: 15px; }
          .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
          .summary-item { display: flex; gap: 10px; }
          .summary-label { color: #6b7280; font-size: 14px; }
          .summary-value { color: #1f2937; font-weight: 500; }
          .footer { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 2px solid #e5e7eb;
          }
          .signatures { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 30px;
          }
          .signature { text-align: center; }
          .signature-line { 
            width: 150px; 
            height: 2px; 
            background: #1f2937; 
            margin-bottom: 5px;
          }
          .verification { text-align: center; color: #6b7280; font-size: 12px; }
          .verification code { color: #1f2937; font-weight: bold; }
          @media print {
            body { padding: 20px; }
            .certificate { border-width: 2px; }
          }
        </style>
      </head>
      <body>
        <div class="certificate">
          <div class="header">
            <div class="award-icon">🏆</div>
            <h1>Certificate of Contribution</h1>
            <p>TARUMT Buddy Programme</p>
            <p>${mentorStats.semesterYear}</p>
          </div>
          
          <div class="body">
            <p>This is to certify that</p>
            <p class="name">${mentorStats.name}</p>
            <p class="details">Student ID: ${mentorStats.studentId}</p>
            <p class="details">${mentorStats.programme}, ${mentorStats.faculty}</p>
            <p>has successfully served as a <strong>Mentor</strong> in the TARUMT Buddy Programme</p>
            
            <div class="summary">
              <h3>Contribution Summary</h3>
              <div class="summary-grid">
                <div class="summary-item">
                  <div>
                    <div class="summary-label">Sessions Conducted</div>
                    <div class="summary-value">${mentorStats.totalSessions} weeks</div>
                  </div>
                </div>
                <div class="summary-item">
                  <div>
                    <div class="summary-label">Mentees Guided</div>
                    <div class="summary-value">${mentorStats.totalMentees} students</div>
                  </div>
                </div>
                <div class="summary-item">
                  <div>
                    <div class="summary-label">Skills Taught</div>
                    <div class="summary-value">${mentorStats.skillsTaught.join(', ') || 'N/A'}</div>
                  </div>
                </div>
                <div class="summary-item">
                  <div>
                    <div class="summary-label">Average Rating</div>
                    <div class="summary-value">${mentorStats.avgFeedbackScore.toFixed(1)}/5.0</div>
                  </div>
                </div>
              </div>
            </div>
            
            <p>This certificate acknowledges their dedication, leadership, and commitment to supporting fellow students throughout the semester.</p>
          </div>
          
          <div class="footer">
            <div class="signatures">
              <div class="signature">
                <div class="signature-line"></div>
                <p>Programme Coordinator</p>
                <p class="details">Buddy Programme</p>
              </div>
              <div class="signature">
                <div class="signature-line"></div>
                <p>Date Issued</p>
                <p class="details">${formatDate(new Date())}</p>
              </div>
            </div>
            <div class="verification">
              <p>Verification Code: <code>${verificationCode}</code></p>
              <p>Visit verify.tarumt.edu.my to authenticate this certificate</p>
            </div>
          </div>
        </div>
      </body>
      </html>
    `;

    // Open print dialog
    const printWindow = window.open('', '_blank');
    if (printWindow) {
      printWindow.document.write(printContent);
      printWindow.document.close();
      printWindow.focus();
      
      // Wait for content to load then print
      setTimeout(() => {
        printWindow.print();
      }, 250);
    } else {
      alert('Please allow pop-ups to download the certificate');
    }
  };

  if (!isEligible) {
    return (
      <div className="bg-amber-50 border border-amber-200 rounded-xl p-6">
        <div className="flex items-start gap-3">
          <AlertCircle className="w-6 h-6 text-amber-600 mt-1" />
          <div>
            <h3 className="text-gray-900 mb-2">Testimonial Not Available</h3>
            <p className="text-gray-700 mb-3">
              You do not meet the minimum requirements for a testimonial certificate.
            </p>
            <div className="space-y-2 text-gray-700">
              <div className="flex items-center gap-2">
                <div className={`w-5 h-5 rounded-full flex items-center justify-center cursor-pointer ${
                  mentorStats.attendanceRate >= 80 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
                }`}>
                  {mentorStats.attendanceRate >= 80 ? '✓' : '✗'}
                </div>
                <span>Minimum 80% attendance (Current: {mentorStats.attendanceRate}%)</span>
              </div>
              <div className="flex items-center gap-2">
                <div className={`w-5 h-5 rounded-full flex items-center justify-center cursor-pointer ${
                  mentorStats.totalSessions >= 10 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
                }`}>
                  {mentorStats.totalSessions >= 10 ? '✓' : '✗'}
                </div>
                <span>Minimum 10 sessions conducted (Current: {mentorStats.totalSessions})</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!isApproved) {
    return (
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div className="flex items-start gap-3">
          <Calendar className="w-6 h-6 text-blue-600 mt-1" />
          <div>
            <h3 className="text-gray-900 mb-2">Testimonial Pending Admin Approval</h3>
            <p className="text-gray-700 mb-3">
              Your testimonial is being reviewed by the admin team. You'll be notified once it's approved and ready for download.
            </p>
            <div className="bg-white rounded-lg p-4">
              <p className="text-gray-700 mb-2">Your contribution summary:</p>
              <ul className="space-y-1 text-gray-600">
                <li>• {mentorStats.totalSessions} sessions conducted</li>
                <li>• {mentorStats.totalMentees} mentees guided</li>
                <li>• {mentorStats.attendanceRate}% attendance rate</li>
                <li>• {mentorStats.avgFeedbackScore.toFixed(1)}/5.0 average feedback score</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Eligibility Confirmation */}
      <div className="bg-green-50 border border-green-200 rounded-xl p-6">
        <div className="flex items-start gap-3">
          <CheckCircle className="w-6 h-6 text-green-600 mt-1" />
          <div className="flex-1">
            <h3 className="text-gray-900 mb-2">Testimonial Available</h3>
            <p className="text-gray-700 mb-4">
              Congratulations! You have successfully completed the Buddy Programme and are eligible for a digital testimonial certificate.
            </p>
            <button
              onClick={downloadTestimonial}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors cursor-pointer"
            >
              <Download className="w-4 h-4 inline mr-2" />
              Download PDF
            </button>
          </div>
        </div>
      </div>

      {/* Testimonial Preview */}
      <div className="bg-white border-2 border-gray-300 rounded-xl p-8 md:p-12">
          {/* Header */}
          <div className="text-center mb-8 pb-6 border-b-2 border-gray-200">
            <div className="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <Award className="w-12 h-12 text-white" />
            </div>
            <h2 className="text-gray-900 mb-2">Certificate of Contribution</h2>
            <p className="text-gray-700">TARUMT Buddy Programme</p>
            <p className="text-gray-600">{mentorStats.semesterYear}</p>
          </div>

          {/* Body */}
          <div className="space-y-6 mb-8">
            <p className="text-center text-gray-700">This is to certify that</p>
            
            <div className="text-center">
              <p className="text-gray-900 mb-1">{mentorStats.name}</p>
              <p className="text-gray-600">Student ID: {mentorStats.studentId}</p>
              <p className="text-gray-600">{mentorStats.programme}, {mentorStats.faculty}</p>
            </div>

            <p className="text-center text-gray-700">
              has successfully served as a <span className="text-gray-900">Mentor</span> in the TARUMT Buddy Programme
            </p>

            {/* Contribution Summary */}
            <div className="bg-gray-50 rounded-lg p-6 space-y-3">
              <p className="text-gray-900 text-center mb-4">Contribution Summary:</p>
              
              <div className="grid md:grid-cols-2 gap-4">
                <div className="flex items-center gap-3">
                  <Calendar className="w-5 h-5 text-blue-600" />
                  <div>
                    <p className="text-gray-600">Sessions Conducted</p>
                    <p className="text-gray-900">{mentorStats.totalSessions} weeks</p>
                  </div>
                </div>
                
                <div className="flex items-center gap-3">
                  <Users className="w-5 h-5 text-blue-600" />
                  <div>
                    <p className="text-gray-600">Mentees Guided</p>
                    <p className="text-gray-900">{mentorStats.totalMentees} students</p>
                  </div>
                </div>
                
                <div className="flex items-center gap-3">
                  <BookOpen className="w-5 h-5 text-blue-600" />
                  <div>
                    <p className="text-gray-600">Skills Taught</p>
                    <p className="text-gray-900">{mentorStats.skillsTaught.join(', ')}</p>
                  </div>
                </div>
                
                <div className="flex items-center gap-3">
                  <Star className="w-5 h-5 text-blue-600" />
                  <div>
                    <p className="text-gray-600">Average Rating</p>
                    <p className="text-gray-900">{mentorStats.avgFeedbackScore.toFixed(1)}/5.0</p>
                  </div>
                </div>
              </div>
            </div>

            <p className="text-center text-gray-700">
              This certificate acknowledges their dedication, leadership, and commitment to supporting fellow students throughout the semester.
            </p>
          </div>

          {/* Footer */}
          <div className="pt-6 border-t-2 border-gray-200">
            <div className="flex justify-between items-end mb-6">
              <div className="text-center">
                <div className="w-32 h-1 bg-gray-900 mb-2"></div>
                <p className="text-gray-700">Programme Coordinator</p>
                <p className="text-gray-600">Buddy Programme</p>
              </div>
              
              <div className="text-center">
                <div className="w-32 h-1 bg-gray-900 mb-2"></div>
                <p className="text-gray-700">Date Issued</p>
                <p className="text-gray-600">{formatDate(new Date())}</p>
              </div>
            </div>

            <div className="text-center text-gray-600">
              <p className="mb-1">Verification Code: <span className="text-gray-900">{verificationCode}</span></p>
              <p>Scan QR code or visit verify.tarumt.edu.my to authenticate this certificate</p>
            </div>
          </div>
        </div>
    </div>
  );
}
