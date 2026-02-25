import { useState } from "react";
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
  Users,
  User,
  Star,
  Award,
  Trophy,
  Crown,
  LayoutDashboard,
  Shield
} from "lucide-react";
import { PollCard } from "./components/poll-petition/PollCard";
import { PollVoteView } from "./components/poll-petition/PollVoteView";
import { CreatePollForm } from "./components/poll-petition/CreatePollForm";
import { PollArchive } from "./components/poll-petition/PollArchive";
import { PetitionCard } from "./components/poll-petition/PetitionCard";
import { PetitionView } from "./components/poll-petition/PetitionView";
import { CreatePetitionForm } from "./components/poll-petition/CreatePetitionForm";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./components/ui/select";
import { MyPage, UserProfile } from "./components/shared/MyPage";
import { DesigningCommunity } from "./components/shared/DesigningCommunity";
import { Dashboard } from "./components/forum/Dashboard";
import { DiscussionView } from "./components/forum/DiscussionView";
import { CreateTopicForm } from "./components/forum/CreateTopicForm";
import { ForumDiscussionLayout } from "./components/forum/ForumDiscussionLayout";
import { ForumManager } from "./components/forum/ForumManager";
import { UserDashboard } from "./components/shared/UserDashboard";
import { AdminDashboard } from "./components/shared/AdminDashboard";
import { UserDashboardPage } from "./pages/UserDashboardPage";
import { AdminDashboardPage } from "./pages/AdminDashboardPage";

interface BadgeLevel {
  id: number;
  name: string;
  icon: React.ComponentType<any>;
  color: string;
  minPoints: number;
  maxPoints: number;
}

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

interface Topic {
  id: string;
  title: string;
  description: string;
  author: string;
  createdAt: string;
  status: 'discussion' | 'voting' | 'petition';
  votes: number;
  comments: number;
  participants: number;
  votesNeeded: number;
  category: string;
  fullDescription: string;
  agreeCount: number;
  disagreeCount: number;
  totalVotes: number;
  hasUserAgreed: boolean;
  hasUserDisagreed: boolean;
}

interface Comment {
  id: string;
  author: string;
  content: string;
  timestamp: string;
  replies?: Comment[];
}

// Mock poll data
const mockPolls: Poll[] = [
  {
    id: "p1",
    title: "What time should library hours be extended to?",
    description: "Help us decide the best closing time for extended library hours on weekdays",
    options: [
      { id: "o1", text: "8:00 PM", votes: 15 },
      { id: "o2", text: "9:00 PM", votes: 42 },
      { id: "o3", text: "10:00 PM", votes: 78 },
      { id: "o4", text: "11:00 PM", votes: 23 },
    ],
    category: "facilities",
    author: "LibraryCommittee",
    createdAt: "Dec 8, 2024",
    expiryDate: "2024-12-20",
    totalVotes: 158,
    hasVoted: false,
    isExpired: false,
    usefulnessScore: 87,
    targetCriteria: {
      faculty: "All Students",
    }
  },
  {
    id: "p2",
    title: "Best day for Engineering Week events?",
    description: "Vote for the day that works best for you to attend Engineering Week activities",
    options: [
      { id: "o5", text: "Monday", votes: 8 },
      { id: "o6", text: "Wednesday", votes: 24 },
      { id: "o7", text: "Friday", votes: 31 },
    ],
    category: "events",
    author: "EngineeringStudent",
    createdAt: "Dec 5, 2024",
    expiryDate: "2024-12-15",
    totalVotes: 63,
    hasVoted: false,
    isExpired: false,
    usefulnessScore: 76,
    targetCriteria: {
      faculty: "Engineering",
    }
  },
  {
    id: "p3",
    title: "Campus cafeteria food preferences?",
    description: "Help us improve our menu by voting for your preferred cuisine type",
    options: [
      { id: "o8", text: "More Asian dishes", votes: 89 },
      { id: "o9", text: "More vegetarian options", votes: 102 },
      { id: "o10", text: "More local cuisine", votes: 45 },
      { id: "o11", text: "More international options", votes: 67 },
    ],
    category: "campus-life",
    author: "CafeteriaManager",
    createdAt: "Nov 28, 2024",
    expiryDate: "2024-12-05",
    totalVotes: 303,
    hasVoted: true,
    isExpired: true,
    usefulnessScore: 92
  }
];

