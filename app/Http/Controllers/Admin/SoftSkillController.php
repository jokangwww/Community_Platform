<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SoftSkillCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SoftSkillController extends Controller
{
    private const ELEMENTS = ['cs', 'ctps', 'ts', 'll', 'kk', 'em', 'ls'];

    // Helper method: parse element array.
    private function parseElementArray(array $validated, string $prefix): array
    {
        $result = [];
        foreach (self::ELEMENTS as $element) {
            $column = $prefix . '_' . $element;
            $result[$column] = (int) ($validated[$column] ?? 0);
        }

        return $result;
    }

    // Helper method: parse position rule rows.
    private function parsePositionRuleRows(array $input): array
    {
        $names = $input['rule_position_name'] ?? [];
        $elementArrays = [];
        foreach (self::ELEMENTS as $element) {
            $elementArrays[$element] = $input['rule_' . $element] ?? [];
        }

        $max = count($names);
        foreach ($elementArrays as $values) {
            $max = max($max, count($values));
        }

        $rows = [];
        for ($i = 0; $i < $max; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $hasAnyValue = false;
            $scores = [];
            foreach (self::ELEMENTS as $element) {
                $raw = $elementArrays[$element][$i] ?? null;
                $hasValue = $raw !== null && $raw !== '';
                $hasAnyValue = $hasAnyValue || $hasValue;
                if ($hasValue && (!is_numeric((string) $raw) || (int) $raw < 0 || (int) $raw > 3)) {
                    throw ValidationException::withMessages([
                        'rule_' . $element . '.' . $i => strtoupper($element) . ' score must be between 0 and 3.',
                    ]);
                }
                $scores[$element] = $hasValue ? (int) $raw : 0;
            }

            if ($name === '' && ! $hasAnyValue) {
                continue;
            }
            if ($name === '') {
                throw ValidationException::withMessages([
                    'rule_position_name.' . $i => 'Position name is required when scores are entered.',
                ]);
            }

            $rows[] = array_merge(['position_name' => $name], $scores);
        }

        return collect($rows)
            ->unique(fn (array $row) => strtolower($row['position_name']))
            ->values()
            ->all();
    }

    // Load the main page listing and apply request filters if provided.
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->with(['club', 'softSkillCategory'])
            ->where('approval_status', 'approved')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('club', function ($clubQuery) use ($keyword) {
                            $clubQuery->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('display_name', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->latest()
            ->get();

        $categories = SoftSkillCategory::query()
            ->with('positionRules')
            ->orderBy('name')
            ->get();

        return view('admin.soft-skill-settings', [
            'events' => $events,
            'categories' => $categories,
            'filters' => ['q' => $keyword],
        ]);
    }

    // Controller action: store category.
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:soft_skill_categories,name'],
            'participant_cs' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ctps' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ts' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ll' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_kk' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_em' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ls' => ['required', 'integer', 'min:0', 'max:3'],
            'rule_position_name' => ['nullable', 'array'],
            'rule_position_name.*' => ['nullable', 'string', 'max:255'],
            'rule_cs' => ['nullable', 'array'],
            'rule_ctps' => ['nullable', 'array'],
            'rule_ts' => ['nullable', 'array'],
            'rule_ll' => ['nullable', 'array'],
            'rule_kk' => ['nullable', 'array'],
            'rule_em' => ['nullable', 'array'],
            'rule_ls' => ['nullable', 'array'],
        ]);

        $category = SoftSkillCategory::create([
            'name' => trim($validated['name']),
            ...$this->parseElementArray($validated, 'participant'),
        ]);

        foreach ($this->parsePositionRuleRows($validated) as $row) {
            $category->positionRules()->create($row);
        }

        return back()->with('status', 'Soft skill category created: ' . $category->name);
    }

    // Controller action: update category.
    public function updateCategory(Request $request, SoftSkillCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('soft_skill_categories', 'name')->ignore($category->id)],
            'participant_cs' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ctps' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ts' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ll' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_kk' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_em' => ['required', 'integer', 'min:0', 'max:3'],
            'participant_ls' => ['required', 'integer', 'min:0', 'max:3'],
            'rule_position_name' => ['nullable', 'array'],
            'rule_position_name.*' => ['nullable', 'string', 'max:255'],
            'rule_cs' => ['nullable', 'array'],
            'rule_ctps' => ['nullable', 'array'],
            'rule_ts' => ['nullable', 'array'],
            'rule_ll' => ['nullable', 'array'],
            'rule_kk' => ['nullable', 'array'],
            'rule_em' => ['nullable', 'array'],
            'rule_ls' => ['nullable', 'array'],
        ]);

        $category->update([
            'name' => trim($validated['name']),
            ...$this->parseElementArray($validated, 'participant'),
        ]);

        $category->positionRules()->delete();
        foreach ($this->parsePositionRuleRows($validated) as $row) {
            $category->positionRules()->create($row);
        }

        return back()->with('status', 'Soft skill category updated: ' . $category->name);
    }

    // Controller action: delete category.
    public function destroyCategory(SoftSkillCategory $category)
    {
        $categoryName = $category->name;
        $category->delete();

        return back()->with('status', 'Soft skill category removed: ' . $categoryName);
    }

    // Controller action: assign event category.
    public function assignEventCategory(Request $request, Event $event)
    {
        $validated = $request->validate([
            'soft_skill_category_id' => ['nullable', 'integer', 'exists:soft_skill_categories,id'],
        ]);

        $event->update([
            'soft_skill_category_id' => $validated['soft_skill_category_id'] ?? null,
        ]);

        return back()->with('status', 'Event category updated for: ' . $event->name);
    }

    // Controller action: apply category to all.
    public function applyCategoryToAll(Request $request)
    {
        $validated = $request->validate([
            'soft_skill_category_id' => ['required', 'integer', 'exists:soft_skill_categories,id'],
        ]);

        $count = Event::query()
            ->where('approval_status', 'approved')
            ->update(['soft_skill_category_id' => (int) $validated['soft_skill_category_id']]);

        return back()->with('status', 'Category applied to all approved events (' . $count . ').');
    }
}
