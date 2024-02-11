<?php

namespace ByteBlitz\Notify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotifyTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function sanitizeMailMessage($params) {
        $message = $this->mail_msg;
        foreach($params as $key => $value) {
            $message = str_replace('{'.$key.'}', $value, $message);
        }
        return $message;
    }

    public function sanitizeNotificationMessage($params) {
        $message = $this->notification_msg;
        foreach($params as $key => $value) {
            $message = str_replace('{'.$key.'}', $value, $message);
        }
        return $message;
    }
}
