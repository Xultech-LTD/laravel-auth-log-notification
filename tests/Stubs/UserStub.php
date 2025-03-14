<?php
namespace Tests\Stubs;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Xultech\AuthLogNotification\Traits\HasAuthLogs;


class UserStub extends Model implements AuthenticatableContract
{
    use Authenticatable, Notifiable, HasAuthLogs;

    protected $table = 'user_stubs';
    protected $guarded = [];
    public $timestamps = false;


    // This is important for Notification::fake to identify the notifiable
    public function getEmailForNotification()
    {
        return 'test@example.com';
    }

    // Optional, but helpful for Slack/Nexmo routing if needed
    public function routeNotificationForMail()
    {
        return $this->getEmailForNotification();
    }

}

