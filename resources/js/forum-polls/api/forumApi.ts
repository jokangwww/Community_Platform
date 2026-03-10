// Forum API Service
// Provides type-safe API calls to the forum backend

const getCSRFToken = (): string => {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta?.getAttribute('content') || '';
};

const headers = () => ({
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'X-CSRF-TOKEN': getCSRFToken(),
});

async function apiRequest<T>(url: string, options?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    headers: headers(),
    ...options,
  });
  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Request failed' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }
  return response.json();
}

// ---------- Categories ----------

export async function fetchCategories() {
  const res = await apiRequest<{ success: boolean; data: any[] }>('/api/forum/categories');
  return res.data;
}

export async function createCategory(data: { name: string; description: string; type: string; hashtags?: string[] }) {
  const res = await apiRequest<{ success: boolean; data: any }>('/api/forum/categories', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function updateCategory(id: string, data: { name?: string; description?: string; type?: string; hashtags?: string[] }) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/categories/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function deleteCategory(id: string) {
  return apiRequest<{ success: boolean }>(`/api/forum/categories/${id}`, {
    method: 'DELETE',
  });
}

// ---------- Posts ----------

export interface FetchPostsParams {
  category_id?: string;
  type?: string;
  search?: string;
  hashtag?: string;
  sort?: string;
  per_page?: number;
  page?: number;
}

export async function fetchPosts(params: FetchPostsParams = {}) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') query.append(key, String(value));
  });
  const res = await apiRequest<{ success: boolean; data: any[]; meta: any }>(`/api/forum/posts?${query}`);
  return res;
}

export async function fetchPost(id: string) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/posts/${id}`);
  return res.data;
}

export async function createPost(data: {
  title: string;
  content: string;
  category_id: string;
  hashtags?: string[];
  attachments?: File[];
}) {
  const formData = new FormData();
  formData.append('title', data.title);
  formData.append('content', data.content);
  formData.append('category_id', data.category_id);
  if (data.hashtags) {
    data.hashtags.forEach((tag, i) => formData.append(`hashtags[${i}]`, tag));
  }
  if (data.attachments) {
    data.attachments.forEach((file, i) => formData.append(`attachments[${i}]`, file));
  }

  const response = await fetch('/api/forum/posts', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCSRFToken(),
    },
    body: formData,
  });
  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to create post' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }
  const res = await response.json();
  return res.data;
}

export async function togglePostLike(id: string) {
  const res = await apiRequest<{ success: boolean; data: { isLiked: boolean; likesCount: number } }>(`/api/forum/posts/${id}/like`, {
    method: 'POST',
  });
  return res.data;
}

export async function searchPostsByHashtag(hashtag: string) {
  const res = await apiRequest<{ success: boolean; data: any[] }>(`/api/forum/posts/search/hashtag?hashtag=${encodeURIComponent(hashtag)}`);
  return res.data;
}

export async function updatePost(id: string, data: { title?: string; content?: string }) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/posts/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function deletePost(id: string) {
  return apiRequest<{ success: boolean; message: string }>(`/api/forum/posts/${id}`, {
    method: 'DELETE',
  });
}

// ---------- Answers ----------

export async function fetchAnswers(postId: string) {
  const res = await apiRequest<{ success: boolean; data: any[] }>(`/api/forum/posts/${postId}/answers`);
  return res.data;
}

export async function createAnswer(postId: string, data: { content: string; mentions?: string[] }) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/posts/${postId}/answers`, {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function voteAnswer(answerId: string, voteType: 'up' | 'down') {
  const res = await apiRequest<{ success: boolean; data: { upvotes: number; downvotes: number; userVote: string | null } }>(`/api/forum/answers/${answerId}/vote`, {
    method: 'POST',
    body: JSON.stringify({ vote_type: voteType }),
  });
  return res.data;
}

export async function acceptAnswer(answerId: string) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/answers/${answerId}/accept`, {
    method: 'POST',
  });
  return res.data;
}

export async function reactToAnswer(answerId: string, emoji: string) {
  const res = await apiRequest<{ success: boolean; data: any[] }>(`/api/forum/answers/${answerId}/react`, {
    method: 'POST',
    body: JSON.stringify({ emoji }),
  });
  return res.data;
}

// ---------- Comments ----------

export async function fetchComments(postId: string) {
  const res = await apiRequest<{ success: boolean; data: any[] }>(`/api/forum/posts/${postId}/comments`);
  return res.data;
}

export async function createComment(postId: string, data: { content: string; parent_id?: string }) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/posts/${postId}/comments`, {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function toggleCommentLike(commentId: string) {
  const res = await apiRequest<{ success: boolean; data: { isLiked: boolean; likesCount: number } }>(`/api/forum/comments/${commentId}/like`, {
    method: 'POST',
  });
  return res.data;
}

export async function updateComment(commentId: string, data: { content: string }) {
  const res = await apiRequest<{ success: boolean; data: any }>(`/api/forum/comments/${commentId}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function deleteComment(commentId: string) {
  return apiRequest<{ success: boolean; message: string }>(`/api/forum/comments/${commentId}`, {
    method: 'DELETE',
  });
}

// ---------- Hashtags ----------

export async function fetchHashtags() {
  const res = await apiRequest<{ success: boolean; data: any[] }>('/api/forum/hashtags');
  return res.data;
}

export async function fetchTrendingHashtags() {
  const res = await apiRequest<{ success: boolean; data: any[] }>('/api/forum/hashtags/trending');
  return res.data;
}

export async function searchHashtags(q: string) {
  const res = await apiRequest<{ success: boolean; data: any[] }>(`/api/forum/hashtags/search?q=${encodeURIComponent(q)}`);
  return res.data;
}

// ---------- Reports ----------

export async function reportContent(data: { reportable_id: string; reportable_type: 'post' | 'answer' | 'comment'; reason: string; details?: string }) {
  const res = await apiRequest<{ success: boolean; message: string }>('/api/forum/reports', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res;
}

// ---------- Dashboard ----------

export async function fetchUserDashboard() {
  const res = await apiRequest<{ success: boolean; data: any }>('/api/forum/dashboard');
  return res.data;
}

export async function fetchAdminStats() {
  const res = await apiRequest<{ success: boolean; data: any }>('/api/forum/admin/stats');
  return res.data;
}
