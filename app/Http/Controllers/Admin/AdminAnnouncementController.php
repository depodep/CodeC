<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = collect();
        $total = 0;

        if (Schema::hasTable('announcements')) {
            $query = DB::table('announcements')->orderByDesc('created_at');

            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                      ->orWhere('message', 'like', $search);
                });
            }

            $total = (clone $query)->count();
            $announcements = $query->paginate(15)->withQueryString();
        }

        return view('admin.announcements', compact('announcements', 'total'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'target'  => 'nullable|in:all,students,faculty,management',
        ]);

        if (!Schema::hasTable('announcements')) {
            return back()->with('error', 'Announcements table does not exist. Run migrations first.');
        }

        DB::table('announcements')->insert([
            'title'      => $validated['title'],
            'message'    => $validated['message'],
            'target'     => $validated['target'] ?? 'all',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function destroy(int $id)
    {
        if (Schema::hasTable('announcements')) {
            DB::table('announcements')->where('id', $id)->delete();
        }
        return back()->with('success', 'Announcement deleted.');
    }
}