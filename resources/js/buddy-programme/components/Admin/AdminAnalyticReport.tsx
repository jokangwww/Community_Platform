import { useState, useEffect, useRef } from 'react';
import { X, FileText, Download, Loader2 } from 'lucide-react';
import html2pdf from 'html2pdf.js';

interface ReportData {
  semester: string;
  academic_year: string;
  programme_overview: {
    total_mentors: number;
    total_mentees: number;
    total_repeaters: number;
    total_matches: number;
    total_mentor_applications: number;
    total_mentee_applications: number;
  };
  performance_metrics: {
    average_attendance_rate: number;
    average_feedback_rating: number;
    total_feedback_responses: number;
  };
  programme_recognition: {
    total_testimonials_awarded: number;
    not_eligible_testimonials: number;
    gap_eligible_count: number;
    gap_not_eligible_count: number;
  };
  report_summary: {
    match_success_rate: number;
    matched_mentees: number;
    total_mentees: number;
    repeater_match_rate: number;
    matched_repeaters: number;
    total_repeaters: number;
    total_participants: number;
    gap_eligibility_rate: number;
    gap_eligible_count: number;
  };
}

interface AdminAnalyticReportProps {
  isOpen: boolean;
  onClose: () => void;
}

// Reusable styles for PDF compatibility (html2canvas doesn't support oklch colors)
const styles = {
  // Section styles
  section: { borderBottom: '1px solid #e5e7eb', paddingBottom: '16px' },
  sectionTitle: { color: '#111827', marginBottom: '16px', fontSize: '18px', fontWeight: 600 } as React.CSSProperties,
  grid3: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '16px' } as React.CSSProperties,
  grid3mb: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '16px', marginBottom: '16px' } as React.CSSProperties,
  grid2: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '16px' } as React.CSSProperties,
  grid2mb: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '16px', marginBottom: '16px' } as React.CSSProperties,
  
  // Text styles
  label: { color: '#4b5563', marginBottom: '4px', fontSize: '14px' },
  value: { color: '#111827', fontSize: '24px', fontWeight: 600 } as React.CSSProperties,
  subtitle: (color: string) => ({ color, marginTop: '4px', fontSize: '12px' }),
  
  // Card base
  card: (bg: string, border: string) => ({
    backgroundColor: bg,
    border: `1px solid ${border}`,
    borderRadius: '8px',
    padding: '16px'
  }),
  
  // Pre-defined card colors
  cards: {
    blue: { backgroundColor: '#eff6ff', border: '1px solid #bfdbfe', borderRadius: '8px', padding: '16px' },
    green: { backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: '8px', padding: '16px' },
    yellow: { backgroundColor: '#fffbeb', border: '1px solid #fde68a', borderRadius: '8px', padding: '16px' },
    cyan: { backgroundColor: '#ecfeff', border: '1px solid #a5f3fc', borderRadius: '8px', padding: '16px' },
    sky: { backgroundColor: '#f0f9ff', border: '1px solid #bae6fd', borderRadius: '8px', padding: '16px' },
    lime: { backgroundColor: '#f7fee7', border: '1px solid #bef264', borderRadius: '8px', padding: '16px' },
    purple: { backgroundColor: '#faf5ff', border: '1px solid #e9d5ff', borderRadius: '8px', padding: '16px' },
    indigo: { backgroundColor: '#eef2ff', border: '1px solid #c7d2fe', borderRadius: '8px', padding: '16px' },
    pink: { backgroundColor: '#fdf2f8', border: '1px solid #fbcfe8', borderRadius: '8px', padding: '16px' },
    teal: { backgroundColor: '#f0fdfa', border: '1px solid #99f6e4', borderRadius: '8px', padding: '16px' },
    rose: { backgroundColor: '#fff1f2', border: '1px solid #fecdd3', borderRadius: '8px', padding: '16px' },
    orange: { backgroundColor: '#fff7ed', border: '1px solid #fed7aa', borderRadius: '8px', padding: '16px' },
  },
  
  // Summary section
  summary: {
    container: { backgroundColor: '#f9fafb', borderRadius: '8px', padding: '16px' },
    title: { color: '#111827', marginBottom: '12px', fontSize: '18px', fontWeight: 600 } as React.CSSProperties,
    list: { display: 'flex', flexDirection: 'column', gap: '8px', color: '#374151', fontSize: '14px' } as React.CSSProperties,
    bold: { color: '#111827', fontWeight: 500 } as React.CSSProperties,
  }
};

