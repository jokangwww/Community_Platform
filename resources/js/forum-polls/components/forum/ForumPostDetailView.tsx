import { useState } from "react";
import { Button } from "../ui/button";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { Textarea } from "../ui/textarea";
import { ReportDialog } from "../shared/ReportDialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "../ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";
import { 
  ArrowLeft, 
  Heart, 
  MessageCircle, 
  Eye, 
  Send,
  Flag,
  Paperclip,
  MoreVertical,
  Pencil,
  Trash2
} from "lucide-react";

interface Comment {
  id: string;
  authorId?: string;
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
  authorId?: string;
  authorAvatar: string;
  timeAgo: string;
  views: number;
  likes: number;
  isLiked: boolean;
  comments: Comment[];
  attachments?: {
    id: string;
    name: string;
    type: string;
    size: string;
    url: string;
  }[];
}

interface ForumPostDetailViewProps {
  post: ForumPostDetail;
  currentUserId: string;
  onBack: () => void;
  onLike: () => void;
  onCommentLike: (commentId: string) => void;
  onAddComment: (content: string) => void;
  onReply: (commentId: string, content: string) => void;
  onReport?: (targetId: string, reason: string) => void;
  onEditPost?: (postId: string, data: { title: string; content: string }) => void;
  onDeletePost?: (postId: string) => void;
  onEditComment?: (commentId: string, content: string) => void;
  onDeleteComment?: (commentId: string) => void;
}

