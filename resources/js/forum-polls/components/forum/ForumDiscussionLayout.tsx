import { useState, useEffect, useCallback, useRef } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Badge } from "../ui/badge";
import { 
  Search, 
  Heart, 
  MessageCircle, 
  Eye, 
  ArrowRight,
  CheckCircle2,
  HelpCircle,
  Paperclip,
  Loader2,
  ChevronLeft,
  ChevronRight,
  SlidersHorizontal,
  Clock,
  TrendingUp,
  Settings,
} from "lucide-react";
import { ForumPost } from "./EnhancedForumPost";
import * as forumApi from "../../api/forumApi";

interface Tag {
  id: string;
  name: string;
  postCount: number;
}

interface TopItem {
  id: string;
  title: string;
  category: string;
  votes: number;
}

interface ForumDiscussionLayoutProps {
  onCreatePost: () => void;
  onPostClick: (postId: string) => void;
  onTagClick: (tagId: string) => void;
  onViewAllTags: () => void;
  onPollClick?: (pollId: string) => void;
  onPetitionClick?: (petitionId: string) => void;
  onViewAllPolls?: () => void;
  onViewAllPetitions?: () => void;
  isAdmin?: boolean;
  isMuted?: boolean;
  mutedUntilDate?: string | null;
  onManageCategories?: () => void;
}

// Tag colors for cycling through
const tagColors = [
  "bg-blue-500",
  "bg-orange-500",
  "bg-green-500",
  "bg-amber-600",
];



