import { useState } from "react";
import { Button } from "../ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../ui/tabs";
import { Settings, List, Plus } from "lucide-react";
import { ForumDiscussionLayout } from "./ForumDiscussionLayout";
import { ForumCategoryManager, ForumCategory } from "./ForumCategoryManager";
import { CreateForumPost } from "./CreateForumPost";
import { EnhancedForumPost, ForumPost, Answer, Comment } from "./EnhancedForumPost";
import { ForumPostDetailView } from "./ForumPostDetailView";
import { ForumTagSearchView } from "./ForumTagSearchView";

interface ForumManagerProps {
  currentUserId: string;
  currentUserNickname: string;
  isVerifiedMentor: boolean;
  isAdmin: boolean;
  onPollClick?: (pollId: string) => void;
  onPetitionClick?: (petitionId: string) => void;
}

const mockCategories: ForumCategory[] = [
  {
    id: "cat1",
    name: "Computer Science Q&A",
    description: "Ask and answer technical questions about programming, algorithms, and more",
    type: "academic-qa",
    hashtags: ["programming", "algorithms", "datastructures", "debugging"],
    postCount: 1245,
    icon: "academic"
  },
  {
    id: "cat2",
    name: "Campus Life",
    description: "General discussions about campus events, facilities, and student life",
    type: "general-discussion",
    hashtags: ["events", "facilities", "clubs", "social"],
    postCount: 892,
    icon: "discussion"
  },
  {
    id: "cat3",
    name: "Career & Internships",
    description: "Share opportunities, ask for advice, and discuss career paths",
    type: "general-discussion",
    hashtags: ["career", "internship", "jobs", "advice"],
    postCount: 567,
    icon: "discussion"
  }
];

const mockForumPosts: ForumPost[] = [
  {
    id: "post1",
    title: "How do I optimize this sorting algorithm?",
    content: "I'm working on a merge sort implementation but it's running slower than expected. The input size is around 10,000 elements. Any suggestions on how to optimize it?",
    author: {
      id: "user1",
      nickname: "CodeNewbie",
      isVerifiedMentor: false
    },
    category: {
      id: "cat1",
      name: "Computer Science Q&A",
      type: "academic-qa"
    },
    hashtags: ["algorithms", "sorting", "optimization"],
    attachments: [
      {
        id: "att1",
        name: "merge_sort.py",
        type: "text/python",
        size: "2.3 KB",
        url: "#"
      }
    ],
    createdAt: "2 hours ago",
    views: 234,
    likes: 12,
    commentCount: 0,
    answerCount: 3,
    hasAcceptedAnswer: true,
    isLiked: false
  },
  {
    id: "post_test_accept",
    title: "What is the difference between async/await and Promises in JavaScript?",
    content: "I'm confused about when to use async/await versus traditional Promise chains. Can someone explain the key differences and best practices? I've been reading the documentation but practical examples would be really helpful!",
    author: {
      id: "user_123",
      nickname: "CommunityHelper",
      isVerifiedMentor: false
    },
    category: {
      id: "cat1",
      name: "Computer Science Q&A",
      type: "academic-qa"
    },
    hashtags: ["javascript", "async", "promises"],
    attachments: [],
    createdAt: "1 hour ago",
    views: 156,
    likes: 8,
    commentCount: 0,
    answerCount: 3,
    hasAcceptedAnswer: false,
    isLiked: false
  },
  {
    id: "1",
    title: "Music Festival Funding",
    content: "Our campus community has been discussing the need for better funding allocation for student-led music festivals. We believe that cultural events like these are essential for building community spirit and showcasing student talent. What are your thoughts on how we can secure better funding and support for these events?",
    author: {
      id: "user2",
      nickname: "David Tan",
      isVerifiedMentor: false
    },
    category: {
      id: "cat2",
      name: "Campus Life",
      type: "general-discussion"
    },
    hashtags: ["community", "events", "funding"],
    attachments: [],
    createdAt: "2 hours ago",
    views: 23,
    likes: 11,
    commentCount: 5,
    isLiked: false
  },
  {
    id: "2",
    title: "Emotional workshop by Student Counselling",
    content: "The Student Counselling Center is organizing a series of emotional wellness workshops next month. These sessions will cover stress management, mindfulness techniques, and building emotional resilience. I highly recommend attending if you're feeling overwhelmed with academic pressures. Registration is now open!",
    author: {
      id: "user3",
      nickname: "Nicole Lee",
      isVerifiedMentor: true
    },
    category: {
      id: "cat2",
      name: "Campus Life",
      type: "general-discussion"
    },
    hashtags: ["event", "wellbeing", "mental-health"],
    attachments: [],
    createdAt: "5 hours ago",
    views: 10,
    likes: 23,
    commentCount: 11,
    isLiked: true
  }
];

