<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'role'           => $this->role,
            'nik'            => $this->nik,
            'position'       => $this->position,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'bio'            => $this->bio,
            'avatar'         => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'whatsapp_number'=> $this->whatsapp_number,
            'whatsapp_notification' => (bool) $this->whatsapp_notification,
            'telegram_chat_id' => $this->telegram_chat_id,
            'telegram_username' => $this->telegram_username,
            'telegram_notification' => (bool) $this->telegram_notification,
            'is_active'      => (bool) $this->is_active,
            'is_confirmed'   => (bool) $this->is_confirmed,
            'face_registered'=> (bool) $this->whenLoaded('faceRegistration', fn() => $this->faceRegistration !== null),
            'created_at'     => $this->created_at,
        ];
    }
}
