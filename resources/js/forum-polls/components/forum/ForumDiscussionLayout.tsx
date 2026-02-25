import { useState } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { 
  Search, 
  Heart, 
  MessageCircle, 
  Eye, 
  TrendingUp,
  Plus,
  Hash,
  ArrowRight,
  CheckCircle2,
  HelpCircle
} from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { ForumCategory } from "./ForumCategoryManager";
import { ForumPost } from "./EnhancedForumPost";

interface Tag {
  id: string;
  name: "Stressed" | "Academic" | "Rested" | "FinaExam";
  color: string;
  emoji?: string;
}

interface TopItem {
  id: string;
  title: string;
  category: string;
  votes: number;
}

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
  previewImage?: string;
  excerpt?: string;
  type: 'academic-qa' | 'general-discussion';
  hasAcceptedAnswer?: boolean;
  answerCount?: number;
}

interface ForumDiscussionLayoutProps {
  onCreatePost: () => void;
  onPostClick: (postId: string) => void;
  onTagClick: (tagId: string) => void;
  onPollClick?: (pollId: string) => void;
  onPetitionClick?: (petitionId: string) => void;
}

const popularTags: Tag[] = [
  { id: "stressed", name: "Stressed", color: "bg-orange-500", emoji: "😰" },
  { id: "academic", name: "Academic", color: "bg-blue-500", emoji: "📚" },
  { id: "rested", name: "Rested", color: "bg-green-500", emoji: "😌" },
  { id: "finaexam", name: "FinaExam", color: "bg-amber-600", emoji: "📝" },
];

const mockDiscussions: DiscussionPost[] = [
  {
    id: "post1",
    title: "How do I optimize this sorting algorithm?",
    tags: ["algorithms", "sorting"],
    author: "CodeNewbie",
    authorAvatar: "CN",
    timeAgo: "2 hours ago",
    views: 234,
    likes: 12,
    comments: 0,
    answerCount: 3,
    hasAcceptedAnswer: true,
    isLiked: false,
    type: 'academic-qa'
  },
  {
    id: "post_test_accept",
    title: "What is the difference between async/await and Promises in JavaScript?",
    tags: ["javascript", "async"],
    author: "CommunityHelper",
    authorAvatar: "CH",
    timeAgo: "1 hour ago",
    views: 156,
    likes: 8,
    comments: 0,
    answerCount: 3,
    hasAcceptedAnswer: false,
    isLiked: false,
    type: 'academic-qa'
  },
  {
    id: "1",
    title: "Music Festival Funding",
    tags: ["community"],
    author: "David Tan",
    authorAvatar: "DT",
    timeAgo: "2 hours ago",
    views: 23,
    likes: 11,
    comments: 5,
    isLiked: false,
    type: 'general-discussion'
  },
  {
    id: "2",
    title: "Emotional workshop by Student Counselling",
    tags: ["event", "wellbeing"],
    author: "Nicole Lee",
    authorAvatar: "NL",
    timeAgo: "5 hours ago",
    views: 10,
    likes: 23,
    comments: 11,
    isLiked: true,
    type: 'general-discussion'
  },
  {
    id: "post2",
    title: "Understanding recursion in JavaScript",
    tags: ["javascript", "recursion"],
    author: "Alex Chen",
    authorAvatar: "AC",
    timeAgo: "1 day ago",
    views: 156,
    likes: 24,
    comments: 0,
    answerCount: 5,
    hasAcceptedAnswer: false,
    isLiked: false,
    type: 'academic-qa'
  }
];

const topPolls: TopItem[] = [
  { id: "1", title: "What time should library hours be extended to", category: "Facilities", votes: 156 },
  { id: "2", title: "Best day of Java Workshop", category: "Academic Events", votes: 89 },
  { id: "3", title: "What is the best canteen among the campus", category: "Campus Life", votes: 234 },
];

const topPetitions: TopItem[] = [
  { id: "1", title: "Improve Mental Health Support Services on Campus", category: "Wellness", votes: 847 },
  { id: "2", title: "Install More Charging Stations Across Campus", category: "Facilities", votes: 542 },
  { id: "3", title: "Increase More Shuttle Buses During Peak Hours", category: "Transportation", votes: 423 },
];

