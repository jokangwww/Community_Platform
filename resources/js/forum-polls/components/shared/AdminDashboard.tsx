import { useState } from "react";
import { Card } from "../ui/card";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../ui/tabs";
import {
  AlertTriangle,
  Ban,
  CheckCircle,
  Download,
  Eye,
  Flag,
  MessageSquare,
  Shield,
  Trash2,
  UserX,
  XCircle,
  FileText,
  BarChart3,
  Clock,
  Users,
  Calendar,
  TrendingDown,
  AlertCircle,
  Edit,
  CheckCircle2,
  PlayCircle,
  PauseCircle
} from "lucide-react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../ui/select";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "../ui/dialog";
import { Textarea } from "../ui/textarea";
import { Input } from "../ui/input";

interface ReportedContent {
  id: string;
  contentId: string;
  contentType: "post" | "comment" | "answer";
  reason: string;
  details: string;
  reportedBy: string;
  reportedByAvatar: string;
  reportedAt: string;
  status: "pending" | "reviewed" | "resolved" | "dismissed";
  contentPreview: string;
  contentAuthor: string;
  priority: "low" | "medium" | "high";
}

interface ModStats {
  pendingReports: number;
  resolvedToday: number;
  totalUsers: number;
  mutedUsers: number;
  deletedContent: number;
  warningsIssued: number;
}

interface AdminPoll {
  id: string;
  title: string;
  category: string;
  creator: string;
  creatorAvatar: string;
  totalVotes: number;
  participation: number; // percentage
  expiresAt: string;
  createdAt: string;
  status: "active" | "expired" | "closed";
  isOfficial: boolean;
  hasDisputes: boolean;
  disputeCount?: number;
}

interface AdminPetition {
  id: string;
  title: string;
  description: string;
  category: string;
  creator: string;
  creatorAvatar: string;
  supporters: number;
  goal: number;
  participation: number; // percentage
  expiresAt: string;
  createdAt: string;
  status: "active" | "successful" | "expired" | "closed";
  isOfficial: boolean;
  hasDisputes: boolean;
  disputeCount?: number;
}

interface AdminDashboardProps {
  adminId: string;
  onViewContent: (contentId: string, contentType: string) => void;
}

