import { useState } from "react";
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
  Share2,
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
  type: "post" | "comment" | "answer" | "upvote" | "mention" | "accepted";
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
}

export function UserDashboard({ userId, userNickname, onPostClick }: UserDashboardProps) {
  const [filterCategory, setFilterCategory] = useState<string>("all");
  const [filterTag, setFilterTag] = useState<string>("all");
  const [activeTab, setActiveTab] = useState<string>("forum");
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

  // Mock user stats
  const stats: UserStats = {
    postsCreated: 12,
    commentsPosted: 45,
    answersGiven: 18,
    upvotesReceived: 234,
    acceptedAnswers: 5,
    totalViews: 3456
  };

  // Mock activities
  const activities: UserActivity[] = [
    {
      id: "1",
      type: "accepted",
      title: "Your answer was accepted",
      description: "Your answer on 'How to implement binary search?' was marked as the best answer",
      timestamp: "2 hours ago",
      postId: "post1",
      category: "Computer Science",
      isUnread: true
    },
    {
      id: "2",
      type: "upvote",
      title: "Your post received 10 upvotes",
      description: "Your post 'Understanding React Hooks' is trending",
      timestamp: "5 hours ago",
      postId: "post2",
      category: "Programming",
      isUnread: true
    },
    {
      id: "3",
      type: "mention",
      title: "You were mentioned",
      description: "Sarah Chen mentioned you in a discussion about Database Design",
      timestamp: "1 day ago",
      postId: "post3",
      category: "Database",
      isUnread: false
    },
    {
      id: "4",
      type: "comment",
      title: "New reply on your post",
      description: "Mike Johnson replied to your post about Algorithm Complexity",
      timestamp: "2 days ago",
      postId: "post4",
      category: "Computer Science",
      isUnread: false
    }
  ];

  // Mock poll participation
  const pollParticipation: PollParticipation[] = [
    {
      id: "p1",
      title: "Should the library extend hours during finals?",
      category: "Campus Life",
      myVote: "Yes, 24/7 access",
      totalVotes: 1247,
      expiresAt: "2 days",
      status: "active",
      isBookmarked: true,
      createdByMe: false,
      results: [
        { option: "Yes, 24/7 access", votes: 789, percentage: 63 },
        { option: "Yes, but until midnight", votes: 312, percentage: 25 },
        { option: "No, keep current hours", votes: 146, percentage: 12 }
      ]
    },
    {
      id: "p2",
      title: "Preferred exam format for online courses",
      category: "Academics",
      myVote: "Take-home assignments",
      totalVotes: 856,
      expiresAt: "5 days",
      status: "active",
      isBookmarked: false,
      createdByMe: true,
      results: [
        { option: "Take-home assignments", votes: 428, percentage: 50 },
        { option: "Proctored online exams", votes: 257, percentage: 30 },
        { option: "In-person exams", votes: 171, percentage: 20 }
      ]
    },
    {
      id: "p3",
      title: "Campus dining improvements priority",
      category: "Campus Life",
      totalVotes: 634,
      expiresAt: "Expired 1 day ago",
      status: "expired",
      isBookmarked: false,
      createdByMe: false,
      results: [
        { option: "More vegan options", votes: 254, percentage: 40 },
        { option: "Extended hours", votes: 228, percentage: 36 },
        { option: "Lower prices", votes: 152, percentage: 24 }
      ]
    }
  ];

  // Mock petition participation
  const petitionParticipation: PetitionParticipation[] = [
    {
      id: "pt1",
      title: "Implement Mental Health Days for Students",
      description: "Allow students 3 mental health days per semester without penalty",
      category: "Student Welfare",
      supporters: 2847,
      goal: 3000,
      expiresAt: "7 days",
      status: "active",
      iSupported: true,
      isBookmarked: true,
      createdByMe: false
    },
    {
      id: "pt2",
      title: "Free Printing Credits for All Students",
      description: "Provide 500 free printing pages per semester",
      category: "Campus Life",
      supporters: 1523,
      goal: 2000,
      expiresAt: "14 days",
      status: "active",
      iSupported: true,
      isBookmarked: false,
      createdByMe: true
    },
    {
      id: "pt3",
      title: "Green Campus Initiative",
      description: "Install solar panels and reduce campus carbon footprint",
      category: "Environment",
      supporters: 3124,
      goal: 3000,
      expiresAt: "Achieved",
      status: "successful",
      iSupported: true,
      isBookmarked: true,
      createdByMe: false
    }
  ];

  // Top 3 Campus Voices (by total votes and interactions)
  const topCampusVoices: CampusVoice[] = [
    {
      id: "v1",
      title: "Should the library extend hours during finals?",
      type: "poll",
      totalVotes: 1247,
      totalInteractions: 2456,
      author: "Sarah Chen",
      authorAvatar: "SC"
    },
    {
      id: "v2",
      title: "Implement Mental Health Days for Students",
      type: "petition",
      totalVotes: 2847,
      totalInteractions: 3421,
      author: "Mike Johnson",
      authorAvatar: "MJ"
    },
    {
      id: "v3",
      title: "Green Campus Initiative",
      type: "petition",
      totalVotes: 3124,
      totalInteractions: 4567,
      author: "Emma Davis",
      authorAvatar: "ED"
    }
  ];

  // Top 3 Campus Concerns (by week-over-week increase)
  const topCampusConcerns: CampusConcern[] = [
    {
      id: "c1",
      title: "Campus Safety Improvements Needed",
      type: "petition",
      currentParticipants: 1845,
      weekOverWeekIncrease: 156,
      category: "Safety"
    },
    {
      id: "c2",
      title: "Affordable Housing Near Campus",
      type: "poll",
      currentParticipants: 1234,
      weekOverWeekIncrease: 142,
      category: "Housing"
    },
    {
      id: "c3",
      title: "Improve Campus WiFi Infrastructure",
      type: "petition",
      currentParticipants: 987,
      weekOverWeekIncrease: 128,
      category: "Technology"
    }
  ];

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
          <TabsList className="grid w-full grid-cols-4 mb-6">
            <TabsTrigger value="forum">
              <MessageCircle className="h-4 w-4 mr-2" />
              Forum
            </TabsTrigger>
            <TabsTrigger value="polls">
              <BarChart3 className="h-4 w-4 mr-2" />
              Polls
            </TabsTrigger>
            <TabsTrigger value="petition">
              <FileText className="h-4 w-4 mr-2" />
              Petitions
            </TabsTrigger>
            <TabsTrigger value="settings">
              <Settings className="h-4 w-4 mr-2" />
              Settings
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
                  <SelectTrigger className="w-[200px]">
                    <Filter className="h-4 w-4 mr-2" />
                    <SelectValue placeholder="All Categories" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Categories</SelectItem>
                    <SelectItem value="Computer Science">Computer Science</SelectItem>
                    <SelectItem value="Programming">Programming</SelectItem>
                    <SelectItem value="Database">Database</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-4">
                {filteredActivities.map((activity) => (
                  <div
                    key={activity.id}
                    className={`flex gap-4 p-4 rounded-lg border-2 cursor-pointer hover:border-[#ff6934] transition-colors ${
                      activity.isUnread ? "bg-blue-50 border-blue-200" : "bg-white border-gray-200"
                    }`}
                    onClick={() => onPostClick(activity.postId)}
                  >
                    <div className={`w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 ${getActivityColor(activity.type)}`}>
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
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                  <div className="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <Bookmark className="h-5 w-5 text-yellow-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{pollParticipation.filter(p => p.isBookmarked).length}</p>
                    <p className="text-xs text-gray-600">Bookmarked</p>
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
                      <Button size="sm" variant="outline">
                        <Eye className="h-4 w-4 mr-2" />
                        View Details
                      </Button>
                      {poll.status === "active" && !poll.myVote && (
                        <Button size="sm" className="bg-[#ff6934] hover:bg-[#ff7a47]">
                          <ThumbsUp className="h-4 w-4 mr-2" />
                          Vote Now
                        </Button>
                      )}
                      <Button size="sm" variant="outline">
                        <Share2 className="h-4 w-4 mr-2" />
                        Share
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            </Card>
          </TabsContent>

          {/* PETITION TAB */}
          <TabsContent value="petition" className="space-y-6">
            {/* Petition Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                  <div className="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <Bookmark className="h-5 w-5 text-yellow-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitionParticipation.filter(p => p.isBookmarked).length}</p>
                    <p className="text-xs text-gray-600">Bookmarked</p>
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
                    <div key={petition.id} className="p-4 border-2 rounded-lg hover:border-[#ff6934] transition-colors">
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
                        <Button size="sm" variant="outline">
                          <Eye className="h-4 w-4 mr-2" />
                          View Details
                        </Button>
                        {petition.status === "active" && !petition.iSupported && (
                          <Button size="sm" className="bg-[#ff6934] hover:bg-[#ff7a47]">
                            <CheckCircle2 className="h-4 w-4 mr-2" />
                            Support Now
                          </Button>
                        )}
                        <Button size="sm" variant="outline">
                          <Share2 className="h-4 w-4 mr-2" />
                          Share
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            </Card>
          </TabsContent>

          {/* SETTINGS TAB */}
          <TabsContent value="settings" className="space-y-6">
            {/* Profile Card - Centered with max width */}
            <div className="max-w-2xl mx-auto">
              <Card>
                <CardHeader>
                  <CardTitle>Profile Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="flex flex-col items-center space-y-4">
                    <Avatar className="h-20 w-20">
                      <AvatarFallback className="text-xl">
                        {nickname.charAt(0).toUpperCase()}
                      </AvatarFallback>
                    </Avatar>
                    
                    {isEditing ? (
                      <div className="space-y-3 w-full max-w-md">
                        <div className="space-y-2">
                          <Label htmlFor="nickname">Nickname</Label>
                          <Input
                            id="nickname"
                            value={nickname}
                            onChange={(e) => setNickname(e.target.value)}
                            placeholder="Enter your nickname"
                            maxLength={20}
                          />
                        </div>
                        <div className="flex gap-2">
                          <Button onClick={handleSave} size="sm" className="flex-1">
                            Save
                          </Button>
                          <Button onClick={handleCancel} variant="outline" size="sm" className="flex-1">
                            Cancel
                          </Button>
                        </div>
                      </div>
                    ) : (
                      <div className="text-center space-y-2">
                        <h3 className="text-lg">{nickname}</h3>
                        <Button onClick={() => setIsEditing(true)} variant="outline" size="sm">
                          <Edit3 className="h-4 w-4 mr-2" />
                          Edit Nickname
                        </Button>
                      </div>
                    )}
                  </div>

                  <Separator />

                  <div className="space-y-2 max-w-md mx-auto">
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">Member since</span>
                      <span>January 2024</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">User ID</span>
                      <span className="font-mono text-xs">#{userId}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}