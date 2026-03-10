import { useState, useEffect, useRef } from 'react';
import { ChevronDown, Archive } from 'lucide-react';

interface SemesterOption {
  id: number;
  label: string;
  is_active: boolean;
  role: string;
}

interface SemesterSelectorProps {
  /** Current semester_id being viewed (null = active semester) */
  selectedSemesterId: number | null;
  onSelect: (semesterId: number | null, role?: string) => void;
}

export function SemesterSelector({ selectedSemesterId, onSelect }: SemesterSelectorProps) {
  const [semesters, setSemesters] = useState<SemesterOption[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    fetch('/api/buddy/semesters', { headers: { Accept: 'application/json' } })
      .then((r) => r.json())
      .then((res) => {
        if (res.success) {
          setSemesters(res.data);

          // If the user has no participant in the active semester (e.g. declined
          // continuation), auto-select the most recent past semester so the
          // dashboard loads their old data instead of showing empty "Current Semester".
          const hasActive = (res.data as SemesterOption[]).some((s) => s.is_active);
          if (!hasActive && selectedSemesterId === null && res.data.length > 0) {
            onSelect(res.data[0].id, res.data[0].role);
          }
        }
      })
      .catch(() => {});
  }, []);

  // Close dropdown on click outside
  useEffect(() => {
    if (!isOpen) return;
    const handleClickOutside = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen]);

  // Only show "Current Semester" when the user has a participant in the active semester
  const hasActiveSemester = semesters.some((s) => s.is_active);
  const pastSemesters = semesters.filter((s) => !s.is_active);
  const selected = semesters.find((s) => s.id === selectedSemesterId);
  const label = selected
    ? selected.label
    : hasActiveSemester
      ? 'Current Semester'
      : pastSemesters[0]?.label ?? 'Select Semester';

  return (
    <div className="relative" ref={containerRef}>
      <button
        type="button"
        onClick={() => setIsOpen((o) => !o)}
        className="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
      >
        {!selected && hasActiveSemester && (
          <span className="w-2 h-2 rounded-full bg-green-500 inline-block" />
        )}
        {(selected || !hasActiveSemester) && <Archive className="w-4 h-4 text-gray-400" />}
        <span className="font-medium">{label}</span>
        <ChevronDown className="w-4 h-4 text-gray-400" />
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-1 w-56 bg-white border border-gray-200 rounded-lg shadow-lg z-[9999]">
          {/* Active / current semester — only when user participates in it */}
          {hasActiveSemester && (
            <button
              type="button"
              onClick={() => { onSelect(null); setIsOpen(false); }}
              className={`w-full text-left px-4 py-2 text-sm flex items-center gap-2 hover:bg-gray-50 rounded-t-lg cursor-pointer ${
                !selectedSemesterId ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'
              }`}
            >
              <span className="w-2 h-2 rounded-full bg-green-500 inline-block" />
              Current Semester
            </button>
          )}

          {pastSemesters.map((s, idx) => (
            <button
              key={s.id}
              type="button"
              onClick={() => { onSelect(s.id, s.role); setIsOpen(false); }}
              className={`w-full text-left px-4 py-2 text-sm flex items-center gap-2 hover:bg-gray-50 cursor-pointer ${
                !hasActiveSemester && idx === 0 ? 'rounded-t-lg' : ''
              } ${
                selectedSemesterId === s.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-500'
              }`}
            >
              <Archive className="w-3.5 h-3.5 text-gray-400" />
              {s.label}
              {s.role && (
                <span className={`ml-auto text-xs px-1.5 py-0.5 rounded ${
                  s.role === 'mentor' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'
                }`}>
                  {s.role === 'mentor' ? 'Mentor' : 'Mentee'}
                </span>
              )}
            </button>
          ))}

          {pastSemesters.length === 0 && !hasActiveSemester && (
            <p className="px-4 py-3 text-xs text-gray-400">No semesters found.</p>
          )}
        </div>
      )}
    </div>
  );
}
