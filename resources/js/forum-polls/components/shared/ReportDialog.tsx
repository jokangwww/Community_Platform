import { useState } from "react";
import { Button } from "../ui/button";
import { Label } from "../ui/label";
import { Textarea } from "../ui/textarea";
import { RadioGroup, RadioGroupItem } from "../ui/radio-group";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "../ui/dialog";
import { AlertCircle } from "lucide-react";

interface ReportDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onReport: (reason: string, details: string) => void;
  content: {
    type: "post" | "comment" | "answer";
    id: string;
  } | null;
}

const reportReasons = [
  { value: "spam", label: "Spam or misleading content" },
  { value: "harassment", label: "Harassment or hate speech" },
  { value: "inappropriate", label: "Inappropriate or offensive content" },
  { value: "misinformation", label: "Misinformation or false information" },
  { value: "offtopic", label: "Off-topic or irrelevant" },
  { value: "other", label: "Other (please specify)" },
];

export function ReportDialog({
  isOpen,
  onClose,
  onReport,
  content,
}: ReportDialogProps) {
  const [selectedReason, setSelectedReason] = useState("");
  const [details, setDetails] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!selectedReason) {
      alert("Please select a reason for reporting");
      return;
    }

    if (selectedReason === "other" && !details.trim()) {
      alert("Please provide details for your report");
      return;
    }

    setIsSubmitting(true);
    try {
      await onReport(selectedReason, details);
      // Reset form
      setSelectedReason("");
      setDetails("");
      onClose();
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5 text-red-500" />
            Report {content?.type}
          </DialogTitle>
          <DialogDescription>
            Help us maintain a safe and respectful community by reporting content that violates our guidelines.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div>
            <Label className="text-base font-medium mb-3 block">
              Why are you reporting this {content?.type}?
            </Label>
            <RadioGroup value={selectedReason} onValueChange={setSelectedReason}>
              <div className="space-y-3">
                {reportReasons.map((reason) => (
                  <div key={reason.value} className="flex items-center space-x-3">
                    <RadioGroupItem value={reason.value} id={reason.value} />
                    <Label
                      htmlFor={reason.value}
                      className="font-normal cursor-pointer"
                    >
                      {reason.label}
                    </Label>
                  </div>
                ))}
              </div>
            </RadioGroup>
          </div>

          <div>
            <Label htmlFor="details">
              Additional details {selectedReason === "other" ? "(required)" : "(optional)"}
            </Label>
            <Textarea
              id="details"
              value={details}
              onChange={(e) => setDetails(e.target.value)}
              placeholder="Provide any additional context that might help us review this report..."
              rows={4}
              className="mt-2"
            />
          </div>

          <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
            <p className="text-sm text-amber-800">
              <strong>Note:</strong> False reports may result in penalties. All reports are reviewed by our moderation team.
            </p>
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={onClose}
            disabled={isSubmitting}
          >
            Cancel
          </Button>
          <Button
            onClick={handleSubmit}
            disabled={!selectedReason || isSubmitting}
            className="bg-red-600 hover:bg-red-700 text-white"
          >
            {isSubmitting ? "Submitting..." : "Submit Report"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}