// Mock petition data
const mockPetitions: Petition[] = [
  {
    id: "pet1",
    title: "Improve Mental Health Support Services on Campus",
    description: "Our university currently has limited mental health resources available to students. Wait times for counseling appointments can exceed 2-3 weeks, and the counseling center is only open during regular business hours, which doesn't accommodate students with conflicting class schedules. Many students are struggling with stress, anxiety, and depression without adequate support.",
    proposedSolution: "We propose expanding the mental health services by: 1) Hiring additional licensed counselors to reduce wait times, 2) Extending counseling center hours to include evenings and weekends, 3) Implementing a 24/7 crisis hotline specifically for students, 4) Creating peer support groups facilitated by trained student leaders, 5) Partnering with telehealth services for immediate virtual consultations.",
    author: "WellnessAdvocate",
    createdAt: "Dec 1, 2024",
    supportCount: 847,
    hasSupported: false,
    attachmentCount: 3,
    commentCount: 156,
    attachments: [
      {
        id: "a1",
        name: "Mental Health Survey Results.pdf",
        type: "application/pdf",
        size: "2.4 MB",
        url: "#"
      },
      {
        id: "a2",
        name: "Counseling Center Statistics.xlsx",
        type: "application/vnd.ms-excel",
        size: "156 KB",
        url: "#"
      },
      {
        id: "a3",
        name: "Student Testimonials.pdf",
        type: "application/pdf",
        size: "1.1 MB",
        url: "#"
      }
    ],
    supporters: [
      {
        id: "s1",
        nickname: "StudentVoice",
        comment: "This is desperately needed. I waited 3 weeks for an appointment during finals and it really impacted my performance.",
        supportedAt: "2 hours ago"
      },
      {
        id: "s2",
        nickname: "CareAboutPeers",
        comment: "Mental health should be a priority. Full support!",
        supportedAt: "5 hours ago"
      },
      {
        id: "s3",
        nickname: "GradStudent2024",
        supportedAt: "1 day ago"
      }
    ]
  },
  {
    id: "pet2",
    title: "Install More Charging Stations Across Campus",
    description: "With the increasing number of students using laptops, tablets, and other electronic devices for their studies, there is a critical shortage of charging stations across campus. Many students struggle to find available outlets in libraries, common areas, and classrooms, which hampers their ability to study effectively.",
    proposedSolution: "Install modern charging stations with multiple USB and AC outlets in key locations including: all library floors, student lounges, outdoor seating areas, and high-traffic hallways. Implement solar-powered charging benches in outdoor spaces to promote sustainability while providing convenient charging options.",
    author: "TechSavvyStudent",
    createdAt: "Nov 25, 2024",
    supportCount: 542,
    hasSupported: false,
    attachmentCount: 1,
    commentCount: 89,
    attachments: [
      {
        id: "a4",
        name: "Proposed Charging Station Locations.pdf",
        type: "application/pdf",
        size: "3.2 MB",
        url: "#"
      }
    ],
    supporters: [
      {
        id: "s4",
        nickname: "EngineeringMajor",
        comment: "Great idea! I've had to leave the library multiple times because my laptop died.",
        supportedAt: "1 day ago"
      },
      {
        id: "s5",
        nickname: "LibraryRegular",
        supportedAt: "2 days ago"
      }
    ]
  }
];

// Mock discussion topics
const mockTopics: Topic[] = [
  {
    id: "1",
    title: "Implement Bike Lanes on Main Street",
    description: "Proposal to add dedicated bike lanes to improve safety and reduce traffic congestion.",
    fullDescription: "Our community needs safer transportation options. Main Street is heavily trafficked by both cars and cyclists, creating dangerous conditions.",
    author: "BikeAdvocate",
    createdAt: "Dec 15, 2024",
    status: "voting",
    votes: 78,
    comments: 23,
    participants: 45,
    votesNeeded: 100,
    category: "transportation",
    agreeCount: 42,
    disagreeCount: 8,
    totalVotes: 50,
    hasUserAgreed: false,
    hasUserDisagreed: false
  }
];

const mockComments: { [key: string]: Comment[] } = {};

const mockUserProfile: UserProfile = {
  id: "user_123",
  nickname: "CommunityHelper",
  joinedDate: "Nov 15, 2024",
  totalComments: 47,
  acceptedTopics: 3,
  likesReceived: 28,
  topicsCreated: 7,
  currentLevel: {
    id: 3,
    name: "Active Member",
    icon: User, // Using User as a placeholder, will be replaced by badgeLevels
    color: "bg-green-100 text-green-800",
    minPoints: 51,
    maxPoints: 150
  },
  activityPoints: 175
};

