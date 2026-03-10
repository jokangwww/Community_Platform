import { useState, useEffect, useCallback } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../ui/tabs";
import { Input } from "../ui/input";
import { Label } from "../ui/label";
import { Separator } from "../ui/separator";
import {
  MessageCircle,
  Heart,
  CheckCircle2,
  Bell,
  TrendingUp,
  FileText,
  Award,
  Eye,
  ArrowUpCircle,
  AtSign,
  Filter,
  BarChart3,
  Clock,
  Users,
  ThumbsUp,
  Bookmark,
  Calendar,
  AlertCircle,
  Flame,
  Trophy,
  Settings,
  Edit3,
  User
} from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";

interface UserActivity {
  id: string;
  type: "post" | "comment" | "answer" | "upvote" | "mention" | "accepted" | "moderation";
  title: string;
  description: string;
  timestamp: string;
  postId: string;
  category?: string;
  isUnread?: boolean;
}

interface UserStats {
  postsCreated: number;
  commentsPosted: number;
  answersGiven: number;
  upvotesReceived: number;
  acceptedAnswers: number;
  totalViews: number;
}

interface PollParticipation {
  id: string;
  title: string;
  category: string;
  myVote?: string;
  totalVotes: number;
  expiresAt: string;
  status: "active" | "expired" | "closed";
  isBookmarked: boolean;
  createdByMe: boolean;
  results?: { option: string; votes: number; percentage: number }[];
}

interface PetitionParticipation {
  id: string;
  title: string;
  description: string;
  category: string;
  supporters: number;
  goal: number;
  expiresAt: string;
  status: "active" | "successful" | "expired";
  iSupported: boolean;
  isBookmarked: boolean;
  createdByMe: boolean;
}

interface CampusVoice {
  id: string;
  title: string;
  type: "poll" | "petition";
  totalVotes: number;
  totalInteractions: number;
  author: string;
  authorAvatar: string;
}

interface CampusConcern {
  id: string;
  title: string;
  type: "poll" | "petition";
  currentParticipants: number;
  weekOverWeekIncrease: number;
  category: string;
}

interface UserDashboardProps {
  userId: string;
  userNickname: string;
  onPostClick: (postId: string) => void;
  onPollClick?: (pollId: string) => void;
  onPetitionClick?: (petitionId: string) => void;
}

