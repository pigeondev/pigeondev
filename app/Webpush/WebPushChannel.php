<?php

namespace App\WebPush;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    /** @var \Minishlink\WebPush\WebPush */
    protected $webPush;

    /**
     * @param  \Minishlink\WebPush\WebPush $webPush
     * @return void
     */
    public function __construct(WebPush $webPush)
    {
        $this->webPush = $webPush;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed $notifiable
     * @param  \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $subscriptions = $notifiable->routeNotificationFor('WebPush');

        if (! $subscriptions || $subscriptions->isEmpty()) {
            return;
        }

        //$payload = json_encode($notification->toWebPush($notifiable, $notification)->toArray());

        $auth = array(
            'VAPID' => array(
                'subject' => "This is subject",//env('VAPID_SUBJECT'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY')
            ),
        );

        //mesto za formiranje specificnog URL-a za analizu klikova
        $subscriptions->each(function ($sub) use ($notification, $notifiable, $auth) {
            if($sub->connected) {
                $subscri = new Subscription($sub->endpoint, $sub->public_key, $sub->auth_token);
                //$notification->data = $this->formLink($notification->data, $notification->source, $sub->id);
                $payload = json_encode($notification->toWebPush($sub->id)->toArray());
                $this->webPush->sendNotification(
                    $subscri,
                    $payload,
                    true,
                    [],
                    $auth
                );
            }
        });

        //$response = $this->webPush->flush();

        //$this->deleteInvalidSubscriptions($response, $subscriptions);
    }

    /**
     * @param  array|bool $response
     * @param  \Illuminate\Database\Eloquent\Collection $subscriptions
     * @return void
     */
    protected function deleteInvalidSubscriptions($response, $subscriptions)
    {
        if (! is_array($response)) {
            return;
        }

        foreach ($response as $index => $value) {
            if (! $value['success'] && isset($subscriptions[$index])) {
                $subscriptions[$index]->delete();
            }
        }
    }

    private function formLink($base, $feed, $subscriber)
    {
        $link = $base . '/'. $feed->id . '/' . $subscriber . '/' . $feed->web_push_id . '/wp';
        return $link;
    }
}