export default function App() {
  const [mainView, setMainView] = useState<'main' | 'profile' | 'community' | 'user-dashboard' | 'admin-dashboard'>('main');
  const [mainTab, setMainTab] = useState<'discussions' | 'polls' | 'petitions'>((window as any).initialTab === 'polls' ? 'polls' : (window as any).initialTab === 'petitions' ? 'petitions' : 'discussions');
  
  // Poll state
  const [pollView, setPollView] = useState<'list' | 'vote' | 'create' | 'archive'>('list');
  const [polls, setPolls] = useState<Poll[]>(mockPolls);
  const [selectedPollId, setSelectedPollId] = useState<string | null>(null);
  const [lastPollCreated, setLastPollCreated] = useState<Date | null>(null);
  const [pollSearchTerm, setPollSearchTerm] = useState("");
  const [pollCategoryFilter, setPollCategoryFilter] = useState("all");
  
  // Petition state
  const [petitionView, setPetitionView] = useState<'list' | 'view' | 'create'>('list');
  const [petitions, setPetitions] = useState<Petition[]>(mockPetitions);
  const [selectedPetitionId, setSelectedPetitionId] = useState<string | null>(null);
  const [lastPetitionCreated, setLastPetitionCreated] = useState<Date | null>(null);
  const [petitionSearchTerm, setPetitionSearchTerm] = useState("");
  
  // Discussion state (existing)
  const [discussionView, setDiscussionView] = useState<'dashboard' | 'topic' | 'create'>('dashboard');
  const [topics, setTopics] = useState(mockTopics);
  const [selectedTopicId, setSelectedTopicId] = useState<string | null>(null);
  
  // User profile
  const [userProfile, setUserProfile] = useState(mockUserProfile);

  // Badge levels
  const badgeLevels: BadgeLevel[] = [
    { id: 1, name: "Newcomer", icon: User, color: "bg-gray-100 text-gray-800", minPoints: 0, maxPoints: 10 },
    { id: 2, name: "Contributor", icon: Star, color: "bg-blue-100 text-blue-800", minPoints: 11, maxPoints: 50 },
    { id: 3, name: "Active Member", icon: Award, color: "bg-green-100 text-green-800", minPoints: 51, maxPoints: 150 },
    { id: 4, name: "Community Leader", icon: Trophy, color: "bg-purple-100 text-purple-800", minPoints: 151, maxPoints: 500 },
    { id: 5, name: "Expert", icon: Crown, color: "bg-yellow-100 text-yellow-800", minPoints: 501, maxPoints: Infinity }
  ];

  const calculateLevel = (points: number): BadgeLevel => {
    return badgeLevels.find(level => points >= level.minPoints && points <= level.maxPoints) || badgeLevels[0];
  };

  const currentLevel = calculateLevel(userProfile.activityPoints);
  const IconComponent = currentLevel.icon;

  // Poll functions
  const canCreatePoll = () => {
    if (!lastPollCreated) return true;
    const daysSinceLastPoll = (Date.now() - lastPollCreated.getTime()) / (1000 * 60 * 60 * 24);
    return daysSinceLastPoll >= 7;
  };

  const getNextPollDate = () => {
    if (!lastPollCreated) return undefined;
    const nextDate = new Date(lastPollCreated);
    nextDate.setDate(nextDate.getDate() + 7);
    return nextDate.toLocaleDateString();
  };

  const handleCreatePoll = (pollData: any) => {
    const newPoll: Poll = {
      id: `p${Date.now()}`,
      ...pollData,
      options: pollData.options.map((text: string, index: number) => ({
        id: `o${Date.now()}_${index}`,
        text,
        votes: 0
      })),
      author: userProfile.nickname,
      createdAt: "Just now",
      totalVotes: 0,
      hasVoted: false,
      isExpired: false
    };

    setPolls(prev => [newPoll, ...prev]);
    setLastPollCreated(new Date());
    setPollView('list');
  };

  const handleVotePoll = (pollId: string, optionId: string) => {
    setPolls(prev => prev.map(poll => {
      if (poll.id === pollId && !poll.hasVoted && !poll.isExpired) {
        return {
          ...poll,
          options: poll.options.map(opt => 
            opt.id === optionId ? { ...opt, votes: opt.votes + 1 } : opt
          ),
          totalVotes: poll.totalVotes + 1,
          hasVoted: true
        };
      }
      return poll;
    }));
  };

  const handleRatePollUsefulness = (pollId: string, isUseful: boolean) => {
    // In a real app, this would update the usefulness score
    console.log(`Poll ${pollId} rated as ${isUseful ? 'useful' : 'not useful'}`);
  };

  // Petition functions
  const canCreatePetition = () => {
    if (!lastPetitionCreated) return true;
    const now = new Date();
    const lastMonth = lastPetitionCreated.getMonth();
    const currentMonth = now.getMonth();
    return lastMonth !== currentMonth || now.getFullYear() !== lastPetitionCreated.getFullYear();
  };

  const getNextPetitionDate = () => {
    if (!lastPetitionCreated) return undefined;
    const nextDate = new Date(lastPetitionCreated);
    nextDate.setMonth(nextDate.getMonth() + 1);
    nextDate.setDate(1);
    return nextDate.toLocaleDateString();
  };

  const handleCreatePetition = (petitionData: any) => {
    const newPetition: Petition = {
      id: `pet${Date.now()}`,
      ...petitionData,
      author: userProfile.nickname,
      createdAt: "Just now",
      supportCount: 0,
      hasSupported: false,
      attachmentCount: petitionData.attachments?.length || 0,
      commentCount: 0,
      attachments: petitionData.attachments?.map((file: File, index: number) => ({
        id: `a${Date.now()}_${index}`,
        name: file.name,
        type: file.type,
        size: `${(file.size / 1024).toFixed(1)} KB`,
        url: "#"
      })) || [],
      supporters: []
    };

    setPetitions(prev => [newPetition, ...prev]);
    setLastPetitionCreated(new Date());
    setPetitionView('list');
  };

  const handleSupportPetition = (petitionId: string, comment?: string) => {
    setPetitions(prev => prev.map(petition => {
      if (petition.id === petitionId && !petition.hasSupported) {
        return {
          ...petition,
          supportCount: petition.supportCount + 1,
          hasSupported: true,
          supporters: [
            {
              id: `s${Date.now()}`,
              nickname: userProfile.nickname,
              comment,
              supportedAt: "Just now"
            },
            ...petition.supporters
          ]
        };
      }
      return petition;
    }));
  };

  // Discussion functions (existing)
  const handleVote = (topicId: string) => {
    setTopics(prev => prev.map(topic => 
      topic.id === topicId ? { ...topic, votes: topic.votes + 1 } : topic
    ));
  };

  const selectedPoll = selectedPollId ? polls.find(p => p.id === selectedPollId) : null;
  const selectedPetition = selectedPetitionId ? petitions.find(p => p.id === selectedPetitionId) : null;
  const selectedTopic = selectedTopicId ? topics.find(t => t.id === selectedTopicId) : null;

  const activePolls = polls.filter(p => !p.isExpired);
  const archivedPolls = polls.filter(p => p.isExpired);

  const filteredPolls = activePolls.filter(poll => {
    const matchesSearch = poll.title.toLowerCase().includes(pollSearchTerm.toLowerCase());
    const matchesCategory = pollCategoryFilter === "all" || poll.category === pollCategoryFilter;
    return matchesSearch && matchesCategory;
  });

  const filteredPetitions = petitions.filter(petition =>
    petition.title.toLowerCase().includes(petitionSearchTerm.toLowerCase()) ||
    petition.description.toLowerCase().includes(petitionSearchTerm.toLowerCase())
  );

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
                  onClick={() => setMainView('user-dashboard')}
                  className="flex items-center gap-2 bg-gray-200 hover:bg-gray-300"
                >
                  <LayoutDashboard className="h-4 w-4" />
                  My Dashboard
                </Button>
                {userProfile.activityPoints >= 500 && (
                  <Button 
                    variant="ghost" 
                    onClick={() => setMainView('admin-dashboard')}
                    className="flex items-center gap-2 text-blue-600"
                  >
                    <Shield className="h-4 w-4" />
                    Moderation
                  </Button>
                )}
                
                
              </div>
            </div>

            {/* Main Tabs */}
            <Tabs value={mainTab} onValueChange={(v: any) => setMainTab(v)}>
              <div className="bg-white rounded-lg px-6 py-2 shadow-sm mb-6">
                <TabsList className="w-full flex bg-muted border-b-0">
                  <TabsTrigger 
                    value="discussions" 
                    className="flex-1 flex items-center justify-center gap-2"
                  >
                    <MessageCircle className="h-4 w-4" />
                    Forum
                  </TabsTrigger>
                  <TabsTrigger 
                    value="polls" 
                    className="flex-1 flex items-center justify-center gap-2"
                  >
                    <BarChart3 className="h-4 w-4" />
                    Polls ({activePolls.length})
                  </TabsTrigger>
                  <TabsTrigger 
                    value="petitions" 
                    className="flex-1 flex items-center justify-center gap-2"
                  >
                    <FileText className="h-4 w-4" />
                    Petitions ({petitions.length})
                  </TabsTrigger>
                </TabsList>
              </div>

              {/* Discussions Tab */}
              <TabsContent value="discussions" className="mt-0">
                <ForumManager
                  currentUserId={userProfile.id}
                  currentUserNickname={userProfile.nickname}
                  isVerifiedMentor={userProfile.activityPoints >= 200}
                  isAdmin={userProfile.activityPoints >= 500}
                  onPollClick={(pollId) => {
                    setSelectedPollId(pollId);
                    setMainTab('polls');
                    setPollView('vote');
                  }}
                  onPetitionClick={(petitionId) => {
                    setSelectedPetitionId(petitionId);
                    setMainTab('petitions');
                    setPetitionView('view');
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
                        <Button onClick={() => setPollView('archive')} variant="outline">
                          <Archive className="h-4 w-4 mr-2" />
                          Archive ({archivedPolls.length})
                        </Button>
                        <Button onClick={() => setPollView('create')}>
                          <Plus className="h-4 w-4 mr-2" />
                          Create Poll
                        </Button>
                      </div>
                    </div>

                    <div className="flex gap-4 items-center">
                      <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                        <Input
                          placeholder="Search polls..."
                          value={pollSearchTerm}
                          onChange={(e) => setPollSearchTerm(e.target.value)}
                          className="pl-10"
                        />
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

                    {filteredPolls.length === 0 ? (
                      <div className="text-center py-12">
                        <p className="text-muted-foreground">No active polls found</p>
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredPolls.map(poll => (
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

                {pollView === 'vote' && selectedPoll && (
                  <PollVoteView
                    poll={selectedPoll}
                    onBack={() => setPollView('list')}
                    onVote={handleVotePoll}
                    onRateUsefulness={handleRatePollUsefulness}
                  />
                )}

                {pollView === 'create' && (
                  <CreatePollForm
                    onBack={() => setPollView('list')}
                    onCreatePoll={handleCreatePoll}
                    canCreatePoll={canCreatePoll()}
                    nextAvailableDate={getNextPollDate()}
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
                      <Button onClick={() => setPetitionView('create')}>
                        <Plus className="h-4 w-4 mr-2" />
                        Create Petition
                      </Button>
                    </div>

                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                      <Input
                        placeholder="Search petitions..."
                        value={petitionSearchTerm}
                        onChange={(e) => setPetitionSearchTerm(e.target.value)}
                        className="pl-10"
                      />
                    </div>

                    {filteredPetitions.length === 0 ? (
                      <div className="text-center py-12">
                        <p className="text-muted-foreground">No petitions found</p>
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredPetitions.map(petition => (
                          <PetitionCard
                            key={petition.id}
                            petition={petition}
                            onViewPetition={(id) => {
                              setSelectedPetitionId(id);
                              setPetitionView('view');
                            }}
                          />
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {petitionView === 'view' && selectedPetition && (
                  <PetitionView
                    petition={selectedPetition}
                    onBack={() => setPetitionView('list')}
                    onSupport={handleSupportPetition}
                  />
                )}

                {petitionView === 'create' && (
                  <CreatePetitionForm
                    onBack={() => setPetitionView('list')}
                    onCreatePetition={handleCreatePetition}
                    canCreatePetition={canCreatePetition()}
                    nextAvailableDate={getNextPetitionDate()}
                  />
                )}
              </TabsContent>
            </Tabs>
          </div>
        )}

        {mainView === 'profile' && (
          <MyPage
            userProfile={userProfile}
            onUpdateProfile={(nickname) => setUserProfile(prev => ({ ...prev, nickname }))}
            onBack={() => setMainView('main')}
          />
        )}

        {mainView === 'community' && (
          <DesigningCommunity
            onBack={() => setMainView('main')}
          />
        )}

        {mainView === 'user-dashboard' && (
          <UserDashboardPage
            userId={userProfile.id}
            userNickname={userProfile.nickname}
            onBack={() => setMainView('main')}
            onPostClick={(postId) => {
              setMainView('main');
              setMainTab('discussions');
            }}
            onSwitchToAdmin={() => setMainView('admin-dashboard')}
          />
        )}

        {mainView === 'admin-dashboard' && (
          <AdminDashboardPage
            adminId={userProfile.id}
            adminNickname={userProfile.nickname}
            onBack={() => setMainView('main')}
            onViewContent={(contentId, contentType) => {
              setMainView('main');
              setMainTab('discussions');
            }}
            onSwitchToUser={() => setMainView('user-dashboard')}
          />
        )}
      </div>
    </div>
  );
}