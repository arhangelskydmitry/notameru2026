<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    /**
     * Отображение списка счетчиков
     */
    public function index()
    {
        $counters = Counter::orderBy('sort_order')->get();
        return view('admin.counters.index', compact('counters'));
    }

    /**
     * Форма создания счетчика
     */
    public function create()
    {
        return view('admin.counters.create');
    }

    /**
     * Сохранение нового счетчика
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'position' => 'required|string|in:sidebar,footer,header',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Counter::create($validated);

        return redirect()->route('admin.counters.index')
            ->with('success', 'Счетчик успешно создан!');
    }

    /**
     * Форма редактирования счетчика
     */
    public function edit(Counter $counter)
    {
        return view('admin.counters.edit', compact('counter'));
    }

    /**
     * Обновление счетчика
     */
    public function update(Request $request, Counter $counter)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'position' => 'required|string|in:sidebar,footer,header',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $counter->update($validated);

        return redirect()->route('admin.counters.index')
            ->with('success', 'Счетчик успешно обновлен!');
    }

    /**
     * Удаление счетчика
     */
    public function destroy(Counter $counter)
    {
        $counter->delete();

        return redirect()->route('admin.counters.index')
            ->with('success', 'Счетчик успешно удален!');
    }

    /**
     * Быстрое переключение активности
     */
    public function toggleActive(Counter $counter)
    {
        $counter->update(['is_active' => !$counter->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $counter->is_active
        ]);
    }
}
