<?php

namespace App\Http\Controllers;

use App\Models\PressCard;
use App\Models\WordPress\User;
use App\Services\PressCardPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PressCardController extends Controller
{
    public function __construct(private PressCardPdfService $pdfService)
    {
    }

    public function index()
    {
        PressCard::syncExpiredStatuses();

        $cards = PressCard::query()
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(20);

        $userIds = $cards->pluck('user_id')->unique()->filter();
        $users = User::query()->whereIn('ID', $userIds)->get()->keyBy('ID');
        $cards->getCollection()->transform(function (PressCard $card) use ($users) {
            $card->setRelation('wpUser', $users->get($card->user_id));

            return $card;
        });

        return view('admin.press-cards.index', compact('cards'));
    }

    public function create(Request $request)
    {
        $users = User::query()
            ->whereHas('userRole')
            ->orderBy('display_name')
            ->get();

        $selectedUser = null;
        if ($request->filled('user_id')) {
            $selectedUser = User::with('userRole')->find($request->integer('user_id'));
        }

        return view('admin.press-cards.create', compact('users', 'selectedUser'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCard($request);

        $user = User::with('userRole')->findOrFail($validated['user_id']);

        $photoPath = $this->storePhoto($request);

        $card = PressCard::create([
            'user_id' => $user->ID,
            'card_number' => PressCard::generateCardNumber(),
            'full_name' => $validated['full_name'],
            'position' => $validated['position'] ?? $user->getPosition(),
            'organization' => $validated['organization'],
            'photo_path' => $photoPath,
            'issued_at' => $validated['issued_at'],
            'expires_at' => $validated['expires_at'],
            'status' => 'active',
            'issued_by' => admin_user()?->ID,
            'notes' => $validated['notes'] ?? null,
        ]);

        admin_log('press_card_issued', "Выдана пресс-карта {$card->card_number} для {$card->full_name}");

        return redirect()
            ->route('admin.press-cards.show', $card->id)
            ->with('success', "Пресс-карта {$card->card_number} успешно выдана.");
    }

    public function show($id)
    {
        $card = PressCard::findOrFail($id);
        $card->setRelation('wpUser', User::find($card->user_id));

        return view('admin.press-cards.show', compact('card'));
    }

    public function edit($id)
    {
        $card = PressCard::findOrFail($id);
        $card->setRelation('wpUser', User::find($card->user_id));

        return view('admin.press-cards.edit', compact('card'));
    }

    public function update(Request $request, $id)
    {
        $card = PressCard::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'organization' => 'required|string|max:255',
            'issued_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:issued_at',
            'status' => 'required|in:active,revoked,expired',
            'notes' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:4096',
        ]);

        $photoPath = $this->storePhoto($request) ?? $card->photo_path;

        $card->update([
            'full_name' => $validated['full_name'],
            'position' => $validated['position'],
            'organization' => $validated['organization'],
            'photo_path' => $photoPath,
            'issued_at' => $validated['issued_at'],
            'expires_at' => $validated['expires_at'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        admin_log('press_card_updated', "Обновлена пресс-карта {$card->card_number}");

        return redirect()
            ->route('admin.press-cards.show', $card->id)
            ->with('success', 'Пресс-карта обновлена.');
    }

    public function revoke($id)
    {
        $card = PressCard::findOrFail($id);
        $card->update(['status' => 'revoked']);

        admin_log('press_card_revoked', "Отозвана пресс-карта {$card->card_number}");

        return redirect()
            ->route('admin.press-cards.index')
            ->with('success', "Пресс-карта {$card->card_number} отозвана.");
    }

    public function pdf($id)
    {
        $card = PressCard::findOrFail($id);

        return $this->pdfService->downloadResponse($card);
    }

    public function preview($id)
    {
        $card = PressCard::findOrFail($id);

        return view('pdf.press-card', app(PressCardPdfService::class)->viewData($card, forPrint: true));
    }

    public function verify(string $cardNumber)
    {
        $card = PressCard::query()->where('card_number', $cardNumber)->first();

        return view('frontend.press-verify', compact('card', 'cardNumber'));
    }

    private function validateCard(Request $request, ?int $fixedUserId = null): array
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'organization' => 'required|string|max:255',
            'issued_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:issued_at',
            'notes' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:4096',
        ];

        if ($fixedUserId === null) {
            $rules['user_id'] = 'required|integer';
        }

        return $request->validate($rules);
    }

    private function storePhoto(Request $request): ?string
    {
        if (!$request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('press-cards/photos', 'public');
    }
}