export function UserDashboard({ userId, userNickname, onPostClick, onPollClick, onPetitionClick }: UserDashboardProps) {
  const [filterCategory, setFilterCategory] = useState<string>("all");
  const [filterTag, setFilterTag] = useState<string>("all");
  const [activeTab, setActiveTab] = useState<string>("forum");
  const [categories, setCategories] = useState<{ id: string; name: string }[]>([]);
  const [isEditing, setIsEditing] = useState(false);
  const [nickname, setNickname] = useState(userNickname);
  const [bio, setBio] = useState("");

  const handleSave = () => {
    if (nickname.trim()) {
      // Handle save logic here
      setIsEditing(false);
    }
  };

  const handleCancel = () => {
    setNickname(userNickname);
    setIsEditing(false);
  };

  // Forum stats from API
  const [stats, setStats] = useState<UserStats>({
    postsCreated: 0,
    commentsPosted: 0,
    answersGiven: 0,
    upvotesReceived: 0,
    acceptedAnswers: 0,
    totalViews: 0
  });
  const [statsLoading, setStatsLoading] = useState(true);

  // Activities from API
  const [activities, setActivities] = useState<UserActivity[]>([]);
  const [activitiesLoading, setActivitiesLoading] = useState(true);

  // Poll & Petition data from API
  const [pollParticipation, setPollParticipation] = useState<PollParticipation[]>([]);
  const [petitionParticipation, setPetitionParticipation] = useState<PetitionParticipation[]>([]);
  const [topCampusVoices, setTopCampusVoices] = useState<CampusVoice[]>([]);
  const [topCampusConcerns, setTopCampusConcerns] = useState<CampusConcern[]>([]);
  const [dashboardLoading, setDashboardLoading] = useState(true);

  const getCsrfToken = useCallback(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }, []);

  // Fetch forum dashboard data (stats + activities)
  const fetchForumDashboard = useCallback(async () => {
    try {
      setStatsLoading(true);
      setActivitiesLoading(true);
      const res = await fetch('/api/forum/dashboard', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      });
      if (res.ok) {
        const data = await res.json();
        if (data.data?.stats) {
          setStats({
            postsCreated: data.data.stats.postsCreated || 0,
            commentsPosted: data.data.stats.commentsPosted || 0,
            answersGiven: data.data.stats.answersGiven || 0,
            upvotesReceived: data.data.stats.upvotesReceived || 0,
            acceptedAnswers: data.data.stats.acceptedAnswers || 0,
            totalViews: data.data.stats.totalViews || 0,
          });
        }
        if (data.data?.activities) {
          setActivities(data.data.activities);
        }
      }
    } catch (err) {
      console.error('Failed to fetch forum dashboard:', err);
    } finally {
      setStatsLoading(false);
      setActivitiesLoading(false);
    }
  }, [getCsrfToken]);

  useEffect(() => {
    fetchForumDashboard();
  }, [fetchForumDashboard]);

  useEffect(() => {
    fetch('/api/forum/categories', {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
    })
      .then(r => r.ok ? r.json() : null)
      .then(data => { if (data?.data) setCategories(data.data); })
      .catch(() => {});
  }, [getCsrfToken]);

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        setDashboardLoading(true);
        const res = await fetch('/api/poll-petition/dashboard', {
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        });
        if (res.ok) {
          const data = await res.json();
          // Merge created + voted polls into flat participation array
          const createdPolls = (data.polls?.created || []).map((p: any) => ({ ...p, createdByMe: true }));
          const votedPolls = (data.polls?.voted || []).map((p: any) => ({ ...p, createdByMe: false }));
          setPollParticipation([...createdPolls, ...votedPolls]);
          // Merge created + supported petitions
          const createdPetitions = (data.petitions?.created || []).map((p: any) => ({ ...p, createdByMe: true }));
          const supportedPetitions = (data.petitions?.supported || []).map((p: any) => ({ ...p, createdByMe: false }));
          setPetitionParticipation([...createdPetitions, ...supportedPetitions]);
          setTopCampusVoices(data.topCampusVoices || []);
          setTopCampusConcerns(data.topCampusConcerns || []);
        }
      } catch (err) {
        console.error('Failed to fetch dashboard data:', err);
      } finally {
        setDashboardLoading(false);
      }
    };
    fetchDashboard();
  }, [getCsrfToken]);

  const getActivityIcon = (type: string) => {
    switch (type) {
      case "post":
        return <FileText className="h-4 w-4" />;
      case "comment":
        return <MessageCircle className="h-4 w-4" />;
      case "answer":
        return <CheckCircle2 className="h-4 w-4" />;
      case "upvote":
        return <ArrowUpCircle className="h-4 w-4" />;
      case "mention":
        return <AtSign className="h-4 w-4" />;
      case "accepted":
        return <Award className="h-4 w-4" />;
      case "moderation":
        return <AlertCircle className="h-4 w-4" />;
      default:
        return <Bell className="h-4 w-4" />;
    }
  };

  const getActivityColor = (type: string) => {
    switch (type) {
      case "post":
        return "bg-blue-100 text-blue-600";
      case "comment":
        return "bg-purple-100 text-purple-600";
      case "answer":
        return "bg-green-100 text-green-600";
      case "upvote":
        return "bg-orange-100 text-orange-600";
      case "mention":
        return "bg-pink-100 text-pink-600";
      case "accepted":
        return "bg-yellow-100 text-yellow-600";
      case "moderation":
        return "bg-red-100 text-red-600";
      default:
        return "bg-gray-100 text-gray-600";
    }
  };

  const filteredActivities = activities.filter(activity => {
    if (filterCategory !== "all" && activity.category !== filterCategory) return false;
    return true;
  });

  return (
    <div className="min-h-screen bg-white py-6">
      <div className="max-w-7xl mx-auto px-6">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold mb-2">My Dashboard</h1>
          <p className="text-gray-600">Track your activity and engagement across the platform</p>
        </div>

        {/* Main Tabs: Forum, Polls, Petition, Settings */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-3 mb-6">
            <TabsTrigger value="forum" className="cursor-pointer">
              <MessageCircle className="h-4 w-4 mr-2" />
              Forum
            </TabsTrigger>
            <TabsTrigger value="polls" className="cursor-pointer">
              <BarChart3 className="h-4 w-4 mr-2" />
              Polls
            </TabsTrigger>
            <TabsTrigger value="petition" className="cursor-pointer">
              <FileText className="h-4 w-4 mr-2" />
              Petitions
            </TabsTrigger>
          </TabsList>

          {/* FORUM TAB */}
          <TabsContent value="forum" className="space-y-6">
            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <FileText className="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.postsCreated}</p>
                    <p className="text-xs text-gray-600">Posts</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <MessageCircle className="h-5 w-5 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.commentsPosted}</p>
                    <p className="text-xs text-gray-600">Comments</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <CheckCircle2 className="h-5 w-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.answersGiven}</p>
                    <p className="text-xs text-gray-600">Answers</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <ArrowUpCircle className="h-5 w-5 text-orange-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.upvotesReceived}</p>
                    <p className="text-xs text-gray-600">Upvotes</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <Award className="h-5 w-5 text-yellow-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.acceptedAnswers}</p>
                    <p className="text-xs text-gray-600">Accepted</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                    <Eye className="h-5 w-5 text-pink-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.totalViews}</p>
                    <p className="text-xs text-gray-600">Views</p>
                  </div>
                </div>
              </Card>
            </div>

            {/* Activity Feed */}
            <Card className="p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-semibold flex items-center gap-2">
                  <Bell className="h-5 w-5" />
                  Recent Activity
                </h2>
                <Select value={filterCategory} onValueChange={setFilterCategory}>
                  <SelectTrigger className="w-[200px] cursor-pointer">
                    <Filter className="h-4 w-4 mr-2" />
                    <SelectValue placeholder="All Categories" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all" className="hover:cursor-pointer">All Categories</SelectItem>
                    <SelectItem value="Moderation" className="hover:cursor-pointer">Moderation</SelectItem>
                    {categories.map(cat => (
                      <SelectItem key={cat.id} value={cat.name} className="hover:cursor-pointer">{cat.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-4">
                {filteredActivities.map((activity) => (
                  <div
                    key={activity.id}
                    className={`flex gap-4 p-4 rounded-lg border-2 ${activity.postId ? 'cursor-pointer hover:border-[#ff6934]' : ''} transition-colors ${
                      activity.type === 'moderation' ? "bg-red-50 border-red-200" :
                      activity.isUnread ? "bg-blue-50 border-blue-200" : "bg-white border-gray-200"
                    }`}
                    onClick={() => activity.postId && onPostClick(activity.postId)}
                  >
                    <div className={`w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 cursor-pointer ${getActivityColor(activity.type)}`}>
                      {getActivityIcon(activity.type)}
                    </div>
                    <div className="flex-1">
                      <div className="flex items-start justify-between mb-1">
                        <h3 className="font-semibold text-sm">{activity.title}</h3>
                        {activity.isUnread && (
                          <Badge className="bg-blue-500 text-white text-xs">New</Badge>
                        )}
                      </div>
                      <p className="text-sm text-gray-600 mb-2">{activity.description}</p>
                      <div className="flex items-center gap-3 text-xs text-gray-500">
                        <span>{activity.timestamp}</span>
                        {activity.category && (
                          <>
                            <span>•</span>
                            <span>{activity.category}</span>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </Card>
          </TabsContent>

          {/* POLLS TAB */}
          <TabsContent value="polls" className="space-y-6">
            {/* Polls Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <BarChart3 className="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{pollParticipation.filter(p => p.createdByMe).length}</p>
                    <p className="text-xs text-gray-600">Created</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <ThumbsUp className="h-5 w-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{pollParticipation.filter(p => p.myVote).length}</p>
                    <p className="text-xs text-gray-600">Voted</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <Users className="h-5 w-5 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">
                      {pollParticipation.reduce((sum, p) => sum + p.totalVotes, 0)}
                    </p>
                    <p className="text-xs text-gray-600">Total Votes</p>
                  </div>
                </div>
              </Card>
            </div>

            {/* Top 3 Campus Voices */}
            

            {/* Top 3 Campus Concerns */}
            

            {/* My Poll Participation */}
            <Card className="p-6">
              <h2 className="text-xl font-semibold mb-4">My Poll Participation</h2>
              <div className="space-y-4">
                {pollParticipation.map((poll) => (
                  <div key={poll.id} className="p-4 border-2 rounded-lg hover:border-[#ff6934] transition-colors">
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <h3 className="font-semibold">{poll.title}</h3>
                          {poll.createdByMe && (
                            <Badge className="bg-blue-500 text-white text-xs">Created by me</Badge>
                          )}
                          {poll.isBookmarked && (
                            <Bookmark className="h-4 w-4 text-yellow-500 fill-yellow-500" />
                          )}
                        </div>
                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                          <Badge variant="outline">{poll.category}</Badge>
                          <span>•</span>
                          <Users className="h-3 w-3" />
                          <span>{poll.totalVotes} votes</span>
                          <span>•</span>
                          <Clock className="h-3 w-3" />
                          <span className={poll.status === "expired" ? "text-red-600" : "text-green-600"}>
                            {poll.status === "active" ? `Expires in ${poll.expiresAt}` : poll.expiresAt}
                          </span>
                        </div>
                        {poll.myVote && (
                          <div className="mb-3">
                            <Badge className="bg-green-100 text-green-700">
                              <CheckCircle2 className="h-3 w-3 mr-1" />
                              You voted: {poll.myVote}
                            </Badge>
                          </div>
                        )}
                      </div>
                      <Badge className={
                        poll.status === "active" ? "bg-green-100 text-green-700" :
                        poll.status === "expired" ? "bg-gray-100 text-gray-700" :
                        "bg-blue-100 text-blue-700"
                      }>
                        {poll.status}
                      </Badge>
                    </div>

                    {/* Results */}
                    {poll.results && (
                      <div className="space-y-2 mb-3">
                        {poll.results.map((result, idx) => (
                          <div key={idx}>
                            <div className="flex items-center justify-between text-sm mb-1">
                              <span className="font-medium">{result.option}</span>
                              <span className="text-gray-600">{result.votes} votes ({result.percentage}%)</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                              <div
                                className="bg-[#ff6934] rounded-full h-2"
                                style={{ width: `${result.percentage}%` }}
                              />
                            </div>
                          </div>
                        ))}
                      </div>
                    )}

                    <div className="flex gap-2">
                      <Button size="sm" variant="outline" className="cursor-pointer" onClick={() => onPollClick?.(poll.id)}>
                        <Eye className="h-4 w-4 mr-2" />
                        View Details
                      </Button>
                      {poll.status === "active" && !poll.myVote && (
                        <Button size="sm" className="bg-[#ff6934] hover:bg-[#ff7a47] cursor-pointer" onClick={() => onPollClick?.(poll.id)}>
                          <ThumbsUp className="h-4 w-4 mr-2" />
                          Vote Now
                        </Button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </Card>
          </TabsContent>

          {/* PETITION TAB */}
          <TabsContent value="petition" className="space-y-6 cursor-pointer">
            {/* Petition Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <FileText className="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitionParticipation.filter(p => p.createdByMe).length}</p>
                    <p className="text-xs text-gray-600">Created</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <CheckCircle2 className="h-5 w-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitionParticipation.filter(p => p.iSupported).length}</p>
                    <p className="text-xs text-gray-600">Supported</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <TrendingUp className="h-5 w-5 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitionParticipation.filter(p => p.status === "successful").length}</p>
                    <p className="text-xs text-gray-600">Successful</p>
                  </div>
                </div>
              </Card>
            </div>

            {/* My Petition Participation */}
            <Card className="p-6">
              <h2 className="text-xl font-semibold mb-4">My Petition Participation</h2>
              <div className="space-y-4">
                {petitionParticipation.map((petition) => {
                  const progress = (petition.supporters / petition.goal) * 100;
                  return (
                    <div key={petition.id} className="p-4 border-2 rounded-lg hover:border-[#ff6934] transition-colors cursor-pointer">
                      <div className="flex items-start justify-between mb-3">
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-2">
                            <h3 className="font-semibold">{petition.title}</h3>
                            {petition.createdByMe && (
                              <Badge className="bg-blue-500 text-white text-xs">Created by me</Badge>
                            )}
                            {petition.isBookmarked && (
                              <Bookmark className="h-4 w-4 text-yellow-500 fill-yellow-500" />
                            )}
                          </div>
                          <p className="text-sm text-gray-600 mb-3">{petition.description}</p>
                          <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                            <Badge variant="outline">{petition.category}</Badge>
                            {petition.iSupported && (
                              <Badge className="bg-green-100 text-green-700">
                                <CheckCircle2 className="h-3 w-3 mr-1" />
                                You supported this
                              </Badge>
                            )}
                          </div>
                        </div>
                        <Badge className={
                          petition.status === "active" ? "bg-blue-100 text-blue-700" :
                          petition.status === "successful" ? "bg-green-100 text-green-700" :
                          "bg-gray-100 text-gray-700"
                        }>
                          {petition.status}
                        </Badge>
                      </div>

                      {/* Progress Bar */}
                      <div className="mb-3">
                        <div className="flex items-center justify-between text-sm mb-2">
                          <span className="font-semibold">{petition.supporters.toLocaleString()} supporters</span>
                          <span className="text-gray-600">Goal: {petition.goal.toLocaleString()}</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-3">
                          <div
                            className={`rounded-full h-3 ${
                              petition.status === "successful" ? "bg-green-500" : "bg-[#ff6934]"
                            }`}
                            style={{ width: `${Math.min(progress, 100)}%` }}
                          />
                        </div>
                        <div className="flex items-center justify-between text-xs text-gray-600 mt-1">
                          <span>{progress.toFixed(1)}% complete</span>
                          {petition.status === "active" && (
                            <span className="flex items-center gap-1">
                              <Clock className="h-3 w-3" />
                              Expires in {petition.expiresAt}
                            </span>
                          )}
                          {petition.status === "successful" && (
                            <span className="text-green-600 font-semibold">✓ Goal Achieved!</span>
                          )}
                        </div>
                      </div>

                      <div className="flex gap-2">
                        <Button size="sm" variant="outline" className="cursor-pointer" onClick={() => onPetitionClick?.(petition.id)}>
                          <Eye className="h-4 w-4 mr-2" />
                          View Details
                        </Button>
                        {petition.status === "active" && !petition.iSupported && (
                          <Button size="sm" className="bg-[#ff6934] hover:bg-[#ff7a47] cursor-pointer" onClick={() => onPetitionClick?.(petition.id)}>
                            <CheckCircle2 className="h-4 w-4 mr-2" />
                            Support Now
                          </Button>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </Card>
          </TabsContent>

          {/* SETTINGS TAB */}
          {/* <TabsContent value="settings" className="space-y-6 cursor-pointer"> */}
            {/* Profile Card - Centered with max width */}
            {/* <div className="max-w-2xl mx-auto cursor-pointer">
              <Card>
                <CardHeader>
                  <CardTitle>Profile Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6 cursor-pointer">
                  <div className="flex flex-col items-center space-y-4 cursor-pointer">
                    <Avatar className="h-20 w-20 cursor-pointer">
                      <AvatarFallback className="text-xl cursor-pointer">
                        {nickname.charAt(0).toUpperCase()}
                      </AvatarFallback>
                    </Avatar>
                    
                    {isEditing ? (
                      <div className="space-y-3 w-full max-w-md cursor-pointer">
                        <div className="space-y-2 cursor-pointer">
                          <Label htmlFor="nickname">Nickname</Label>
                          <Input
                            id="nickname"
                            value={nickname}
                            onChange={(e) => setNickname(e.target.value)}
                            placeholder="Enter your nickname"
                            maxLength={20}
                          />
                        </div>
                        <div className="flex gap-2 cursor-pointer">
                          <Button onClick={handleSave} size="sm" className="flex-1 cursor-pointer">
                            Save
                          </Button>
                          <Button onClick={handleCancel} variant="outline" size="sm" className="flex-1 cursor-pointer">
                            Cancel
                          </Button>
                        </div>
                      </div>
                    ) : (
                      <div className="text-center space-y-2 cursor-pointer">
                        <h3 className="text-lg cursor-pointer">{nickname}</h3>
                        <Button onClick={() => setIsEditing(true)} variant="outline" size="sm">
                          <Edit3 className="h-4 w-4 mr-2 cursor-pointer" />
                          Edit Nickname
                        </Button>
                      </div>
                    )}
                  </div>

                  <Separator />

                  <div className="space-y-2 max-w-md mx-auto cursor-pointer">
                    <div className="flex justify-between text-sm cursor-pointer">
                      <span className="text-muted-foreground cursor-pointer">Member since</span>
                      <span>January 2024</span>
                    </div>
                    <div className="flex justify-between text-sm cursor-pointer">
                      <span className="text-muted-foreground cursor-pointer">User ID</span>
                      <span className="font-mono text-xs cursor-pointer">#{userId}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent> */}
        </Tabs>
      </div>
    </div>
  );
}