<?php

namespace App\Http\Controllers\Api\Mac;

use App\Http\Controllers\Controller;
use App\Models\AuthorStatistic;
use App\Models\WordPress\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = mac_app_user($request);
        if (!$user->isSuperAdmin() && !$user->isEditor()) {
            abort(403);
        }

        $users = User::with(['userRole.role', 'statistics', 'activePressCard'])
            ->whereHas('userRole')
            ->orderBy('display_name')
            ->get();

        foreach ($users as $staffUser) {
            AuthorStatistic::updateForUser($staffUser->ID);
        }

        $users->load(['statistics', 'activePressCard']);

        return response()->json([
            'data' => $users->map(function (User $u) {
                $role = $u->getRole();
                $pressCard = $u->activePressCard;
                $statistics = $u->statistics;

                return [
                'id' => $u->ID,
                'name' => $u->display_name,
                'email' => $u->user_email,
                'login' => $u->user_login,
                'slug' => $u->user_nicename,
                'position' => $u->getPosition(),
                'role' => $role ? $role->name : null,
                'role_label' => $role ? $role->display_name : null,
                'active' => $u->admin_account_active !== false,
                'press_card' => $pressCard ? [
                    'id' => $pressCard->id,
                    'card_number' => $pressCard->card_number,
                    'status' => $pressCard->status,
                    'status_label' => $pressCard->statusLabel(),
                    'expires_at' => $pressCard->expires_at ? $pressCard->expires_at->format('Y-m-d') : null,
                    'verify_url' => $pressCard->verifyUrl(),
                ] : null,
                'statistics' => $statistics ? [
                    'total_posts' => $statistics->total_posts,
                    'published_posts' => $statistics->published_posts,
                    'draft_posts' => $statistics->draft_posts,
                    'this_month_posts' => $statistics->this_month_posts,
                    'this_week_posts' => $statistics->this_week_posts,
                    'total_views' => $statistics->total_views,
                    'total_comments' => $statistics->total_comments,
                    'last_post_date' => $statistics->last_post_date ? $statistics->last_post_date->format('Y-m-d') : null,
                ] : null,
                ];
            }),
        ]);
    }

    public function setActive(Request $request, int $id)
    {
        $actor = mac_app_user($request);
        if (!$actor->isSuperAdmin() && !$actor->isEditor()) {
            abort(403);
        }

        $validated = $request->validate(['active' => 'required|boolean']);
        $target = User::findOrFail($id);
        $target->admin_account_active = $validated['active'];
        $target->save();

        return response()->json(['ok' => true, 'active' => (bool) $target->admin_account_active]);
    }
}
