<?php

namespace App\Http\Controllers;

use App\Models\CaptionStyle;
use App\Services\Caption\CaptionStyleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaptionStyleController extends Controller
{
    public function index()
    {
        $styles = CaptionStyle::where('user_id', Auth::id())->latest()->get();

        return view('caption-styles.index', compact('styles'));
    }

    public function create()
    {
        return view('caption-styles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'font_family' => 'nullable|string|max:100',
            'font_size' => 'nullable|integer|min:12|max:120',
            'font_color' => 'nullable|string|max:7',
            'stroke_color' => 'nullable|string|max:7',
            'stroke_width' => 'nullable|integer|min:0|max:10',
            'background_color' => 'nullable|string|max:7',
            'position_y' => 'nullable|integer|min:0|max:100',
            'max_lines' => 'nullable|integer|min:1|max:4',
            'chars_per_line' => 'nullable|integer|min:5|max:40',
        ]);

        $validated['user_id'] = Auth::id();

        CaptionStyle::create($validated);

        return redirect()->route('caption-styles.index')
            ->with('success', 'テロップスタイルを作成しました。');
    }

    public function edit(CaptionStyle $captionStyle)
    {
        $this->authorize('update', $captionStyle);

        return view('caption-styles.edit', ['style' => $captionStyle]);
    }

    public function update(Request $request, CaptionStyle $captionStyle)
    {
        $this->authorize('update', $captionStyle);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'font_family' => 'nullable|string|max:100',
            'font_size' => 'nullable|integer|min:12|max:120',
            'font_color' => 'nullable|string|max:7',
            'stroke_color' => 'nullable|string|max:7',
            'stroke_width' => 'nullable|integer|min:0|max:10',
            'background_color' => 'nullable|string|max:7',
            'position_y' => 'nullable|integer|min:0|max:100',
            'max_lines' => 'nullable|integer|min:1|max:4',
            'chars_per_line' => 'nullable|integer|min:5|max:40',
        ]);

        $captionStyle->update($validated);

        return redirect()->route('caption-styles.index')
            ->with('success', 'テロップスタイルを更新しました。');
    }

    public function destroy(CaptionStyle $captionStyle)
    {
        $this->authorize('delete', $captionStyle);

        $captionStyle->delete();

        return redirect()->route('caption-styles.index')
            ->with('success', 'テロップスタイルを削除しました。');
    }

    public function duplicate(CaptionStyle $captionStyle, CaptionStyleService $service)
    {
        $this->authorize('view', $captionStyle);

        $service->duplicate($captionStyle);

        return redirect()->route('caption-styles.index')
            ->with('success', 'テロップスタイルを複製しました。');
    }
}