const mockAnswers: Answer[] = [
  {
    id: "ans1",
    postId: "post1",
    content: "Your merge sort looks good, but you can optimize the merging step by preallocating the temporary array once instead of creating it in each merge call. This reduces memory allocation overhead significantly.",
    author: {
      id: "user2",
      nickname: "AlgoExpert",
      isVerifiedMentor: true
    },
    upvotes: 15,
    downvotes: 1,
    isAccepted: true,
    userVote: undefined,
    reactions: [
      { emoji: "👍", count: 8, userReacted: false },
      { emoji: "❤️", count: 3, userReacted: false }
    ],
    createdAt: "1 hour ago",
    mentions: ["CodeNewbie"]
  },
  {
    id: "ans2",
    postId: "post1",
    content: "Also consider using Timsort which is a hybrid sorting algorithm. Python's built-in sort actually uses Timsort, which performs better on real-world data.",
    author: {
      id: "user3",
      nickname: "PythonPro",
      isVerifiedMentor: false
    },
    upvotes: 8,
    downvotes: 0,
    isAccepted: false,
    reactions: [
      { emoji: "🤔", count: 2, userReacted: false }
    ],
    createdAt: "45 minutes ago",
    mentions: []
  }
];

export function ForumManager({ 
  currentUserId, 
  currentUserNickname,
  isVerifiedMentor,
  isAdmin,
  onPollClick,
  onPetitionClick
}: ForumManagerProps) {
  const [view, setView] = useState<'list' | 'create' | 'view' | 'categories' | 'tagSearch'>('list');
  const [categories, setCategories] = useState<ForumCategory[]>(mockCategories);
  const [posts, setPosts] = useState<ForumPost[]>(mockForumPosts);
  const [answers, setAnswers] = useState<{ [postId: string]: Answer[] }>({
    'post1': mockAnswers,
    'post_test_accept': [
      {
        id: "ans_test1",
        postId: "post_test_accept",
        content: "The main difference is that async/await is syntactic sugar built on top of Promises. With async/await, you can write asynchronous code that looks more synchronous, which makes it easier to read and debug. For example, instead of chaining .then() calls, you can use await to pause execution until the promise resolves.",
        author: {
          id: "user_mentor1",
          nickname: "JavaScriptGuru",
          isVerifiedMentor: true
        },
        upvotes: 12,
        downvotes: 0,
        isAccepted: false,
        userVote: undefined,
        reactions: [
          { emoji: "👍", count: 5, userReacted: false },
          { emoji: "❤️", count: 2, userReacted: false }
        ],
        createdAt: "30 minutes ago",
        mentions: ["CommunityHelper"]
      },
      {
        id: "ans_test2",
        postId: "post_test_accept",
        content: "Another key point: async/await makes error handling much cleaner with try/catch blocks, compared to handling errors in .catch() with Promises. Plus, you can use await in loops, which is much harder with Promise chains!",
        author: {
          id: "user_dev2",
          nickname: "CodeMaster",
          isVerifiedMentor: false
        },
        upvotes: 8,
        downvotes: 1,
        isAccepted: false,
        reactions: [
          { emoji: "🎉", count: 3, userReacted: false }
        ],
        createdAt: "20 minutes ago",
        mentions: []
      },
      {
        id: "ans_test3",
        postId: "post_test_accept",
        content: "From a practical standpoint, async/await is generally preferred in modern JavaScript because it's more readable. However, sometimes you still need Promise.all() for running multiple async operations in parallel. Best practice: use async/await for sequential operations and Promise.all() for parallel ones!",
        author: {
          id: "user_senior",
          nickname: "SeniorDev",
          isVerifiedMentor: true
        },
        upvotes: 15,
        downvotes: 0,
        isAccepted: false,
        reactions: [
          { emoji: "👏", count: 4, userReacted: false },
          { emoji: "😊", count: 2, userReacted: false }
        ],
        createdAt: "10 minutes ago",
        mentions: []
      }
    ]
  });
  const [comments, setComments] = useState<{ [postId: string]: Comment[] }>({});
  const [selectedPostId, setSelectedPostId] = useState<string | null>(null);
  const [selectedTag, setSelectedTag] = useState<{ id: string; name: string; color: string; emoji?: string } | null>(null);

  const selectedPost = posts.find(p => p.id === selectedPostId);

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
      replies?: Array<{
        id: string;
        author: string;
        authorAvatar: string;
        content: string;
        timeAgo: string;
        likes: number;
        isLiked: boolean;
      }>;
    }>;
  }>({
    '1': [
      {
        id: 'c1',
        author: 'Sarah Chen',
        authorAvatar: 'SC',
        content: 'Great idea! We should also consider partnering with local businesses for sponsorships.',
        timeAgo: '1 hour ago',
        likes: 5,
        isLiked: false,
        replies: [
          {
            id: 'r1',
            author: 'Mike Johnson',
            authorAvatar: 'MJ',
            content: 'That\'s a brilliant suggestion! I know a few local cafes that might be interested.',
            timeAgo: '45 minutes ago',
            likes: 2,
            isLiked: false
          }
        ]
      },
      {
        id: 'c2',
        author: 'Alex Kim',
        authorAvatar: 'AK',
        content: 'I attended last year\'s festival and it was amazing. Definitely worth the investment!',
        timeAgo: '30 minutes ago',
        likes: 8,
        isLiked: true
      }
    ],
    '2': [
      {
        id: 'c3',
        author: 'Emma Davis',
        authorAvatar: 'ED',
        content: 'This is exactly what we need! Mental health should be a priority on campus.',
        timeAgo: '2 hours ago',
        likes: 12,
        isLiked: false
      }
    ]
  });

  // Category management
  const handleCreateCategory = (categoryData: Omit<ForumCategory, 'id' | 'postCount'>) => {
    const newCategory: ForumCategory = {
      ...categoryData,
      id: `cat${Date.now()}`,
      postCount: 0
    };
    setCategories([...categories, newCategory]);
  };

  const handleEditCategory = (id: string, categoryData: Partial<ForumCategory>) => {
    setCategories(prev => prev.map(cat =>
      cat.id === id ? { ...cat, ...categoryData } : cat
    ));
  };

  const handleDeleteCategory = (id: string) => {
    if (confirm('Are you sure you want to delete this category? All posts in this category will be unassigned.')) {
      setCategories(prev => prev.filter(cat => cat.id !== id));
    }
  };

  // Post creation
  const handleCreatePost = (postData: {
    title: string;
    content: string;
    categoryId: string;
    hashtags: string[];
    attachments: File[];
  }) => {
    const category = categories.find(c => c.id === postData.categoryId);
    if (!category) return;

    const newPost: ForumPost = {
      id: `post${Date.now()}`,
      title: postData.title,
      content: postData.content,
      author: {
        id: currentUserId,
        nickname: currentUserNickname,
        isVerifiedMentor
      },
      category: {
        id: category.id,
        name: category.name,
        type: category.type
      },
      hashtags: postData.hashtags,
      attachments: postData.attachments.map((file, idx) => ({
        id: `att${Date.now()}_${idx}`,
        name: file.name,
        type: file.type,
        size: `${(file.size / 1024).toFixed(1)} KB`,
        url: "#"
      })),
      createdAt: "Just now",
      views: 0,
      likes: 0,
      commentCount: 0,
      answerCount: category.type === 'academic-qa' ? 0 : undefined,
      hasAcceptedAnswer: false,
      isLiked: false
    };

    setPosts([newPost, ...posts]);
    
    // Update category post count
    setCategories(prev => prev.map(cat =>
      cat.id === postData.categoryId 
        ? { ...cat, postCount: cat.postCount + 1 }
        : cat
    ));

    setView('list');
  };

  // Voting (Q&A)
  const handleVote = (answerId: string, voteType: 'up' | 'down') => {
    if (!selectedPostId) return;

    setAnswers(prev => ({
      ...prev,
      [selectedPostId]: prev[selectedPostId]?.map(ans => {
        if (ans.id === answerId) {
          const currentVote = ans.userVote;
          let newUpvotes = ans.upvotes;
          let newDownvotes = ans.downvotes;
          let newUserVote: 'up' | 'down' | undefined;

          if (currentVote === voteType) {
            // Remove vote
            if (voteType === 'up') newUpvotes--;
            else newDownvotes--;
            newUserVote = undefined;
          } else {
            // Change or add vote
            if (currentVote === 'up') newUpvotes--;
            if (currentVote === 'down') newDownvotes--;
            if (voteType === 'up') newUpvotes++;
            else newDownvotes++;
            newUserVote = voteType;
          }

          return {
            ...ans,
            upvotes: newUpvotes,
            downvotes: newDownvotes,
            userVote: newUserVote
          };
        }
        return ans;
      }) || []
    }));
  };

  // Accept answer
  const handleAcceptAnswer = (answerId: string) => {
    if (!selectedPostId) return;

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
  };

  // Reactions
  const handleReact = (targetId: string, emoji: string) => {
    if (!selectedPostId) return;

    const postCategory = posts.find(p => p.id === selectedPostId)?.category.type;

    if (postCategory === 'academic-qa') {
      // Update answer reactions
      setAnswers(prev => ({
        ...prev,
        [selectedPostId]: prev[selectedPostId]?.map(ans => {
          if (ans.id === targetId) {
            const existingReaction = ans.reactions.find(r => r.emoji === emoji);
            if (existingReaction) {
              return {
                ...ans,
                reactions: ans.reactions.map(r =>
                  r.emoji === emoji
                    ? { ...r, count: r.userReacted ? r.count - 1 : r.count + 1, userReacted: !r.userReacted }
                    : r
                )
              };
            } else {
              return {
                ...ans,
                reactions: [...ans.reactions, { emoji, count: 1, userReacted: true }]
              };
            }
          }
          return ans;
        }) || []
      }));
    } else {
      // Update comment reactions
      // Similar logic for comments
    }
  };

  // Reply/Comment
  const handleReply = (targetId: string, content: string, mentions: string[]) => {
    if (!selectedPostId) return;

    const post = posts.find(p => p.id === selectedPostId);
    if (!post) return;

    if (post.category.type === 'academic-qa') {
      // Add as answer
      const newAnswer: Answer = {
        id: `ans${Date.now()}`,
        postId: selectedPostId,
        content,
        author: {
          id: currentUserId,
          nickname: currentUserNickname,
          isVerifiedMentor
        },
        upvotes: 0,
        downvotes: 0,
        isAccepted: false,
        reactions: [],
        createdAt: "Just now",
        mentions
      };

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
      // Add as comment
      const newComment: Comment = {
        id: `comment${Date.now()}`,
        postId: selectedPostId,
        content,
        author: {
          id: currentUserId,
          nickname: currentUserNickname,
          isVerifiedMentor
        },
        createdAt: "Just now",
        replies: [],
        reactions: [],
        mentions
      };

      setComments(prev => ({
        ...prev,
        [selectedPostId]: [...(prev[selectedPostId] || []), newComment]
      }));

      setPosts(prev => prev.map(p =>
        p.id === selectedPostId
          ? { ...p, commentCount: p.commentCount + 1 }
          : p
      ));
    }
  };

  // Report content
  const handleReport = (targetId: string, reason: string) => {
    console.log(`Content ${targetId} reported for: ${reason}`);
    alert('Content has been reported and will be reviewed by moderators.');
    // In a real app, this would send to moderation queue
  };

  return (
    <div className="space-y-6">
      {view === 'list' && (
        <>
          {isAdmin && (
            <div className="flex gap-2 justify-end">
              <Button
                variant="outline"
                onClick={() => setView('categories')}
              >
                <Settings className="h-4 w-4 mr-2" />
                Manage Categories
              </Button>
            </div>
          )}
          <ForumDiscussionLayout
            onCreatePost={() => setView('create')}
            onPostClick={(id) => {
              setSelectedPostId(id);
              setView('view');
            }}
            onTagClick={(tagId) => {
              const tagMap: { [key: string]: { name: string; color: string; emoji?: string } } = {
                'stressed': { name: 'Stressed', color: 'bg-orange-500', emoji: '😰' },
                'academic': { name: 'Academic', color: 'bg-blue-500', emoji: '📚' },
                'rested': { name: 'Rested', color: 'bg-green-500', emoji: '😌' },
                'finaexam': { name: 'FinaExam', color: 'bg-amber-600', emoji: '📝' }
              };
              setSelectedTag({ id: tagId, ...tagMap[tagId] });
              setView('tagSearch');
            }}
            onPollClick={onPollClick}
            onPetitionClick={onPetitionClick}
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
          posts={[
            {
              id: "1",
              title: "Managing exam stress - tips and tricks",
              tags: [selectedTag.name.toLowerCase(), "wellness"],
              author: "Sarah Chen",
              authorAvatar: "SC",
              timeAgo: "2 hours ago",
              views: 145,
              likes: 67,
              comments: 23,
              isLiked: false
            },
            {
              id: "2",
              title: "Study group for final exams",
              tags: [selectedTag.name.toLowerCase(), "study"],
              author: "Mike Johnson",
              authorAvatar: "MJ",
              timeAgo: "5 hours ago",
              views: 89,
              likes: 34,
              comments: 12,
              isLiked: false
            }
          ]}
          onBack={() => setView('list')}
          onPostClick={(id) => {
            setSelectedPostId(id);
            setView('view');
          }}
          onLike={(id) => {
            console.log('Like post:', id);
          }}
        />
      )}

      {view === 'view' && selectedPost && (
        <>
          {selectedPost.category.type === 'academic-qa' ? (
            <div>
              <Button
                variant="ghost"
                onClick={() => setView('list')}
                className="mb-4"
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
                authorAvatar: selectedPost.author.nickname.substring(0, 2).toUpperCase(),
                timeAgo: selectedPost.createdAt,
                views: selectedPost.views,
                likes: selectedPost.likes,
                isLiked: selectedPost.isLiked,
                comments: postComments[selectedPost.id] || []
              }}
              onBack={() => setView('list')}
              onLike={() => {
                setPosts(prev => prev.map(p =>
                  p.id === selectedPost.id
                    ? { ...p, isLiked: !p.isLiked, likes: p.isLiked ? p.likes - 1 : p.likes + 1 }
                    : p
                ));
              }}
              onCommentLike={(commentId) => {
                setPostComments(prev => ({
                  ...prev,
                  [selectedPost.id]: prev[selectedPost.id]?.map(comment => {
                    if (comment.id === commentId) {
                      return {
                        ...comment,
                        isLiked: !comment.isLiked,
                        likes: comment.isLiked ? comment.likes - 1 : comment.likes + 1
                      };
                    }
                    // Check replies
                    if (comment.replies) {
                      return {
                        ...comment,
                        replies: comment.replies.map(reply =>
                          reply.id === commentId
                            ? {
                                ...reply,
                                isLiked: !reply.isLiked,
                                likes: reply.isLiked ? reply.likes - 1 : reply.likes + 1
                              }
                            : reply
                        )
                      };
                    }
                    return comment;
                  }) || []
                }));
              }}
              onAddComment={(content) => {
                const newComment = {
                  id: `c${Date.now()}`,
                  author: currentUserNickname,
                  authorAvatar: currentUserNickname.substring(0, 2).toUpperCase(),
                  content,
                  timeAgo: 'Just now',
                  likes: 0,
                  isLiked: false
                };
                setPostComments(prev => ({
                  ...prev,
                  [selectedPost.id]: [...(prev[selectedPost.id] || []), newComment]
                }));
                setPosts(prev => prev.map(p =>
                  p.id === selectedPost.id
                    ? { ...p, commentCount: p.commentCount + 1 }
                    : p
                ));
              }}
              onReply={(commentId, content) => {
                const newReply = {
                  id: `r${Date.now()}`,
                  author: currentUserNickname,
                  authorAvatar: currentUserNickname.substring(0, 2).toUpperCase(),
                  content,
                  timeAgo: 'Just now',
                  likes: 0,
                  isLiked: false
                };
                setPostComments(prev => ({
                  ...prev,
                  [selectedPost.id]: prev[selectedPost.id]?.map(comment =>
                    comment.id === commentId
                      ? {
                          ...comment,
                          replies: [...(comment.replies || []), newReply]
                        }
                      : comment
                  ) || []
                }));
              }}
            />
          )}
        </>
      )}

      {view === 'categories' && isAdmin && (
        <div>
          <Button
            variant="ghost"
            onClick={() => setView('list')}
            className="mb-4"
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