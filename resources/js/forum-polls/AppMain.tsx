import { useState, useEffect, useCallback } from "react";
import { Button } from "./components/ui/button";
import { Input } from "./components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "./components/ui/tabs";
import { Badge } from "./components/ui/badge";
import { Avatar, AvatarFallback } from "./components/ui/avatar";
import { 
  MessageCircle, 
  BarChart3, 
  FileText, 
  Search, 
  Plus, 
  Filter, 
  Archive,
  User,
  LayoutDashboard,
  AlertCircle,
  Loader2,
} from "lucide-react";
import { PollCard } from "./components/poll-petition/PollCard";
import { PollVoteView } from "./components/poll-petition/PollVoteView";
import { CreatePollForm } from "./components/poll-petition/CreatePollForm";
import { PollArchive } from "./components/poll-petition/PollArchive";
import { PetitionCard } from "./components/poll-petition/PetitionCard";
import { PetitionView } from "./components/poll-petition/PetitionView";
import { CreatePetitionForm } from "./components/poll-petition/CreatePetitionForm";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./components/ui/select";
import { ForumManager } from "./components/forum/ForumManager";
import { UserDashboardPage } from "./pages/UserDashboardPage";
import { AdminDashboardPage } from "./pages/AdminDashboardPage";
import { ModerationNoticePopup } from "./components/shared/ModerationNoticePopup";

interface PollOption {
  id: string;
  text: string;
  votes: number;
}

interface Poll {
  id: string;
  title: string;
  description: string;
  options: PollOption[];
  category: string;
  author: string;
  createdAt: string;
  expiryDate: string;
  totalVotes: number;
  hasVoted: boolean;
  isExpired: boolean;
  hasRated?: boolean;
  usefulnessScore?: number;
  targetCriteria?: {
    faculty?: string;
    yearOfStudy?: string;
    course?: string;
  };
}

interface Petition {
  id: string;
  title: string;
  description: string;
  proposedSolution: string;
  author: string;
  createdAt: string;
  supportCount: number;
  hasSupported: boolean;
  attachmentCount: number;
  commentCount: number;
  attachments: {
    id: string;
    name: string;
    type: string;
    size: string;
    url: string;
  }[];
  supporters: {
    id: string;
    nickname: string;
    comment?: string;
    supportedAt: string;
  }[];
}

/* -- Helper: CSRF-safe fetch -- */
function getCsrfToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

async function apiFetch(url: string, options: RequestInit = {}) {
  const res = await fetch(url, {
    ...options,
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
      ...options.headers,
    },
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message || `Request failed (${res.status})`);
  }
  return res.json();
}

const userProfile = {
  id: (window as any).authUser?.id?.toString() || "user_123",
  nickname: (window as any).authUser?.nickname || (window as any).authUser?.name || "CommunityHelper",
  joinedDate: "Nov 15, 2024",
  totalComments: 47,
  acceptedTopics: 3,
  likesReceived: 28,
  topicsCreated: 7,
};

const isAdmin = (window as any).authUser?.is_admin === true;
const isMuted = (() => {
  const mutedUntil = (window as any).authUser?.muted_until;
  return mutedUntil ? new Date(mutedUntil) > new Date() : false;
})();
const mutedUntilDate = (window as any).authUser?.muted_until
  ? new Date((window as any).authUser.muted_until).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
  : null;

