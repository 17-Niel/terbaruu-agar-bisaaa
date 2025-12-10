<?php

namespace App\Http\Middleware;

use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Models\HakAksesModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek Token, tapi JANGAN redirect jika kosong
        $authToken = ToolsHelper::getAuthToken();

        if (! empty($authToken)) {
            // 2. Jika token ada, coba ambil data user
            $response = UserApi::getMe($authToken);

            if (isset($response->data->user)) {
                $user = $response->data->user;

                // 3. Ambil hak akses
                $akses = HakAksesModel::where('user_id', $user->id)->first();
                $user->akses = isset($akses->akses) ? explode(',', $akses->akses) : [];

                // 4. Simpan ke request attributes (agar bisa dibaca Controller/Inertia)
                $request->attributes->set('auth', $user);
            }
        }

        // 5. Lanjutkan request (apapun hasilnya, tamu tetap boleh masuk)
        return $next($request);
    }
}
