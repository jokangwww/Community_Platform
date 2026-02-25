import { useState } from "react";
import { Button } from "../ui/button";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { Textarea } from "../ui/textarea";
import { ReportDialog } from "../shared/ReportDialog";
import { 
  ArrowLeft, 
  Heart, 
  MessageCircle, 
  Eye, 
  Share2,
  MoreVertical,
  Send,
  Flag
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";

interface Comment {
  id: string;
  author: string;
  authorAvatar: string;
  content: string;
  timeAgo: string;
  likes: number;
  isLiked: boolean;
  replies?: Comment[];
}

interface ForumPostDetail {
  id: string;
  title: string;
  content: string;
  tags: string[];
  author: string;
  authorAvatar: string;
  timeAgo: string;
  views: number;
  likes: number;
  isLiked: boolean;
  comments: Comment[];
}

interface ForumPostDetailViewProps {
  post: ForumPostDetail;
  currentUserId: string;
  onBack: () => void;
  onLike: () => void;
  onCommentLike: (commentId: string) => void;
  onAddComment: (content: string) => void;
  onReply: (commentId: string, content: string) => void;
  onReport?: (contentId: string, contentType: 'post' | 'comment', reason: string, details: string, reporterId: string) => void;
}

export function ForumPostDetailView({
  post,
  currentUserId,
  onBack,
  onLike,
  onCommentLike,
  onAddComment,
  onReply,
  onReport
}: ForumPostDetailViewProps) {
  const [newComment, setNewComment] = useState("");
  const [replyingTo, setReplyingTo] = useState<string | null>(null);
  const [replyContent, setReplyContent] = useState("");
  const [showReportDialog, setShowReportDialog] = useState(false);
  const [reportingContent, setReportingContent] = useState<{
    type: 'post' | 'comment';
    id: string;
  } | null>(null);

  const handleSubmitComment = () => {
    if (newComment.trim()) {
      onAddComment(newComment);
      setNewComment("");
    }
  };

  const handleSubmitReply = (commentId: string) => {
    if (replyContent.trim()) {
      onReply(commentId, replyContent);
      setReplyContent("");
      setReplyingTo(null);
    }
  };

  const handleReport = (reason: string, details: string) => {
    if (onReport && reportingContent) {
      onReport(reportingContent.id, reportingContent.type, reason, details, currentUserId);
    }
    setShowReportDialog(false);
    setReportingContent(null);
  };

  return (
    <div className="min-h-screen bg-[#e8e8ea]">
      <div className="max-w-4xl mx-auto px-6 py-6">
        {/* Back Button */}
        <Button
          variant="ghost"
          onClick={onBack}
          className="mb-4 text-gray-700 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back to Forum
        </Button>

        {/* Post Content */}
        <div className="bg-[#2c3138] rounded-xl p-6 mb-6">
          {/* Post Header */}
          <div className="flex items-start justify-between mb-4">
            <div className="flex items-center gap-3">
              <Avatar className="h-12 w-12">
                <AvatarFallback className="bg-[#6b7280] text-white">
                  {post.authorAvatar}
                </AvatarFallback>
              </Avatar>
              <div>
                <h3 className="text-white font-medium">{post.author}</h3>
                <p className="text-gray-400 text-sm">{post.timeAgo}</p>
              </div>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="text-gray-400 hover:text-white">
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent className="bg-[#2c3138] text-gray-300">
                <DropdownMenuItem
                  onClick={() => {
                    setReportingContent({ type: 'post', id: post.id });
                    setShowReportDialog(true);
                  }}
                >
                  <Flag className="h-4 w-4 mr-2" />
                  Report
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          {/* Post Title */}
          <h1 className="text-white text-2xl font-semibold mb-4">{post.title}</h1>

          {/* Post Content */}
          <div className="text-gray-300 mb-4 leading-relaxed">
            {post.content}
          </div>

          {/* Tags */}
          <div className="flex gap-2 mb-6">
            {post.tags.map(tag => (
              <Badge 
                key={tag}
                variant="secondary"
                className="bg-[#3a4149] text-gray-300 text-xs px-3 py-1 rounded-full"
              >
                {tag}
              </Badge>
            ))}
          </div>

          {/* Post Stats and Actions */}
          <div className="flex items-center justify-between pt-4 border-t border-gray-700">
            <div className="flex items-center gap-6 text-sm text-gray-400">
              <div className="flex items-center gap-1.5">
                <Eye className="h-4 w-4" />
                <span>{post.views} Views</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Heart className={`h-4 w-4 ${post.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`} />
                <span>{post.likes} Likes</span>
              </div>
              <div className="flex items-center gap-1.5">
                <MessageCircle className="h-4 w-4" />
                <span>{post.comments.length} Comments</span>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={onLike}
                className={`${post.isLiked ? 'bg-[#ff6934] text-white border-[#ff6934]' : 'bg-[#3a4149] text-gray-300 border-gray-700'} hover:bg-[#ff7a47]`}
              >
                <Heart className={`h-4 w-4 mr-2 ${post.isLiked ? 'fill-white' : ''}`} />
                {post.isLiked ? 'Liked' : 'Like'}
              </Button>
              
            </div>
          </div>
        </div>

        {/* Comments Section */}
        <div className="bg-[#2c3138] rounded-xl p-6">
          <h2 className="text-white text-xl font-semibold mb-6">
            Comments ({post.comments.length})
          </h2>

          {/* Add Comment */}
          <div className="mb-6">
            <div className="flex gap-3">
              <Avatar className="h-10 w-10">
                <AvatarFallback className="bg-[#6b7280] text-white text-sm">
                  You
                </AvatarFallback>
              </Avatar>
              <div className="flex-1">
                <Textarea
                  placeholder="Write a comment..."
                  value={newComment}
                  onChange={(e) => setNewComment(e.target.value)}
                  className="bg-[#3a4149] border-gray-700 text-white placeholder:text-gray-500 min-h-[80px] resize-none"
                />
                <div className="flex justify-end mt-2">
                  <Button
                    onClick={handleSubmitComment}
                    disabled={!newComment.trim()}
                    className="bg-[#ff6934] hover:bg-[#ff7a47] text-white"
                  >
                    <Send className="h-4 w-4 mr-2" />
                    Comment
                  </Button>
                </div>
              </div>
            </div>
          </div>

          {/* Comments List */}
          <div className="space-y-6">
            {post.comments.map(comment => (
              <div key={comment.id} className="space-y-4">
                {/* Comment */}
                <div className="flex gap-3">
                  <Avatar className="h-10 w-10">
                    <AvatarFallback className="bg-[#6b7280] text-white text-sm">
                      {comment.authorAvatar}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <div className="bg-[#3a4149] rounded-lg p-4">
                      <div className="flex items-center justify-between mb-2">
                        <div>
                          <h4 className="text-white font-medium text-sm">{comment.author}</h4>
                          <p className="text-gray-400 text-xs">{comment.timeAgo}</p>
                        </div>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="text-gray-400 hover:text-white">
                              <MoreVertical className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent className="bg-[#2c3138] text-gray-300">
                            <DropdownMenuItem
                              onClick={() => {
                                setReportingContent({ type: 'comment', id: comment.id });
                                setShowReportDialog(true);
                              }}
                            >
                              <Flag className="h-4 w-4 mr-2" />
                              Report
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </div>
                      <p className="text-gray-300 text-sm">{comment.content}</p>
                    </div>
                    
                    {/* Comment Actions */}
                    <div className="flex items-center gap-4 mt-2 ml-2">
                      <button
                        onClick={() => onCommentLike(comment.id)}
                        className="flex items-center gap-1.5 text-gray-400 hover:text-[#ff6934] transition-colors"
                      >
                        <Heart className={`h-3.5 w-3.5 ${comment.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`} />
                        <span className="text-xs">{comment.likes}</span>
                      </button>
                      <button
                        onClick={() => setReplyingTo(comment.id)}
                        className="text-gray-400 hover:text-white text-xs transition-colors"
                      >
                        Reply
                      </button>
                    </div>

                    {/* Reply Input */}
                    {replyingTo === comment.id && (
                      <div className="mt-3 ml-4 flex gap-3">
                        <Avatar className="h-8 w-8">
                          <AvatarFallback className="bg-[#6b7280] text-white text-xs">
                            You
                          </AvatarFallback>
                        </Avatar>
                        <div className="flex-1">
                          <Textarea
                            placeholder={`Reply to ${comment.author}...`}
                            value={replyContent}
                            onChange={(e) => setReplyContent(e.target.value)}
                            className="bg-[#2c3138] border-gray-700 text-white placeholder:text-gray-500 min-h-[60px] resize-none text-sm"
                          />
                          <div className="flex justify-end gap-2 mt-2">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => {
                                setReplyingTo(null);
                                setReplyContent("");
                              }}
                              className="text-gray-400 hover:text-white"
                            >
                              Cancel
                            </Button>
                            <Button
                              onClick={() => handleSubmitReply(comment.id)}
                              disabled={!replyContent.trim()}
                              size="sm"
                              className="bg-[#ff6934] hover:bg-[#ff7a47] text-white"
                            >
                              Reply
                            </Button>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Nested Replies */}
                    {comment.replies && comment.replies.length > 0 && (
                      <div className="mt-4 ml-4 space-y-4 pl-4 border-l-2 border-gray-700">
                        {comment.replies.map(reply => (
                          <div key={reply.id} className="flex gap-3">
                            <Avatar className="h-8 w-8">
                              <AvatarFallback className="bg-[#6b7280] text-white text-xs">
                                {reply.authorAvatar}
                              </AvatarFallback>
                            </Avatar>
                            <div className="flex-1">
                              <div className="bg-[#3a4149] rounded-lg p-3">
                                <div className="flex items-center justify-between mb-2">
                                  <div>
                                    <h4 className="text-white font-medium text-sm">{reply.author}</h4>
                                    <p className="text-gray-400 text-xs">{reply.timeAgo}</p>
                                  </div>
                                </div>
                                <p className="text-gray-300 text-sm">{reply.content}</p>
                              </div>
                              
                              {/* Reply Actions */}
                              <div className="flex items-center gap-4 mt-2 ml-2">
                                <button
                                  onClick={() => onCommentLike(reply.id)}
                                  className="flex items-center gap-1.5 text-gray-400 hover:text-[#ff6934] transition-colors"
                                >
                                  <Heart className={`h-3.5 w-3.5 ${reply.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`} />
                                  <span className="text-xs">{reply.likes}</span>
                                </button>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* No Comments State */}
          {post.comments.length === 0 && (
            <div className="text-center py-12">
              <MessageCircle className="h-12 w-12 text-gray-600 mx-auto mb-3" />
              <p className="text-gray-400">No comments yet. Be the first to comment!</p>
            </div>
          )}
        </div>
      </div>
      <ReportDialog
        isOpen={showReportDialog}
        onClose={() => setShowReportDialog(false)}
        onReport={handleReport}
        content={reportingContent}
      />
    </div>
  );
}