export function ForumPostDetailView({
  post,
  currentUserId,
  onBack,
  onLike,
  onCommentLike,
  onAddComment,
  onReply,
  onReport,
  onEditPost,
  onDeletePost,
  onEditComment,
  onDeleteComment
}: ForumPostDetailViewProps) {
  const [newComment, setNewComment] = useState("");
  const [replyingTo, setReplyingTo] = useState<string | null>(null);
  const [replyContent, setReplyContent] = useState("");
  const [showReportDialog, setShowReportDialog] = useState(false);
  const [reportingContent, setReportingContent] = useState<{
    type: 'post' | 'comment';
    id: string;
  } | null>(null);

  // Edit post state
  const [editingPost, setEditingPost] = useState(false);
  const [editPostTitle, setEditPostTitle] = useState(post.title);
  const [editPostContent, setEditPostContent] = useState(post.content);

  // Edit comment state
  const [editingCommentId, setEditingCommentId] = useState<string | null>(null);
  const [editCommentContent, setEditCommentContent] = useState("");

  // Delete confirmation state
  const [deleteTarget, setDeleteTarget] = useState<{ type: 'post' | 'comment'; id: string } | null>(null);

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
      onReport(reportingContent.id, `${reason}: ${details}`);
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
          className="mb-4 text-gray-700 hover:text-gray-900 hover:bg-gray-300 cursor-pointer"
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
            {post.authorId !== currentUserId && (
              <DropdownMenu>
                <DropdownMenuTrigger className="inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                  <MoreVertical className="h-4 w-4" />
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                  <DropdownMenuItem
                    className="text-gray-300 hover:text-white cursor-pointer"
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
            )}
            {post.authorId === currentUserId && (
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
                    className="text-gray-300 hover:text-white cursor-pointer"
                    onClick={() => setDeleteTarget({ type: 'post', id: post.id })}
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>

          {/* Post Title */}
          <h1 className="text-white text-2xl font-semibold mb-4">{post.title}</h1>

          {/* Post Content */}
          <div className="text-gray-300 mb-4 leading-relaxed">
            {post.content}
          </div>

          {/* Tags */}
          <div className="flex gap-2 mb-4">
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

          {/* Attachments */}
          {post.attachments && post.attachments.length > 0 && (
            <div className="mb-6 space-y-2">
              <p className="text-gray-400 text-sm font-medium">Attachments:</p>
              {post.attachments.map(attachment => (
                <button
                  key={attachment.id}
                  onClick={() => window.open(attachment.url, '_blank')}
                  className="flex items-center gap-2 text-sm text-blue-400 hover:text-blue-300 hover:underline cursor-pointer"
                >
                  <Paperclip className="h-4 w-4" />
                  <span>{attachment.name}</span>
                  <span className="text-gray-500">({attachment.size})</span>
                </button>
              ))}
            </div>
          )}

          {/* Post Stats and Actions */}
          <div className="flex items-center justify-between pt-4 border-t border-gray-700">
            <div className="flex items-center gap-6 text-sm text-gray-400">
              {/* <div className="flex items-center gap-1.5">
                <Eye className="h-4 w-4" />
                <span>{post.views} Views</span>
              </div> */}
              <div className="flex items-center gap-1.5">
                <Heart className={`h-4 w-4 cursor-pointer ${post.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`} />
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
                className={`cursor-pointer ${post.isLiked ? 'bg-[#ff6934] text-white border-[#ff6934]' : 'bg-[#3a4149] text-gray-300 border-gray-700'} hover:bg-[#ff7a47]`}
              >
                <Heart className={`h-4 w-4 mr-2 cursor-pointer ${post.isLiked ? 'fill-white' : ''}`} />
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
                    className="bg-[#ff6934] hover:bg-[#ff7a47] text-white cursor-pointer"
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
                        {comment.authorId !== currentUserId && (
                          <DropdownMenu>
                            <DropdownMenuTrigger className="inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                              <MoreVertical className="h-4 w-4" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="bg-[#2c3138] border-gray-700">
                              <DropdownMenuItem
                                className="text-gray-300 hover:text-white cursor-pointer"
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
                        )}
                        {comment.authorId === currentUserId && (
                          <DropdownMenu>
                            <DropdownMenuTrigger className="inline-flex items-center justify-center h-8 w-8 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 cursor-pointer focus:outline-none">
                              <MoreVertical className="h-4 w-4" />
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
                                className="text-gray-300 hover:text-white cursor-pointer"
                                onClick={() => setDeleteTarget({ type: 'comment', id: comment.id })}
                              >
                                <Trash2 className="h-4 w-4 mr-2" />
                                Delete
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        )}
                      </div>
                      {editingCommentId === comment.id ? (
                        <div className="space-y-2">
                          <Textarea
                            value={editCommentContent}
                            onChange={(e) => setEditCommentContent(e.target.value)}
                            className="bg-[#2c3138] border-gray-700 text-white placeholder:text-gray-500 min-h-[60px] resize-none text-sm"
                          />
                          <div className="flex gap-2 justify-end">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setEditingCommentId(null)}
                              className="text-gray-400 hover:text-white cursor-pointer"
                            >
                              Cancel
                            </Button>
                            <Button
                              size="sm"
                              disabled={!editCommentContent.trim()}
                              onClick={() => {
                                onEditComment?.(comment.id, editCommentContent);
                                setEditingCommentId(null);
                              }}
                              className="bg-[#ff6934] hover:bg-[#ff7a47] text-white cursor-pointer"
                            >
                              Save
                            </Button>
                          </div>
                        </div>
                      ) : (
                        <p className="text-gray-300 text-sm">{comment.content}</p>
                      )}
                    </div>
                    
                    {/* Comment Actions */}
                    <div className="flex items-center gap-4 mt-2 ml-2">
                      <button
                        onClick={() => onCommentLike(comment.id)}
                        className="flex items-center gap-1.5 text-gray-400 hover:text-[#ff6934] transition-colors cursor-pointer"
                      >
                        <Heart className={`h-3.5 w-3.5 cursor-pointer ${comment.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`} />
                        <span className="text-xs">{comment.likes}</span>
                      </button>
                      <button
                        onClick={() => setReplyingTo(comment.id)}
                        className="text-gray-400 hover:text-white text-xs transition-colors cursor-pointer"
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
                              className="text-gray-400 hover:text-white cursor-pointer"
                            >
                              Cancel
                            </Button>
                            <Button
                              onClick={() => handleSubmitReply(comment.id)}
                              disabled={!replyContent.trim()}
                              size="sm"
                              className="bg-[#ff6934] hover:bg-[#ff7a47] text-white cursor-pointer"
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
                                  className="flex items-center gap-1.5 text-gray-400 hover:text-[#ff6934] transition-colors cursor-pointer"
                                >
                                  <Heart className={`h-3.5 w-3.5 cursor-pointer ${reply.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`} />
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

      {/* Edit Post Dialog */}
      <Dialog open={editingPost} onOpenChange={setEditingPost}>
        <DialogContent className="bg-[#2c3138] border-gray-700 text-white">
          <DialogHeader>
            <DialogTitle className="text-white">Edit Post</DialogTitle>
            <DialogDescription className="text-gray-400">Update your post title and content.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <label className="text-sm text-gray-400 mb-1 block">Title</label>
              <Textarea
                value={editPostTitle}
                onChange={(e) => setEditPostTitle(e.target.value)}
                className="bg-[#3a4149] border-gray-700 text-white placeholder:text-gray-500 min-h-[40px] resize-none"
              />
            </div>
            <div>
              <label className="text-sm text-gray-400 mb-1 block">Content</label>
              <Textarea
                value={editPostContent}
                onChange={(e) => setEditPostContent(e.target.value)}
                className="bg-[#3a4149] border-gray-700 text-white placeholder:text-gray-500 min-h-[120px] resize-none"
              />
            </div>
            <div className="flex gap-2 justify-end">
              <Button
                variant="ghost"
                onClick={() => setEditingPost(false)}
                className="text-gray-400 hover:text-white cursor-pointer"
              >
                Cancel
              </Button>
              <Button
                disabled={!editPostTitle.trim() || !editPostContent.trim()}
                onClick={() => {
                  onEditPost?.(post.id, { title: editPostTitle, content: editPostContent });
                  setEditingPost(false);
                }}
                className="bg-[#ff6934] hover:bg-[#ff7a47] text-white cursor-pointer"
              >
                Save Changes
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={!!deleteTarget} onOpenChange={(open) => { if (!open) setDeleteTarget(null); }}>
        <DialogContent className="bg-[#2c3138] border-gray-700 text-white">
          <DialogHeader>
            <DialogTitle className="text-white">Delete {deleteTarget?.type === 'post' ? 'Post' : 'Comment'}</DialogTitle>
            <DialogDescription className="text-gray-400">
              Are you sure you want to delete this {deleteTarget?.type}? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <div className="flex gap-2 justify-end">
            <Button
              variant="ghost"
              onClick={() => setDeleteTarget(null)}
              className="text-gray-400 hover:text-white cursor-pointer"
            >
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