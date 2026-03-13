import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Textarea } from "../ui/textarea";
import { Badge } from "../ui/badge";
import { Separator } from "../ui/separator";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { 
  ArrowLeft, 
  Users, 
  Calendar, 
  FileText, 
  Download,
  Heart,
  MessageSquare,
  TrendingUp,
  Image as ImageIcon
} from "lucide-react";
import { PetitionAllSupportersView } from "./PetitionAllSupportersView";

interface Petition {
  id: string;
  title: string;
  description: string;
  proposedSolution: string;
  author: string;
  createdAt: string;
  supportCount: number;
  hasSupported: boolean;
  attachments: {
    id: string;
    name: string;
    type: string;
    size: string;
    url: string;
  }[];
  supporters: {
    id: string;
    nickname: string;
    comment?: string;
    supportedAt: string;
  }[];
}

interface PetitionViewProps {
  petition: Petition;
  onBack: () => void;
  onSupport: (petitionId: string, comment?: string) => void;
}

export function PetitionView({ petition, onBack, onSupport }: PetitionViewProps) {
  const [supportComment, setSupportComment] = useState("");
  const [showCommentBox, setShowCommentBox] = useState(false);
  const [showAllSupporters, setShowAllSupporters] = useState(false);

  const handleSupport = () => {
    if (!petition.hasSupported) {
      onSupport(petition.id, supportComment.trim() || undefined);
      setSupportComment("");
      setShowCommentBox(false);
    }
  };

  const getFileIcon = (type: string) => {
    if (type.startsWith('image/')) {
      return <ImageIcon className="h-4 w-4" />;
    }
    return <FileText className="h-4 w-4" />;
  };

  if (showAllSupporters) {
    return (
      <PetitionAllSupportersView
        petitionTitle={petition.title}
        supporters={petition.supporters}
        onBack={() => setShowAllSupporters(false)}
      />
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <Button onClick={onBack} variant="ghost" className="mb-3 -ml-2 cursor-pointer">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Petitions
          </Button>
          <h1>{petition.title}</h1>
          <div className="flex items-center gap-3 mt-2">
            <div className="flex items-center gap-1 text-sm text-muted-foreground">
              <Users className="h-4 w-4" />
              <span>{petition.author}</span>
            </div>
            <div className="flex items-center gap-1 text-sm text-muted-foreground">
              <Calendar className="h-4 w-4" />
              <span>{petition.createdAt}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Problem Description</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-muted-foreground whitespace-pre-wrap">
                {petition.description}
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Proposed Solution</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-muted-foreground whitespace-pre-wrap">
                {petition.proposedSolution}
              </p>
            </CardContent>
          </Card>

          {petition.attachments.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <FileText className="h-5 w-5" />
                  Supporting Documents ({petition.attachments.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  {petition.attachments.map((attachment) => (
                    <div 
                      key={attachment.id} 
                      className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        {getFileIcon(attachment.type)}
                        <div>
                          <p className="text-sm font-medium">{attachment.name}</p>
                          <p className="text-xs text-muted-foreground">{attachment.size}</p>
                        </div>
                      </div>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          const link = document.createElement('a');
                          link.href = attachment.url;
                          link.download = attachment.name;
                          document.body.appendChild(link);
                          link.click();
                          document.body.removeChild(link);
                        }}
                      >
                        <Download className="h-4 w-4 mr-2" />
                        Download
                      </Button>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <MessageSquare className="h-5 w-5" />
                Supporter Comments ({petition.supporters.filter(s => s.comment).length})
              </CardTitle>
            </CardHeader>
            <CardContent>
              {petition.supporters.filter(s => s.comment).length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-8">
                  No comments yet. Be the first to support and share your thoughts!
                </p>
              ) : (
                <div className="space-y-4">
                  {petition.supporters
                    .filter(supporter => supporter.comment)
                    .map((supporter) => (
                      <div key={supporter.id} className="space-y-2">
                        <div className="flex items-start gap-3">
                          <Avatar className="h-8 w-8">
                            <AvatarFallback className="text-sm">
                              {supporter.nickname.charAt(0).toUpperCase()}
                            </AvatarFallback>
                          </Avatar>
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <span className="text-sm font-medium">{supporter.nickname}</span>
                              <span className="text-xs text-muted-foreground">
                                {supporter.supportedAt}
                              </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                              {supporter.comment}
                            </p>
                          </div>
                        </div>
                        <Separator />
                      </div>
                    ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <TrendingUp className="h-5 w-5 text-green-600" />
                Support This Petition
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="text-center py-4">
                <div className="text-4xl font-bold mb-2">{petition.supportCount}</div>
                <p className="text-sm text-muted-foreground">
                  {petition.supportCount === 1 ? 'supporter' : 'supporters'}
                </p>
              </div>

              {!petition.hasSupported ? (
                <div className="space-y-3">
                  {showCommentBox ? (
                    <>
                      <Textarea
                        value={supportComment}
                        onChange={(e) => setSupportComment(e.target.value)}
                        placeholder="Add a comment to explain why you support this petition (optional)"
                        rows={4}
                        maxLength={500}
                      />
                      <p className="text-xs text-muted-foreground">
                        {supportComment.length}/500 characters
                      </p>
                      <div className="flex gap-2">
                        <Button onClick={handleSupport} className="flex-1 cursor-pointer">
                          <Heart className="h-4 w-4 mr-2" />
                          Support
                        </Button>
                        <Button 
                          variant="outline" 
                          onClick={() => {
                            setShowCommentBox(false);
                            setSupportComment("");
                          }}
                        >
                          Cancel
                        </Button>
                      </div>
                    </>
                  ) : (
                    <>
                      <Button onClick={handleSupport} className="w-full cursor-pointer">
                        <Heart className="h-4 w-4 mr-2" />
                        Support Petition
                      </Button>
                      <Button 
                        onClick={() => setShowCommentBox(true)} 
                        variant="outline" 
                        className="w-full cursor-pointer"
                      >
                        <MessageSquare className="h-4 w-4 mr-2" />
                        Support with Comment
                      </Button>
                    </>
                  )}
                </div>
              ) : (
                <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
                  <div className="flex items-center gap-2 text-green-800">
                    <Heart className="h-5 w-5 fill-current" />
                    <p className="text-sm font-medium">
                      You're supporting this petition
                    </p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Users className="h-5 w-5" />
                  Recent Supporters
                </div>
                {petition.supporters.length > 0 && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setShowAllSupporters(true)}
                    className="text-sm text-primary hover:text-primary/80 cursor-pointer"
                  >
                    View All
                  </Button>
                )}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {petition.supporters.length === 0 ? (
                <p className="text-sm text-muted-foreground text-center py-4">
                  Be the first to support!
                </p>
              ) : (
                <div className="space-y-3">
                  {petition.supporters.slice(0, 10).map((supporter) => (
                    <div key={supporter.id} className="flex items-center gap-2">
                      <Avatar className="h-8 w-8">
                        <AvatarFallback className="text-xs">
                          {supporter.nickname.charAt(0).toUpperCase()}
                        </AvatarFallback>
                      </Avatar>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium truncate">
                          {supporter.nickname}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          {supporter.supportedAt}
                        </p>
                      </div>
                    </div>
                  ))}
                  {petition.supporters.length > 10 && (
                    <p className="text-xs text-muted-foreground text-center pt-2">
                      and {petition.supporters.length - 10} more...
                    </p>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
