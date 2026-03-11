<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumAnswer;
use App\Models\Forum\ForumComment;
use App\Models\Forum\ForumPost;
use App\Models\Forum\ForumReport;
use App\Models\User;
use App\Models\UserModerationAction;
use App\Notifications\ModerationActionNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumReportController extends Controller
{
    /**
     * Report content (post, answer, or comment)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_id' => 'required|integer',
            'reportable_type' => 'required|in:post,answer,comment',
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string|max:2000',
        ]);

        $typeMap = [
            'post' => ForumPost::class,
            'answer' => ForumAnswer::class,
            'comment' => ForumComment::class,
        ];

        $morphType = $typeMap[$validated['reportable_type']];

        // Verify the content exists
        $morphType::findOrFail($validated['reportable_id']);

        // Prevent duplicate reports
        $existing = ForumReport::where('reporter_id', Auth::id())
            ->where('reportable_id', $validated['reportable_id'])
            ->where('reportable_type', $morphType)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reported this content.',
            ], 422);
        }

        ForumReport::create([
            'reporter_id' => Auth::id(),
            'reportable_id' => $validated['reportable_id'],
            'reportable_type' => $morphType,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully. It will be reviewed by moderators.',
        ], 201);
    }

    /**
     * List reports (admin only) — returns format matching AdminDashboard interface
     */
    public function index(Request $request): JsonResponse
    {
        $query = ForumReport::with(['reporter'])
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(50);

        $reportsData = $reports->getCollection()->map(function ($report) {
            $contentPreview = '';
            $contentAuthor = 'Unknown';
            $contentType = 'post';

            if ($report->reportable_type === ForumPost::class) {
                $post = ForumPost::with('user')->find($report->reportable_id);
                $contentPreview = $post ? \Illuminate\Support\Str::limit($post->content, 120) : '[Deleted content]';
                $contentAuthor = $post ? ($post->user->nickname ?? $post->user->name ?? 'Unknown') : 'Unknown';
                $contentType = 'post';
            } elseif ($report->reportable_type === ForumAnswer::class) {
                $answer = ForumAnswer::with('user')->find($report->reportable_id);
                $contentPreview = $answer ? \Illuminate\Support\Str::limit($answer->content, 120) : '[Deleted content]';
                $contentAuthor = $answer ? ($answer->user->nickname ?? $answer->user->name ?? 'Unknown') : 'Unknown';
                $contentType = 'answer';
            } elseif ($report->reportable_type === ForumComment::class) {
                $comment = ForumComment::with('user')->find($report->reportable_id);
                $contentPreview = $comment ? \Illuminate\Support\Str::limit($comment->content, 120) : '[Deleted content]';
                $contentAuthor = $comment ? ($comment->user->nickname ?? $comment->user->name ?? 'Unknown') : 'Unknown';
                $contentType = 'comment';
            }

            // Determine priority based on reason
            $highPriorityReasons = ['harassment', 'misinformation'];
            $mediumPriorityReasons = ['spam', 'inappropriate'];
            $priority = in_array($report->reason, $highPriorityReasons) ? 'high'
                : (in_array($report->reason, $mediumPriorityReasons) ? 'medium' : 'low');

            $reporterName = $report->reporter->nickname ?? $report->reporter->name ?? 'Anonymous';
            $reporterAvatar = strtoupper(substr(preg_replace('/[^A-Za-z ]/', '', $reporterName), 0, 1)
                . substr(preg_replace('/[^A-Za-z ]/', '', trim(strstr($reporterName, ' '))), 0, 1));

            // Map status: 'reviewed' in DB may mean 'resolved' or 'dismissed' depending on admin_action
            $status = $report->status;
            if ($status === 'reviewed') {
                $status = str_starts_with($report->admin_action ?? '', 'dismiss') ? 'dismissed' : 'resolved';
            }

            // Extract the base action keyword (before any ': note' suffix)
            $adminAction = $report->admin_action
                ? strtok($report->admin_action, ':')
                : null;

            return [
                'id' => (string) $report->id,
                'contentId' => (string) $report->reportable_id,
                'contentType' => $contentType,
                'reason' => $report->reason,
                'details' => $report->details ?? '',
                'reportedBy' => $reporterName,
                'reportedByAvatar' => $reporterAvatar ?: 'U',
                'reportedAt' => $report->created_at->diffForHumans(),
                'status' => $status,
                'adminAction' => $adminAction ? trim($adminAction) : null,
                'contentPreview' => $contentPreview,
                'contentAuthor' => $contentAuthor,
                'priority' => $priority,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $reportsData,
            'meta' => [
                'currentPage' => $reports->currentPage(),
                'lastPage' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Review a report (admin only)
     */
    public function review(Request $request, int $reportId): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:dismiss,delete,warn,mute,restore',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $report = ForumReport::findOrFail($reportId);

        // Resolve the content author
        $contentAuthor = $this->getContentAuthor($report);
        $contentType = $this->getContentTypeName($report);

        $result = DB::transaction(function () use ($report, $validated, $contentAuthor, $contentType) {
            $report->update([
                'status' => 'reviewed',
                'admin_action' => $validated['action'] . ($validated['admin_note'] ? ': ' . $validated['admin_note'] : ''),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $muteDurationDays = null;

            // Execute action
            switch ($validated['action']) {
                case 'delete':
                    if ($report->reportable_type === ForumPost::class) {
                        ForumPost::where('id', $report->reportable_id)
                            ->update(['status' => 'deleted']);
                    } elseif ($report->reportable_type === ForumAnswer::class) {
                        ForumAnswer::destroy($report->reportable_id);
                    } elseif ($report->reportable_type === ForumComment::class) {
                        ForumComment::destroy($report->reportable_id);
                    }
                    break;

                case 'mute':
                    if ($contentAuthor) {
                        // Count previous mutes for escalation
                        $previousMutes = UserModerationAction::where('user_id', $contentAuthor->id)
                            ->where('action', 'mute')
                            ->count();

                        // Escalation: 1st = 1 day, 2nd = 7 days, 3rd+ = 30 days
                        $muteDurationDays = match (true) {
                            $previousMutes === 0 => 1,
                            $previousMutes === 1 => 7,
                            default              => 30,
                        };

                        $contentAuthor->update([
                            'muted_until' => now()->addDays($muteDurationDays),
                        ]);
                    }
                    break;
            }

            // Log moderation action (for warn, mute, delete)
            if (in_array($validated['action'], ['warn', 'mute', 'delete']) && $contentAuthor) {
                UserModerationAction::create([
                    'user_id'            => $contentAuthor->id,
                    'admin_id'           => Auth::id(),
                    'report_id'          => $report->id,
                    'action'             => $validated['action'],
                    'note'               => $validated['admin_note'],
                    'content_type'       => $contentType,
                    'content_id'         => $report->reportable_id,
                    'mute_duration_days' => $muteDurationDays,
                ]);

                // Send notification
                $totalWarnings = UserModerationAction::where('user_id', $contentAuthor->id)
                    ->where('action', 'warn')->count();
                $totalMutes = UserModerationAction::where('user_id', $contentAuthor->id)
                    ->where('action', 'mute')->count();

                $contentAuthor->notify(new ModerationActionNotification(
                    action: $validated['action'],
                    reason: $report->reason,
                    contentType: $contentType,
                    note: $validated['admin_note'],
                    muteDurationDays: $muteDurationDays,
                    totalWarnings: $totalWarnings,
                    totalMutes: $totalMutes,
                ));
            }

            return [
                'muteDurationDays' => $muteDurationDays,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Report reviewed successfully.',
            'mute_duration_days' => $result['muteDurationDays'],
        ]);
    }

    /**
     * Get moderation history for a content author (warn/mute counts).
     * Used by admin confirmation dialog.
     */
    public function contentAuthorHistory(int $reportId): JsonResponse
    {
        $report = ForumReport::findOrFail($reportId);
        $contentAuthor = $this->getContentAuthor($report);

        if (!$contentAuthor) {
            return response()->json([
                'success' => true,
                'data' => [
                    'authorName' => 'Unknown',
                    'warnCount' => 0,
                    'muteCount' => 0,
                    'currentlyMuted' => false,
                    'mutedUntil' => null,
                    'nextMuteDuration' => 1,
                ],
            ]);
        }

        $warnCount = UserModerationAction::where('user_id', $contentAuthor->id)
            ->where('action', 'warn')->count();
        $muteCount = UserModerationAction::where('user_id', $contentAuthor->id)
            ->where('action', 'mute')->count();

        $nextMuteDuration = match (true) {
            $muteCount === 0 => 1,
            $muteCount === 1 => 7,
            default          => 30,
        };

        return response()->json([
            'success' => true,
            'data' => [
                'authorName' => $contentAuthor->nickname ?? $contentAuthor->name,
                'warnCount' => $warnCount,
                'muteCount' => $muteCount,
                'currentlyMuted' => $contentAuthor->muted_until && $contentAuthor->muted_until->isFuture(),
                'mutedUntil' => $contentAuthor->muted_until?->toDateTimeString(),
                'nextMuteDuration' => $nextMuteDuration,
            ],
        ]);
    }

    /**
     * Resolve the author of the reported content.
     */
    private function getContentAuthor(ForumReport $report): ?User
    {
        if ($report->reportable_type === ForumPost::class) {
            $post = ForumPost::find($report->reportable_id);
            return $post ? User::find($post->user_id) : null;
        } elseif ($report->reportable_type === ForumAnswer::class) {
            $answer = ForumAnswer::find($report->reportable_id);
            return $answer ? User::find($answer->user_id) : null;
        } elseif ($report->reportable_type === ForumComment::class) {
            $comment = ForumComment::find($report->reportable_id);
            return $comment ? User::find($comment->user_id) : null;
        }
        return null;
    }

    /**
     * Get human-readable content type name.
     */
    private function getContentTypeName(ForumReport $report): string
    {
        return match ($report->reportable_type) {
            ForumPost::class    => 'post',
            ForumAnswer::class  => 'answer',
            ForumComment::class => 'comment',
            default             => 'content',
        };
    }

    /**
     * Get admin dashboard stats — matches AdminDashboard ModStats interface
     */
    public function adminStats(): JsonResponse
    {
        $totalReports = ForumReport::count();
        $pendingReports = ForumReport::where('status', 'pending')->count();

        $resolvedToday = ForumReport::where('status', 'reviewed')
            ->whereDate('reviewed_at', today())
            ->count();

        $totalUsers = DB::table('users')->count();

        // Users currently muted (muted_until in the future)
        $mutedUsers = User::whereNotNull('muted_until')
            ->where('muted_until', '>', now())
            ->count();

        // Content deleted via moderation
        $deletedContent = UserModerationAction::where('action', 'delete')->count();

        // Warnings issued
        $warningsIssued = UserModerationAction::where('action', 'warn')->count();

        // Total posts and average per day
        $totalPosts = ForumPost::count();
        $firstPost = ForumPost::orderBy('created_at', 'asc')->first();
        $postDays = $firstPost ? max(1, now()->diffInDays($firstPost->created_at) + 1) : 1;
        $averagePostPerDay = round($totalPosts / $postDays, 1);

        return response()->json([
            'success' => true,
            'data' => [
                'totalReports' => $totalReports,
                'pendingReports' => $pendingReports,
                'resolvedToday' => $resolvedToday,
                'totalUsers' => $totalUsers,
                'mutedUsers' => $mutedUsers,
                'deletedContent' => $deletedContent,
                'warningsIssued' => $warningsIssued,
                'totalPosts' => $totalPosts,
                'averagePostPerDay' => $averagePostPerDay,
            ],
        ]);
    }
}
