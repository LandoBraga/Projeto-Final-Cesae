<?php

namespace App\Traits;

use App\Models\Audit;
use App\Models\User;

trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            // Regista eventos do modelo dinamicamente (created/updated/deleted).
            // Usar call_user_func para invocar o método estático de registo de evento (ex. static::created(...)).
            call_user_func([static::class, $event], function ($model) use ($event) {
                try {
                    $request = null;
                    if (function_exists('request')) {
                        $request = request();
                    }

                    $userId = null;
                    // Tenta obter o utilizador autenticado (o guard pode ser nulo em alguns contextos)
                    $authGuard = null;
                    if (function_exists('auth')) {
                        $authGuard = auth();
                    }

                    if ($authGuard && method_exists($authGuard, 'user')) {
                        $authUser = $authGuard->user();
                        if ($authUser) {
                            // Suporte para objectos de utilizador Eloquent
                            $userId = $authUser->id ?? ($authUser->getKey() ?? null);
                        }
                    } elseif ($request) {
                        $token = $request->header('X-Auth-Token') ?: $request->bearerToken();
                        if (is_string($token) && $token !== '') {
                            $u = User::where('api_token', $token)->first();
                            $userId = $u ? $u->id : null;
                        }
                    }

                    $old = null;
                    $new = null;

                    if ($event === 'created') {
                        $new = $model->getAttributes();
                    } elseif ($event === 'deleted') {
                        $old = $model->getOriginal();
                    } else { // updated
                        $changes = $model->getChanges();
                        if (!empty($changes)) {
                            $oldVals = [];
                            $newVals = [];
                            foreach ($changes as $k => $v) {
                                $oldVals[$k] = $model->getOriginal($k);
                                $newVals[$k] = $v;
                            }
                            $old = $oldVals;
                            $new = $newVals;
                        }
                    }

                    Audit::create([
                        'user_id'        => $userId,
                        'auditable_type' => get_class($model),
                        'auditable_id'   => $model->getKey(),
                        'event'          => $event,
                        'old_values'     => $old,
                        'new_values'     => $new,
                        'url'            => $request ? $request->fullUrl() : null,
                        'ip_address'     => $request ? $request->ip() : null,
                        'user_agent'     => $request ? $request->userAgent() : null,
                    ]);
                } catch (\Throwable $e) {
                    // Falha silenciosamente para não quebrar o fluxo principal se a auditoria falhar
                }
            });
        }
    }
}
