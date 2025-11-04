<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class AlternativeToken
{

    /**
     * Intercepta o header alternativo e injeta no Authorization
     */

    public function handle(Request $request, Closure $next)
    {

        if (!$request->hasHeader('Authorization')) {

            //Tenta pegar o token alternativo

            $token = $request->header('X-TOKEN');

            if ($token) {

                // Injeta o header Authorization que o JWT espera

                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
