import { useEffect, useState, useCallback } from "react";
import { formatDate } from "../../../shared/utils/date";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "../ui/dialog";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { AlertTriangle, Ban, Trash2, ShieldAlert } from "lucide-react";

interface ModerationNotice {
  id: string;
  title: string;
  message: string;
  action: string;
  reason: string;
  content_type: string;
  note: string | null;
  mute_duration_days: number | null;
  total_warnings: number;
  total_mutes: number;
  created_at: string;
}

interface ModerationNoticePopupProps {
  mutedUntil?: string | null;
}

export function ModerationNoticePopup({ mutedUntil }: ModerationNoticePopupProps) {
  const [notices, setNotices] = useState<ModerationNotice[]>([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isOpen, setIsOpen] = useState(false);

  const getCsrfToken = useCallback(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") || "" : "";
  }, []);

  // Fetch unread moderation notices on mount
  useEffect(() => {
    const fetchNotices = async () => {
      try {
        const res = await fetch("/api/forum/moderation-notices", {
          headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": getCsrfToken(),
          },
        });
        if (res.ok) {
          const data = await res.json();
          if (data.notices && data.notices.length > 0) {
            setNotices(data.notices);
            setCurrentIndex(0);
            setIsOpen(true);
          }
        }
      } catch (err) {
        console.error("Failed to fetch moderation notices:", err);
      }
    };
    fetchNotices();
  }, [getCsrfToken]);

  const currentNotice = notices[currentIndex];

  const handleAcknowledge = async () => {
    if (!currentNotice) return;

    // Mark as read
    try {
      await fetch(`/api/forum/moderation-notices/${currentNotice.id}/read`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": getCsrfToken(),
        },
      });
    } catch (err) {
      console.error("Failed to mark notice as read:", err);
    }

    // Move to next or close
    if (currentIndex < notices.length - 1) {
      setCurrentIndex(currentIndex + 1);
    } else {
      setIsOpen(false);
    }
  };

  const getActionIcon = (action: string) => {
    switch (action) {
      case "warn":
        return <AlertTriangle className="h-6 w-6 text-orange-500" />;
      case "mute":
        return <Ban className="h-6 w-6 text-red-500" />;
      case "delete":
        return <Trash2 className="h-6 w-6 text-red-600" />;
      default:
        return <ShieldAlert className="h-6 w-6 text-blue-500" />;
    }
  };

  const getActionColor = (action: string) => {
    switch (action) {
      case "warn":
        return "bg-orange-50 border-orange-300";
      case "mute":
        return "bg-red-50 border-red-300";
      case "delete":
        return "bg-red-50 border-red-300";
      default:
        return "bg-blue-50 border-blue-300";
    }
  };

  if (!currentNotice) return null;

  const isMuted = mutedUntil && new Date(mutedUntil) > new Date();

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            {getActionIcon(currentNotice.action)}
            {currentNotice.title}
          </DialogTitle>
          <DialogDescription>
            {notices.length > 1 && (
              <span className="text-xs">
                Notice {currentIndex + 1} of {notices.length}
              </span>
            )}
          </DialogDescription>
        </DialogHeader>

        <div className={`p-4 rounded-lg border-2 ${getActionColor(currentNotice.action)}`}>
          <p className="text-sm text-gray-800 mb-3">{currentNotice.message}</p>

          {currentNotice.note && (
            <div className="bg-white p-2 rounded border mb-3">
              <p className="text-xs text-gray-500 mb-1">Admin note:</p>
              <p className="text-sm text-gray-700 italic">"{currentNotice.note}"</p>
            </div>
          )}

          <div className="flex flex-wrap gap-2 mb-3">
            <Badge variant="outline" className="text-xs">
              {currentNotice.content_type}
            </Badge>
            <Badge variant="outline" className="text-xs">
              Reason: {currentNotice.reason}
            </Badge>
            <Badge variant="outline" className="text-xs text-gray-500">
              {currentNotice.created_at}
            </Badge>
          </div>

          <div className="flex gap-3 text-xs text-gray-600">
            <span>Total warnings: <strong className="text-orange-600">{currentNotice.total_warnings}</strong></span>
            <span>Total mutes: <strong className="text-red-600">{currentNotice.total_mutes}</strong></span>
          </div>
        </div>

        {isMuted && (
          <div className="bg-red-100 border border-red-300 rounded-lg p-3 text-sm text-red-800">
            <p className="font-semibold flex items-center gap-1">
              <Ban className="h-4 w-4" /> Your account is currently muted
            </p>
            <p className="text-xs mt-1">
              You cannot post or comment until{" "}
              <strong>{formatDate(mutedUntil!)}</strong>.
            </p>
          </div>
        )}

        <DialogFooter>
          <Button onClick={handleAcknowledge} className="w-full">
            I Understand
            {notices.length > 1 && currentIndex < notices.length - 1 && " (Next)"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
