import { useState } from "react";
import { Button } from "../ui/button";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { 
  ArrowLeft, 
  Heart, 
  MessageCircle, 
  Eye,
  Hash
} from "lucide-react";

interface DiscussionPost {
  id: string;
  title: string;
  tags: string[];
  author: string;
  authorAvatar: string;
  timeAgo: string;
  views: number;
  likes: number;
  comments: number;
  isLiked: boolean;
}

interface ForumTagSearchViewProps {
  tag: string;
  tagColor: string;
  tagEmoji?: string;
  posts: DiscussionPost[];
  onBack: () => void;
  onPostClick: (postId: string) => void;
  onLike: (postId: string) => void;
}

export function ForumTagSearchView({
  tag,
  tagColor,
  tagEmoji,
  posts,
  onBack,
  onPostClick,
  onLike
}: ForumTagSearchViewProps) {
  return (
    <div className="min-h-screen bg-[#e8e8ea]">
      <div className="max-w-5xl mx-auto px-6 py-6">
        {/* Back Button */}
        <Button
          variant="ghost"
          onClick={onBack}
          className="mb-4 text-gray-700 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back to Forum
        </Button>

        {/* Tag Header */}
        <div className="bg-[#2c3138] rounded-xl p-6 mb-6">
          <div className="flex items-center gap-4">
            <div className={`${tagColor} w-16 h-16 rounded-xl flex items-center justify-center text-3xl`}>
              {tagEmoji || <Hash className="h-8 w-8 text-white" />}
            </div>
            <div>
              <h1 className="text-white text-3xl font-semibold">#{tag}</h1>
              <p className="text-gray-400 mt-1">{posts.length} posts found</p>
            </div>
          </div>
        </div>

        {/* Posts List */}
        <div className="space-y-4">
          {posts.length === 0 ? (
            <div className="bg-[#2c3138] rounded-xl p-12 text-center">
              <Hash className="h-16 w-16 text-gray-600 mx-auto mb-4" />
              <h3 className="text-white text-xl mb-2">No posts found</h3>
              <p className="text-gray-400">There are no posts with this tag yet.</p>
            </div>
          ) : (
            posts.map(post => (
              <div 
                key={post.id}
                className="bg-[#2c3138] rounded-xl p-5 hover:bg-[#333a42] transition-colors cursor-pointer"
                onClick={() => onPostClick(post.id)}
              >
                <h3 className="text-white text-lg mb-3">{post.title}</h3>
                
                <div className="flex gap-2 mb-4">
                  {post.tags.map(postTag => (
                    <Badge 
                      key={postTag}
                      variant="secondary"
                      className={`${postTag.toLowerCase() === tag.toLowerCase() ? 'bg-[#ff6934] text-white' : 'bg-[#3a4149] text-gray-300'} text-xs px-3 py-1 rounded-full`}
                    >
                      {postTag}
                    </Badge>
                  ))}
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                      <AvatarFallback className="bg-[#6b7280] text-white text-xs">
                        {post.authorAvatar}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-white text-sm">{post.author}</span>
                        <span className="text-gray-500 text-xs">•</span>
                        <span className="text-gray-400 text-xs">{post.timeAgo}</span>
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-6 text-sm text-gray-400">
                    <div className="flex items-center gap-1.5">
                      <Eye className="h-4 w-4" />
                      <span>{post.views} Views</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <Heart 
                        className={`h-4 w-4 ${post.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`}
                        onClick={(e) => {
                          e.stopPropagation();
                          onLike(post.id);
                        }}
                      />
                      <span>{post.likes} Likes</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <MessageCircle className="h-4 w-4" />
                      <span>{post.comments} comments</span>
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
