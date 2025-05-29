<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $post;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($post, $userId)
    // public function __construct($post)
    {

        // dd('PostCreated', $post, $userId);
        $this->post = $post;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        // If you want to broadcast to a specific user, use the PrivateChannel
        return new PrivateChannel('user.' . $this->userId);
        // return new Channel('posts');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => 'A new post with title  '. $this->post['message'] .' has been created ',
            'post' => $this->post,
            // 'message' => 'A new post with title  '. $this->post->title  . ' with content '. $this->post->content .' has been created ',
            // 'message' => '<b style="color:blue;">'. 'Event '.$this->post->event_loop_count .' . ' .'</b>'. ' A new post created with title  <b style="color:red;">'. $this->post->title .'</b> '.' for the USER ID '. '<b>' . $this->userId .'</b>',
        ];
    }
    /**
     * Get the name of the event.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'create';
    }


}
