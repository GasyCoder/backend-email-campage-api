<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;

class WorkspaceController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'workspace' => app('workspace'),
        ]);
    }
}