export function AdminAnalyticReport({ isOpen, onClose }: AdminAnalyticReportProps) {
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [reportLoading, setReportLoading] = useState(false);
  const [isDownloading, setIsDownloading] = useState(false);
  const reportRef = useRef<HTMLDivElement>(null);

  const fetchReportData = async () => {
    setReportLoading(true);
    try {
      const response = await fetch('/api/buddy/admin/report-data', {
        headers: { 'Accept': 'application/json' }
      });
      const result = await response.json();
      if (result.success) {
        setReportData(result.data);
      }
    } catch (error) {
      console.error('Failed to fetch report data:', error);
    } finally {
      setReportLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen) {
      fetchReportData();
    }
  }, [isOpen]);

  const handleDownloadPDF = async () => {
    if (!reportRef.current) return;
    
    setIsDownloading(true);
    
    const filename = `Buddy_Programme_Analytics_Report_${reportData?.semester?.replace(' ', '') || 'Sem2'}_${reportData?.academic_year?.replace('/', '-') || '2024-2025'}.pdf`;
    
    const options = {
      margin: [10, 10, 10, 10] as [number, number, number, number],
      filename: filename,
      image: { type: 'jpeg' as const, quality: 0.98 },
      html2canvas: { 
        scale: 2,
        useCORS: true,
        letterRendering: true,
        backgroundColor: '#ffffff',
        onclone: (clonedDoc: Document) => {
          // Fix oklch color issue by replacing with hex fallbacks
          const allElements = clonedDoc.querySelectorAll('*');
          allElements.forEach((el) => {
            const htmlEl = el as HTMLElement;
            const computedStyle = window.getComputedStyle(htmlEl);
            
            // Check and fix background color
            if (computedStyle.backgroundColor.includes('oklch')) {
              htmlEl.style.backgroundColor = '#ffffff';
            }
            // Check and fix color
            if (computedStyle.color.includes('oklch')) {
              htmlEl.style.color = '#000000';
            }
            // Check and fix border color
            if (computedStyle.borderColor.includes('oklch')) {
              htmlEl.style.borderColor = '#e5e7eb';
            }
          });
        }
      },
      jsPDF: { 
        unit: 'mm', 
        format: 'a4', 
        orientation: 'portrait' as const
      }
    };

    try {
      await html2pdf().set(options).from(reportRef.current).save();
    } catch (error) {
      console.error('Failed to generate PDF:', error);
      alert('Failed to generate PDF. Please try again.');
    } finally {
      setIsDownloading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div 
      className="fixed inset-0 flex items-center justify-center p-4 z-50" 
      style={{ backdropFilter: 'blur(8px)', WebkitBackdropFilter: 'blur(8px)', backgroundColor: 'rgba(255, 255, 255, 0.3)' }}
      onClick={onClose}
    >
      <div 
        className="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col" 
        style={{ boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)' }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <h2 className="text-gray-900 mb-1">TARUMT Buddy Programme Analytics Report</h2>
            <p className="text-gray-600">{reportData?.semester || 'Current Semester'}, Academic Year {reportData?.academic_year || '2024/2025'}</p>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scrollable Content */}
        <div className="flex-1 overflow-y-auto p-6">
        {reportLoading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
            <span className="ml-2 text-gray-600">Loading report data...</span>
          </div>
        ) : (
          <>
            {/* Report Content */}
            <div ref={reportRef} className="space-y-6 mb-6" style={{ backgroundColor: '#ffffff', padding: '16px' }}>
              {/* Programme Overview */}
              <div style={styles.section}>
                <h3 style={styles.sectionTitle}>Programme Overview</h3>
                <div style={styles.grid3mb}>
                  <div style={styles.cards.blue}>
                    <p style={styles.label}>Total Mentors</p>
                    <p style={styles.value}>{reportData?.programme_overview.total_mentors || 0}</p>
                  </div>
                  <div style={styles.cards.green}>
                    <p style={styles.label}>Total Mentees</p>
                    <p style={styles.value}>{reportData?.programme_overview.total_mentees || 0}</p>
                  </div>
                  <div style={styles.cards.yellow}>
                    <p style={styles.label}>Total Repeaters</p>
                    <p style={styles.value}>{reportData?.programme_overview.total_repeaters || 0}</p>
                  </div>
                </div>
                <div style={styles.grid3}>
                  <div style={styles.cards.cyan}>
                    <p style={styles.label}>Total Matches</p>
                    <p style={styles.value}>{reportData?.programme_overview.total_matches || 0}</p>
                  </div>
                  <div style={styles.cards.sky}>
                    <p style={styles.label}>Total Mentor Applications</p>
                    <p style={styles.value}>{reportData?.programme_overview.total_mentor_applications || 0}</p>
                  </div>
                  <div style={styles.cards.lime}>
                    <p style={styles.label}>Total Mentee Applications</p>
                    <p style={styles.value}>{reportData?.programme_overview.total_mentee_applications || 0}</p>
                  </div>
                </div>
              </div>

              {/* Performance Metrics */}
              <div style={styles.section}>
                <h3 style={styles.sectionTitle}>Performance Metrics</h3>
                <div style={styles.grid2}>
                  <div style={styles.cards.purple}>
                    <p style={styles.label}>Average Attendance Rate</p>
                    <p style={styles.value}>{reportData?.performance_metrics.average_attendance_rate.toFixed(1) || 0}%</p>
                    <p style={styles.subtitle('#7c3aed')}>Across all sessions</p>
                  </div>
                  <div style={styles.cards.indigo}>
                    <p style={styles.label}>Average Feedback Rating</p>
                    <p style={styles.value}>{reportData?.performance_metrics.average_feedback_rating.toFixed(1) || 0} / 5.0</p>
                    <p style={styles.subtitle('#4338ca')}>Based on {reportData?.performance_metrics.total_feedback_responses || 0} responses</p>
                  </div>
                </div>
              </div>

              {/* Programme Recognition */}
              <div style={styles.section}>
                <h3 style={styles.sectionTitle}>Programme Recognition</h3>
                <div style={styles.grid2mb}>
                  <div style={styles.cards.pink}>
                    <p style={styles.label}>Total Testimonials Awarded</p>
                    <p style={styles.value}>{reportData?.programme_recognition.total_testimonials_awarded || 0}</p>
                    <p style={styles.subtitle('#be185d')}>Outstanding mentors</p>
                  </div>
                  <div style={styles.cards.teal}>
                    <p style={styles.label}>GAP Points Eligible</p>
                    <p style={styles.value}>{reportData?.programme_recognition.gap_eligible_count || 0} students</p>
                    <p style={styles.subtitle('#0f766e')}>≥80% attendance achieved</p>
                  </div>
                </div>
                <div style={styles.grid2}>
                  <div style={styles.cards.rose}>
                    <p style={styles.label}>Not Eligible for Testimonials</p>
                    <p style={styles.value}>{reportData?.programme_recognition.not_eligible_testimonials || 0} mentors</p>
                    <p style={styles.subtitle('#be123c')}>Below requirements</p>
                  </div>
                  <div style={styles.cards.orange}>
                    <p style={styles.label}>Not Eligible for GAP Points</p>
                    <p style={styles.value}>{reportData?.programme_recognition.gap_not_eligible_count || 0} students</p>
                    <p style={styles.subtitle('#c2410c')}>&lt;80% attendance</p>
                  </div>
                </div>
              </div>

              {/* Report Summary */}
              <div style={styles.summary.container}>
                <h3 style={styles.summary.title}>Report Summary</h3>
                <div style={styles.summary.list}>
                  <p>• <span style={styles.summary.bold}>Match Success Rate:</span> {reportData?.report_summary.match_success_rate.toFixed(1) || 0}% ({reportData?.report_summary.matched_mentees || 0} of {reportData?.report_summary.total_mentees || 0} mentees matched)</p>
                  <p>• <span style={styles.summary.bold}>Repeater Support:</span> {reportData?.report_summary.repeater_match_rate.toFixed(1) || 0}% of repeaters successfully matched ({reportData?.report_summary.matched_repeaters || 0} of {reportData?.report_summary.total_repeaters || 0})</p>
                  <p>• <span style={styles.summary.bold}>Programme Participation:</span> {reportData?.report_summary.total_participants || 0} total participants ({reportData?.programme_overview.total_mentors || 0} mentors + {reportData?.programme_overview.total_mentees || 0} mentees)</p>
                  <p>• <span style={styles.summary.bold}>Quality Assurance:</span> {reportData?.report_summary.gap_eligibility_rate.toFixed(1) || 0}% GAP eligibility rate ({reportData?.report_summary.gap_eligible_count || 0} of {reportData?.report_summary.total_participants || 0} students)</p>
                </div>
              </div>
            </div>

            <div className="border border-gray-200 rounded-lg p-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <FileText className="w-6 h-6 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-gray-900">Buddy_Programme_Analytics_Report_{reportData?.semester?.replace(' ', '') || 'Sem2'}_{reportData?.academic_year?.replace('/', '-') || '2024-2025'}.pdf</p>
                    <p className="text-gray-600">Complete analytics and statistics report</p>
                  </div>
                </div>
                <button 
                  onClick={handleDownloadPDF}
                  disabled={isDownloading}
                  className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isDownloading ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Generating...
                    </>
                  ) : (
                    <>
                      <Download className="w-4 h-4" />
                      Download PDF
                    </>
                  )}
                </button>
              </div>
            </div>
          </>
        )}
        </div>
      </div>
    </div>
  );
}
