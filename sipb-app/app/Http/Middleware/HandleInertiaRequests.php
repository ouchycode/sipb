<?php

namespace App\Http\Middleware;

use App\Models\FoundItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? [
                        ...$request->user()->only('id', 'name', 'email', 'role'),
                        'role_label' => $request->user()->roleLabel(),
                        'is_super_admin' => $request->user()->isSuperAdmin(),
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => [
                'pending_reports' => fn () => $request->user()
                    ? FoundItem::where('status', 'draft')->count()
                    : 0,
                'latest_reports' => fn () => $request->user()
                    ? FoundItem::query()
                        ->where('status', 'draft')
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'name', 'category', 'location', 'status', 'created_at'])
                        ->map(fn (FoundItem $item) => [
                            'id' => $item->id,
                            'name' => $item->name,
                            'category' => $item->category,
                            'location' => $item->location,
                            'status' => $item->status,
                            'created_at' => $item->created_at?->toISOString(),
                        ])
                    : [],
            ],
        ];
    }
}
