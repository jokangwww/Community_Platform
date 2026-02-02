<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Posting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PostingController extends Controller
{
    private function requireUser(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function favoriteIds(User $user): array
    {
        return $user->favoritePostings()
            ->pluck('postings.id')
            ->all();
    }

    public function index()
    {
        $user = $this->requireUser();

        $postings = Posting::with(['event', 'images'])
            ->latest()
            ->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
            'favoriteIds' => $this->favoriteIds($user),
        ]);
    }

    public function favorites()
    {
        $user = $this->requireUser();

        $postings = $user->favoritePostings()
            ->with(['event', 'images'])
            ->latest('postings.created_at')
            ->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'favorites',
            'favoriteIds' => $this->favoriteIds($user),
        ]);
    }

    public function show(Posting $posting)
    {
        $user = $this->requireUser();

        $posting->load(['event', 'images']);

        return view('user.event-posting-show', [
            'posting' => $posting,
            'favoriteIds' => $this->favoriteIds($user),
        ]);
    }

    public function toggleFavorite(Posting $posting)
    {
        $user = $this->requireUser();

        $user->favoritePostings()->toggle($posting->id);

        return redirect()
            ->back();
    }
}