export function ForumDiscussionLayout({ 
  onCreatePost, 
  onPostClick, 
  onTagClick,
  onPollClick,
  onPetitionClick
}: ForumDiscussionLayoutProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [discussions, setDiscussions] = useState<DiscussionPost[]>(mockDiscussions);

  const handleLike = (postId: string) => {
    setDiscussions(prev => prev.map(post => 
      post.id === postId 
        ? { ...post, isLiked: !post.isLiked, likes: post.isLiked ? post.likes - 1 : post.likes + 1 }
        : post
    ));
  };

  return (
    <div className="min-h-screen bg-[#e8e8ea]">
      <div className="max-w-7xl mx-auto px-6 py-6">
        <div className="grid grid-cols-[1fr_300px] gap-6">
          {/* Main Content */}
          <div className="space-y-4">
            {/* Search Bar */}
            <div className="relative">
              <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
              <Input
                placeholder="Search"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="bg-white border-gray-200 h-12 rounded-lg pl-[42px] pr-[10px] py-[3px] w-full"
              />
            </div>

            {/* Create Post Section */}
            <div className="bg-[#2c3138] rounded-xl p-5">
              <div className="flex items-center gap-4">
                <Avatar className="h-10 w-10">
                  <AvatarFallback className="bg-[#6b7280] text-white text-sm">
                    You
                  </AvatarFallback>
                </Avatar>
                <div 
                  className="flex-1 bg-[#3a4149] rounded-lg px-4 py-3 cursor-pointer hover:bg-[#424a52] transition-colors"
                  onClick={onCreatePost}
                >
                  <p className="text-gray-400 text-sm">
                    Let's share what going on your mind...
                  </p>
                </div>
                <Button 
                  onClick={onCreatePost}
                  className="bg-[#ff6934] hover:bg-[#ff7a47] text-white px-6 rounded-lg"
                >
                  Create Post
                </Button>
              </div>
            </div>

            {/* Discussion Posts */}
            <div className="space-y-4">
              {discussions.map(post => (
                <div 
                  key={post.id}
                  className="bg-[#2c3138] rounded-xl p-5 hover:bg-[#333a42] transition-colors cursor-pointer"
                  onClick={() => onPostClick(post.id)}
                >
                  {/* Post Header with Type Indicator */}
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        {post.type === 'academic-qa' ? (
                          <>
                            <HelpCircle className="h-4 w-4 text-blue-400" />
                            <span className="text-blue-400 text-xs font-medium">Question</span>
                          </>
                        ) : (
                          <>
                            <MessageCircle className="h-4 w-4 text-gray-400" />
                            <span className="text-gray-400 text-xs font-medium">Discussion</span>
                          </>
                        )}
                        {post.type === 'academic-qa' && post.hasAcceptedAnswer && (
                          <div className="flex items-center gap-1 text-green-500">
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            <span className="text-xs">Answered</span>
                          </div>
                        )}
                      </div>
                      <h3 className="text-white text-lg">{post.title}</h3>
                    </div>
                  </div>
                  
                  <div className="flex gap-2 mb-4">
                    {post.tags.map(tag => (
                      <Badge 
                        key={tag}
                        variant="secondary"
                        className="bg-[#3a4149] text-gray-300 text-xs px-3 py-1 rounded-full hover:bg-[#424a52]"
                      >
                        {tag}
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
                        <span>{post.views}</span>
                      </div>
                      <div className="flex items-center gap-1.5">
                        <Heart 
                          className={`h-4 w-4 ${post.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`}
                          onClick={(e) => {
                            e.stopPropagation();
                            handleLike(post.id);
                          }}
                        />
                        <span>{post.likes}</span>
                      </div>
                      <div className="flex items-center gap-1.5">
                        {post.type === 'academic-qa' ? (
                          <>
                            <MessageCircle className="h-4 w-4" />
                            <span>{post.answerCount} {post.answerCount === 1 ? 'answer' : 'answers'}</span>
                          </>
                        ) : (
                          <>
                            <MessageCircle className="h-4 w-4" />
                            <span>{post.comments} {post.comments === 1 ? 'comment' : 'comments'}</span>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Right Sidebar */}
          <div className="space-y-6">
            {/* Top Campus Voices (Polls) */}
            <div className="bg-[#2c3138] rounded-xl p-5">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-white font-medium">Top Campus Voices (Polls)</h3>
                <ArrowRight className="h-4 w-4 text-gray-400" />
              </div>
              <div className="space-y-3">
                {topPolls.map((item, index) => (
                  <div 
                    key={item.id}
                    onClick={() => onPollClick && onPollClick('p' + item.id)}
                    className="flex gap-3 p-3 rounded-lg hover:bg-[#363d45] transition-colors cursor-pointer"
                  >
                    <div className="w-12 h-12 bg-[#3a4149] rounded-lg flex items-center justify-center shrink-0">
                      <span className="text-white text-sm">📊</span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-white text-sm mb-1 line-clamp-2">{item.title}</p>
                      <p className="text-gray-400 text-xs">{item.category}</p>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="text-[#ff6934] text-sm font-medium">{item.votes}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Top Campus Concerns (Petitions) */}
            <div className="bg-[#2c3138] rounded-xl p-5">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-white font-medium">Top Campus Concerns (Petitions)</h3>
                <ArrowRight className="h-4 w-4 text-gray-400" />
              </div>
              <div className="space-y-3">
                {topPetitions.map((item, index) => (
                  <div 
                    key={item.id}
                    onClick={() => onPetitionClick && onPetitionClick('pet' + item.id)}
                    className="flex gap-3 p-3 rounded-lg hover:bg-[#363d45] transition-colors cursor-pointer"
                  >
                    <div className="w-12 h-12 bg-[#3a4149] rounded-lg flex items-center justify-center shrink-0">
                      <span className="text-white text-sm">📝</span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-white text-sm mb-1 line-clamp-2">{item.title}</p>
                      <p className="text-gray-400 text-xs">{item.category}</p>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="text-[#ff6934] text-sm font-medium">{item.votes}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Popular Tags */}
            <div className="bg-[#2c3138] rounded-xl p-5">
              <h3 className="text-white mb-4 font-medium">Popular Tags</h3>
              <div className="space-y-2">
                {popularTags.map(tag => (
                  <button
                    key={tag.id}
                    onClick={() => onTagClick(tag.id)}
                    className="w-full flex items-center gap-3 hover:bg-[#363d45] p-2.5 rounded-lg transition-colors group"
                  >
                    <div className={`${tag.color} w-10 h-10 rounded-lg flex items-center justify-center text-xl`}>
                      {tag.emoji}
                    </div>
                    <div className="flex flex-col items-start text-left flex-1">
                      <span className="text-white text-sm">#{tag.name}</span>
                    </div>
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}