export default function App() {
  const [mainView, setMainView] = useState<'main' | 'profile' | 'community' | 'user-dashboard' | 'admin-dashboard'>('main');
  const [mainTab, setMainTab] = useState<'discussions' | 'polls' | 'petitions'>(() => {
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    if (urlTab === 'petitions') return 'petitions';
    if (urlTab === 'polls') return 'polls';
    return (window as any).initialTab === 'polls' ? 'polls'
      : (window as any).initialTab === 'petitions' ? 'petitions'
      : 'discussions';
  });
  
  // Poll state
  const [pollView, setPollView] = useState<'list' | 'vote' | 'create' | 'archive'>('list');
  const [polls, setPolls] = useState<Poll[]>([]);
  const [archivedPolls, setArchivedPolls] = useState<Poll[]>([]);
  const [selectedPollId, setSelectedPollId] = useState<string | null>(null);
  const [canCreatePollFlag, setCanCreatePollFlag] = useState(true);
  const [nextPollDate, setNextPollDate] = useState<string | undefined>(undefined);
  const [pollSearchTerm, setPollSearchTerm] = useState("");
  const [activePollSearch, setActivePollSearch] = useState("");
  const [pollCategoryFilter, setPollCategoryFilter] = useState("all");
  const [pollsLoading, setPollsLoading] = useState(false);
  
  // Petition state
  const [petitionView, setPetitionView] = useState<'list' | 'view' | 'create'>('list');
  const [petitions, setPetitions] = useState<Petition[]>([]);
  const [selectedPetitionId, setSelectedPetitionId] = useState<string | null>(null);
  const [canCreatePetitionFlag, setCanCreatePetitionFlag] = useState(true);
  const [nextPetitionDate, setNextPetitionDate] = useState<string | undefined>(undefined);
  const [petitionSearchTerm, setPetitionSearchTerm] = useState("");
  const [activePetitionSearch, setActivePetitionSearch] = useState("");
  const [petitionsLoading, setPetitionsLoading] = useState(false);
  const [selectedPetitionDetail, setSelectedPetitionDetail] = useState<any>(null);
  const [petitionDetailLoading, setPetitionDetailLoading] = useState(false);

  // For fetching a single poll directly (handles expired/closed polls not in active/archived lists)
  const [singlePollDetail, setSinglePollDetail] = useState<any>(null);
  const [singlePollLoading, setSinglePollLoading] = useState(false);

  // Post to open when returning from user dashboard
  const [openPostId, setOpenPostId] = useState<string | null>(() => {
    return new URLSearchParams(window.location.search).get('viewPost');
  });

  /* -- Data fetchers -- */

  const fetchPolls = useCallback(async () => {
    setPollsLoading(true);
    try {
      const params = new URLSearchParams({ status: 'active' });
      if (activePollSearch) params.set('search', activePollSearch);
      if (pollCategoryFilter !== 'all') params.set('category', pollCategoryFilter);
      const data = await apiFetch(`/api/poll-petition/polls?${params}`);
      setPolls(data);
    } catch (e) {
      console.error('Failed to fetch polls', e);
    } finally {
      setPollsLoading(false);
    }
  }, [activePollSearch, pollCategoryFilter]);

  const fetchArchivedPolls = useCallback(async () => {
    try {
      const data = await apiFetch('/api/poll-petition/polls/archived');
      setArchivedPolls(data);
    } catch (e) {
      console.error('Failed to fetch archived polls', e);
    }
  }, []);

  const fetchPetitions = useCallback(async () => {
    setPetitionsLoading(true);
    try {
      const params = new URLSearchParams();
      if (activePetitionSearch) params.set('search', activePetitionSearch);
      const data = await apiFetch(`/api/poll-petition/petitions?${params}`);
      setPetitions(data);
    } catch (e) {
      console.error('Failed to fetch petitions', e);
    } finally {
      setPetitionsLoading(false);
    }
  }, [activePetitionSearch]);

  const fetchCanCreatePoll = useCallback(async () => {
    try {
      const data = await apiFetch('/api/poll-petition/polls/can-create');
      setCanCreatePollFlag(data.can_create);
      setNextPollDate(data.next_available_date ?? undefined);
    } catch (e) {
      console.error('Failed to check poll create status', e);
    }
  }, []);

  const fetchCanCreatePetition = useCallback(async () => {
    try {
      const data = await apiFetch('/api/poll-petition/petitions/can-create');
      setCanCreatePetitionFlag(data.can_create);
      setNextPetitionDate(data.next_available_date ?? undefined);
    } catch (e) {
      console.error('Failed to check petition create status', e);
    }
  }, []);

  const fetchPetitionDetail = useCallback(async (id: string) => {
    setPetitionDetailLoading(true);
    try {
      const data = await apiFetch(`/api/poll-petition/petitions/${id}`);
      setSelectedPetitionDetail(data);
    } catch (e) {
      console.error('Failed to fetch petition detail', e);
    } finally {
      setPetitionDetailLoading(false);
    }
  }, []);

  const fetchPollById = useCallback(async (id: string) => {
    setSinglePollLoading(true);
    try {
      const data = await apiFetch(`/api/poll-petition/polls/${id}`);
      setSinglePollDetail(data);
    } catch (e) {
      console.error('Failed to fetch poll detail', e);
    } finally {
      setSinglePollLoading(false);
    }
  }, []);

  // Fetch active polls and petitions on mount & when tab changes
  useEffect(() => {
    if (mainTab === 'polls') {
      fetchPolls();
    } else if (mainTab === 'petitions') {
      fetchPetitions();
    }
  }, [mainTab, fetchPolls, fetchPetitions]);

  // Refetch when category filter changes (polls)
  useEffect(() => {
    if (mainTab === 'polls') fetchPolls();
  }, [pollCategoryFilter]);

  // Handle URL params for poll/petition deep links (from admin dashboard View buttons)
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const viewId = params.get('viewId');
    const tab = params.get('tab');
    if (viewId && tab === 'polls') {
      setSinglePollDetail(null);
      setSelectedPollId(viewId);
      setPollView('vote');
      fetchPollById(viewId);
    } else if (viewId && tab === 'petitions') {
      setSelectedPetitionDetail(null);
      setSelectedPetitionId(viewId);
      setPetitionView('view');
      fetchPetitionDetail(viewId);
    }
    // Clean URL params after consuming
    if (viewId || params.get('viewPost')) {
      window.history.replaceState({}, '', window.location.pathname);
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  /* -- Poll actions -- */

  const handleCreatePoll = async (pollData: any) => {
    try {
      await apiFetch('/api/poll-petition/polls', {
        method: 'POST',
        body: JSON.stringify({
          title: pollData.title,
          description: pollData.description,
          options: pollData.options,
          expiry_date: pollData.expiryDate,
          category: pollData.category,
          target_faculty: pollData.targetCriteria?.faculty,
          target_year: pollData.targetCriteria?.yearOfStudy,
          target_course: pollData.targetCriteria?.course,
        }),
      });
      setPollView('list');
      fetchPolls();
      fetchCanCreatePoll();
    } catch (e: any) {
      alert(e.message || 'Failed to create poll');
    }
  };

  const handleVotePoll = async (pollId: string, optionId: string) => {
    try {
      const updated = await apiFetch(`/api/poll-petition/polls/${pollId}/vote`, {
        method: 'POST',
        body: JSON.stringify({ option_id: optionId }),
      });
      setPolls(prev => prev.map(p => p.id === pollId ? updated : p));
    } catch (e: any) {
      alert(e.message || 'Failed to vote');
    }
  };

  const handleRatePollUsefulness = async (pollId: string, isUseful: boolean) => {
    try {
      const updated = await apiFetch(`/api/poll-petition/polls/${pollId}/rate`, {
        method: 'POST',
        body: JSON.stringify({ is_useful: isUseful }),
      });
      setPolls(prev => prev.map(p => p.id === pollId ? updated : p));
    } catch (e: any) {
      alert(e.message || 'Failed to rate');
    }
  };

  /* -- Petition actions -- */

  const handleCreatePetition = async (petitionData: any) => {
    try {
      const formData = new FormData();
      formData.append('title', petitionData.title);
      formData.append('description', petitionData.description);
      formData.append('proposed_solution', petitionData.proposedSolution);
      formData.append('supporter_goal', (petitionData.targetSupporters ?? 500).toString());
      if (petitionData.attachments) {
        petitionData.attachments.forEach((file: File, i: number) => {
          formData.append(`attachments[${i}]`, file);
        });
      }

      await apiFetch('/api/poll-petition/petitions', {
        method: 'POST',
        body: formData,
      });
      setPetitionView('list');
      fetchPetitions();
      fetchCanCreatePetition();
    } catch (e: any) {
      alert(e.message || 'Failed to create petition');
    }
  };

  const handleSupportPetition = async (petitionId: string, comment?: string) => {
    try {
      const updated = await apiFetch(`/api/poll-petition/petitions/${petitionId}/support`, {
        method: 'POST',
        body: JSON.stringify({ comment: comment || null }),
      });
      // Update the full petition detail shown in PetitionView
      setSelectedPetitionDetail(updated);
      // Update summary fields in petitions list
      setPetitions(prev => prev.map(p => p.id === petitionId ? {
        ...p,
        supportCount: updated.supportCount,
        hasSupported: updated.hasSupported,
        commentCount: updated.commentCount,
      } : p));
    } catch (e: any) {
      alert(e.message || 'Failed to support petition');
    }
  };

  /* -- Derived state -- */

  const selectedPoll = selectedPollId
    ? polls.find(p => p.id === selectedPollId)
      || archivedPolls.find(p => p.id === selectedPollId)
      || (singlePollDetail?.id === selectedPollId ? singlePollDetail : null)
    : null;
  const selectedPetition = selectedPetitionId ? petitions.find(p => p.id === selectedPetitionId) : null;

  const activePolls = polls.filter(p => !p.isExpired);

  const pollCategories = Array.from(new Set(polls.map(p => p.category)));

  return (
    <div className="min-h-screen bg-[#e8e8ea]">
      <div className="container mx-auto px-4 py-8">
        {mainView === 'main' && (
          <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center bg-white rounded-lg px-6 py-4 shadow-sm">
              <div>
                <h1 className="text-2xl font-semibold text-gray-900">Campus Community Platform</h1>
                <p className="text-gray-600 text-sm">
                  Discuss, poll, and petition for meaningful change
                </p>
              </div>
              <div className="flex items-center gap-3">
                <Button 
                  variant="ghost" 
                  onClick={() => {
                    if (isAdmin) {
                      window.location.href = '/admin/forum';
                    } else {
                      setMainView('user-dashboard');
                    }
                  }}
                  className="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 cursor-pointer"
                >
                  <LayoutDashboard className="h-4 w-4" />
                  My Dashboard
                </Button>
              </div>
            </div>

            {/* Main Tabs */}
            <Tabs value={mainTab} onValueChange={(v: any) => setMainTab(v)}>
              <div className="bg-white rounded-lg px-6 py-2 shadow-sm">
                <TabsList className="w-full flex bg-muted border-b-0">
                  <TabsTrigger 
                    value="discussions" 
                    className="flex-1 flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <MessageCircle className="h-4 w-4" />
                    Forum
                  </TabsTrigger>
                  <TabsTrigger 
                    value="polls" 
                    className="flex-1 flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <BarChart3 className="h-4 w-4" />
                    Polls
                  </TabsTrigger>
                  <TabsTrigger 
                    value="petitions" 
                    className="flex-1 flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <FileText className="h-4 w-4" />
                    Petitions
                  </TabsTrigger>
                </TabsList>
              </div>

              {/* Discussions Tab */}
              <TabsContent value="discussions" className="mt-0">
                <ForumManager
                  currentUserId={userProfile.id}
                  currentUserNickname={userProfile.nickname}
                  isVerifiedMentor={false}
                  isAdmin={isAdmin}
                  isMuted={isMuted}
                  mutedUntilDate={mutedUntilDate}
                  initialPostId={openPostId}
                  onPollClick={(pollId) => {
                    setSelectedPollId(pollId);
                    setMainTab('polls');
                    setPollView('vote');
                  }}
                  onPetitionClick={(petitionId) => {
                    setSelectedPetitionId(petitionId);
                    setMainTab('petitions');
                    setPetitionView('view');
                    fetchPetitionDetail(petitionId);
                  }}
                  onViewAllPolls={() => {
                    setMainTab('polls');
                    setPollView('list');
                  }}
                  onViewAllPetitions={() => {
                    setMainTab('petitions');
                    setPetitionView('list');
                  }}
                />
              </TabsContent>

              {/* Polls Tab */}
              <TabsContent value="polls">
                {pollView === 'list' && (
                  <div className="space-y-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <h2 className="text-2xl">Active Polls</h2>
                        <p className="text-muted-foreground">
                          Vote on campus polls and share your opinion
                        </p>
                      </div>
                      <div className="flex gap-2">
                        <Button 
                          onClick={() => { setPollView('archive'); fetchArchivedPolls(); }} variant="outline"
                          className="hover: cursor-pointer"
                        >
                          <Archive className="h-4 w-4 mr-2" />
                          Archive
                        </Button>
                        <Button onClick={() => { setPollView('create'); fetchCanCreatePoll(); }}
                          className="hover: cursor-pointer"
                        >
                          <Plus className="h-4 w-4 mr-2" />
                          Create Poll
                        </Button>
                      </div>
                    </div>

                    <div className="flex gap-4 items-center">
                      <div className="relative flex-1 flex gap-2">
                        <div className="relative flex-1">
                          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                          <Input
                            placeholder="Search polls..."
                            value={pollSearchTerm}
                            onChange={(e) => setPollSearchTerm(e.target.value)}
                            onKeyDown={(e) => { if (e.key === 'Enter') { setActivePollSearch(pollSearchTerm); } }}
                            className="pl-10"
                          />
                        </div>
                        <Button
                          onClick={() => setActivePollSearch(pollSearchTerm)}
                          variant="secondary"
                          className="cursor-pointer"
                        >
                          <Search className="h-4 w-4 mr-2" />
                          Search
                        </Button>
                      </div>
                      <Select value={pollCategoryFilter} onValueChange={setPollCategoryFilter}>
                        <SelectTrigger className="w-48">
                          <Filter className="h-4 w-4 mr-2" />
                          <SelectValue placeholder="Category" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All Categories</SelectItem>
                          {pollCategories.map(cat => (
                            <SelectItem key={cat} value={cat}>
                              {cat.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    {pollsLoading ? (
                      <div className="flex items-center justify-center py-12">
                        <div className="text-center">
                          <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                          <p className="text-muted-foreground">Loading polls...</p>
                        </div>
                      </div>
                    ) : activePolls.length === 0 ? (
                      <div className="text-center py-12">
                        <p className="text-muted-foreground">No active polls found</p>
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {activePolls.map(poll => (
                          <PollCard
                            key={poll.id}
                            poll={poll}
                            onViewPoll={(id) => {
                              setSelectedPollId(id);
                              setPollView('vote');
                            }}
                          />
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {pollView === 'vote' && singlePollLoading && (
                  <div className="flex items-center justify-center py-20">
                    <div className="text-center">
                      <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                      <p className="text-muted-foreground">Loading poll...</p>
                    </div>
                  </div>
                )}

                {pollView === 'vote' && !singlePollLoading && selectedPoll && (
                  <PollVoteView
                    poll={selectedPoll}
                    onBack={() => { setPollView('list'); fetchPolls(); setSinglePollDetail(null); }}
                    onVote={handleVotePoll}
                    onRateUsefulness={handleRatePollUsefulness}
                  />
                )}

                {pollView === 'create' && (
                  <CreatePollForm
                    onBack={() => setPollView('list')}
                    onCreatePoll={handleCreatePoll}
                    canCreatePoll={canCreatePollFlag}
                    nextAvailableDate={nextPollDate}
                  />
                )}

                {pollView === 'archive' && (
                  <PollArchive
                    archivedPolls={archivedPolls}
                    onBack={() => setPollView('list')}
                    onViewPoll={(id) => {
                      setSelectedPollId(id);
                      setPollView('vote');
                    }}
                  />
                )}
              </TabsContent>

              {/* Petitions Tab */}
              <TabsContent value="petitions">
                {petitionView === 'list' && (
                  <div className="space-y-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <h2 className="text-2xl">Campus Petitions</h2>
                        <p className="text-muted-foreground">
                          Support petitions that matter to you
                        </p>
                      </div>
                      <Button onClick={() => { setPetitionView('create'); fetchCanCreatePetition(); }}
                        className="hover: cursor-pointer"
                      >
                        <Plus className="h-4 w-4 mr-2" />
                        Create Petition
                      </Button>
                    </div>

                    <div className="flex gap-2">
                      <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                        <Input
                          placeholder="Search petitions..."
                          value={petitionSearchTerm}
                          onChange={(e) => setPetitionSearchTerm(e.target.value)}
                          onKeyDown={(e) => { if (e.key === 'Enter') { setActivePetitionSearch(petitionSearchTerm); } }}
                          className="pl-10"
                        />
                      </div>
                      <Button
                        onClick={() => setActivePetitionSearch(petitionSearchTerm)}
                        variant="secondary"
                        className="cursor-pointer"
                      >
                        <Search className="h-4 w-4 mr-2" />
                        Search
                      </Button>
                    </div>

                    {petitionsLoading ? (
                      <div className="flex items-center justify-center py-12">
                        <div className="text-center">
                          <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                          <p className="text-muted-foreground">Loading petitions...</p>
                        </div>
                      </div>
                    ) : petitions.length === 0 ? (
                      <div className="text-center py-12">
                        <p className="text-muted-foreground">No petitions found</p>
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {petitions.map(petition => (
                          <PetitionCard
                            key={petition.id}
                            petition={petition}
                            onViewPetition={(id) => {
                              setSelectedPetitionId(id);
                              setPetitionView('view');
                              fetchPetitionDetail(id);
                            }}
                          />
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {petitionView === 'view' && (
                  petitionDetailLoading ? (
                    <div className="flex items-center justify-center py-20">
                      <div className="text-center">
                        <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                        <p className="text-muted-foreground">Loading petition...</p>
                      </div>
                    </div>
                  ) : selectedPetitionDetail ? (
                    <PetitionView
                      petition={selectedPetitionDetail}
                      onBack={() => { setPetitionView('list'); fetchPetitions(); setSelectedPetitionDetail(null); }}
                      onSupport={handleSupportPetition}
                    />
                  ) : null
                )}

                {petitionView === 'create' && (
                  <CreatePetitionForm
                    onBack={() => setPetitionView('list')}
                    onCreatePetition={handleCreatePetition}
                    canCreatePetition={canCreatePetitionFlag}
                    nextAvailableDate={nextPetitionDate}
                  />
                )}
              </TabsContent>
            </Tabs>
          </div>
        )}

        {mainView === 'user-dashboard' && (
          <UserDashboardPage
            userId={userProfile.id}
            userNickname={userProfile.nickname}
            onBack={() => setMainView('main')}
            onPostClick={(postId) => {
              setOpenPostId(postId);
              setMainView('main');
              setMainTab('discussions');
            }}
            onPollClick={(pollId) => {
              setSinglePollDetail(null);
              setSelectedPollId(pollId);
              setPollView('vote');
              setMainView('main');
              setMainTab('polls');
              // Fetch directly when poll isn't already in active or archived lists (e.g. expired poll)
              if (!polls.find(p => p.id === pollId) && !archivedPolls.find(p => p.id === pollId)) {
                fetchPollById(pollId);
              }
            }}
            onPetitionClick={(petitionId) => {
              setSelectedPetitionDetail(null);
              setSelectedPetitionId(petitionId);
              setPetitionView('view');
              fetchPetitionDetail(petitionId);
              setMainView('main');
              setMainTab('petitions');
            }}
            onSwitchToAdmin={() => { window.location.href = '/admin/forum'; }}
          />
        )}

        {mainView === 'admin-dashboard' && (
          <AdminDashboardPage
            adminId={userProfile.id}
            adminNickname={userProfile.nickname}
            onBack={() => setMainView('main')}
            onViewContent={(contentId, contentType) => {
              if (contentType === 'poll') {
                setSinglePollDetail(null);
                setSelectedPollId(contentId);
                setPollView('vote');
                setMainView('main');
                setMainTab('polls');
              } else if (contentType === 'petition') {
                setSelectedPetitionDetail(null);
                setSelectedPetitionId(contentId);
                setPetitionView('view');
                fetchPetitionDetail(contentId);
                setMainView('main');
                setMainTab('petitions');
              } else {
                setOpenPostId(contentId);
                setMainView('main');
                setMainTab('discussions');
              }
            }}
            onSwitchToUser={() => setMainView('user-dashboard')}
          />
        )}

        {/* Moderation notice popup for users */}
        {!isAdmin && (
          <ModerationNoticePopup mutedUntil={(window as any).authUser?.muted_until} />
        )}
      </div>
    </div>
  );
}