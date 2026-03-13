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
  Smile,
  Award,
  MoreVertical,
  Pencil,
  Trash2
} from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "../ui/dialog";
import { ReportDialog } from "../shared/ReportDialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";

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
  onEditPost?: (postId: string, data: { title: string; content: string }) => void;
  onDeletePost?: (postId: string) => void;
  onEditComment?: (commentId: string, content: string) => void;
  onDeleteComment?: (commentId: string) => void;
  isAuthor: boolean;
  isMuted?: boolean;
  mutedUntilDate?: string | null;
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
  onEditPost,
  onDeletePost,
  onEditComment,
  onDeleteComment,
  isAuthor,
  isMuted,
  mutedUntilDate
}: EnhancedForumPostProps) {
  const [replyTo, setReplyTo] = useState<string | null>(null);
  const [replyContent, setReplyContent] = useState("");
  const [showReportDialog, setShowReportDialog] = useState(false);
  const [reportTarget, setReportTarget] = useState<{ type: 'post' | 'answer' | 'comment'; id: string } | null>(null);
  const [showEmojiPicker, setShowEmojiPicker] = useState<string | null>(null);
  const [showAcceptDialog, setShowAcceptDialog] = useState(false);
  const [answerToAccept, setAnswerToAccept] = useState<string | null>(null);

  // Edit post state
  const [editingPost, setEditingPost] = useState(false);
  const [editPostTitle, setEditPostTitle] = useState(post.title);
  const [editPostContent, setEditPostContent] = useState(post.content);

  // Edit comment state
  const [editingCommentId, setEditingCommentId] = useState<string | null>(null);
  const [editCommentContent, setEditCommentContent] = useState("");

  // Delete confirmation state
  const [deleteTarget, setDeleteTarget] = useState<{ type: 'post' | 'comment'; id: string } | null>(null);

  const isQA = post.category.type === 'academic-qa';
  // Keep the order as fetched from the server (sorted by accepted + vote score)
  // Answers will be re-sorted on next page load, not immediately after voting
  const sortedAnswers = answers;

  const handleReply = (targetId: string) => {
    if (!replyContent.trim()) return;
    
    // Extract mentions from content (e.g., @username)
    const mentions = replyContent.match(/@(\w+)/g)?.map(m => m.substring(1)) || [];
    
    onReply?.(targetId, replyContent, mentions);
    setReplyContent("");
    setReplyTo(null);
  };

  const handleReport = (reason: string, details: string) => {
    if (reportTarget) {
      onReport?.(reportTarget.id, `${reason}: ${details}`);
      setShowReportDialog(false);
      setReportTarget(null);
    }
  };

  const renderAnswer = (answer: Answer) => (
    <Card key={answer.id} className={`p-4 cursor-pointer ${answer.isAccepted ? 'border-green-500 border-2' : ''}`}>
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
              className="mt-2 cursor-pointer"
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
                className="h-7 text-xs cursor-pointer"
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

            {answer.author.id !== currentUserId && (
              <DropdownMenu>
                <DropdownMenuTrigger className="inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                  <MoreVertical className="h-4 w-4" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                  <DropdownMenuItem
                    className="text-gray-300 hover:text-white cursor-pointer"
                    onClick={() => {
                      setReportTarget({ type: 'answer', id: answer.id });
                      setShowReportDialog(true);
                    }}
                  >
                    <Flag className="h-4 w-4 mr-2" />
                    Report
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </div>
      </div>
    </Card>
  );

  const renderComment = (comment: Comment, depth: number = 0) => (
    <div key={comment.id} className={` cursor-pointer ${depth > 0 ? 'ml-8 mt-2' : 'mt-2'}`}>
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
            {editingCommentId === comment.id ? (
              <div className="space-y-2 mb-2">
                <Textarea
                  value={editCommentContent}
                  onChange={(e) => setEditCommentContent(e.target.value)}
                  rows={2}
                  className="text-sm"
                />
                <div className="flex gap-2">
                  <Button size="sm" disabled={!editCommentContent.trim()} onClick={() => { onEditComment?.(comment.id, editCommentContent); setEditingCommentId(null); }}>
                    Save
                  </Button>
                  <Button size="sm" variant="outline" onClick={() => setEditingCommentId(null)}>
                    Cancel
                  </Button>
                </div>
              </div>
            ) : (
              <p className="text-sm mb-2">{comment.content}</p>
            )}
            
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
                  className="h-6 text-xs cursor-pointer"
                  onClick={() => onReact?.(comment.id, reaction.emoji)}
                >
                  {reaction.emoji} {reaction.count}
                </Button>
              ))}
              {!isMuted && (
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-6 text-xs cursor-pointer"
                  onClick={() => setReplyTo(replyTo === comment.id ? null : comment.id)}
                >
                  Reply
                </Button>
              )}
              {comment.author.id !== currentUserId && (
                <DropdownMenu>
                  <DropdownMenuTrigger className="inline-flex items-center justify-center h-6 w-6 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                    <MoreVertical className="h-3 w-3" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                    <DropdownMenuItem
                      className="text-gray-300 hover:text-red-300 cursor-pointer"
                      onClick={() => {
                        setReportTarget({ type: 'comment', id: comment.id });
                        setShowReportDialog(true);
                      }}
                    >
                      <Flag className="h-4 w-4 mr-2" />
                      Report
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              )}
              {comment.author.id === currentUserId && (
                <DropdownMenu>
                  <DropdownMenuTrigger className="inline-flex items-center justify-center h-6 w-6 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                    <MoreVertical className="h-3 w-3" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                    <DropdownMenuItem
                      className="text-gray-300 hover:text-white cursor-pointer"
                      onClick={() => {
                        setEditingCommentId(comment.id);
                        setEditCommentContent(comment.content);
                      }}
                    >
                      <Pencil className="h-4 w-4 mr-2" />
                      Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator className="bg-gray-700" />
                    <DropdownMenuItem
                      className="text-gray-300 hover:text-red-300 cursor-pointer"
                      onClick={() => setDeleteTarget({ type: 'comment', id: comment.id })}
                    >
                      <Trash2 className="h-4 w-4 mr-2" />
                      Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              )}
            </div>

            {/* Reply form */}
            {replyTo === comment.id && (
              <div className="mt-2">
                <Textarea
                  value={replyContent}
                  onChange={(e) => setReplyContent(e.target.value)}
                  placeholder="Write a reply... (use @student_id to mention)"
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
    <div className="space-y-4 cursor-pointer">
      {/* Main post header */}
      <Card className="p-6 cursor-pointer">
        <div className="flex items-start gap-4 mb-4 cursor-pointer">
          <Avatar className="h-10 w-10 cursor-pointer">
            <AvatarFallback>{post.author.nickname[0]}</AvatarFallback>
          </Avatar>
          <div className="flex-1 cursor-pointer">
            <div className="flex items-center justify-between mb-1 cursor-pointer">
              <div className="flex items-center gap-2 cursor-pointer">
                <span className="font-semibold cursor-pointer">{post.author.nickname}</span>
                {post.author.isVerifiedMentor && (
                  <Badge variant="secondary" className="flex items-center gap-1 cursor-pointer">
                    <Award className="h-3 w-3 cursor-pointer" />
                    Verified Mentor
                  </Badge>
                )}
                <Badge variant="outline">{post.category.name}</Badge>
                <span className="text-xs text-muted-foreground cursor-pointer">{post.createdAt}</span>
              </div>
              {post.author.id !== currentUserId && (
                <DropdownMenu>
                  <DropdownMenuTrigger className="inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                    <MoreVertical className="h-4 w-4" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                    <DropdownMenuItem
                      className="text-gray-300 hover:text-red-300 cursor-pointer"
                      onClick={() => {
                        setReportTarget({ type: 'post', id: post.id });
                        setShowReportDialog(true);
                      }}
                    >
                      <Flag className="h-4 w-4 mr-2" />
                      Report
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              )}
              {post.author.id === currentUserId && (
                <DropdownMenu>
                  <DropdownMenuTrigger className="inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                    <MoreVertical className="h-4 w-4" />
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                    <DropdownMenuItem
                      className="text-gray-300 hover:text-white cursor-pointer"
                      onClick={() => {
                        setEditPostTitle(post.title);
                        setEditPostContent(post.content);
                        setEditingPost(true);
                      }}
                    >
                      <Pencil className="h-4 w-4 mr-2" />
                      Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator className="bg-gray-700" />
                    <DropdownMenuItem
                      className="text-gray-300 hover:text-red-300 cursor-pointer"
                      onClick={() => setDeleteTarget({ type: 'post', id: post.id })}
                    >
                      <Trash2 className="h-4 w-4 mr-2" />
                      Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              )}
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
                  <button
                    key={attachment.id}
                    onClick={(e) => {
                      e.stopPropagation();
                      const link = document.createElement('a');
                      link.href = attachment.url;
                      link.download = attachment.name;
                      document.body.appendChild(link);
                      link.click();
                      document.body.removeChild(link);
                    }}
                    className="flex items-center gap-2 text-sm text-blue-500 hover:text-blue-400 hover:underline cursor-pointer"
                  >
                    <Paperclip className="h-4 w-4" />
                    <span>{attachment.name}</span>
                    <span className="text-muted-foreground">({attachment.size})</span>
                  </button>
                ))}
              </div>
            )}

            {/* Stats */}
            <div className="flex items-center gap-4 text-sm text-muted-foreground">
              {/* <div className="flex items-center gap-1">
                <Eye className="h-4 w-4" />
                {post.views}
              </div> */}
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
        {isMuted ? (
          <Card className="p-4 border-red-500/30 bg-red-500/10">
            <p className="text-red-400 text-sm font-medium">
              🔇 Your account is muted{mutedUntilDate ? ` until ${mutedUntilDate}` : ''}. You cannot {isQA ? 'post answers' : 'comment'} during this period.
            </p>
          </Card>
        ) : (
          <Card className="p-4">
            <Textarea
              value={replyContent}
              onChange={(e) => setReplyContent(e.target.value)}
              placeholder={isQA ? "Write your answer..." : "Write a comment... (use @student_id to mention)"}
              rows={4}
            />
            <Button className="mt-2 cursor-pointer" onClick={() => handleReply(post.id)}>
              {isQA ? 'Post Answer' : 'Post Comment'}
            </Button>
          </Card>
        )}
      </div>

      {/* Report dialog */}
      <ReportDialog
        isOpen={showReportDialog}
        onClose={() => { setShowReportDialog(false); setReportTarget(null); }}
        onReport={handleReport}
        content={reportTarget}
      />

      {/* Accept answer dialog */}
      <Dialog open={showAcceptDialog} onOpenChange={setShowAcceptDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Accept Answer</DialogTitle>
            <DialogDescription>
              Are you sure you want to mark this as the accepted answer? This will highlight it as the best solution to your question.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 cursor-pointer">
            <p className="text-sm text-muted-foreground cursor-pointer">
              Only one answer can be accepted at a time. You can change the accepted answer later if needed.
            </p>
            <div className="flex gap-2 justify-end cursor-pointer">
              <Button variant="outline" onClick={() => setShowAcceptDialog(false)}>
                Cancel
              </Button>
              <Button 
                className="bg-green-600 hover:bg-green-700 cursor-pointer"
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

      {/* Edit Post Dialog */}
      <Dialog open={editingPost} onOpenChange={setEditingPost}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Post</DialogTitle>
            <DialogDescription>Update your post title and content.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <label className="text-sm text-muted-foreground mb-1 block">Title</label>
              <Textarea
                value={editPostTitle}
                onChange={(e) => setEditPostTitle(e.target.value)}
                className="min-h-[40px] resize-none"
              />
            </div>
            <div>
              <label className="text-sm text-muted-foreground mb-1 block">Content</label>
              <Textarea
                value={editPostContent}
                onChange={(e) => setEditPostContent(e.target.value)}
                className="min-h-[120px] resize-none"
              />
            </div>
            <div className="flex gap-2 justify-end">
              <Button variant="outline" onClick={() => setEditingPost(false)} className="cursor-pointer">
                Cancel
              </Button>
              <Button
                disabled={!editPostTitle.trim() || !editPostContent.trim()}
                onClick={() => {
                  onEditPost?.(post.id, { title: editPostTitle, content: editPostContent });
                  setEditingPost(false);
                }}
                className="cursor-pointer"
              >
                Save Changes
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={!!deleteTarget} onOpenChange={(open) => { if (!open) setDeleteTarget(null); }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete {deleteTarget?.type === 'post' ? 'Post' : 'Comment'}</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete this {deleteTarget?.type}? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <div className="flex gap-2 justify-end">
            <Button variant="outline" onClick={() => setDeleteTarget(null)} className="cursor-pointer">
              Cancel
            </Button>
            <Button
              onClick={() => {
                if (deleteTarget?.type === 'post') {
                  onDeletePost?.(deleteTarget.id);
                } else if (deleteTarget?.type === 'comment') {
                  onDeleteComment?.(deleteTarget.id);
                }
                setDeleteTarget(null);
              }}
              className="bg-red-600 hover:bg-red-700 text-white cursor-pointer"
            >
              Delete
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}