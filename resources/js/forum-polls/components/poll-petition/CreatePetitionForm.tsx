import { useState } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Label } from "../ui/label";
import { Textarea } from "../ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { ArrowLeft, Upload, X, FileText, Image as ImageIcon, Calendar } from "lucide-react";

interface CreatePetitionFormProps {
  onBack: () => void;
  onCreatePetition: (petition: {
    title: string;
    description: string;
    proposedSolution: string;
    attachments: File[];
    targetSupporters?: number;
  }) => void;
  canCreatePetition: boolean;
  nextAvailableDate?: string;
}

export function CreatePetitionForm({ 
  onBack, 
  onCreatePetition, 
  canCreatePetition,
  nextAvailableDate 
}: CreatePetitionFormProps) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [proposedSolution, setProposedSolution] = useState("");
  const [attachments, setAttachments] = useState<File[]>([]);
  const [targetSupporters, setTargetSupporters] = useState(500);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const newFiles = Array.from(e.target.files);
      setAttachments(prev => [...prev, ...newFiles].slice(0, 5)); // Max 5 files
    }
  };

  const handleRemoveFile = (index: number) => {
    setAttachments(prev => prev.filter((_, i) => i !== index));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!canCreatePetition) {
      return;
    }

    if (title.trim() && description.trim() && proposedSolution.trim()) {
      onCreatePetition({
        title: title.trim(),
        description: description.trim(),
        proposedSolution: proposedSolution.trim(),
        attachments,
        targetSupporters,
      });
    }
  };

  const isFormValid = () => {
    return title.trim() && description.trim() && proposedSolution.trim();
  };

  const getFileIcon = (fileName: string) => {
    const ext = fileName.split('.').pop()?.toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext || '')) {
      return <ImageIcon className="h-4 w-4" />;
    }
    return <FileText className="h-4 w-4" />;
  };

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1>Create New Petition</h1>
          <p className="text-muted-foreground">
            Submit a formal petition to advocate for change in your campus community
          </p>
        </div>
        <Button onClick={onBack} variant="outline">
          <ArrowLeft className="h-4 w-4 mr-2 cursor-pointer" />
          Back
        </Button>
      </div>

      {!canCreatePetition && nextAvailableDate && (
        <Card className="border-yellow-200 bg-yellow-50 cursor-pointer">
          <CardContent className="pt-6 cursor-pointer">
            <div className="flex items-start gap-3 cursor-pointer">
              <Calendar className="h-5 w-5 text-yellow-600 mt-0.5 cursor-pointer" />
              <div>
                <h3 className="text-yellow-900 mb-1 cursor-pointer">Petition Limit Reached</h3>
                <p className="text-yellow-800 text-sm cursor-pointer">
                  You can only have one active petition per month. You'll be able to create another petition on{" "}
                  <strong>{nextAvailableDate}</strong>.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <form onSubmit={handleSubmit} className="space-y-6 cursor-pointer">
        <Card>
          <CardHeader>
            <CardTitle>Petition Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4 cursor-pointer">
            <div className="space-y-2 cursor-pointer">
              <Label htmlFor="title">Petition Title *</Label>
              <Input
                id="title"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="e.g., Improve Mental Health Support Services on Campus"
                maxLength={150}
                required
                disabled={!canCreatePetition}
              />
              <p className="text-xs text-muted-foreground cursor-pointer">
                {title.length}/150 characters
              </p>
            </div>

            <div className="space-y-2 cursor-pointer">
              <Label htmlFor="description">Detailed Description *</Label>
              <Textarea
                id="description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Describe the issue in detail. Include background information, current situation, and why this matters to the student community..."
                rows={6}
                maxLength={2000}
                required
                disabled={!canCreatePetition}
              />
              <p className="text-xs text-muted-foreground cursor-pointer">
                {description.length}/2000 characters
              </p>
            </div>

            <div className="space-y-2 cursor-pointer">
              <Label htmlFor="solution">Proposed Solution *</Label>
              <Textarea
                id="solution"
                value={proposedSolution}
                onChange={(e) => setProposedSolution(e.target.value)}
                placeholder="Explain your proposed solution. Be specific about what changes you want to see and how they could be implemented..."
                rows={6}
                maxLength={2000}
                required
                disabled={!canCreatePetition}
              />
              <p className="text-xs text-muted-foreground cursor-pointer">
                {proposedSolution.length}/2000 characters
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="target-supporters">Target Supporters (Optional)</Label>
              <Input
                id="target-supporters"
                type="number"
                min={100}
                max={10000}
                value={targetSupporters}
                onChange={(e) => setTargetSupporters(Math.max(10, parseInt(e.target.value) || 500))}
                disabled={!canCreatePetition}
              />
              <p className="text-xs text-muted-foreground">
                Set how many supporters you aim to reach. Default is 500.
              </p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Supporting Documents (Optional)</CardTitle>
            <p className="text-sm text-muted-foreground cursor-pointer">
              Upload images, documents, or other files to support your petition (Max 5 files, 10MB each)
            </p>
          </CardHeader>
          <CardContent className="space-y-4 cursor-pointer">
            <div className="space-y-3 cursor-pointer">
              {attachments.map((file, index) => (
                <div 
                  key={index} 
                  className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border cursor-pointer"
                >
                  <div className="flex items-center gap-3">
                    {getFileIcon(file.name)}
                    <div>
                      <p className="text-sm font-medium">{file.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {formatFileSize(file.size)}
                      </p>
                    </div>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => handleRemoveFile(index)}
                    disabled={!canCreatePetition}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              ))}
            </div>

            {attachments.length < 5 && (
              <div>
                <Label 
                  htmlFor="file-upload" 
                  className={`flex items-center justify-center gap-2 p-6 border-2 border-dashed rounded-lg cursor-pointer transition-colors ${
                    canCreatePetition 
                      ? 'hover:border-blue-500 hover:bg-blue-50' 
                      : 'opacity-50 cursor-not-allowed'
                  }`}
                >
                  <Upload className="h-5 w-5 text-muted-foreground" />
                  <span className="text-sm text-muted-foreground">
                    Click to upload files ({attachments.length}/5)
                  </span>
                </Label>
                <Input
                  id="file-upload"
                  type="file"
                  onChange={handleFileChange}
                  className="hidden"
                  multiple
                  accept="image/*,.pdf,.doc,.docx"
                  disabled={!canCreatePetition}
                />
              </div>
            )}
          </CardContent>
        </Card>

        <div className="flex justify-end gap-3">
          <Button type="button" variant="outline" onClick={onBack}>
            Cancel
          </Button>
          <Button type="submit" disabled={!isFormValid() || !canCreatePetition}>
            Submit Petition
          </Button>
        </div>
      </form>
    </div>
  );
}
