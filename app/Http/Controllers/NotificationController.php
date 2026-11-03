<?php

namespace App\Http\Controllers;

use App\Models\HrmsNotification;
use App\Models\User;
use App\Services\CompanyContextService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected CompanyContextService $companyContext
    ) {}

    /**
     * User's personal notification history.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = HrmsNotification::withoutGlobalScopes()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class);

        // Optional company isolation if assigned
        if ($companyId && !$user->isHoAdmin()) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        // Read/Unread filter
        if ($request->query('status') === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->query('status') === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = Auth::user();
        $success = $this->notificationService->markAsRead($user, $id);

        if ($request->wantsJson()) {
            return response()->json(['success' => $success]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'updated' => $count]);
        }

        return back()->with('success', "Marked {$count} notifications as read.");
    }

    /**
     * JSON feed for interactive bell dropdown.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = Auth::user();
        $unreadCount = $this->notificationService->getUnreadCount($user);
        $recent = $this->notificationService->getRecentNotifications($user, 6);

        $items = $recent->map(function ($notif) {
            $data = is_array($notif->data) ? $notif->data : (json_decode($notif->data, true) ?? []);
            return [
                'id' => $notif->id,
                'title' => $data['title'] ?? 'Notification',
                'body' => $data['body'] ?? '',
                'action_url' => $data['action_url'] ?? null,
                'category' => $notif->category ?? 'Compliance',
                'icon' => $data['icon'] ?? 'bi-bell',
                'is_read' => $notif->read_at !== null,
                'time_ago' => $notif->created_at?->diffForHumans() ?? '',
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $items,
        ]);
    }

    /**
     * User notification channel preferences.
     */
    public function preferences(Request $request)
    {
        $user = Auth::user();
        $preferences = $this->notificationService->getUserPreferences($user);

        // Group by category
        $grouped = $preferences->groupBy('category');

        return view('notifications.preferences', compact('grouped'));
    }

    /**
     * Save user notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        $submitted = $request->input('preferences', []);

        $this->notificationService->updateUserPreferences($user, $submitted);

        return back()->with('success', 'Notification preferences updated successfully.');
    }
}

