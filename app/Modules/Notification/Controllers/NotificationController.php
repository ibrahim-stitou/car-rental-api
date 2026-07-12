<?php

namespace App\Modules\Notification\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Notification\Enums\NotificationType;
use App\Modules\Notification\Resources\NotificationResource;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function __construct(protected NotificationService $service) {}

    /**
     * @OA\Get(path="/notifications", summary="Liste des notifications de l'utilisateur connecté", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *   @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="severity", in="query", @OA\Schema(type="string", enum={"info","warning","critical"})),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $data = $this->service->getUserNotifications(
            $user,
            $request->integer('per_page', 20),
            $request->input('type'),
            $request->input('severity')
        );

        return $this->paginated($data, NotificationResource::class);
    }

    /**
     * @OA\Get(path="/notifications/unread", summary="Notifications non lues", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function unread(): JsonResponse
    {
        $user = auth('api')->user();
        $notifications = $this->service->getUnreadNotifications($user);
        return $this->success(NotificationResource::collection($notifications));
    }

    /**
     * @OA\Get(path="/notifications/summary", summary="Résumé des notifications (compteur badge)", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function summary(): JsonResponse
    {
        $user = auth('api')->user();
        return $this->success($this->service->getSummary($user));
    }

    /**
     * @OA\Get(path="/notifications/count", summary="Nombre de notifications non lues", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth('api')->user();
        return $this->success(['count' => $this->service->getUnreadCount($user)]);
    }

    /**
     * @OA\Get(path="/notifications/{id}", summary="Détail d'une notification", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $user = auth('api')->user();
        $notification = $user->notifications()->find($id);
        if (!$notification) {
            return $this->notFound('Notification not found');
        }
        return $this->success(new NotificationResource($notification));
    }

    /**
     * @OA\Patch(path="/notifications/{id}/read", summary="Marquer une notification comme lue", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = auth('api')->user();
        $result = $this->service->markAsRead($user, $id);
        if (!$result) {
            return $this->notFound('Notification not found');
        }
        return $this->success(null, 'Notification marquée comme lue');
    }

    /**
     * @OA\Post(path="/notifications/read-all", summary="Marquer toutes les notifications comme lues", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth('api')->user();
        $count = $this->service->markAllAsRead($user);
        return $this->success(['marked' => $count], "{$count} notification(s) marquée(s) comme lue(s)");
    }

    /**
     * @OA\Delete(path="/notifications/{id}", summary="Supprimer une notification", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $user = auth('api')->user();
        $result = $this->service->deleteNotification($user, $id);
        if (!$result) {
            return $this->notFound('Notification not found');
        }
        return $this->success(null, 'Notification supprimée');
    }

    /**
     * @OA\Delete(path="/notifications/read", summary="Supprimer toutes les notifications lues", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function deleteAllRead(): JsonResponse
    {
        $user = auth('api')->user();
        $count = $this->service->deleteAllRead($user);
        return $this->success(['deleted' => $count], "{$count} notification(s) supprimée(s)");
    }

    /**
     * @OA\Get(path="/notifications/types", summary="Liste des types de notifications disponibles", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function types(): JsonResponse
    {
        $types = collect(NotificationType::cases())->map(fn(NotificationType $t) => [
            'value'    => $t->value,
            'label'    => $t->label(),
            'severity' => $t->severity(),
            'icon'     => $t->icon(),
        ]);
        return $this->success($types);
    }

    /**
     * @OA\Post(path="/notifications/send", summary="Envoyer une notification manuelle (admin)", tags={"Notifications"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"title","body"},
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="user_ids", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="agency_id", type="string"),
     *     @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="severity", type="string", enum={"info","warning","critical"}),
     *     @OA\Property(property="action_url", type="string")
     *   )),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'title'      => 'required|string|max:500',
            'body'       => 'required|string|max:2000',
            'user_ids'   => 'nullable|array',
            'user_ids.*' => 'uuid|exists:users,id',
            'agency_id'  => 'nullable|uuid|exists:agencies,id',
            'roles'      => 'nullable|array',
            'roles.*'    => 'string|exists:roles,name',
            'severity'   => 'nullable|in:info,warning,critical',
            'action_url' => 'nullable|string',
        ]);

        $payload = [
            'title'      => $request->title,
            'body'       => $request->body,
            'action_url' => $request->action_url,
            'force_mail' => $request->severity === 'critical',
        ];

        $count = 0;

        // Envoi à des utilisateurs spécifiques
        if ($request->filled('user_ids')) {
            $users = \App\Models\User::whereIn('id', $request->user_ids)->get();
            $this->service->sendToUsers($users, NotificationType::SYSTEM_ALERT, $payload);
            $count += $users->count();
        }

        // Envoi à une agence
        if ($request->filled('agency_id')) {
            $this->service->sendToAgency($request->agency_id, NotificationType::SYSTEM_ALERT, $payload, $request->roles ?? []);
            $agencyCount = \App\Models\User::where('agency_id', $request->agency_id)->where('is_active', true)->count();
            $count += $agencyCount;
        }

        // Envoi par rôle (tous les utilisateurs)
        if ($request->filled('roles') && !$request->filled('agency_id') && !$request->filled('user_ids')) {
            $users = \App\Models\User::where('is_active', true)->role($request->roles, 'api')->get();
            $this->service->sendToUsers($users, NotificationType::SYSTEM_ALERT, $payload);
            $count += $users->count();
        }

        // Si aucune cible, envoyer aux admins
        if ($count === 0) {
            $this->service->sendToAdmins(NotificationType::SYSTEM_ALERT, $payload);
            $count = \App\Models\User::where('is_active', true)->role(['super-admin', 'admin'], 'api')->count();
        }

        return $this->success(['recipients' => $count], "Notification envoyée à {$count} utilisateur(s)");
    }
}

