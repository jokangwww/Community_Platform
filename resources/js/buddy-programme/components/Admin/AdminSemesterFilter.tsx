import { useState, useEffect, useRef } from 'react';
import { ChevronDown, Archive } from 'lucide-react';

interface SemesterOption {
  id: number;
  label: string;
  is_active: boolean;
}

interface AdminSemesterFilterProps {
  /** Currently selected semester_id (null = active semester) */
  selectedSemesterId: number | null;
  onSelect: (semesterId: number | null) => void;
}

/**
 * Compact semester pill filter for admin tabs.
 * Uses fixed positioning so the dropdown is never clipped by parent overflow.
 */
export function AdminSemesterFilter({ selectedSemesterId, onSelect }: AdminSemesterFilterProps) {
  const [semesters, setSemesters] = useState<SemesterOption[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [dropdownStyle, setDropdownStyle] = useState<{ top: number; left: number }>({ top: 0, left: 0 });
  const btnRef = useRef<HTMLButtonElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    fetch('/api/buddy/admin/all-semesters', { headers: { Accept: 'application/json' } })
      .then((r) => r.json())
      .then((res) => {
        if (res.success) setSemesters(res.data);
      })
      .catch(() => {});
  }, []);

  // Close dropdown on click outside (ref-based, no overlay)
  useEffect(() => {
    if (!isOpen) return;
    const handleClickOutside = (e: MouseEvent) => {
      const target = e.target as Node;
      if (
        containerRef.current && !containerRef.current.contains(target) &&
        dropdownRef.current && !dropdownRef.current.contains(target)
      ) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen]);

  // Recalculate position when window is scrolled/resized while open
  useEffect(() => {
    if (!isOpen) return;
    const update = () => {
      if (btnRef.current) {
        const rect = btnRef.current.getBoundingClientRect();
        setDropdownStyle({ top: rect.bottom + 4, left: rect.right - 208 });
      }
    };
    window.addEventListener('scroll', update, true);
    window.addEventListener('resize', update);
    return () => {
      window.removeEventListener('scroll', update, true);
      window.removeEventListener('resize', update);
    };
  }, [isOpen]);

  const handleToggle = () => {
    if (!isOpen && btnRef.current) {
      const rect = btnRef.current.getBoundingClientRect();
      setDropdownStyle({ top: rect.bottom + 4, left: rect.right - 208 });
    }
    setIsOpen((o) => !o);
  };

  const selected = semesters.find((s) => s.id === selectedSemesterId);
  const label = selected ? selected.label : 'Current Semester';

  return (
    <div className="flex items-center gap-2" ref={containerRef}>
      <span className="text-sm text-gray-500">Semester:</span>
      <button
        type="button"
        ref={btnRef}
        onClick={handleToggle}
        className="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition-colors cursor-pointer"
      >
        {!selected ? (
          <span className="w-2 h-2 rounded-full bg-green-500 inline-block" />
        ) : (
          <Archive className="w-3.5 h-3.5 text-gray-400" />
        )}
        <span className={selected ? 'text-gray-600' : 'text-green-700 font-medium'}>{label}</span>
        <ChevronDown className="w-3.5 h-3.5 text-gray-400" />
      </button>

      {isOpen && (
        <div
          ref={dropdownRef}
          className="fixed z-[9999] w-52 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
          style={{ top: dropdownStyle.top, left: dropdownStyle.left }}
        >
          <button
            type="button"
            onClick={() => { onSelect(null); setIsOpen(false); }}
            className={`w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 hover:bg-gray-50 transition-colors cursor-pointer ${
              !selectedSemesterId ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700'
            }`}
          >
            <span className="w-2 h-2 rounded-full bg-green-500 inline-block flex-shrink-0" />
            <span>Current Semester</span>
          </button>

          {semesters.filter((s) => !s.is_active).map((s) => (
            <button
              key={s.id}
              type="button"
              onClick={() => { onSelect(s.id); setIsOpen(false); }}
              className={`w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 hover:bg-gray-50 transition-colors border-t border-gray-100 cursor-pointer ${
                selectedSemesterId === s.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-500'
              }`}
            >
              <Archive className="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
              <span>{s.label}</span>
            </button>
          ))}

          {semesters.filter((s) => !s.is_active).length === 0 && (
            <p className="px-4 py-3 text-xs text-gray-400 border-t border-gray-100">No archived semesters.</p>
          )}
        </div>
      )}
    </div>
  );
}
