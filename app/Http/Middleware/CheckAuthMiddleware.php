<?php

namespace App\Http\Middleware;

use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Models\HakAksesModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authToken = ToolsHelper::getAuthToken();
        if (empty($authToken)) {
            // Jika token auth tidak diset, redirect ke halaman login
            return redirect()->route('auth.login');
        }

        $response = UserApi::getMe($authToken);

        if (! isset($response->data->user)) {
            // Jika user tidak ditemukan, redirect ke halaman login
            return redirect()->route('auth.login');
        }

        // Dapatkan hak akses user
        $auth = $response->data->user;
        $akses = HakAksesModel::where('user_id', $auth->id)->first();
        $auth->akses = isset($akses->akses) ? explode(',', $akses->akses) : [];

        if (! in_array('Admin', $auth->akses)) {
            // PERBAIKAN: Jika bukan Admin, redirect ke landing page
            // Opsional: Anda bisa menambahkan ->with('error', '...') jika ingin menampilkan pesan
            return redirect()->route('landing.index');
        }

        $request->attributes->set('auth', $auth);

        return $next($request);
    }
}
