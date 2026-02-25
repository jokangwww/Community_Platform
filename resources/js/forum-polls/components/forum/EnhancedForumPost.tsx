import { useState } from "react";
import { Button } from "../ui/button";
import { Textarea } from "../ui/textarea";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { Card } from "../ui/card";
import { 
  Heart, 
  MessageCircle, 
  Eye, 
  ThumbsUp, 
  ThumbsDown,
  Check,
  Flag,
  Paperclip,
  ChevronDown,
  ChevronUp,
  Smile,
  Award,
  MoreVertical
} from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "../ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";
import { ReportDialog } from "../shared/ReportDialog";

export interface ForumPost {
  id: string;
  title: string;
  content: string;
  author: {
    id: string;
    nickname: string;
    isVerifiedMentor: boolean;
    avatar?: string;
  };
  category: {
    id: string;
    name: string;
    type: 'academic-qa' | 'general-discussion';
  };
  hashtags: string[];
  attachments?: {
    id: string;
    name: string;
    type: string;
    size: string;
    url: string;
  }[];
  createdAt: string;
  views: number;
  likes: number;
  commentCount: number;
  answerCount?: number;
  hasAcceptedAnswer?: boolean;
  isLiked: boolean;
}

export interface Answer {
  id: string;
  postId: string;
  content: string;
  author: {
    id: string;
    nickname: string;
    isVerifiedMentor: boolean;
  };
  upvotes: number;
  downvotes: number;
  isAccepted: boolean;
  userVote?: 'up' | 'down';
  reactions: {
    emoji: string;
    count: number;
    userReacted: boolean;
  }[];
  createdAt: string;
  mentions: string[];
}

export interface Comment {
  id: string;
  postId: string;
  content: string;
  author: {
    id: string;
    nickname: string;
    isVerifiedMentor: boolean;
  };
  createdAt: string;
  replies: Comment[];
  reactions: {
    emoji: string;
    count: number;
    userReacted: boolean;
  }[];
  mentions: string[];
}

interface EnhancedForumPostProps {
  post: ForumPost;
  answers?: Answer[];
  comments?: Comment[];
  currentUserId: string;
  onVote?: (answerId: string, voteType: 'up' | 'down') => void;
  onAcceptAnswer?: (answerId: string) => void;
  onReact?: (targetId: string, emoji: string) => void;
  onReply?: (targetId: string, content: string, mentions: string[]) => void;
  onReport?: (targetId: string, reason: string) => void;
  isAuthor: boolean;
}

const emojiOptions = ['👍', '❤️', '😊', '🎉', '🤔', '👏'];

