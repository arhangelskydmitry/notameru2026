<?php

namespace App\Http\Controllers\Api\Mac;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PressCardController as WebPressCardController;
use App\Models\PressCard;
use App\Models\WordPress\User;
use Illuminate\Http\Request;

class PressCardApiController extends Controller
{
    public function index()
    {
        PressCard::syncExpiredStatuses();
        $cards = PressCard::with(['wpUser.userRole.role', 'wpUser.statistics'])
            ->orderByDesc('issued_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $cards->map(fn (PressCard $c) => $this->item($c)),
        ]);
    }

    public function store(Request $request)
    {
        $user = mac_app_user($request);
        if (!$user->isSuperAdmin() && !$user->isEditor()) {
            abort(403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'organization' => 'required|string|max:255',
            'issued_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:issued_at',
            'notes' => 'nullable|string',
        ]);

        $card = PressCard::create([
            'user_id' => $validated['user_id'],
            'card_number' => PressCard::generateCardNumber(),
            'full_name' => $validated['full_name'],
            'position' => $validated['position'] ?? null,
            'organization' => $validated['organization'],
            'issued_at' => $validated['issued_at'],
            'expires_at' => $validated['expires_at'],
            'status' => 'active',
            'issued_by' => $user->ID,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $this->item($card)], 201);
    }

    public function pdf(int $id)
    {
        return app(WebPressCardController::class)->pdf($id);
    }

    public function journalists()
    {
        $users = User::with(['userRole.role', 'statistics', 'activePressCard'])
            ->whereHas('userRole')
            ->orderBy('display_name')
            ->get();

        return response()->json([
            'data' => $users->map(function (User $u) {
                $role = $u->getRole();
                $pressCard = $u->activePressCard;

                return [
                'id' => $u->ID,
                'name' => $u->display_name,
                'email' => $u->user_email,
                'login' => $u->user_login,
                'slug' => $u->user_nicename,
                'position' => $u->getPosition(),
                'role' => $role ? $role->name : null,
                'role_label' => $role ? $role->display_name : null,
                'active_press_card_number' => $pressCard ? $pressCard->card_number : null,
                ];
            }),
        ]);
    }

    private function item(PressCard $card): array
    {
        $cardUser = $card->wpUser;
        $role = $cardUser ? $cardUser->getRole() : null;

        return [
            'id' => $card->id,
            'user_id' => $card->user_id,
            'card_number' => $card->card_number,
            'full_name' => $card->full_name,
            'position' => $card->position,
            'organization' => $card->organization,
            'issued_at' => $card->issued_at->format('Y-m-d'),
            'expires_at' => $card->expires_at->format('Y-m-d'),
            'status' => $card->status,
            'status_label' => $card->statusLabel(),
            'verify_url' => $card->verifyUrl(),
            'pdf_url' => url('/notaadmin/press-cards/' . $card->id . '/pdf'),
            'user' => $cardUser ? [
                'id' => $cardUser->ID,
                'name' => $cardUser->display_name,
                'email' => $cardUser->user_email,
                'login' => $cardUser->user_login,
                'slug' => $cardUser->user_nicename,
                'position' => $cardUser->getPosition(),
                'role' => $role ? $role->name : null,
                'role_label' => $role ? $role->display_name : null,
            ] : null,
        ];
    }
}
