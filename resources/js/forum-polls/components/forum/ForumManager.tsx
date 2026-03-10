import { useState, useEffect } from "react";
import { Button } from "../ui/button";
import { Loader2 } from "lucide-react";

import { ForumDiscussionLayout } from "./ForumDiscussionLayout";
import { ForumCategoryManager, ForumCategory } from "./ForumCategoryManager";
import { CreateForumPost } from "./CreateForumPost";
import { EnhancedForumPost, ForumPost, Answer, Comment } from "./EnhancedForumPost";
import { ForumPostDetailView } from "./ForumPostDetailView";
import { ForumTagSearchView } from "./ForumTagSearchView";
import { ForumAllTagsView } from "./ForumAllTagsView";
import * as forumApi from "../../api/forumApi";

interface ForumManagerProps {
  currentUserId: string;
  currentUserNickname: string;
  isVerifiedMentor: boolean;
  isAdmin: boolean;
  isMuted?: boolean;
  mutedUntilDate?: string | null;
  onPollClick?: (pollId: string) => void;
  onPetitionClick?: (petitionId: string) => void;
  onViewAllPolls?: () => void;
  onViewAllPetitions?: () => void;
  initialPostId?: string | null;
}

export function ForumManager({ 
  currentUserId, 
  currentUserNickname,
  isVerifiedMentor,
  isAdmin,
  isMuted,
  mutedUntilDate,
  onPollClick,
  onPetitionClick,
  onViewAllPolls,
  onViewAllPetitions,
  initialPostId
}: ForumManagerProps) {
  const [view, setView] = useState<'list' | 'create' | 'view' | 'categories' | 'tagSearch' | 'allTags'>('list');
  const [categories, setCategories] = useState<ForumCategory[]>([]);
  const [posts, setPosts] = useState<ForumPost[]>([]);
  const [answers, setAnswers] = useState<{ [postId: string]: Answer[] }>({});
  const [comments, setComments] = useState<{ [postId: string]: Comment[] }>({});
  const [selectedPostId, setSelectedPostId] = useState<string | null>(null);
  const [pendingOpenPostId, setPendingOpenPostId] = useState<string | null>(null);
  const [selectedTag, setSelectedTag] = useState<{ id: string; name: string; color: string; emoji?: string } | null>(null);
  const [loading, setLoading] = useState(true);
  const [tagSearchPosts, setTagSearchPosts] = useState<ForumPost[]>([]);
  const [allHashtags, setAllHashtags] = useState<{ id: string; name: string; postCount: number }[]>([]);

  // Initialize mock comments for general discussion posts
  const [postComments, setPostComments] = useState<{
    [postId: string]: Array<{
      id: string;
      author: string;
      authorAvatar: string;
      content: string;
      timeAgo: string;
      likes: number;
      isLiked: boolean;
      isVerifiedMentor?: boolean;
      replies?: Array<{
        id: string;
        author: string;
        authorAvatar: string;
        content: string;
        timeAgo: string;
        likes: number;
        isLiked: boolean;
        isVerifiedMentor?: boolean;
      }>;
    }>;
  }>({});

  // Fetch initial data
  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        const [categoriesData, postsRes] = await Promise.all([
          forumApi.fetchCategories(),
          forumApi.fetchPosts(),
        ]);
        setCategories(categoriesData);
        setPosts(postsRes.data);
      } catch (err) {
        console.error('Failed to load forum data:', err);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, []);

  // When initialPostId changes, queue opening that post
  useEffect(() => {
    if (initialPostId) {
      setPendingOpenPostId(initialPostId);
    }
  }, [initialPostId]);

  // Once posts are loaded, open the pending post
  useEffect(() => {
    if (pendingOpenPostId && posts.length > 0) {
      setSelectedPostId(pendingOpenPostId);
      setView('view');
      setPendingOpenPostId(null);
    }
  }, [pendingOpenPostId, posts]);
  useEffect(() => {
    if (view !== 'tagSearch' || !selectedTag) return;
    setTagSearchPosts([]);
    forumApi.searchPostsByHashtag(selectedTag.name.toLowerCase())
      .then(data => setTagSearchPosts(data))
      .catch(err => console.error('Failed to load tag posts:', err));
  }, [view, selectedTag]);

  // Load all hashtags when viewing allTags
  useEffect(() => {
    if (view !== 'allTags') return;
    forumApi.fetchHashtags()
      .then((tags: any[]) => {
        setAllHashtags(tags.sort((a: any, b: any) => (b.postCount || 0) - (a.postCount || 0)).map((t: any) => ({ id: t.id, name: t.name, postCount: t.postCount || 0 })));
      })
      .catch(err => console.error('Failed to load hashtags:', err));
  }, [view]);

  // Load answers/comments when viewing a post
  useEffect(() => {
    if (!selectedPostId || view !== 'view') return;
    const post = posts.find(p => p.id === selectedPostId);
    if (!post) return;

    if (post.category.type === 'academic-qa') {
      forumApi.fetchAnswers(selectedPostId).then(data => {
        setAnswers(prev => ({ ...prev, [selectedPostId]: data }));
      }).catch(console.error);
    } else {
      forumApi.fetchComments(selectedPostId).then(data => {
        setPostComments(prev => ({ ...prev, [selectedPostId]: data }));
      }).catch(console.error);
    }
  }, [selectedPostId, view]);

  const selectedPost = posts.find(p => p.id === selectedPostId);

  // Category management
  const handleCreateCategory = async (categoryData: Omit<ForumCategory, 'id' | 'postCount'>) => {
    try {
      const newCategory = await forumApi.createCategory({
        name: categoryData.name,
        description: categoryData.description,
        type: categoryData.type,
        hashtags: categoryData.hashtags,
      });
      setCategories(prev => [...prev, newCategory]);
    } catch (err: any) {
      alert(err.message || 'Failed to create category');
    }
  };

  const handleEditCategory = async (id: string, categoryData: Partial<ForumCategory>) => {
    try {
      const updated = await forumApi.updateCategory(id, {
        name: categoryData.name,
        description: categoryData.description,
        type: categoryData.type,
        hashtags: categoryData.hashtags,
      });
      setCategories(prev => prev.map(cat => cat.id === id ? updated : cat));
    } catch (err: any) {
      alert(err.message || 'Failed to update category');
    }
  };

  const handleDeleteCategory = async (id: string) => {
    if (!confirm('Are you sure you want to delete this category? All posts in this category will be unassigned.')) return;
    try {
      await forumApi.deleteCategory(id);
      setCategories(prev => prev.filter(cat => cat.id !== id));
    } catch (err: any) {
      alert(err.message || 'Failed to delete category');
    }
  };

  // Post creation
  const handleCreatePost = async (postData: {
    title: string;
    content: string;
    categoryId: string;
    hashtags: string[];
    attachments: File[];
  }) => {
    try {
      const newPost = await forumApi.createPost({
        title: postData.title,
        content: postData.content,
        category_id: postData.categoryId,
        hashtags: postData.hashtags,
        attachments: postData.attachments,
      });
      setPosts(prev => [newPost, ...prev]);
      // Update category post count
      setCategories(prev => prev.map(cat =>
        cat.id === postData.categoryId 
          ? { ...cat, postCount: cat.postCount + 1 }
          : cat
      ));
      setView('list');
    } catch (err: any) {
      alert(err.message || 'Failed to create post');
    }
  };

  // Voting (Q&A)
  const handleVote = async (answerId: string, voteType: 'up' | 'down') => {
    if (!selectedPostId) return;
    try {
      const result = await forumApi.voteAnswer(answerId, voteType);
      setAnswers(prev => ({
        ...prev,
        [selectedPostId]: prev[selectedPostId]?.map(ans => {
          if (ans.id === answerId) {
            return {
              ...ans,
              upvotes: result.upvotes,
              downvotes: result.downvotes,
              userVote: result.userVote as 'up' | 'down' | undefined,
            };
          }
          return ans;
        }) || []
      }));
    } catch (err) {
      console.error('Vote failed:', err);
    }
  };

  // Accept answer
  const handleAcceptAnswer = async (answerId: string) => {
    if (!selectedPostId) return;
    try {
      await forumApi.acceptAnswer(answerId);
      setAnswers(prev => ({
        ...prev,
        [selectedPostId]: prev[selectedPostId]?.map(ans => ({
          ...ans,
          isAccepted: ans.id === answerId
        })) || []
      }));
      setPosts(prev => prev.map(post =>
        post.id === selectedPostId
          ? { ...post, hasAcceptedAnswer: true }
          : post
      ));
    } catch (err) {
      console.error('Accept answer failed:', err);
    }
  };

  // Reactions
  const handleReact = async (targetId: string, emoji: string) => {
    if (!selectedPostId) return;
    const post = posts.find(p => p.id === selectedPostId);
    if (!post) return;
    try {
      if (post.category.type === 'academic-qa') {
        const updated = await forumApi.reactToAnswer(targetId, emoji);
        setAnswers(prev => ({
          ...prev,
          [selectedPostId]: prev[selectedPostId]?.map(ans =>
            ans.id === targetId ? { ...ans, reactions: updated } : ans
          ) || []
        }));
      } else {
        // TODO: Implement comment reactions if supported by backend
      }
    } catch (err) {
      console.error('React failed:', err);
    }
  };

  // Reply/Comment
  const handleReply = async (targetId: string, content: string, mentions: string[]) => {
    if (!selectedPostId) return;
    const post = posts.find(p => p.id === selectedPostId);
    if (!post) return;
    try {
      if (post.category.type === 'academic-qa') {
        const newAnswer = await forumApi.createAnswer(selectedPostId, { content, mentions });
        setAnswers(prev => ({
          ...prev,
          [selectedPostId]: [...(prev[selectedPostId] || []), newAnswer]
        }));
        setPosts(prev => prev.map(p =>
          p.id === selectedPostId
            ? { ...p, answerCount: (p.answerCount || 0) + 1 }
            : p
        ));
      } else {
        const newComment = await forumApi.createComment(selectedPostId, { content });
        setPostComments(prev => ({
          ...prev,
          [selectedPostId]: [...(prev[selectedPostId] || []), newComment]
        }));
        setPosts(prev => prev.map(p =>
          p.id === selectedPostId
            ? { ...p, commentCount: p.commentCount + 1 }
            : p
        ));
      }
    } catch (err) {
      alert('Failed to submit reply/comment');
    }
  };

  // Report content
  const handleReport = async (targetId: string, reason: string) => {
    if (!selectedPostId) return;
    const post = posts.find(p => p.id === selectedPostId);
    if (!post) return;
    try {
      let type: 'post' | 'answer' | 'comment' = 'post';
      if (answers[selectedPostId]?.some(a => a.id === targetId)) {
        type = 'answer';
      } else if (postComments[selectedPostId]?.some(c => c.id === targetId) || comments[selectedPostId]?.some(c => c.id === targetId)) {
        type = 'comment';
      }
      await forumApi.reportContent({ reportable_id: targetId, reportable_type: type, reason });
      alert('Content has been reported and will be reviewed by moderators.');
    } catch (err) {
      alert('Failed to report content');
    }
  };

  // Edit post
  const handleEditPost = async (postId: string, data: { title: string; content: string }) => {
    try {
      const updated = await forumApi.updatePost(postId, data);
      setPosts(prev => prev.map(p => p.id === postId ? { ...p, title: updated.title, content: updated.content } : p));
    } catch (err: any) {
      alert(err.message || 'Failed to edit post');
    }
  };

  // Delete post
  const handleDeletePost = async (postId: string) => {
    try {
      await forumApi.deletePost(postId);
      setPosts(prev => prev.filter(p => p.id !== postId));
      setView('list');
    } catch (err: any) {
      alert(err.message || 'Failed to delete post');
    }
  };

  // Edit comment
  const handleEditComment = async (commentId: string, content: string) => {
    try {
      const updated = await forumApi.updateComment(commentId, { content });
      if (selectedPostId) {
        setPostComments(prev => ({
          ...prev,
          [selectedPostId!]: prev[selectedPostId!]?.map(c => 
            c.id === commentId ? { ...c, content: updated.content } : {
              ...c,
              replies: c.replies?.map(r => r.id === commentId ? { ...r, content: updated.content } : r)
            }
          ) || []
        }));
      }
    } catch (err: any) {
      alert(err.message || 'Failed to edit comment');
    }
  };

  // Delete comment
  const handleDeleteComment = async (commentId: string) => {
    try {
      await forumApi.deleteComment(commentId);
      if (selectedPostId) {
        setPostComments(prev => ({
          ...prev,
          [selectedPostId!]: prev[selectedPostId!]?.filter(c => c.id !== commentId).map(c => ({
            ...c,
            replies: c.replies?.filter(r => r.id !== commentId)
          })) || []
        }));
        setPosts(prev => prev.map(p =>
          p.id === selectedPostId ? { ...p, commentCount: Math.max(0, p.commentCount - 1) } : p
        ));
      }
    } catch (err: any) {
      alert(err.message || 'Failed to delete comment');
    }
  };

  // show a full-screen loader while initial data is being fetched AND there's a pending navigation
  if ((loading || pendingOpenPostId) && pendingOpenPostId) {
    return (
      <div className="flex flex-col items-center justify-center py-32 space-y-4">
        <Loader2 className="h-10 w-10 animate-spin text-[#ff6934]" />
        <p className="text-gray-500 text-sm">Loading post...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {view === 'list' && (
        <>
          <ForumDiscussionLayout
            onCreatePost={() => { if (!isMuted) setView('create'); }}
            onPostClick={(id) => {
              setSelectedPostId(id);
              setView('view');
            }}
            onTagClick={(tagName) => {
              setSelectedTag({ id: tagName, name: tagName, color: 'bg-blue-500' });
              setView('tagSearch');
            }}
            onViewAllTags={() => setView('allTags')}
            onPollClick={onPollClick}
            onPetitionClick={onPetitionClick}
            onViewAllPolls={onViewAllPolls}
            onViewAllPetitions={onViewAllPetitions}
            isAdmin={isAdmin}
            isMuted={isMuted}
            mutedUntilDate={mutedUntilDate}
            onManageCategories={() => setView('categories')}
          />
        </>
      )}

      {view === 'create' && (
        <CreateForumPost
          categories={categories}
          onCreatePost={handleCreatePost}
          onCancel={() => setView('list')}
        />
      )}

      {view === 'tagSearch' && selectedTag && (
        <ForumTagSearchView
          tag={selectedTag.name}
          tagColor={selectedTag.color}
          tagEmoji={selectedTag.emoji}
          posts={tagSearchPosts}
          onBack={() => setView('list')}
          onPostClick={(id) => {
            setSelectedPostId(id);
            setView('view');
          }}
          onLike={async (id) => {
            try {
              const result = await forumApi.togglePostLike(id);
              setTagSearchPosts(prev => prev.map(p =>
                p.id === id ? { ...p, isLiked: result.isLiked, likes: result.likesCount } : p
              ));
              setPosts(prev => prev.map(p =>
                p.id === id ? { ...p, isLiked: result.isLiked, likes: result.likesCount } : p
              ));
            } catch (err) {
              console.error('Failed to like post:', err);
            }
          }}
        />
      )}

      {view === 'view' && !selectedPost && loading && (
        <div className="flex flex-col items-center justify-center py-32 space-y-4">
          <Loader2 className="h-10 w-10 animate-spin text-[#ff6934]" />
          <p className="text-gray-500 text-sm">Loading post...</p>
        </div>
      )}

      {view === 'view' && !selectedPost && !loading && (
        <div className="mt-6 w-full rounded-lg bg-gray-100 flex flex-col items-center justify-center py-20 px-6">
          <div className="text-gray-500 text-lg font-semibold mb-2">Post Not Available</div>
          <p className="text-gray-400 text-sm mb-4">This post has been deleted or is no longer available.</p>
          <button
            onClick={() => setView('list')}
            className="px-4 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-300 cursor-pointer transition-colors"
          >
            ← Back to Forum
          </button>
        </div>
      )}

      {view === 'view' && selectedPost && (
        <>
          {selectedPost.category.type === 'academic-qa' ? (
            <div>
              <Button
                variant="ghost"
                onClick={() => setView('list')}
                className="mb-4 hover:bg-gray-300 cursor-pointer"
              >
                ← Back to Forum
              </Button>
              <EnhancedForumPost
                post={selectedPost}
                answers={answers[selectedPostId!] || []}
                comments={comments[selectedPostId!] || []}
                currentUserId={currentUserId}
                onVote={handleVote}
                onAcceptAnswer={handleAcceptAnswer}
                onReact={handleReact}
                onReply={handleReply}
                onReport={handleReport}
                onEditPost={handleEditPost}
                onDeletePost={handleDeletePost}
                onEditComment={handleEditComment}
                onDeleteComment={handleDeleteComment}
                isAuthor={selectedPost.author.id === currentUserId}
              />
            </div>
          ) : (
            <ForumPostDetailView
              post={{
                id: selectedPost.id,
                title: selectedPost.title,
                content: selectedPost.content || '',
                tags: selectedPost.hashtags,
                author: selectedPost.author.nickname,
                authorId: selectedPost.author.id,
                authorAvatar: selectedPost.author.nickname.substring(0, 2).toUpperCase(),
                timeAgo: selectedPost.createdAt,
                views: selectedPost.views,
                likes: selectedPost.likes,
                isLiked: selectedPost.isLiked,
                comments: postComments[selectedPost.id] || [],
                attachments: selectedPost.attachments,
              }}
              currentUserId={currentUserId}
              onBack={() => setView('list')}
              onLike={async () => {
                try {
                  const result = await forumApi.togglePostLike(selectedPost.id);
                  setPosts(prev => prev.map(p =>
                    p.id === selectedPost.id
                      ? { ...p, isLiked: result.isLiked, likes: result.likesCount }
                      : p
                  ));
                } catch (err) {
                  console.error('Failed to like post:', err);
                }
              }}
              onCommentLike={async (commentId) => {
                try {
                  const result = await forumApi.toggleCommentLike(commentId);
                  setPostComments(prev => ({
                    ...prev,
                    [selectedPost.id]: prev[selectedPost.id]?.map(comment => {
                      if (comment.id === commentId) {
                        return { ...comment, isLiked: result.isLiked, likes: result.likesCount };
                      }
                      if (comment.replies) {
                        return {
                          ...comment,
                          replies: comment.replies.map(reply =>
                            reply.id === commentId
                              ? { ...reply, isLiked: result.isLiked, likes: result.likesCount }
                              : reply
                          )
                        };
                      }
                      return comment;
                    }) || []
                  }));
                } catch (err) {
                  console.error('Failed to like comment:', err);
                }
              }}
              onAddComment={async (content) => {
                try {
                  const newComment = await forumApi.createComment(selectedPost.id, { content });
                  setPostComments(prev => ({
                    ...prev,
                    [selectedPost.id]: [...(prev[selectedPost.id] || []), newComment]
                  }));
                  setPosts(prev => prev.map(p =>
                    p.id === selectedPost.id
                      ? { ...p, commentCount: p.commentCount + 1 }
                      : p
                  ));
                } catch (err) {
                  console.error('Failed to add comment:', err);
                }
              }}
              onReply={async (commentId, content) => {
                try {
                  const newReply = await forumApi.createComment(selectedPost.id, { content, parent_id: commentId });
                  setPostComments(prev => ({
                    ...prev,
                    [selectedPost.id]: prev[selectedPost.id]?.map(comment =>
                      comment.id === commentId
                        ? { ...comment, replies: [...(comment.replies || []), newReply] }
                        : comment
                    ) || []
                  }));
                } catch (err) {
                  console.error('Failed to add reply:', err);
                }
              }}
              onReport={handleReport}
              onEditPost={handleEditPost}
              onDeletePost={handleDeletePost}
              onEditComment={handleEditComment}
              onDeleteComment={handleDeleteComment}
            />
          )}
        </>
      )}

      {view === 'allTags' && (
        <ForumAllTagsView
          tags={allHashtags}
          onBack={() => setView('list')}
          onTagClick={(tagName) => {
            setSelectedTag({ id: tagName, name: tagName, color: 'bg-blue-500' });
            setView('tagSearch');
          }}
        />
      )}

      {view === 'categories' && isAdmin && (
        <div>
          <Button
            variant="ghost"
            onClick={() => setView('list')}
            className="mb-4 hover:bg-gray-300 cursor-pointer"
          >
            ← Back to Forum
          </Button>
          <ForumCategoryManager
            categories={categories}
            onCreateCategory={handleCreateCategory}
            onEditCategory={handleEditCategory}
            onDeleteCategory={handleDeleteCategory}
            isAdmin={isAdmin}
          />
        </div>
      )}
    </div>
  );
}