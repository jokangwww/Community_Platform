import { useState, useEffect, useCallback } from "react";
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
  PauseCircle,
  Loader2,
  User
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
  adminAction?: string | null;
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
  defaultTab?: string;
}

export function AdminDashboard({ adminId, onViewContent, defaultTab }: AdminDashboardProps) {
  const [selectedReport, setSelectedReport] = useState<ReportedContent | null>(null);
  const [actionDialog, setActionDialog] = useState<{
    isOpen: boolean;
    action: "delete" | "warn" | "mute" | "restore" | null;
  }>({ isOpen: false, action: null });
  const [actionNote, setActionNote] = useState("");
  const [filterStatus, setFilterStatus] = useState<string>("all");

  // Author moderation history for confirmation dialog
  const [authorHistory, setAuthorHistory] = useState<{
    authorName: string;
    warnCount: number;
    muteCount: number;
    currentlyMuted: boolean;
    mutedUntil: string | null;
    nextMuteDuration: number;
  } | null>(null);
  const [authorHistoryLoading, setAuthorHistoryLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<string>(defaultTab ?? "forum");
  
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
  
  // Forum moderation stats from API
  const [stats, setStats] = useState<ModStats>({
    pendingReports: 0,
    resolvedToday: 0,
    totalUsers: 0,
    mutedUsers: 0,
    deletedContent: 0,
    warningsIssued: 0
  });
  const [statsLoading, setStatsLoading] = useState(true);

  // Forum reports from API
  const [reports, setReports] = useState<ReportedContent[]>([]);
  const [reportsLoading, setReportsLoading] = useState(true);

  // Polls & Petitions data from API
  const [polls, setPolls] = useState<AdminPoll[]>([]);
  const [petitions, setPetitions] = useState<AdminPetition[]>([]);
  const [pollsLoading, setPollsLoading] = useState(true);
  const [petitionsLoading, setPetitionsLoading] = useState(true);

  const getCsrfToken = useCallback(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
  }, []);

  const apiFetch = useCallback(async (url: string, options: RequestInit = {}) => {
    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    };
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
    }
    return fetch(url, { ...options, headers: { ...headers, ...(options.headers || {}) } });
  }, [getCsrfToken]);

  const fetchAuthorHistory = useCallback(async (reportId: string) => {
    try {
      setAuthorHistoryLoading(true);
      const res = await apiFetch(`/api/forum/reports/${reportId}/author-history`);
      if (res.ok) {
        const data = await res.json();
        setAuthorHistory(data.data || null);
      }
    } catch (err) {
      console.error('Failed to fetch author history:', err);
    } finally {
      setAuthorHistoryLoading(false);
    }
  }, [apiFetch]);

  const fetchPolls = useCallback(async () => {
    try {
      setPollsLoading(true);
      const res = await apiFetch('/api/poll-petition/admin/polls');
      if (res.ok) {
        const data = await res.json();
        setPolls(data.polls || []);
      }
    } catch (err) {
      console.error('Failed to fetch admin polls:', err);
    } finally {
      setPollsLoading(false);
    }
  }, [apiFetch]);

  const fetchPetitions = useCallback(async () => {
    try {
      setPetitionsLoading(true);
      const res = await apiFetch('/api/poll-petition/admin/petitions');
      if (res.ok) {
        const data = await res.json();
        setPetitions(data.petitions || []);
      }
    } catch (err) {
      console.error('Failed to fetch admin petitions:', err);
    } finally {
      setPetitionsLoading(false);
    }
  }, [apiFetch]);

  // Fetch forum stats
  const fetchForumStats = useCallback(async () => {
    try {
      setStatsLoading(true);
      const res = await apiFetch('/api/forum/admin/stats');
      if (res.ok) {
        const data = await res.json();
        if (data.data) {
          setStats({
            pendingReports: data.data.pendingReports || 0,
            resolvedToday: data.data.resolvedToday || 0,
            totalUsers: data.data.totalUsers || 0,
            mutedUsers: data.data.mutedUsers || 0,
            deletedContent: data.data.deletedContent || 0,
            warningsIssued: data.data.warningsIssued || 0,
          });
        }
      }
    } catch (err) {
      console.error('Failed to fetch forum stats:', err);
    } finally {
      setStatsLoading(false);
    }
  }, [apiFetch]);

  // Fetch forum reports
  const fetchReports = useCallback(async (status?: string) => {
    try {
      setReportsLoading(true);
      const url = status && status !== 'all'
        ? `/api/forum/reports?status=${status}`
        : '/api/forum/reports?status=all';
      const res = await apiFetch(url);
      if (res.ok) {
        const data = await res.json();
        setReports(data.data || []);
      }
    } catch (err) {
      console.error('Failed to fetch forum reports:', err);
    } finally {
      setReportsLoading(false);
    }
  }, [apiFetch]);

  useEffect(() => {
    fetchForumStats();
    fetchReports();
    fetchPolls();
    fetchPetitions();
  }, [fetchForumStats, fetchReports, fetchPolls, fetchPetitions]);

  // Refetch relevant data whenever the admin switches tabs
  useEffect(() => {
    if (activeTab === 'polls') fetchPolls();
    else if (activeTab === 'petitions') fetchPetitions();
    else if (activeTab === 'forum') { fetchForumStats(); fetchReports(); }
  }, [activeTab]); // eslint-disable-line react-hooks/exhaustive-deps

  // filteredReports computed below handles client-side filtering — no need to re-fetch on filter change

  const handleAction = async (action: "delete" | "warn" | "mute" | "restore" | "dismiss") => {
    if (!selectedReport) return;

    try {
      const res = await apiFetch(`/api/forum/reports/${selectedReport.id}/review`, {
        method: 'PUT',
        body: JSON.stringify({
          action: action,
          admin_note: actionNote || null,
        }),
      });

      if (res.ok) {
        // Update report status locally
        setReports(prev =>
          prev.map(r =>
            r.id === selectedReport.id
              ? { ...r, status: action === "dismiss" ? "dismissed" : "resolved", adminAction: action }
              : r
          )
        );
        // Refresh stats
        fetchForumStats();
      } else {
        const errData = await res.json();
        alert(errData.message || 'Failed to process action');
      }
    } catch (err) {
      console.error('Failed to review report:', err);
      alert('Failed to process action. Please try again.');
    }

    // Close dialogs
    setActionDialog({ isOpen: false, action: null });
    setSelectedReport(null);
    setActionNote("");
  };

  const exportAnalytics = async (type: "forum" | "polls" | "petitions") => {
    if (type === "forum") {
      // Forum analytics remain local for now
      const reportData = {
        generatedAt: new Date().toISOString(),
        generatedDate: new Date().toLocaleDateString(),
        generatedTime: new Date().toLocaleTimeString(),
        period: "Last 30 days",
        type: type,
        stats: stats,
        reports: reports,
        summary: {
          totalReports: reports.length,
          pendingReports: reports.filter(r => r.status === "pending").length,
          resolvedReports: reports.filter(r => r.status === "resolved").length,
          dismissedReports: reports.filter(r => r.status === "dismissed").length
        }
      };
      setAnalyticsPreview({ isOpen: true, type, data: reportData });
      return;
    }

    try {
      const res = await apiFetch('/api/poll-petition/admin/analytics');
      if (res.ok) {
        const analytics = await res.json();
        const reportData = {
          generatedAt: new Date().toISOString(),
          generatedDate: new Date().toLocaleDateString(),
          generatedTime: new Date().toLocaleTimeString(),
          period: "Last 30 days",
          type: type,
          summary: type === "polls" ? {
            totalPolls: analytics.polls?.total || 0,
            activePolls: analytics.polls?.active || 0,
            averageParticipation: analytics.polls?.averageParticipation || "0.0",
            totalVotes: analytics.polls?.totalVotes || 0,
            lowParticipation: analytics.polls?.lowParticipation || 0,
            hasDisputes: 0
          } : {
            totalPetitions: analytics.petitions?.total || 0,
            activePetitions: analytics.petitions?.active || 0,
            successfulPetitions: analytics.petitions?.successful || 0,
            averageParticipation: analytics.petitions?.averageParticipation || "0.0",
            totalSupporters: analytics.petitions?.totalSupporters || 0,
            lowParticipation: analytics.petitions?.lowParticipation || 0,
            hasDisputes: 0
          }
        };
        setAnalyticsPreview({ isOpen: true, type, data: reportData });
      }
    } catch (err) {
      console.error('Failed to fetch analytics:', err);
    }
  };

  const downloadAnalytics = async () => {
    if (!analyticsPreview.data) return;
    
    const type = analyticsPreview.type;

    if (type === "polls" || type === "petitions") {
      try {
        const res = await apiFetch('/api/poll-petition/admin/analytics/export');
        if (res.ok) {
          const blob = await res.blob();
          const url = URL.createObjectURL(blob);
          const link = document.createElement("a");
          link.href = url;
          link.download = `${type}-analytics-${new Date().toISOString().split('T')[0]}.json`;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          URL.revokeObjectURL(url);
        }
      } catch (err) {
        console.error('Failed to download analytics:', err);
      }
    } else {
      // Forum analytics - local export
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
    }

    setAnalyticsPreview({ isOpen: false, type: null, data: null });
  };

  const handleDisablePoll = async (pollId: string) => {
    try {
      const res = await apiFetch(`/api/poll-petition/admin/polls/${pollId}/disable`, { method: 'POST' });
      if (res.ok) {
        setPolls(prev => prev.map(p => p.id === pollId ? { ...p, status: "closed" as const } : p));
        alert("Poll has been disabled");
      }
    } catch (err) {
      console.error('Failed to disable poll:', err);
    }
  };

  const handleDisablePetition = async (petitionId: string) => {
    try {
      const res = await apiFetch(`/api/poll-petition/admin/petitions/${petitionId}/disable`, { method: 'POST' });
      if (res.ok) {
        setPetitions(prev => prev.map(p => p.id === petitionId ? { ...p, status: "closed" as const } : p));
        alert("Petition has been disabled");
      }
    } catch (err) {
      console.error('Failed to disable petition:', err);
    }
  };

  const handleExtendDeadline = async () => {
    try {
      const endpoint = extendDeadlineDialog.type === "poll"
        ? `/api/poll-petition/admin/polls/${extendDeadlineDialog.itemId}/extend`
        : `/api/poll-petition/admin/petitions/${extendDeadlineDialog.itemId}/extend`;
      const res = await apiFetch(endpoint, {
        method: 'POST',
        body: JSON.stringify({ new_deadline: newDeadline }),
      });
      if (res.ok) {
        // Refetch from server so status changes (e.g. expired→active) are reflected immediately
        if (extendDeadlineDialog.type === "poll") {
          fetchPolls();
        } else {
          fetchPetitions();
        }
        alert("Deadline extended successfully");
      }
    } catch (err) {
      console.error('Failed to extend deadline:', err);
    }
    setExtendDeadlineDialog({ isOpen: false, itemId: "", type: "poll" });
    setNewDeadline("");
  };

  const handlePublishOfficial = async (id: string, type: "poll" | "petition") => {
    try {
      const res = await apiFetch(`/api/poll-petition/admin/polls/${id}/official`, { method: 'POST' });
      if (res.ok) {
        if (type === "poll") {
          setPolls(prev => prev.map(p => p.id === id ? { ...p, isOfficial: true } : p));
        } else {
          setPetitions(prev => prev.map(p => p.id === id ? { ...p, isOfficial: true } : p));
        }
        alert(`${type.charAt(0).toUpperCase() + type.slice(1)} published as official`);
      }
    } catch (err) {
      console.error('Failed to publish official:', err);
    }
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

  const getStatusColor = (status: string, adminAction?: string | null) => {
    if (status === "resolved" && adminAction) {
      switch (adminAction) {
        case "delete":  return "bg-red-100 text-red-700";
        case "warn":    return "bg-orange-100 text-orange-700";
        case "mute":    return "bg-purple-100 text-purple-700";
        case "restore": return "bg-green-100 text-green-700";
      }
    }
    switch (status) {
      case "pending":   return "bg-yellow-100 text-yellow-700";
      case "reviewed":  return "bg-blue-100 text-blue-700";
      case "resolved":  return "bg-green-100 text-green-700";
      case "dismissed": return "bg-gray-100 text-gray-700";
      default:          return "bg-gray-100 text-gray-700";
    }
  };

  const getStatusLabel = (status: string, adminAction?: string | null) => {
    if (status === "dismissed") return "Dismissed";
    if (status === "resolved" && adminAction) {
      switch (adminAction) {
        case "delete":  return "Deleted";
        case "warn":    return "Warning Issued";
        case "mute":    return "User Muted";
        case "restore": return "Restored";
      }
    }
    return status.charAt(0).toUpperCase() + status.slice(1);
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
            <TabsTrigger value="forum" className="cursor-pointer">
              <MessageSquare className="h-4 w-4 mr-2" />
              Forum
            </TabsTrigger>
            <TabsTrigger value="polls" className="cursor-pointer">
              <BarChart3 className="h-4 w-4 mr-2" />
              Polls
            </TabsTrigger>
            <TabsTrigger value="petitions" className="cursor-pointer">
              <FileText className="h-4 w-4 mr-2" />
              Petitions
            </TabsTrigger>
          </TabsList>

          {/* FORUM TAB */}
          <TabsContent value="forum" className="space-y-6">
            {/* Export Button */}
            <div className="flex justify-end">
              <Button onClick={() => exportAnalytics("forum")} className="bg-blue-600 hover:bg-blue-700 cursor-pointer">
                <Download className="h-4 w-4 mr-2" />
                Export Analytics (PDF)
              </Button>
            </div>

            {/* Stats Grid */}
            <div className="flex flex-wrap gap-4">
              <Card className="p-4 flex-1 min-w-[120px] border border-red-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-green-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-blue-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-purple-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-orange-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-indigo-200">
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
                    <SelectItem value="resolved">Resolved</SelectItem>
                    <SelectItem value="dismissed">Dismissed</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-4">
                {reportsLoading ? (
                  <div className="flex items-center justify-center py-16">
                    <div className="text-center">
                      <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                      <p className="text-gray-500">Loading reports...</p>
                    </div>
                  </div>
                ) : filteredReports.length === 0 ? (
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
                              <Badge className={getStatusColor(report.status, report.adminAction)}>
                                {getStatusLabel(report.status, report.adminAction)}
                              </Badge>
                              <Badge variant="outline">
                                {report.contentType}
                              </Badge>
                            </div>
                            <span className="text-xs text-gray-500">
                              {report.reportedAt}
                            </span>
                          </div>

                          {/* Reported user (post owner) — prominent row */}
                          <div className="flex items-center gap-2 mb-3 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                            <User className="h-4 w-4 text-amber-600 shrink-0" />
                            <span className="text-xs text-amber-700 font-medium">User:</span>
                            <span className="text-xs font-semibold text-amber-900">{report.contentAuthor}</span>
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

                            {report.status === "pending" ? (
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
                                    setAuthorHistory(null);
                                    fetchAuthorHistory(report.id);
                                    setActionDialog({ isOpen: true, action: "delete" });
                                  }}
                                >
                                  <Trash2 className="h-4 w-4 mr-1" />
                                  Delete
                                </Button>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="text-orange-600 cursor-pointer"
                                  onClick={() => {
                                    setSelectedReport(report);
                                    setAuthorHistory(null);
                                    fetchAuthorHistory(report.id);
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
                                    setAuthorHistory(null);
                                    fetchAuthorHistory(report.id);
                                    setActionDialog({ isOpen: true, action: "mute" });
                                  }}
                                >
                                  <Ban className="h-4 w-4 mr-1" />
                                  Mute
                                </Button>
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  onClick={() => {
                                    setSelectedReport(report);
                                    handleAction("dismiss");
                                  }}
                                >
                                  <XCircle className="h-4 w-4 mr-1" />
                                  Dismiss
                                </Button>
                              </div>
                            ) : (
                              <div className="flex gap-2">
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => onViewContent(report.contentId, report.contentType)}
                                >
                                  <Eye className="h-4 w-4 mr-1" />
                                  View
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
              <Button onClick={() => exportAnalytics("polls")} className="bg-blue-600 hover:bg-blue-700 cursor-pointer">
                <Download className="h-4 w-4 mr-2" />
                Export Analytics (PDF)
              </Button>
            </div>

            {/* Poll Stats */}
            <div className="flex flex-wrap gap-4">
              <Card className="p-4 flex-1 min-w-[120px] border border-blue-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-green-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-red-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-orange-200">
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
                {pollsLoading ? (
                  <div className="flex items-center justify-center py-16">
                    <div className="text-center">
                      <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                      <p className="text-gray-500">Loading polls...</p>
                    </div>
                  </div>
                ) : polls.map((poll) => (
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

                    <div className="flex gap-2 pt-3 border-t border-gray-200">
                      <Button size="sm" variant="outline" onClick={() => onViewContent(poll.id, "poll")}>
                        <Eye className="h-4 w-4 mr-1 cursor-pointer" />
                        View
                      </Button>
                      {(poll.status === "active" || poll.status === "expired") && (
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
                      )}
                      {poll.status === "active" && (
                        <Button 
                          size="sm" 
                          variant="destructive"
                          onClick={() => handleDisablePoll(poll.id)}
                        >
                          <PauseCircle className="h-4 w-4 mr-1" />
                          Disable
                        </Button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </Card>
          </TabsContent>

          {/* PETITION TAB */}
          <TabsContent value="petitions" className="space-y-6">
            {/* Export Button */}
            <div className="flex justify-end">
              <Button onClick={() => exportAnalytics("petitions")} className="bg-blue-600 hover:bg-blue-700 cursor-pointer">
                <Download className="h-4 w-4 mr-2" />
                Export Analytics (PDF)
              </Button>
            </div>

            {/* Petition Stats */}
            <div className="flex flex-wrap gap-4">
              <Card className="p-4 flex-1 min-w-[120px] border border-blue-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-green-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-red-200">
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

              <Card className="p-4 flex-1 min-w-[120px] border border-orange-200">
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
                {petitionsLoading ? (
                  <div className="flex items-center justify-center py-16">
                    <div className="text-center">
                      <Loader2 className="h-12 w-12 text-[#ff6934] mx-auto mb-4 animate-spin" />
                      <p className="text-gray-500">Loading petitions...</p>
                    </div>
                  </div>
                ) : petitions.map((petition) => {
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

                      <div className="flex gap-2 pt-3 border-t border-gray-200">
                        <Button size="sm" variant="outline" onClick={() => onViewContent(petition.id, "petition")}>
                          <Eye className="h-4 w-4 mr-1 cursor-pointer" />
                          View
                        </Button>
                        {(petition.status === "active" || petition.status === "expired") && (
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
                        )}
                        {petition.status === "active" && (
                          <Button 
                            size="sm" 
                            variant="destructive"
                            onClick={() => handleDisablePetition(petition.id)}
                          >
                            <PauseCircle className="h-4 w-4 mr-1" />
                            Disable
                          </Button>
                        )}
                      </div>
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
        onOpenChange={(open) => {
          setActionDialog({ isOpen: open, action: null });
          if (!open) setAuthorHistory(null);
        }}
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

          <div className="py-4 space-y-4">
            {/* Author moderation history */}
            {authorHistoryLoading ? (
              <div className="text-sm text-gray-500 text-center py-2">Loading user history...</div>
            ) : authorHistory && (
              <div className="bg-gray-50 border rounded-lg p-3 space-y-2">
                <p className="text-sm font-medium">
                  Content Author: <span className="text-blue-700">{authorHistory.authorName}</span>
                </p>
                <div className="flex gap-4 text-sm">
                  <div className="flex items-center gap-1">
                    <AlertTriangle className="h-3.5 w-3.5 text-orange-500" />
                    <span>Warnings: <span className="font-semibold text-orange-600">{authorHistory.warnCount}</span></span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Ban className="h-3.5 w-3.5 text-red-500" />
                    <span>Mutes: <span className="font-semibold text-red-600">{authorHistory.muteCount}</span></span>
                  </div>
                </div>
                {authorHistory.currentlyMuted && (
                  <p className="text-xs text-red-600 font-medium">
                    Currently muted until {new Date(authorHistory.mutedUntil!).toLocaleDateString()}
                  </p>
                )}
                {actionDialog.action === "mute" && (
                  <div className="mt-1 p-2 bg-red-50 border border-red-200 rounded text-sm">
                    <p className="font-medium text-red-700">
                      Mute duration: {authorHistory.nextMuteDuration} day{authorHistory.nextMuteDuration > 1 ? 's' : ''}
                    </p>
                    <p className="text-xs text-red-600 mt-1">
                      {authorHistory.muteCount === 0 && "1st mute → 1 day"}
                      {authorHistory.muteCount === 1 && "2nd mute → 7 days"}
                      {authorHistory.muteCount >= 2 && "3rd+ mute → 30 days"}
                    </p>
                  </div>
                )}
                {actionDialog.action === "warn" && authorHistory.warnCount >= 2 && (
                  <p className="text-xs text-orange-600 font-medium">
                    This user has already received {authorHistory.warnCount} warning(s). Consider muting instead.
                  </p>
                )}
              </div>
            )}

            <div>
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
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setActionDialog({ isOpen: false, action: null });
                setAuthorHistory(null);
              }}
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
                  <Card className="p-6 bg-green-50 border-green h-[120px] overflow-hidden gap-0 block">
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
              className="bg-blue-600 hover:bg-blue-700 cursor-pointer"
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