export function EnhancedForumPost({
  post,
  answers = [],
  comments = [],
  currentUserId,
  onVote,
  onAcceptAnswer,
  onReact,
  onReply,
  onReport,
  isAuthor
}: EnhancedForumPostProps) {
  const [replyTo, setReplyTo] = useState<string | null>(null);
  const [replyContent, setReplyContent] = useState("");
  const [showReportDialog, setShowReportDialog] = useState(false);
  const [reportTarget, setReportTarget] = useState<string | null>(null);
  const [reportReason, setReportReason] = useState("");
  const [showEmojiPicker, setShowEmojiPicker] = useState<string | null>(null);
  const [showAcceptDialog, setShowAcceptDialog] = useState(false);
  const [answerToAccept, setAnswerToAccept] = useState<string | null>(null);

  const isQA = post.category.type === 'academic-qa';
  const sortedAnswers = [...answers].sort((a, b) => {
    if (a.isAccepted) return -1;
    if (b.isAccepted) return 1;
    return (b.upvotes - b.downvotes) - (a.upvotes - a.downvotes);
  });

  const handleReply = (targetId: string) => {
    if (!replyContent.trim()) return;
    
    // Extract mentions from content (e.g., @username)
    const mentions = replyContent.match(/@(\w+)/g)?.map(m => m.substring(1)) || [];
    
    onReply?.(targetId, replyContent, mentions);
    setReplyContent("");
    setReplyTo(null);
  };

  const handleReport = () => {
    if (reportTarget && reportReason.trim()) {
      onReport?.(reportTarget, reportReason);
      setShowReportDialog(false);
      setReportTarget(null);
      setReportReason("");
    }
  };

  const renderAnswer = (answer: Answer) => (
    <Card key={answer.id} className={`p-4 ${answer.isAccepted ? 'border-green-500 border-2' : ''}`}>
      {answer.isAccepted && (
        <div className="flex items-center gap-2 mb-3 text-green-600">
          <Check className="h-5 w-5" />
          <span className="font-semibold">Accepted Answer</span>
        </div>
      )}
      
      <div className="flex gap-4">
        {/* Vote controls for Q&A */}
        <div className="flex flex-col items-center gap-2">
          <Button
            size="sm"
            variant={answer.userVote === 'up' ? 'default' : 'ghost'}
            onClick={() => onVote?.(answer.id, 'up')}
          >
            <ThumbsUp className="h-4 w-4" />
          </Button>
          <span className="font-semibold">
            {answer.upvotes - answer.downvotes}
          </span>
          <Button
            size="sm"
            variant={answer.userVote === 'down' ? 'default' : 'ghost'}
            onClick={() => onVote?.(answer.id, 'down')}
          >
            <ThumbsDown className="h-4 w-4" />
          </Button>
          {isAuthor && !answer.isAccepted && (
            <Button
              size="sm"
              variant="outline"
              className="mt-2"
              onClick={() => {
                setAnswerToAccept(answer.id);
                setShowAcceptDialog(true);
              }}
            >
              <Check className="h-4 w-4 text-green-600" />
            </Button>
          )}
        </div>

        {/* Answer content */}
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-2">
            <Avatar className="h-8 w-8">
              <AvatarFallback>{answer.author.nickname[0]}</AvatarFallback>
            </Avatar>
            <div className="flex items-center gap-2">
              <span className="font-semibold">{answer.author.nickname}</span>
              {answer.author.isVerifiedMentor && (
                <Badge variant="secondary" className="text-xs flex items-center gap-1">
                  <Award className="h-3 w-3" />
                  Verified Mentor
                </Badge>
              )}
            </div>
            <span className="text-xs text-muted-foreground">{answer.createdAt}</span>
          </div>
          
          <p className="text-sm mb-3">{answer.content}</p>
          
          {/* Mentions */}
          {answer.mentions.length > 0 && (
            <div className="flex gap-1 mb-2">
              {answer.mentions.map(mention => (
                <Badge key={mention} variant="outline" className="text-xs">
                  @{mention}
                </Badge>
              ))}
            </div>
          )}

          {/* Reactions */}
          <div className="flex items-center gap-2 relative">
            {answer.reactions.map(reaction => (
              <Button
                key={reaction.emoji}
                size="sm"
                variant={reaction.userReacted ? 'default' : 'outline'}
                className="h-7 text-xs"
                onClick={() => onReact?.(answer.id, reaction.emoji)}
              >
                {reaction.emoji} {reaction.count}
              </Button>
            ))}
            <Button
              size="sm"
              variant="ghost"
              onClick={() => setShowEmojiPicker(showEmojiPicker === answer.id ? null : answer.id)}
            >
              <Smile className="h-4 w-4" />
            </Button>
            
            {showEmojiPicker === answer.id && (
              <div className="absolute left-0 top-full mt-2 bg-white border rounded-lg shadow-lg p-2 flex gap-1 z-10">
                {emojiOptions.map(emoji => (
                  <Button
                    key={emoji}
                    size="sm"
                    variant="ghost"
                    onClick={() => {
                      onReact?.(answer.id, emoji);
                      setShowEmojiPicker(null);
                    }}
                  >
                    {emoji}
                  </Button>
                ))}
              </div>
            )}

            <Button
              size="sm"
              variant="ghost"
              onClick={() => {
                setReportTarget(answer.id);
                setShowReportDialog(true);
              }}
            >
              <Flag className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    </Card>
  );

  const renderComment = (comment: Comment, depth: number = 0) => (
    <div key={comment.id} className={`${depth > 0 ? 'ml-8 mt-2' : 'mt-2'}`}>
      <Card className="p-3">
        <div className="flex items-start gap-3">
          <Avatar className="h-7 w-7">
            <AvatarFallback className="text-xs">{comment.author.nickname[0]}</AvatarFallback>
          </Avatar>
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-1">
              <span className="text-sm font-semibold">{comment.author.nickname}</span>
              {comment.author.isVerifiedMentor && (
                <Badge variant="secondary" className="text-xs flex items-center gap-1">
                  <Award className="h-3 w-3" />
                  Mentor
                </Badge>
              )}
              <span className="text-xs text-muted-foreground">{comment.createdAt}</span>
            </div>
            <p className="text-sm mb-2">{comment.content}</p>
            
            {/* Mentions */}
            {comment.mentions.length > 0 && (
              <div className="flex gap-1 mb-2">
                {comment.mentions.map(mention => (
                  <Badge key={mention} variant="outline" className="text-xs">
                    @{mention}
                  </Badge>
                ))}
              </div>
            )}

            {/* Reactions */}
            <div className="flex items-center gap-2">
              {comment.reactions.map(reaction => (
                <Button
                  key={reaction.emoji}
                  size="sm"
                  variant={reaction.userReacted ? 'default' : 'outline'}
                  className="h-6 text-xs"
                  onClick={() => onReact?.(comment.id, reaction.emoji)}
                >
                  {reaction.emoji} {reaction.count}
                </Button>
              ))}
              <Button
                size="sm"
                variant="ghost"
                className="h-6 text-xs"
                onClick={() => setReplyTo(replyTo === comment.id ? null : comment.id)}
              >
                Reply
              </Button>
              <Button
                size="sm"
                variant="ghost"
                className="h-6"
                onClick={() => {
                  setReportTarget(comment.id);
                  setShowReportDialog(true);
                }}
              >
                <Flag className="h-3 w-3" />
              </Button>
            </div>

            {/* Reply form */}
            {replyTo === comment.id && (
              <div className="mt-2">
                <Textarea
                  value={replyContent}
                  onChange={(e) => setReplyContent(e.target.value)}
                  placeholder="Write a reply... (use @username to mention)"
                  rows={2}
                  className="text-sm"
                />
                <div className="flex gap-2 mt-2">
                  <Button size="sm" onClick={() => handleReply(comment.id)}>
                    Reply
                  </Button>
                  <Button size="sm" variant="outline" onClick={() => setReplyTo(null)}>
                    Cancel
                  </Button>
                </div>
              </div>
            )}
          </div>
        </div>
      </Card>
      
      {/* Nested replies */}
      {comment.replies.map(reply => renderComment(reply, depth + 1))}
    </div>
  );

  return (
    <div className="space-y-4">
      {/* Main post header */}
      <Card className="p-6">
        <div className="flex items-start gap-4 mb-4">
          <Avatar className="h-10 w-10">
            <AvatarFallback>{post.author.nickname[0]}</AvatarFallback>
          </Avatar>
          <div className="flex-1">
            <div className="flex items-center justify-between mb-1">
              <div className="flex items-center gap-2">
                <span className="font-semibold">{post.author.nickname}</span>
                {post.author.isVerifiedMentor && (
                  <Badge variant="secondary" className="flex items-center gap-1">
                    <Award className="h-3 w-3" />
                    Verified Mentor
                  </Badge>
                )}
                <Badge variant="outline">{post.category.name}</Badge>
                <span className="text-xs text-muted-foreground">{post.createdAt}</span>
              </div>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem
                    onClick={() => {
                      setReportTarget(post.id);
                      setShowReportDialog(true);
                    }}
                  >
                    <Flag className="h-4 w-4 mr-2" />
                    Report
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
            <h2 className="text-xl mb-2">{post.title}</h2>
            <p className="text-sm text-muted-foreground mb-3">{post.content}</p>
            
            {/* Hashtags */}
            {post.hashtags.length > 0 && (
              <div className="flex gap-2 flex-wrap mb-3">
                {post.hashtags.map(tag => (
                  <Badge key={tag} variant="secondary" className="text-xs">
                    #{tag}
                  </Badge>
                ))}
              </div>
            )}

            {/* Attachments */}
            {post.attachments && post.attachments.length > 0 && (
              <div className="space-y-2 mb-3">
                {post.attachments.map(attachment => (
                  <div key={attachment.id} className="flex items-center gap-2 text-sm">
                    <Paperclip className="h-4 w-4" />
                    <span>{attachment.name}</span>
                    <span className="text-muted-foreground">({attachment.size})</span>
                  </div>
                ))}
              </div>
            )}

            {/* Stats */}
            <div className="flex items-center gap-4 text-sm text-muted-foreground">
              <div className="flex items-center gap-1">
                <Eye className="h-4 w-4" />
                {post.views}
              </div>
              <div className="flex items-center gap-1">
                <Heart className={post.isLiked ? "h-4 w-4 fill-red-500 text-red-500" : "h-4 w-4"} />
                {post.likes}
              </div>
              {isQA ? (
                <div className="flex items-center gap-1">
                  <MessageCircle className="h-4 w-4" />
                  {post.answerCount} Answers
                  {post.hasAcceptedAnswer && <Check className="h-4 w-4 text-green-600 ml-1" />}
                </div>
              ) : (
                <div className="flex items-center gap-1">
                  <MessageCircle className="h-4 w-4" />
                  {post.commentCount} Comments
                </div>
              )}
            </div>
          </div>
        </div>
      </Card>

      {/* Answers (for Q&A) or Comments (for Discussion) */}
      <div className="space-y-4">
        <h3 className="font-semibold">
          {isQA ? `${answers.length} Answers` : `${comments.length} Comments`}
        </h3>
        
        {isQA ? (
          <div className="space-y-3">
            {sortedAnswers.map(renderAnswer)}
          </div>
        ) : (
          <div className="space-y-2">
            {comments.map(comment => renderComment(comment, 0))}
          </div>
        )}

        {/* Add answer/comment form */}
        <Card className="p-4">
          <Textarea
            value={replyContent}
            onChange={(e) => setReplyContent(e.target.value)}
            placeholder={isQA ? "Write your answer..." : "Write a comment... (use @username to mention)"}
            rows={4}
          />
          <Button className="mt-2" onClick={() => handleReply(post.id)}>
            {isQA ? 'Post Answer' : 'Post Comment'}
          </Button>
        </Card>
      </div>

      {/* Report dialog */}
      <Dialog open={showReportDialog} onOpenChange={setShowReportDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Report Content</DialogTitle>
            <DialogDescription>Please describe why you're reporting this content...</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <Textarea
              value={reportReason}
              onChange={(e) => setReportReason(e.target.value)}
              placeholder="Please describe why you're reporting this content..."
              rows={4}
            />
            <div className="flex gap-2">
              <Button onClick={handleReport}>Submit Report</Button>
              <Button variant="outline" onClick={() => setShowReportDialog(false)}>
                Cancel
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Accept answer dialog */}
      <Dialog open={showAcceptDialog} onOpenChange={setShowAcceptDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Accept Answer</DialogTitle>
            <DialogDescription>
              Are you sure you want to mark this as the accepted answer? This will highlight it as the best solution to your question.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Only one answer can be accepted at a time. You can change the accepted answer later if needed.
            </p>
            <div className="flex gap-2 justify-end">
              <Button variant="outline" onClick={() => setShowAcceptDialog(false)}>
                Cancel
              </Button>
              <Button 
                className="bg-green-600 hover:bg-green-700"
                onClick={() => {
                  onAcceptAnswer?.(answerToAccept!);
                  setShowAcceptDialog(false);
                  setAnswerToAccept(null);
                }}
              >
                Accept Answer
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}