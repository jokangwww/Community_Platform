import { useState, useEffect, useCallback } from "react";
import { formatDate } from "../../../shared/utils/date";
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
  User,
  Search
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
  totalReports: number;
  pendingReports: number;
  resolvedToday: number;
  totalUsers: number;
  mutedUsers: number;
  deletedContent: number;
  warningsIssued: number;
  totalPosts: number;
  averagePostPerDay: number;
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
  status: "active" | "expired" | "closed" | "disabled";
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
  status: "active" | "successful" | "expired" | "closed" | "disabled";
  isOfficial: boolean;
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
  
  // Disable confirmation dialog
  const [disableDialog, setDisableDialog] = useState<{
    isOpen: boolean;
    itemId: string;
    type: "poll" | "petition";
    title: string;
  }>({ isOpen: false, itemId: "", type: "poll", title: "" });

  // Analytics preview dialog
  const [analyticsPreview, setAnalyticsPreview] = useState<{
    isOpen: boolean;
    type: "forum" | "polls" | "petitions" | null;
    data: any;
  }>({ isOpen: false, type: null, data: null });
  
  // Forum moderation stats from API
  const [stats, setStats] = useState<ModStats>({
    totalReports: 0,
    pendingReports: 0,
    resolvedToday: 0,
    totalUsers: 0,
    mutedUsers: 0,
    deletedContent: 0,
    warningsIssued: 0,
    totalPosts: 0,
    averagePostPerDay: 0
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

  // Search state for admin poll/petition tabs
  const [pollSearchTerm, setPollSearchTerm] = useState("");
  const [petitionSearchTerm, setPetitionSearchTerm] = useState("");

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
            totalReports: data.data.totalReports || 0,
            pendingReports: data.data.pendingReports || 0,
            resolvedToday: data.data.resolvedToday || 0,
            totalUsers: data.data.totalUsers || 0,
            mutedUsers: data.data.mutedUsers || 0,
            deletedContent: data.data.deletedContent || 0,
            warningsIssued: data.data.warningsIssued || 0,
            totalPosts: data.data.totalPosts || 0,
            averagePostPerDay: data.data.averagePostPerDay || 0,
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
        generatedDate: formatDate(new Date()),
        generatedTime: new Date().toLocaleTimeString(),
        period: "Last 30 days",
        type: type,
        stats: stats,
        reports: reports,
        summary: {
          totalReports: reports.length,
          pendingReports: reports.filter(r => r.status === "pending").length,
          resolvedReports: reports.filter(r => r.status === "resolved").length,
          dismissedReports: reports.filter(r => r.status === "dismissed").length,
          mutedUsers: stats.mutedUsers,
          warningsIssued: stats.warningsIssued,
          deletedContent: stats.deletedContent,
          averagePostPerDay: stats.averagePostPerDay
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
          generatedDate: formatDate(new Date()),
          generatedTime: new Date().toLocaleTimeString(),
          period: "Last 30 days",
          type: type,
          summary: type === "polls" ? {
            totalPolls: analytics.polls?.total || 0,
            activePolls: analytics.polls?.active || 0,
            averagePollPerDay: analytics.polls?.averagePollPerDay || "0.0",
            totalVotes: analytics.polls?.totalVotes || 0,
            lowParticipation: polls.filter(p => p.participation < 30).length,
            hasDisputes: polls.filter(p => p.hasDisputes).length,
            disabledPolls: polls.filter(p => p.status === "disabled").length
          } : {
            totalPetitions: analytics.petitions?.total || 0,
            activePetitions: analytics.petitions?.active || 0,
            successfulPetitions: analytics.petitions?.successful || 0,
            averagePetitionPerDay: analytics.petitions?.averagePetitionPerDay || "0.0",
            totalSupporters: analytics.petitions?.totalSupporters || 0,
            lowParticipation: petitions.filter(p => p.participation < 30).length,
            disabledPetitions: petitions.filter(p => p.status === "disabled").length
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
    const summary = analyticsPreview.data.summary;
    const title = (type?.charAt(0).toUpperCase() + type?.slice(1)) + " Analytics Report";

    // Build summary rows based on report type
    let rows = "";
    if (type === "forum") {
      rows = `
        <tr><td>Total Reports</td><td>${summary.totalReports ?? 0}</td></tr>
        <tr><td>Pending Reports</td><td>${summary.pendingReports ?? 0}</td></tr>
        <tr><td>Resolved Reports</td><td>${summary.resolvedReports ?? 0}</td></tr>
        <tr><td>Dismissed Reports</td><td>${summary.dismissedReports ?? 0}</td></tr>
        <tr><td>Total Muted Users</td><td>${summary.mutedUsers ?? 0}</td></tr>
        <tr><td>Total Warnings</td><td>${summary.warningsIssued ?? 0}</td></tr>
        <tr><td>Total Deleted Posts</td><td>${summary.deletedContent ?? 0}</td></tr>
        <tr><td>Average Post Per Day</td><td>${summary.averagePostPerDay ?? 0}</td></tr>
      `;
    } else if (type === "polls") {
      rows = `
        <tr><td>Total Polls</td><td>${summary.totalPolls ?? 0}</td></tr>
        <tr><td>Active Polls</td><td>${summary.activePolls ?? 0}</td></tr>
        <tr><td>Average Poll Per Day</td><td>${summary.averagePollPerDay ?? 0}</td></tr>
        <tr><td>Total Votes</td><td>${summary.totalVotes ?? 0}</td></tr>
        <tr><td>Low Participation</td><td>${summary.lowParticipation ?? 0}</td></tr>
        <tr><td>Has Disputes</td><td>${summary.hasDisputes ?? 0}</td></tr>
        <tr><td>Disabled Polls</td><td>${summary.disabledPolls ?? 0}</td></tr>
      `;
    } else if (type === "petitions") {
      rows = `
        <tr><td>Total Petitions</td><td>${summary.totalPetitions ?? 0}</td></tr>
        <tr><td>Active Petitions</td><td>${summary.activePetitions ?? 0}</td></tr>
        <tr><td>Successful Petitions</td><td>${summary.successfulPetitions ?? 0}</td></tr>
        <tr><td>Average Petition Per Day</td><td>${summary.averagePetitionPerDay ?? 0}</td></tr>
        <tr><td>Total Supporters</td><td>${summary.totalSupporters ?? 0}</td></tr>
        <tr><td>Low Participation</td><td>${summary.lowParticipation ?? 0}</td></tr>
        <tr><td>Disabled Petitions</td><td>${summary.disabledPetitions ?? 0}</td></tr>
      `;
    }

    const html = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>${title}</title>
        <style>
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body { font-family: Arial, Helvetica, sans-serif; padding: 40px; color: #1a1a2e; }
          .header { text-align: center; margin-bottom: 32px; border-bottom: 2px solid #0e5ec6; padding-bottom: 20px; }
          .header h1 { font-size: 24px; color: #0e5ec6; margin-bottom: 8px; }
          .header p { font-size: 13px; color: #555; }
          .badge { display: inline-block; background: #e0edff; color: #0e5ec6; padding: 4px 14px; border-radius: 6px; font-weight: 700; font-size: 13px; margin-top: 8px; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
          th { background: #f0f6ff; color: #0e5ec6; font-weight: 700; font-size: 13px; text-transform: uppercase; }
          td:last-child { font-weight: 700; text-align: right; font-size: 18px; }
          tr:hover { background: #f8fbff; }
          .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #e2e8f0; padding-top: 16px; }
          @media print { body { padding: 20px; } }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>${title}</h1>
          <p>Generated on ${analyticsPreview.data.generatedDate} at ${analyticsPreview.data.generatedTime}</p>
          <p>Period: ${analyticsPreview.data.period}</p>
          <span class="badge">${type?.toUpperCase()}</span>
        </div>
        <table>
          <thead><tr><th>Metric</th><th style="text-align:right">Value</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
        <div class="footer">
          <p>Community Platform — Admin Analytics Report</p>
        </div>
      </body>
      </html>
    `;

    // Open in a new tab and trigger print (Save as PDF)
    const printWindow = window.open("", "_blank");
    if (printWindow) {
      printWindow.document.write(html);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => { printWindow.print(); }, 400);
    }

    setAnalyticsPreview({ isOpen: false, type: null, data: null });
  };

  const confirmDisable = async () => {
    const { itemId, type } = disableDialog;
    try {
      const endpoint = type === "poll"
        ? `/api/poll-petition/admin/polls/${itemId}/disable`
        : `/api/poll-petition/admin/petitions/${itemId}/disable`;
      const res = await apiFetch(endpoint, { method: 'POST' });
      if (res.ok) {
        if (type === "poll") {
          setPolls(prev => prev.map(p => p.id === itemId ? { ...p, status: "disabled" as const } : p));
        } else {
          setPetitions(prev => prev.map(p => p.id === itemId ? { ...p, status: "disabled" as const } : p));
        }
      }
    } catch (err) {
      console.error(`Failed to disable ${type}:`, err);
    }
    setDisableDialog({ isOpen: false, itemId: "", type: "poll", title: "" });
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

  const filteredPolls = pollSearchTerm
    ? polls.filter(p => p.title.toLowerCase().includes(pollSearchTerm.toLowerCase()) || p.creator.toLowerCase().includes(pollSearchTerm.toLowerCase()) || p.category.toLowerCase().includes(pollSearchTerm.toLowerCase()))
    : polls;

  const filteredPetitions = petitionSearchTerm
    ? petitions.filter(p => p.title.toLowerCase().includes(petitionSearchTerm.toLowerCase()) || p.creator.toLowerCase().includes(petitionSearchTerm.toLowerCase()) || p.description.toLowerCase().includes(petitionSearchTerm.toLowerCase()))
    : petitions;

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
                    <Flag className="h-5 w-5 text-indigo-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{stats.totalReports}</p>
                    <p className="text-xs text-gray-600">Total Reports</p>
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
                                {/* <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => onViewContent(report.contentId, report.contentType)}
                                >
                                  <Eye className="h-4 w-4 mr-1" />
                                  View
                                </Button> */}
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
                                {/* <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => onViewContent(report.contentId, report.contentType)}
                                >
                                  <Eye className="h-4 w-4 mr-1" />
                                  View
                                </Button> */}
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

            {/* Poll Search */}
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                <Input
                  placeholder="Search polls..."
                  value={pollSearchTerm}
                  onChange={(e) => setPollSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
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
                ) : filteredPolls.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    {pollSearchTerm ? "No polls match your search." : "No polls found."}
                  </div>
                ) : filteredPolls.map((poll) => (
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
                        poll.status === "disabled" ? "bg-red-100 text-red-700" :
                        "bg-orange-100 text-orange-700"
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
                          onClick={() => setDisableDialog({ isOpen: true, itemId: poll.id, type: "poll", title: poll.title })}
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

              <Card className="p-4 flex-1 min-w-[120px] border border-gray-200">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <PauseCircle className="h-5 w-5 text-gray-600" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{petitions.filter(p => p.status === "disabled").length}</p>
                    <p className="text-xs text-gray-600">Disabled Petitions</p>
                  </div>
                </div>
              </Card>

            </div>

            {/* Petition Search */}
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                <Input
                  placeholder="Search petitions..."
                  value={petitionSearchTerm}
                  onChange={(e) => setPetitionSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
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
                ) : filteredPetitions.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    {petitionSearchTerm ? "No petitions match your search." : "No petitions found."}
                  </div>
                ) : filteredPetitions.map((petition) => {
                  const progress = (petition.supporters / petition.goal) * 100;
                  return (
                    <div 
                      key={petition.id} 
                      className={`p-4 border-2 rounded-lg ${
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
                          petition.status === "disabled" ? "bg-red-100 text-red-700" :
                          "bg-orange-100 text-orange-700"
                        }>
                          {petition.status}
                        </Badge>
                      </div>

                      <div className="flex gap-2 pt-3 border-t border-gray-200">
                        <Button size="sm" variant="outline" onClick={() => onViewContent(petition.id, "petition")}>
                          <Eye className="h-4 w-4 mr-1 cursor-pointer" />
                          View
                        </Button>
                        {(petition.status === "active" || petition.status === "expired" || petition.status === "closed") && (
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
                            onClick={() => setDisableDialog({ isOpen: true, itemId: petition.id, type: "petition", title: petition.title })}
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

      {/* Disable Confirmation Dialog */}
      <Dialog
        open={disableDialog.isOpen}
        onOpenChange={(open) => {
          if (!open) setDisableDialog({ isOpen: false, itemId: "", type: "poll", title: "" });
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              Disable {disableDialog.type === "poll" ? "Poll" : "Petition"}
            </DialogTitle>
            <DialogDescription>
              Are you sure you want to disable this {disableDialog.type}? This action will prevent further participation.
            </DialogDescription>
          </DialogHeader>
          <div className="py-3">
            <p className="text-sm text-gray-700 font-medium">{disableDialog.title}</p>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setDisableDialog({ isOpen: false, itemId: "", type: "poll", title: "" })}
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={confirmDisable}
            >
              <PauseCircle className="h-4 w-4 mr-2" />
              Confirm Disable
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

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
                    Currently muted until {formatDate(authorHistory.mutedUntil!)}
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
                  <Card className="p-6 bg-purple-50 border-purple-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Muted Users</p>
                    <p className="text-3xl font-bold text-purple-900 mt-8">{analyticsPreview.data?.summary.mutedUsers}</p>
                  </Card>
                  <Card className="p-6 bg-orange-50 border-orange-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Warnings</p>
                    <p className="text-3xl font-bold text-orange-900 mt-8">{analyticsPreview.data?.summary.warningsIssued}</p>
                  </Card>
                  <Card className="p-6 bg-red-50 border-red-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Deleted Posts</p>
                    <p className="text-3xl font-bold text-red-900 mt-8">{analyticsPreview.data?.summary.deletedContent}</p>
                  </Card>
                  {/* <Card className="p-6 bg-indigo-50 border-indigo-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Average Post Per Day</p>
                    <p className="text-3xl font-bold text-indigo-900 mt-8">{analyticsPreview.data?.summary.averagePostPerDay}</p>
                  </Card> */}
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
                  {/* <Card className="p-6 bg-purple-50 border-purple-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Average Poll Per Day</p>
                    <p className="text-3xl font-bold text-purple-900 mt-8">{analyticsPreview.data?.summary.averagePollPerDay}</p>
                  </Card> */}
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
                  <Card className="p-6 bg-gray-50 border-gray-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Disabled Polls</p>
                    <p className="text-3xl font-bold text-gray-900 mt-8">{analyticsPreview.data?.summary.disabledPolls}</p>
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
                  {/* <Card className="p-6 bg-purple-50 border-purple-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Average Petition Per Day</p>
                    <p className="text-3xl font-bold text-purple-900 mt-8">{analyticsPreview.data?.summary.averagePetitionPerDay}</p>
                  </Card> */}
                  <Card className="p-6 bg-indigo-50 border-indigo-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Total Supporters</p>
                    <p className="text-3xl font-bold text-indigo-900 mt-8">{analyticsPreview.data?.summary.totalSupporters}</p>
                  </Card>
                  <Card className="p-6 bg-red-50 border-red-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Low Participation</p>
                    <p className="text-3xl font-bold text-red-900 mt-8">{analyticsPreview.data?.summary.lowParticipation}</p>
                  </Card>
                  <Card className="p-6 bg-gray-50 border-gray-200 h-[120px] overflow-hidden gap-0 block">
                    <p className="text-sm text-gray-600">Disabled Petitions</p>
                    <p className="text-3xl font-bold text-gray-900 mt-8">{analyticsPreview.data?.summary.disabledPetitions}</p>
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