export function AdminDashboard({ adminId, onViewContent }: AdminDashboardProps) {
  const [selectedReport, setSelectedReport] = useState<ReportedContent | null>(null);
  const [actionDialog, setActionDialog] = useState<{
    isOpen: boolean;
    action: "delete" | "warn" | "mute" | "restore" | null;
  }>({ isOpen: false, action: null });
  const [actionNote, setActionNote] = useState("");
  const [filterStatus, setFilterStatus] = useState<string>("all");
  const [activeTab, setActiveTab] = useState<string>("forum");
  
  // Poll/Petition management dialogs
  const [extendDeadlineDialog, setExtendDeadlineDialog] = useState<{
    isOpen: boolean;
    itemId: string;
    type: "poll" | "petition";
  }>({ isOpen: false, itemId: "", type: "poll" });
  const [newDeadline, setNewDeadline] = useState("");
  
  // Analytics preview dialog
  const [analyticsPreview, setAnalyticsPreview] = useState<{
    isOpen: boolean;
    type: "forum" | "polls" | "petitions" | null;
    data: any;
  }>({ isOpen: false, type: null, data: null });
  
  // Mock moderation stats
  const stats: ModStats = {
    pendingReports: 12,
    resolvedToday: 5,
    totalUsers: 1847,
    mutedUsers: 3,
    deletedContent: 28,
    warningsIssued: 15
  };

  // Mock reported content
  const [reports, setReports] = useState<ReportedContent[]>([
    {
      id: "r1",
      contentId: "post123",
      contentType: "post",
      reason: "harassment",
      details: "User is being disrespectful and attacking other students",
      reportedBy: "Sarah Chen",
      reportedByAvatar: "SC",
      reportedAt: "5 minutes ago",
      status: "pending",
      contentPreview: "This is a sample post content that has been reported for review...",
      contentAuthor: "John Doe",
      priority: "high"
    },
    {
      id: "r2",
      contentId: "comment456",
      contentType: "comment",
      reason: "spam",
      details: "Posted the same promotional link multiple times",
      reportedBy: "Mike Johnson",
      reportedByAvatar: "MJ",
      reportedAt: "15 minutes ago",
      status: "pending",
      contentPreview: "Check out this amazing deal at...",
      contentAuthor: "Jane Smith",
      priority: "medium"
    },
    {
      id: "r3",
      contentId: "answer789",
      contentType: "answer",
      reason: "misinformation",
      details: "Contains factually incorrect information about the subject",
      reportedBy: "Alex Kim",
      reportedByAvatar: "AK",
      reportedAt: "1 hour ago",
      status: "pending",
      contentPreview: "The answer provided contains incorrect algorithm complexity analysis...",
      contentAuthor: "Bob Wilson",
      priority: "high"
    },
    {
      id: "r4",
      contentId: "post234",
      contentType: "post",
      reason: "inappropriate",
      details: "Contains inappropriate language",
      reportedBy: "Emma Davis",
      reportedByAvatar: "ED",
      reportedAt: "2 hours ago",
      status: "reviewed",
      contentPreview: "This post contains language that violates community guidelines...",
      contentAuthor: "Chris Brown",
      priority: "medium"
    }
  ]);

  // Mock polls data
  const [polls, setPolls] = useState<AdminPoll[]>([
    {
      id: "p1",
      title: "Should the library extend hours during finals?",
      category: "Campus Life",
      creator: "Sarah Chen",
      creatorAvatar: "SC",
      totalVotes: 1247,
      participation: 68,
      expiresAt: "2024-03-15",
      createdAt: "2024-03-01",
      status: "active",
      isOfficial: false,
      hasDisputes: false
    },
    {
      id: "p2",
      title: "Preferred exam format for online courses",
      category: "Academics",
      creator: "Mike Johnson",
      creatorAvatar: "MJ",
      totalVotes: 856,
      participation: 47,
      expiresAt: "2024-03-20",
      createdAt: "2024-03-05",
      status: "active",
      isOfficial: true,
      hasDisputes: false
    },
    {
      id: "p3",
      title: "Campus parking improvements",
      category: "Campus Life",
      creator: "Alex Kim",
      creatorAvatar: "AK",
      totalVotes: 234,
      participation: 12,
      expiresAt: "2024-03-18",
      createdAt: "2024-03-08",
      status: "active",
      isOfficial: false,
      hasDisputes: true,
      disputeCount: 5
    }
  ]);

  // Mock petitions data
  const [petitions, setPetitions] = useState<AdminPetition[]>([
    {
      id: "pt1",
      title: "Implement Mental Health Days for Students",
      description: "Allow students 3 mental health days per semester without penalty",
      category: "Student Welfare",
      creator: "Emma Davis",
      creatorAvatar: "ED",
      supporters: 2847,
      goal: 3000,
      participation: 95,
      expiresAt: "2024-03-22",
      createdAt: "2024-02-15",
      status: "active",
      isOfficial: false,
      hasDisputes: false
    },
    {
      id: "pt2",
      title: "Free Printing Credits for All Students",
      description: "Provide 500 free printing pages per semester",
      category: "Campus Life",
      creator: "Chris Brown",
      creatorAvatar: "CB",
      supporters: 1523,
      goal: 2000,
      participation: 76,
      expiresAt: "2024-03-28",
      createdAt: "2024-03-01",
      status: "active",
      isOfficial: true,
      hasDisputes: false
    },
    {
      id: "pt3",
      title: "Improve Campus WiFi Infrastructure",
      description: "Upgrade WiFi to support more devices and faster speeds",
      category: "Technology",
      creator: "David Lee",
      creatorAvatar: "DL",
      supporters: 487,
      goal: 1500,
      participation: 32,
      expiresAt: "2024-03-25",
      createdAt: "2024-03-10",
      status: "active",
      isOfficial: false,
      hasDisputes: true,
      disputeCount: 3
    }
  ]);

  const handleAction = async (action: "delete" | "warn" | "mute" | "restore" | "dismiss") => {
    if (!selectedReport) return;

    // Simulate action processing
    console.log(`Action: ${action} on report ${selectedReport.id}`, actionNote);

    // Update report status
    setReports(prev =>
      prev.map(r =>
        r.id === selectedReport.id
          ? { ...r, status: action === "dismiss" ? "dismissed" : "resolved" }
          : r
      )
    );

    // Close dialogs
    setActionDialog({ isOpen: false, action: null });
    setSelectedReport(null);
    setActionNote("");
  };

  const exportAnalytics = (type: "forum" | "polls" | "petitions") => {
    let reportData: any = {
      generatedAt: new Date().toISOString(),
      generatedDate: new Date().toLocaleDateString(),
      generatedTime: new Date().toLocaleTimeString(),
      period: "Last 30 days",
      type: type
    };

    if (type === "forum") {
      reportData = {
        ...reportData,
        stats: stats,
        reports: reports,
        summary: {
          totalReports: reports.length,
          pendingReports: reports.filter(r => r.status === "pending").length,
          resolvedReports: reports.filter(r => r.status === "resolved").length,
          dismissedReports: reports.filter(r => r.status === "dismissed").length
        }
      };
    } else if (type === "polls") {
      reportData = {
        ...reportData,
        polls: polls,
        summary: {
          totalPolls: polls.length,
          activePolls: polls.filter(p => p.status === "active").length,
          averageParticipation: polls.length > 0 ? (polls.reduce((sum, p) => sum + p.participation, 0) / polls.length).toFixed(1) : "0.0",
          totalVotes: polls.reduce((sum, p) => sum + p.totalVotes, 0),
          lowParticipation: polls.filter(p => p.participation < 30).length,
          hasDisputes: polls.filter(p => p.hasDisputes).length
        }
      };
    } else {
      reportData = {
        ...reportData,
        petitions: petitions,
        summary: {
          totalPetitions: petitions.length,
          activePetitions: petitions.filter(p => p.status === "active").length,
          successfulPetitions: petitions.filter(p => p.status === "successful").length,
          averageParticipation: petitions.length > 0 ? (petitions.reduce((sum, p) => sum + p.participation, 0) / petitions.length).toFixed(1) : "0.0",
          totalSupporters: petitions.reduce((sum, p) => sum + p.supporters, 0),
          lowParticipation: petitions.filter(p => p.participation < 30).length,
          hasDisputes: petitions.filter(p => p.hasDisputes).length
        }
      };
    }

    // Show preview dialog
    setAnalyticsPreview({ isOpen: true, type, data: reportData });
  };

  const downloadAnalytics = () => {
    if (!analyticsPreview.data) return;
    
    const type = analyticsPreview.type;
    // Create a downloadable JSON file (in production, this would be a PDF)
    const dataStr = JSON.stringify(analyticsPreview.data, null, 2);
    const dataBlob = new Blob([dataStr], { type: "application/json" });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `${type}-analytics-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    setAnalyticsPreview({ isOpen: false, type: null, data: null });
    alert(`${type?.charAt(0).toUpperCase()}${type?.slice(1)} analytics report downloaded! (In production, this would be a PDF)`);
  };

  const handleDisablePoll = (pollId: string) => {
    setPolls(prev => prev.map(p => p.id === pollId ? { ...p, status: "closed" as const } : p));
    alert("Poll has been disabled");
  };

  const handleDisablePetition = (petitionId: string) => {
    setPetitions(prev => prev.map(p => p.id === petitionId ? { ...p, status: "closed" as const } : p));
    alert("Petition has been disabled");
  };

  const handleExtendDeadline = () => {
    if (extendDeadlineDialog.type === "poll") {
      setPolls(prev => prev.map(p => 
        p.id === extendDeadlineDialog.itemId ? { ...p, expiresAt: newDeadline } : p
      ));
    } else {
      setPetitions(prev => prev.map(p => 
        p.id === extendDeadlineDialog.itemId ? { ...p, expiresAt: newDeadline } : p
      ));
    }
    setExtendDeadlineDialog({ isOpen: false, itemId: "", type: "poll" });
    setNewDeadline("");
    alert("Deadline extended successfully");
  };

  const handlePublishOfficial = (id: string, type: "poll" | "petition") => {
    if (type === "poll") {
      setPolls(prev => prev.map(p => p.id === id ? { ...p, isOfficial: true } : p));
    } else {
      setPetitions(prev => prev.map(p => p.id === id ? { ...p, isOfficial: true } : p));
    }
    alert(`${type.charAt(0).toUpperCase() + type.slice(1)} published as official`);
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case "high":
        return "bg-red-100 text-red-700 border-red-200";
      case "medium":
        return "bg-orange-100 text-orange-700 border-orange-200";
      case "low":
        return "bg-blue-100 text-blue-700 border-blue-200";
      default:
        return "bg-gray-100 text-gray-700 border-gray-200";
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case "pending":
        return "bg-yellow-100 text-yellow-700";
      case "reviewed":
        return "bg-blue-100 text-blue-700";
      case "resolved":
        return "bg-green-100 text-green-700";
      case "dismissed":
        return "bg-gray-100 text-gray-700";
      default:
        return "bg-gray-100 text-gray-700";
    }
  };

  const filteredReports = filterStatus === "all" 
    ? reports 
    : reports.filter(r => r.status === filterStatus);

  return (
    <div className="min-h-screen bg-white py-6">
      <div className="max-w-7xl mx-auto px-6">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-3xl font-bold mb-2 flex items-center gap-2">
              <Shield className="h-8 w-8 text-blue-600" />
              Moderation Dashboard
            </h1>
            <p className="text-gray-600">Monitor and manage platform content</p>
          </div>
        </div>

        {/* Main Tabs: Forum, Polls, Petition */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-3 mb-6">
            <TabsTrigger value="forum">
              <MessageSquare className="h-4 w-4 mr-2" />
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
          </TabsList>

          {/* FORUM TAB */}
          <TabsContent value="forum" className="space-y-6">
            {/* Export Button */}
            <div className="flex justify-end">
              <Button onClick={() => exportAnalytics("forum")} className="bg-blue-600 hover:bg-blue-700">
                <Download className="h-4 w-4 mr-2" />
                Export Analytics (PDF)
              </Button>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
              <Card className="p-4 border-l-4 border-red-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <Flag className="h-5 w-5 text-red-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.pendingReports}</p>
                    <p className="text-xs text-gray-600">Pending</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-green-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <CheckCircle className="h-5 w-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.resolvedToday}</p>
                    <p className="text-xs text-gray-600">Resolved Today</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <UserX className="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.mutedUsers}</p>
                    <p className="text-xs text-gray-600">Muted Users</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <Trash2 className="h-5 w-5 text-purple-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.deletedContent}</p>
                    <p className="text-xs text-gray-600">Deleted</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <AlertTriangle className="h-5 w-5 text-orange-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.warningsIssued}</p>
                    <p className="text-xs text-gray-600">Warnings</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <Users className="h-5 w-5 text-indigo-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.totalUsers}</p>
                    <p className="text-xs text-gray-600">Total Users</p>
                  </div>
                </div>
              </Card>
            </div>

            {/* Reported Content */}
            <Card className="p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-semibold">Reported Content</h2>
                <Select value={filterStatus} onValueChange={setFilterStatus}>
                  <SelectTrigger className="w-[180px]">
                    <SelectValue placeholder="Filter by status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Reports</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="reviewed">Reviewed</SelectItem>
                    <SelectItem value="resolved">Resolved</SelectItem>
                    <SelectItem value="dismissed">Dismissed</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-4">
                {filteredReports.length === 0 ? (
                  <div className="text-center py-12 text-gray-500">
                    <Flag className="h-12 w-12 mx-auto mb-3 text-gray-400" />
                    <p>No reports found</p>
                  </div>
                ) : (
                  filteredReports.map((report) => (
                    <div
                      key={report.id}
                      className={`p-4 rounded-lg border-2 ${
                        report.priority === "high" ? "border-red-300 bg-red-50" : "border-gray-200 bg-white"
                      }`}
                    >
                      <div className="flex gap-4">
                        <div className="flex-1">
                          <div className="flex items-start justify-between mb-3">
                            <div className="flex items-center gap-2">
                              <Badge className={getPriorityColor(report.priority)}>
                                {report.priority.toUpperCase()}
                              </Badge>
                              <Badge className={getStatusColor(report.status)}>
                                {report.status}
                              </Badge>
                              <Badge variant="outline">
                                {report.contentType}
                              </Badge>
                            </div>
                            <span className="text-xs text-gray-500">
                              {report.reportedAt}
                            </span>
                          </div>

                          <div className="mb-3">
                            <div className="flex items-center gap-2 mb-2">
                              <AlertTriangle className="h-4 w-4 text-orange-600" />
                              <span className="font-medium text-sm">
                                Reason: {report.reason}
                              </span>
                            </div>
                            <p className="text-sm text-gray-600 mb-2">
                              {report.details}
                            </p>
                            <div className="bg-gray-100 p-3 rounded border">
                              <p className="text-sm font-medium mb-1">Content Preview:</p>
                              <p className="text-sm text-gray-700 italic">
                                "{report.contentPreview}"
                              </p>
                              <p className="text-xs text-gray-500 mt-2">
                                Author: {report.contentAuthor}
                              </p>
                            </div>
                          </div>

                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                              <Avatar className="h-6 w-6">
                                <AvatarFallback className="bg-gray-300 text-xs">
                                  {report.reportedByAvatar}
                                </AvatarFallback>
                              </Avatar>
                              <span className="text-xs text-gray-600">
                                Reported by {report.reportedBy}
                              </span>
                            </div>

                            {report.status === "pending" && (
                              <div className="flex gap-2">
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => onViewContent(report.contentId, report.contentType)}
                                >
                                  <Eye className="h-4 w-4 mr-1" />
                                  View
                                </Button>
                                <Button
                                  size="sm"
                                  variant="destructive"
                                  onClick={() => {
                                    setSelectedReport(report);
                                    setActionDialog({ isOpen: true, action: "delete" });
                                  }}
                                >
                                  <Trash2 className="h-4 w-4 mr-1" />
                                  Delete
                                </Button>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="text-orange-600"
                                  onClick={() => {
                                    setSelectedReport(report);
                                    setActionDialog({ isOpen: true, action: "warn" });
                                  }}
                                >
                                  <AlertTriangle className="h-4 w-4 mr-1" />
                                  Warn
                                </Button>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => {
                                    setSelectedReport(report);
                                    setActionDialog({ isOpen: true, action: "mute" });
                                  }}
                                >
                                  <Ban className="h-4 w-4 mr-1" />
                                  Mute
                                </Button>
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  onClick={() => handleAction("dismiss")}
                                >
                                  <XCircle className="h-4 w-4 mr-1" />
                                  Dismiss
                                </Button>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </Card>
          </TabsContent>

          {/* POLLS TAB */}
          <TabsContent value="polls" className="space-y-6">
            {/* Export Button */}
            <div className="flex justify-end">
              <Button onClick={() => exportAnalytics("polls")} className="bg-blue-600 hover:bg-blue-700">
                <Download className="h-4 w-4 mr-2" />
                Export Analytics (PDF)
              </Button>
            </div>

            {/* Poll Stats */}
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <BarChart3 className="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{polls.length}</p>
                    <p className="text-xs text-gray-600">Total Polls</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-green-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <PlayCircle className="h-5 w-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{polls.filter(p => p.status === "active").length}</p>
                    <p className="text-xs text-gray-600">Active</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-red-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <TrendingDown className="h-5 w-5 text-red-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{polls.filter(p => p.participation < 30).length}</p>
                    <p className="text-xs text-gray-600">Low Participation</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-orange-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <AlertCircle className="h-5 w-5 text-orange-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{polls.filter(p => p.hasDisputes).length}</p>
                    <p className="text-xs text-gray-600">Has Disputes</p>
                  </div>
                </div>
              </Card>

              
            </div>

            {/* Poll Management */}
            <Card className="p-6">
              <h2 className="text-xl font-semibold mb-4">Poll Management</h2>
              <div className="space-y-4">
                {polls.map((poll) => (
                  <div 
                    key={poll.id} 
                    className={`p-4 border-2 rounded-lg ${
                      poll.hasDisputes ? "border-orange-300 bg-orange-50" :
                      poll.participation < 30 ? "border-red-200 bg-red-50" :
                      "border-gray-200 bg-white"
                    }`}
                  >
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <h3 className="font-semibold">{poll.title}</h3>
                          {poll.isOfficial && (
                            null
                          )}
                          {poll.hasDisputes && (
                            <Badge className="bg-orange-500 text-white">
                              <AlertCircle className="h-3 w-3 mr-1" />
                              {poll.disputeCount} Disputes
                            </Badge>
                          )}
                          {poll.participation < 30 && (
                            <Badge className="bg-red-500 text-white">
                              <TrendingDown className="h-3 w-3 mr-1" />
                              Low Participation
                            </Badge>
                          )}
                        </div>
                        <div className="flex items-center gap-3 text-sm text-gray-600 mb-2">
                          <Badge variant="outline">{poll.category}</Badge>
                          <span>•</span>
                          <div className="flex items-center gap-1">
                            <Users className="h-3 w-3" />
                            <span>{poll.totalVotes} votes ({poll.participation}%)</span>
                          </div>
                          <span>•</span>
                          <div className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />
                            <span>Expires: {poll.expiresAt}</span>
                          </div>
                        </div>
                        <div className="flex items-center gap-2 text-xs text-gray-500">
                          <Avatar className="h-5 w-5">
                            <AvatarFallback className="text-xs bg-gray-300">
                              {poll.creatorAvatar}
                            </AvatarFallback>
                          </Avatar>
                          <span>Created by {poll.creator}</span>
                        </div>
                      </div>
                      <Badge className={
                        poll.status === "active" ? "bg-green-100 text-green-700" :
                        poll.status === "expired" ? "bg-gray-100 text-gray-700" :
                        "bg-red-100 text-red-700"
                      }>
                        {poll.status}
                      </Badge>
                    </div>

                    {poll.status === "active" && (
                      <div className="flex gap-2 pt-3 border-t border-gray-200">
                        <Button size="sm" variant="outline" onClick={() => onViewContent(poll.id, "poll")}>
                          <Eye className="h-4 w-4 mr-1" />
                          View
                        </Button>
                        <Button 
                          size="sm" 
                          variant="outline"
                          onClick={() => {
                            setExtendDeadlineDialog({ isOpen: true, itemId: poll.id, type: "poll" });
                          }}
                        >
                          <Clock className="h-4 w-4 mr-1" />
                          Extend Deadline
                        </Button>
                        <Button 
                          size="sm" 
                          variant="destructive"
                          onClick={() => handleDisablePoll(poll.id)}
                        >
                          <PauseCircle className="h-4 w-4 mr-1" />
                          Disable
                        </Button>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </Card>
          </TabsContent>

          {/* PETITION TAB */}
          <TabsContent value="petition" className="space-y-6">
            {/* Export Button */}
            <div className="flex justify-end">
              <Button onClick={() => exportAnalytics("petitions")} className="bg-blue-600 hover:bg-blue-700">
                <Download className="h-4 w-4 mr-2" />
                Export Analytics (PDF)
              </Button>
            </div>

            {/* Petition Stats */}
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
              <Card className="p-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <FileText className="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitions.length}</p>
                    <p className="text-xs text-gray-600">Total Petitions</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-green-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <PlayCircle className="h-5 w-5 text-green-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitions.filter(p => p.status === "active").length}</p>
                    <p className="text-xs text-gray-600">Active</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-red-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <TrendingDown className="h-5 w-5 text-red-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitions.filter(p => p.participation < 30).length}</p>
                    <p className="text-xs text-gray-600">Low Participation</p>
                  </div>
                </div>
              </Card>

              <Card className="p-4 border-l-4 border-orange-500">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <AlertCircle className="h-5 w-5 text-orange-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitions.filter(p => p.hasDisputes).length}</p>
                    <p className="text-xs text-gray-600">Has Disputes</p>
                  </div>
                </div>
              </Card>

              
            </div>

            {/* Petition Management */}
            <Card className="p-6">
              <h2 className="text-xl font-semibold mb-4">Petition Management</h2>
              <div className="space-y-4">
                {petitions.map((petition) => {
                  const progress = (petition.supporters / petition.goal) * 100;
                  return (
                    <div 
                      key={petition.id} 
                      className={`p-4 border-2 rounded-lg ${
                        petition.hasDisputes ? "border-orange-300 bg-orange-50" :
                        petition.participation < 30 ? "border-red-200 bg-red-50" :
                        "border-gray-200 bg-white"
                      }`}
                    >
                      <div className="flex items-start justify-between mb-3">
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-2">
                            <h3 className="font-semibold">{petition.title}</h3>
                            {petition.isOfficial && (
                              null
                            )}
                            {petition.hasDisputes && (
                              <Badge className="bg-orange-500 text-white">
                                <AlertCircle className="h-3 w-3 mr-1" />
                                {petition.disputeCount} Disputes
                              </Badge>
                            )}
                            {petition.participation < 30 && (
                              <Badge className="bg-red-500 text-white">
                                <TrendingDown className="h-3 w-3 mr-1" />
                                Low Participation
                              </Badge>
                            )}
                          </div>
                          <p className="text-sm text-gray-600 mb-2">{petition.description}</p>
                          <div className="flex items-center gap-3 text-sm text-gray-600 mb-3">
                            <Badge variant="outline">{petition.category}</Badge>
                            <span>•</span>
                            <div className="flex items-center gap-1">
                              <Users className="h-3 w-3" />
                              <span>{petition.supporters} supporters ({petition.participation}%)</span>
                            </div>
                            <span>•</span>
                            <div className="flex items-center gap-1">
                              <Calendar className="h-3 w-3" />
                              <span>Expires: {petition.expiresAt}</span>
                            </div>
                          </div>
                          
                          {/* Progress Bar */}
                          <div className="mb-3">
                            <div className="flex items-center justify-between text-xs mb-1">
                              <span>{petition.supporters.toLocaleString()} / {petition.goal.toLocaleString()}</span>
                              <span>{progress.toFixed(1)}%</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                              <div
                                className={`rounded-full h-2 ${
                                  petition.status === "successful" ? "bg-green-500" : "bg-[#ff6934]"
                                }`}
                                style={{ width: `${Math.min(progress, 100)}%` }}
                              />
                            </div>
                          </div>

                          <div className="flex items-center gap-2 text-xs text-gray-500">
                            <Avatar className="h-5 w-5">
                              <AvatarFallback className="text-xs bg-gray-300">
                                {petition.creatorAvatar}
                              </AvatarFallback>
                            </Avatar>
                            <span>Created by {petition.creator}</span>
                          </div>
                        </div>
                        <Badge className={
                          petition.status === "active" ? "bg-blue-100 text-blue-700" :
                          petition.status === "successful" ? "bg-green-100 text-green-700" :
                          petition.status === "expired" ? "bg-gray-100 text-gray-700" :
                          "bg-red-100 text-red-700"
                        }>
                          {petition.status}
                        </Badge>
                      </div>

                      {petition.status === "active" && (
                        <div className="flex gap-2 pt-3 border-t border-gray-200">
                          <Button size="sm" variant="outline" onClick={() => onViewContent(petition.id, "petition")}>
                            <Eye className="h-4 w-4 mr-1" />
                            View
                          </Button>
                          <Button 
                            size="sm" 
                            variant="outline"
                            onClick={() => {
                              setExtendDeadlineDialog({ isOpen: true, itemId: petition.id, type: "petition" });
                            }}
                          >
                            <Clock className="h-4 w-4 mr-1" />
                            Extend Deadline
                          </Button>
                          <Button 
                            size="sm" 
                            variant="destructive"
                            onClick={() => handleDisablePetition(petition.id)}
                          >
                            <PauseCircle className="h-4 w-4 mr-1" />
                            Disable
                          </Button>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      {/* Action Confirmation Dialog */}
      <Dialog 
        open={actionDialog.isOpen} 
        onOpenChange={(open) => setActionDialog({ isOpen: open, action: null })}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              Confirm {actionDialog.action?.charAt(0).toUpperCase()}{actionDialog.action?.slice(1)} Action
            </DialogTitle>
            <DialogDescription>
              This action will be logged and the user will be notified.
            </DialogDescription>
          </DialogHeader>

          <div className="py-4">
            <label className="text-sm font-medium mb-2 block">
              Add a note (optional)
            </label>
            <Textarea
              value={actionNote}
              onChange={(e) => setActionNote(e.target.value)}
              placeholder="Provide details about this moderation action..."
              rows={4}
            />
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setActionDialog({ isOpen: false, action: null })}
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={() => handleAction(actionDialog.action!)}
            >
              Confirm {actionDialog.action}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Extend Deadline Dialog */}
      <Dialog 
        open={extendDeadlineDialog.isOpen} 
        onOpenChange={(open) => setExtendDeadlineDialog({ isOpen: open, itemId: "", type: "poll" })}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Extend Deadline</DialogTitle>
            <DialogDescription>
              Set a new expiration date for this {extendDeadlineDialog.type}
            </DialogDescription>
          </DialogHeader>

          <div className="py-4">
            <label className="text-sm font-medium mb-2 block">
              New Deadline Date
            </label>
            <Input
              type="date"
              value={newDeadline}
              onChange={(e) => setNewDeadline(e.target.value)}
              min={new Date().toISOString().split('T')[0]}
            />
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setExtendDeadlineDialog({ isOpen: false, itemId: "", type: "poll" })}
            >
              Cancel
            </Button>
            <Button
              onClick={handleExtendDeadline}
              disabled={!newDeadline}
            >
              Extend Deadline
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Analytics Preview Dialog */}
      <Dialog 
        open={analyticsPreview.isOpen} 
        onOpenChange={(open) => setAnalyticsPreview({ isOpen: open, type: null, data: null })}
      >
        <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Analytics Report Preview</DialogTitle>
            <DialogDescription>
              Review the analytics report before downloading
            </DialogDescription>
          </DialogHeader>

          <div className="py-4 space-y-6">
            {/* Report Metadata */}
            <Card className="p-4 bg-gray-50">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="font-semibold text-lg mb-1">
                    {analyticsPreview.type?.charAt(0).toUpperCase() + analyticsPreview.type?.slice(1)} Analytics Report
                  </h3>
                  <p className="text-sm text-gray-600">
                    Generated on {analyticsPreview.data?.generatedDate} at {analyticsPreview.data?.generatedTime}
                  </p>
                  <p className="text-sm text-gray-600">Period: {analyticsPreview.data?.period}</p>
                </div>
                <Badge className="bg-blue-100 text-blue-700 text-lg px-4 py-2">
                  {analyticsPreview.type?.toUpperCase()}
                </Badge>
              </div>
            </Card>

            {/* Forum Analytics */}
            {analyticsPreview.type === "forum" && (
              <div>
                <h3 className="text-lg font-semibold mb-4">Forum Analytics</h3>
                <div className="grid grid-cols-2 gap-4">
                  <Card className="p-6 bg-blue-50 border-blue-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Reports</p>
                    <p className="text-3xl font-bold text-blue-900 mt-8">{analyticsPreview.data?.summary.totalReports}</p>
                  </Card>
                  <Card className="p-6 bg-yellow-50 border-yellow-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Pending Reports</p>
                    <p className="text-3xl font-bold text-yellow-900 mt-8">{analyticsPreview.data?.summary.pendingReports}</p>
                  </Card>
                  <Card className="p-6 bg-green-50 border-green-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Resolved Reports</p>
                    <p className="text-3xl font-bold text-green-900 mt-8">{analyticsPreview.data?.summary.resolvedReports}</p>
                  </Card>
                  <Card className="p-6 bg-gray-50 border-gray-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Dismissed Reports</p>
                    <p className="text-3xl font-bold text-gray-900 mt-8">{analyticsPreview.data?.summary.dismissedReports}</p>
                  </Card>
                </div>
              </div>
            )}

            {/* Polls Analytics */}
            {analyticsPreview.type === "polls" && (
              <div>
                <h3 className="text-lg font-semibold mb-4">Polls Analytics</h3>
                <div className="grid grid-cols-2 gap-4">
                  <Card className="p-6 bg-blue-50 border-blue-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Polls</p>
                    <p className="text-3xl font-bold text-blue-900 mt-8">{analyticsPreview.data?.summary.totalPolls}</p>
                  </Card>
                  <Card className="p-6 bg-green-50 border-green-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Active Polls</p>
                    <p className="text-3xl font-bold text-green-900 mt-8">{analyticsPreview.data?.summary.activePolls}</p>
                  </Card>
                  <Card className="p-6 bg-purple-50 border-purple-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Average Participation</p>
                    <p className="text-3xl font-bold text-purple-900 mt-8">{analyticsPreview.data?.summary.averageParticipation}%</p>
                  </Card>
                  <Card className="p-6 bg-indigo-50 border-indigo-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Votes</p>
                    <p className="text-3xl font-bold text-indigo-900 mt-8">{analyticsPreview.data?.summary.totalVotes}</p>
                  </Card>
                  <Card className="p-6 bg-red-50 border-red-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Low Participation</p>
                    <p className="text-3xl font-bold text-red-900 mt-8">{analyticsPreview.data?.summary.lowParticipation}</p>
                  </Card>
                  <Card className="p-6 bg-orange-50 border-orange-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Has Disputes</p>
                    <p className="text-3xl font-bold text-orange-900 mt-8">{analyticsPreview.data?.summary.hasDisputes}</p>
                  </Card>
                </div>
              </div>
            )}

            {/* Petitions Analytics */}
            {analyticsPreview.type === "petitions" && (
              <div>
                <h3 className="text-lg font-semibold mb-4">Petitions Analytics</h3>
                <div className="grid grid-cols-2 gap-4">
                  <Card className="p-6 bg-blue-50 border-blue-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Petitions</p>
                    <p className="text-3xl font-bold text-blue-900 mt-8">{analyticsPreview.data?.summary.totalPetitions}</p>
                  </Card>
                  <Card className="p-6 bg-green-50 border-green-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Active Petitions</p>
                    <p className="text-3xl font-bold text-green-900 mt-8">{analyticsPreview.data?.summary.activePetitions}</p>
                  </Card>
                  <Card className="p-6 bg-emerald-50 border-emerald-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Successful Petitions</p>
                    <p className="text-3xl font-bold text-emerald-900 mt-8">{analyticsPreview.data?.summary.successfulPetitions}</p>
                  </Card>
                  <Card className="p-6 bg-purple-50 border-purple-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Average Participation</p>
                    <p className="text-3xl font-bold text-purple-900 mt-8">{analyticsPreview.data?.summary.averageParticipation}%</p>
                  </Card>
                  <Card className="p-6 bg-indigo-50 border-indigo-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Supporters</p>
                    <p className="text-3xl font-bold text-indigo-900 mt-8">{analyticsPreview.data?.summary.totalSupporters}</p>
                  </Card>
                  <Card className="p-6 bg-red-50 border-red-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Low Participation</p>
                    <p className="text-3xl font-bold text-red-900 mt-8">{analyticsPreview.data?.summary.lowParticipation}</p>
                  </Card>
                  <Card className="p-6 bg-orange-50 border-orange-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Has Disputes</p>
                    <p className="text-3xl font-bold text-orange-900 mt-8">{analyticsPreview.data?.summary.hasDisputes}</p>
                  </Card>
                </div>
              </div>
            )}
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setAnalyticsPreview({ isOpen: false, type: null, data: null })}
            >
              Cancel
            </Button>
            <Button
              onClick={downloadAnalytics}
              className="bg-blue-600 hover:bg-blue-700"
            >
              <Download className="h-4 w-4 mr-2" />
              Download Report
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}