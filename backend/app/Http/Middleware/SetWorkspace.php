<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $workspaceId = $request->header('X-Workspace-ID') ?: $user->workspace_id;

        if (!$workspaceId) {
            return response()->json(['message' => 'No workspace assigned to user.'], 422);
        }

        $workspace = Workspace::query()->whereKey($workspaceId)->first();
        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        app()->instance('workspace', $workspace);

        return $next($request);
    }
}
