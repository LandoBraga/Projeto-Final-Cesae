<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $this->requireRole($user, [User::ROLE_ADMIN]);

        $audits = Audit::with('user')->orderBy('created_at', 'desc')->limit(200)->get();

        return response()->json(['audits' => $audits]);
    }
}