export function ForumDiscussionLayout({ 
  onCreatePost, 
  onPostClick, 
  onTagClick,
  onViewAllTags,
  onPollClick,
  onPetitionClick,
  onViewAllPolls,
  onViewAllPetitions,
  isAdmin,
  isMuted,
  mutedUntilDate,
  onManageCategories,
}: ForumDiscussionLayoutProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [discussions, setDiscussions] = useState<ForumPost[]>([]);
  const [loading, setLoading] = useState(true);
  const [popularTags, setPopularTags] = useState<Tag[]>([]);
  const [allTags, setAllTags] = useState<Tag[]>([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [activeSearch, setActiveSearch] = useState("");
  const [hashtagSuggestions, setHashtagSuggestions] = useState<Tag[]>([]);
  const [showHashtagDropdown, setShowHashtagDropdown] = useState(false);
  const [activeSort, setActiveSort] = useState<string>('newest');
  const [topPolls, setTopPolls] = useState<TopItem[]>([]);
  const [topPetitions, setTopPetitions] = useState<TopItem[]>([]);
  const searchRef = useRef<HTMLDivElement>(null);
  const perPage = 15;
  const [pageReady, setPageReady] = useState(false);

  // Fetch posts (with search & pagination)
  const loadPosts = useCallback(async (search?: string, page: number = 1, sort: string = 'newest') => {
    setLoading(true);
    try {
      const params: forumApi.FetchPostsParams = { per_page: perPage, page, sort };
      if (search && search.trim()) {
        params.search = search.trim();
      }
      const res = await forumApi.fetchPosts(params);
      setDiscussions(res.data);
      if (res.meta) {
        setCurrentPage(res.meta.currentPage);
        setLastPage(res.meta.lastPage);
        setTotal(res.meta.total);
      }
    } catch (err) {
      setDiscussions([]);
    } finally {
      setLoading(false);
    }
  }, []);

  // Initial load — all API calls in parallel, show page only when all are done
  useEffect(() => {
    let cancelled = false;
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';
    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };

    const fetchPosts = loadPosts();

    const fetchTags = forumApi.fetchHashtags()
      .then((tags: any[]) => {
        if (cancelled) return;
        const sorted = tags
          .sort((a: any, b: any) => (b.postCount || 0) - (a.postCount || 0))
          .map((t: any) => ({ id: t.id, name: t.name, postCount: t.postCount || 0 }));
        setAllTags(sorted);
        setPopularTags(sorted.slice(0, 4));
      })
      .catch(() => { if (!cancelled) { setPopularTags([]); setAllTags([]); } });

    const fetchSidebar = Promise.allSettled([
      fetch('/api/poll-petition/polls?per_page=3&sort=popularity', { headers }).then(r => r.ok ? r.json() : null),
      fetch('/api/poll-petition/petitions?per_page=3', { headers }).then(r => r.ok ? r.json() : null),
    ]).then(([pollsRes, petitionsRes]) => {
      if (cancelled) return;
      if (pollsRes.status === 'fulfilled' && pollsRes.value) {
        const rawPolls = Array.isArray(pollsRes.value)
          ? pollsRes.value
          : (pollsRes.value.polls || []);
        setTopPolls(rawPolls.slice(0, 3).map((p: any) => ({
          id: String(p.id), title: p.title, category: p.category, votes: p.totalVotes ?? p.total_votes ?? 0,
        })));
      }
      if (petitionsRes.status === 'fulfilled' && petitionsRes.value) {
        const raw = Array.isArray(petitionsRes.value)
          ? petitionsRes.value
          : (petitionsRes.value.petitions || []);
        setTopPetitions(raw.slice(0, 3).map((p: any) => ({
          id: String(p.id), title: p.title, category: p.category ?? 'General', votes: p.supportCount ?? p.support_count ?? 0,
        })));
      }
    });

    Promise.all([fetchPosts, fetchTags, fetchSidebar]).finally(() => {
      if (!cancelled) setPageReady(true);
    });

    return () => { cancelled = true; };
  }, [loadPosts]);

  // Fetch hashtag suggestions when user types # in search
  useEffect(() => {
    const trimmed = searchTerm.trim();
    if (trimmed.startsWith('#') && trimmed.length > 1) {
      const query = trimmed.substring(1);
      forumApi.searchHashtags(query)
        .then((tags: any[]) => {
          setHashtagSuggestions(tags.map((t: any) => ({ id: t.id, name: t.name, postCount: t.postCount || 0 })));
          setShowHashtagDropdown(true);
        })
        .catch(() => setHashtagSuggestions([]));
    } else {
      setHashtagSuggestions([]);
      setShowHashtagDropdown(false);
    }
  }, [searchTerm]);

  // Close hashtag dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(e.target as Node)) {
        setShowHashtagDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Trigger search (called on Enter key or Search button click)
  const handleSearch = useCallback(() => {
    const trimmed = searchTerm.trim();
    // If search starts with #, treat as hashtag search
    if (trimmed.startsWith('#') && trimmed.length > 1) {
      const tagName = trimmed.substring(1);
      setShowHashtagDropdown(false);
      onTagClick(tagName);
      return;
    }
    setActiveSearch(searchTerm);
    setCurrentPage(1);
    loadPosts(searchTerm, 1, activeSort);
  }, [searchTerm, loadPosts, onTagClick, activeSort]);

  // Handle sort change
  const handleSortChange = useCallback((sort: string) => {
    setActiveSort(sort);
    setCurrentPage(1);
    loadPosts(activeSearch, 1, sort);
  }, [activeSearch, loadPosts]);

  // Handle page change
  const handlePageChange = useCallback((page: number) => {
    setCurrentPage(page);
    loadPosts(activeSearch, page, activeSort);
    // Scroll to top of post list
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, [activeSearch, activeSort, loadPosts]);

  const handleLike = async (postId: string) => {
    try {
      const result = await forumApi.togglePostLike(postId);
      setDiscussions(prev => prev.map(post => 
        post.id === postId 
          ? { ...post, isLiked: result.isLiked, likes: result.likesCount }
          : post
      ));
    } catch (err) {
      // ignore
    }
  };

  return (
    <div className="min-h-screen bg-[#e8e8ea]">
      {!pageReady && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-[#e8e8ea]">
          <div className="text-center">
            <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
            <p className="text-gray-500">Loading community...</p>
          </div>
        </div>
      )}
      <div className="max-w-7xl mx-auto px-6 py-6">
        {/* Manage Categories Button (Admin only) — above grid, right-aligned */}
        {isAdmin && onManageCategories && (
          <div className="flex justify-end mb-4">
            <button
              onClick={onManageCategories}
              className="flex items-center gap-2 bg-white border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50 transition-colors cursor-pointer shadow-sm text-gray-700 font-medium text-sm"
            >
              <Settings className="h-4 w-4 text-gray-600" />
              Manage Categories
            </button>
          </div>
        )}
        <div className="grid grid-cols-[1fr_300px] gap-6">
          {/* Main Content */}
          <div className="space-y-4">
            {/* Search Bar */}
            <div className="relative" ref={searchRef}>
              <div className="flex gap-2">
                <div className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4 pointer-events-none" />
                  <Input
                    placeholder="Search posts or type # to search hashtags..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') {
                        handleSearch();
                      }
                      if (e.key === 'Escape') {
                        setShowHashtagDropdown(false);
                      }
                    }}
                    onFocus={() => {
                      if (searchTerm.trim().startsWith('#') && hashtagSuggestions.length > 0) {
                        setShowHashtagDropdown(true);
                      }
                    }}
                    className="bg-white border-gray-200 h-12 rounded-lg pl-10 pr-4 py-0.75 w-full"
                  />
                </div>
                <Button
                  onClick={handleSearch}
                  className="bg-[#ff6934] hover:bg-[#ff7a47] text-white h-12 px-5 rounded-lg cursor-pointer"
                >
                  <Search className="h-4 w-4" />
                </Button>
              </div>
              {/* Hashtag Suggestions Dropdown - aligned to full search bar width */}
              {showHashtagDropdown && hashtagSuggestions.length > 0 && (
                <div className="absolute top-full left-0 right-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-64 overflow-y-auto">
                  {hashtagSuggestions.map((tag) => (
                    <button
                      key={tag.id}
                      onClick={() => {
                        setSearchTerm(`#${tag.name}`);
                        setShowHashtagDropdown(false);
                        onTagClick(tag.name);
                      }}
                      className="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer text-left"
                    >
                      <div className="bg-[#ff6934] w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                        {hashtagSuggestions.indexOf(tag) + 1}
                      </div>
                      <div className="flex-1">
                        <span className="text-gray-800 text-sm font-medium">#{tag.name}</span>
                        <span className="text-gray-400 text-xs ml-2">{tag.postCount} {tag.postCount === 1 ? 'post' : 'posts'}</span>
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Filter Buttons */}
            <div className="flex items-center gap-2">
              <SlidersHorizontal className="h-4 w-4 text-gray-500" />
              {[
                { key: 'newest', label: 'Newest', icon: Clock },
                { key: 'popular', label: 'Most Popular', icon: TrendingUp },
                { key: 'unanswered', label: 'Unanswered', icon: HelpCircle },
              ].map(({ key, label, icon: Icon }) => (
                <Button
                  key={key}
                  variant="ghost"
                  size="sm"
                  onClick={() => handleSortChange(key)}
                  className={`h-9 px-4 rounded-lg text-sm cursor-pointer transition-colors ${
                    activeSort === key
                      ? 'bg-[#ff6934] text-white hover:bg-[#ff7a47]'
                      : 'bg-[#2c3138] text-gray-300 hover:bg-[#363d45] hover:text-white'
                  }`}
                >
                  <Icon className="h-3.5 w-3.5 mr-1.5" />
                  {label}
                </Button>
              ))}
            </div>

            {/* Create Post Section */}
            {isMuted ? (
              <div className="bg-red-900/30 border border-red-500/50 rounded-xl p-5">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                  </div>
                  <div>
                    <p className="text-red-300 font-semibold text-sm">Your account is muted</p>
                    <p className="text-red-400/80 text-xs">You cannot create posts or comments until {mutedUntilDate}.</p>
                  </div>
                </div>
              </div>
            ) : (
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
                    className="bg-[#ff6934] hover:bg-[#ff7a47] text-white px-6 rounded-l cursor-pointer"
                  >
                    Create Post
                  </Button>
                </div>
              </div>
            )}

            {/* Discussion Posts */}
            <div className="space-y-4">
              {loading ? (
                <div className="flex flex-col items-center justify-center py-16">
                  <Loader2 className="h-8 w-8 animate-spin text-[#ff6934] mb-3" />
                  <p className="text-gray-400 text-sm">Loading posts...</p>
                </div>
              ) : discussions.length === 0 ? (
                <div className="text-center py-16">
                  <MessageCircle className="h-10 w-10 text-gray-500 mx-auto mb-3" />
                  <p className="text-gray-400">{searchTerm ? 'No posts found matching your search.' : 'No posts yet. Be the first to create one!'}</p>
                </div>
              ) : discussions.map(post => (
                <div 
                  key={post.id}
                  className="bg-[#2c3138] rounded-xl p-5 hover:bg-[#333a42] transition-colors cursor-pointer"
                  onClick={() => onPostClick(post.id)}
                >
                  {/* Post Header with Type Indicator */}
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        {post.category.type === 'academic-qa' ? (
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
                        {post.category.type === 'academic-qa' && post.hasAcceptedAnswer && (
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
                    {post.hashtags.map(tag => (
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
                          {post.author.nickname.substring(0, 2).toUpperCase()}
                        </AvatarFallback>
                      </Avatar>
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="text-white text-sm">{post.author.nickname}</span>
                          <span className="text-gray-500 text-xs">•</span>
                          <span className="text-gray-400 text-xs">{post.createdAt}</span>
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center gap-6 text-sm text-gray-400">
                      {/* <div className="flex items-center gap-1.5">
                        <Eye className="h-4 w-4" />
                        <span>{post.views}</span>
                      </div> */}
                      <div className="flex items-center gap-1.5">
                        <Heart 
                          className={`h-4 w-4 cursor-pointer ${post.isLiked ? 'fill-[#ff6934] text-[#ff6934]' : ''}`}
                          onClick={(e) => {
                            e.stopPropagation();
                            handleLike(post.id);
                          }}
                        />
                        <span>{post.likes}</span>
                      </div>
                      <div className="flex items-center gap-1.5">
                        {post.category.type === 'academic-qa' ? (
                          <>
                            <MessageCircle className="h-4 w-4" />
                            <span>{post.answerCount} {post.answerCount === 1 ? 'answer' : 'answers'}</span>
                          </>
                        ) : (
                          <>
                            <MessageCircle className="h-4 w-4" />
                            <span>{post.commentCount} {post.commentCount === 1 ? 'comment' : 'comments'}</span>
                          </>
                        )}
                      </div>
                      {post.attachments && post.attachments.length > 0 && (
                        <div className="flex items-center gap-1.5">
                          <Paperclip className="h-4 w-4" />
                          <span>{post.attachments.length} {post.attachments.length === 1 ? 'file' : 'files'}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Pagination Controls */}
            {!loading && (
              <div className="flex items-center justify-between bg-[#2c3138] rounded-xl px-5 py-3">
                <p className="text-gray-400 text-sm">
                  Page {currentPage} of {lastPage} ({total} {total === 1 ? 'post' : 'posts'})
                </p>
                <div className="flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage <= 1}
                    className="text-gray-400 hover:text-white disabled:opacity-30 cursor-pointer"
                  >
                    <ChevronLeft className="h-4 w-4" />
                  </Button>
                  {Array.from({ length: lastPage }, (_, i) => i + 1)
                    .filter(page => {
                      // Show first, last, current, and adjacent pages
                      if (page === 1 || page === lastPage) return true;
                      if (Math.abs(page - currentPage) <= 1) return true;
                      return false;
                    })
                    .reduce<(number | string)[]>((acc, page, idx, arr) => {
                      if (idx > 0 && page - (arr[idx - 1] as number) > 1) {
                        acc.push('...');
                      }
                      acc.push(page);
                      return acc;
                    }, [])
                    .map((item, idx) =>
                      typeof item === 'string' ? (
                        <span key={`ellipsis-${idx}`} className="text-gray-500 px-2">...</span>
                      ) : (
                        <Button
                          key={item}
                          variant="ghost"
                          size="sm"
                          onClick={() => handlePageChange(item)}
                          className={`min-w-8 h-8 cursor-pointer ${
                            item === currentPage
                              ? 'bg-[#ff6934] text-white hover:bg-[#ff7a47]'
                              : 'text-gray-400 hover:text-white hover:bg-[#3a4149]'
                          }`}
                        >
                          {item}
                        </Button>
                      )
                    )}
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage >= lastPage}
                    className="text-gray-400 hover:text-white disabled:opacity-30 cursor-pointer"
                  >
                    <ChevronRight className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            )}
          </div>

          {/* Right Sidebar */}
          <div className="space-y-6">
            {/* Popular Tags */}
            <div className="bg-[#2c3138] rounded-xl p-5">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-white font-medium">Popular Tags</h3>
                {allTags.length > 4 && (
                  <button
                    onClick={onViewAllTags}
                    className="text-[#ff6934] text-xs font-medium hover:text-[#ff7a47] transition-colors cursor-pointer"
                  >
                    View All
                  </button>
                )}
              </div>
              {popularTags.length === 0 ? (
                <p className="text-gray-500 text-sm">No tags yet</p>
              ) : (
                <div className="space-y-2">
                  {popularTags.map((tag, index) => (
                    <button
                      key={tag.id}
                      onClick={() => onTagClick(tag.name)}
                      className="w-full flex items-center gap-3 hover:bg-[#363d45] p-2.5 rounded-lg transition-colors group cursor-pointer"
                    >
                      <div className={` cursor-pointer ${tagColors[index % tagColors.length]} w-10 h-10 rounded-lg flex items-center justify-center text-white text-sm font-bold`}>
                        {index + 1}
                      </div>
                      <div className="flex flex-col items-start text-left flex-1">
                        <span className="text-white text-sm">#{tag.name}</span>
                        <span className="text-gray-500 text-xs">{tag.postCount} {tag.postCount === 1 ? 'post' : 'posts'}</span>
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Top Campus Voices (Polls) */}
            <div className="bg-[#2c3138] rounded-xl p-5">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-white font-medium">Top Campus Voices (Polls)</h3>
                <button onClick={() => onViewAllPolls && onViewAllPolls()} className="cursor-pointer hover:text-white transition-colors">
                  <ArrowRight className="h-4 w-4 text-gray-400 hover:text-white" />
                </button>
              </div>
              <div className="space-y-3">
                {topPolls.map((item, index) => (
                  <div 
                    key={item.id}
                    onClick={() => onPollClick && onPollClick(item.id)}
                    className="flex gap-3 p-3 rounded-lg hover:bg-[#363d45] transition-colors cursor-pointer"
                  >
                    <div className="w-12 h-12 bg-[#ff6934] rounded-lg flex items-center justify-center shrink-0 text-white font-bold text-lg">
                      {index + 1}
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
                <button onClick={() => onViewAllPetitions && onViewAllPetitions()} className="cursor-pointer hover:text-white transition-colors">
                  <ArrowRight className="h-4 w-4 text-gray-400 hover:text-white" />
                </button>
              </div>
              <div className="space-y-3">
                {topPetitions.map((item, index) => (
                  <div 
                    key={item.id}
                    onClick={() => onPetitionClick && onPetitionClick(item.id)}
                    className="flex gap-3 p-3 rounded-lg hover:bg-[#363d45] transition-colors cursor-pointer"
                  >
                    <div className="w-12 h-12 bg-[#ff6934] rounded-lg flex items-center justify-center shrink-0 text-white font-bold text-lg">
                      {index + 1}
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
          </div>
        </div>
      </div>
    </div>
  );
}