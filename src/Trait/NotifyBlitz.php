<?php

namespace ByteBlitz\Notify\Trait;

use Illuminate\Support\Facades\Mail;
use ByteBlitz\Notify\Models\Notification;
use Illuminate\Support\Facades\Queue;
use ByteBlitz\Notify\Models\NotifyTemplate;

trait NotifyBlitz
{
    protected $title;
    protected $mailMessage;
    protected $message;

    protected $customEmail;
    protected $customPhone;

    protected $channel=[];

    protected $template=null;

    protected array $params = [];

    protected  $mailObject;

    protected bool $saveToDB=false;
    
    protected string $redirectTo;

    protected array $attachments = [];

    protected array $fcmToken = [];

    protected  $notifications;


    public function template(string $slug, array $params = [], bool $save=false, $redirectTo=null) {
        $this->template = NotifyTemplate::where('slug', $slug)->first();
        $this->params = $params;
        $this->saveToDB = $save;
        $this->redirectTo = $redirectTo;
        return $this;
    }

    public function via($channel) {
        $validChannels = ['mail', 'fcm', 'sms', 'whatsapp'];
        $invalidChannel = array_diff($channel, $validChannels);
        if(!empty($invalidChannel)) {
            throw new \RuntimeException("Invalid channels found: " . implode(', ', $invalidChannel).',  Valid channels are: '.implode(', ', $validChannels));
        }
        $this->channel = $channel;
        return $this;
    }

    public function customMail($mailObject)
    {
        $this->mailObject = $mailObject;
        return $this;
    }

    public function title(string $title) {
        $this->title = $title;
        return $this;
    }

    public function mailMsg($msg) {
        $this->mailMessage = $msg;
        return $this;
    }

    public function message($message) {
        $this->message = $message;
        return $this;
    }

    public function attachments(array $attachments = [])
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function toMail($email) {
        $this->customEmail = $email;
        return $this;
    }

    public function toPhone($phone) {
        $this->customPhone = $phone;
        return $this;
    }

    public function fcmToken(array $tokens) {
        $this->fcmToken = $tokens;
        return $this;
    }



    public function notify()
    {
        $this->validateNotification();

        Queue::push(function ($job) {
            if ($this->template) {
                $this->title = $this->template->title;
                if ($this->template->is_mail) {
                    $this->mailMessage = $this->template->sanitizeMailMessage($this->params);
                    $this->sendMail();
                }
                if ($this->template->is_push) {
                    $this->message = $this->template->sanitizeNotificationMessage($this->params);
                    $this->sendPush();
                }

                if ($this->template->is_whatsapp && $this->phone || $this->template->is_whatsapp && $this->customPhone) {
                    $this->message = $this->template->sanitizeNotificationMessage($this->params);
                    if(count($this->attachments) > 0) {
                        $extesion = pathinfo($this->attachments[0], PATHINFO_EXTENSION);
                        if(in_array($extesion, config('notify.channels.whatsapp.attachments_allowed.documents'))) {
                            $this->sendDocumentToWhatsapp();
                        } else if(in_array($extesion, config('notify.channels.whatsapp.attachments_allowed.images'))) {
                            $this->sendImageToWhatsapp();
                        } else {
                            throw new \RuntimeException("This type attachment is not supported");
                        }
                    } else {
                        $this->sendTextToWhatsapp();
                    }
                }

                if($this->template->is_sms && $this->phone || $this->template->is_sms && $this->customPhone) {
                    $this->sendSms();
                }

                if($this->saveToDB) {
                    $this->saveNotifications();
                }
            } else {
                if(in_array('mail', $this->channel)) {
                    $this->sendMail();
                }

                if(in_array('fcm', $this->channel)) {
                    $this->sendPush();
                }

                if(in_array('whatsapp', $this->channel) && $this->phone || in_array('whatsapp', $this->channel) && $this->customPhone) {
                    if(count($this->attachments) > 0) {
                        $extesion = pathinfo($this->attachments[0], PATHINFO_EXTENSION);
                        if(in_array($extesion, config('notify.channels.whatsapp.attachments_allowed.documents'))) {
                            $this->sendDocumentToWhatsapp();
                        } else if(in_array($extesion, config('notify.channels.whatsapp.attachments_allowed.images'))) {
                            $this->sendImageToWhatsapp();
                        } else {
                            throw new \RuntimeException("This type attachment is not supported");
                        }
                    } else {
                        $this->sendTextToWhatsapp();
                    }
                }

                if(in_array('sms', $this->channel) && $this->phone || in_array('sms', $this->channel) && $this->customPhone) {
                    $this->sendSms();
                }
            }
            $job->delete(); // Remove the job from the queue after processing
        });
    }


    public function sendMail()
    {
        if (!$this->mailObject) {
            Mail::to($this->customEmail ? $this->customEmail : $this->email)->queue(new \ByteBlitz\Notify\Mail\NotifyMail($this->title, $this->mailMessage, $this->attachments));
        } else {
            Mail::to($this->customEmail ? $this->customEmail : $this->email)->queue($this->mailObject);
        }
        return;
    }


    public function sendPush()
    {
        $SERVER_API_KEY = config('notify.channels.fcm.server_key');
        if($this->fcmToken) {
            $firebaseTokens = $this->fcmToken;
        } else if(count($this->deviceTokens) > 0) {
            $firebaseTokens = $this->deviceTokens->pluck('token')->toArray();
        } else {
            $firebaseTokens = [];
        }
        $firebaseToken = $firebaseTokens;

        $image = null;
        if(count($this->attachments) > 0) {
            $extesion = pathinfo($this->attachments[0], PATHINFO_EXTENSION);
            $image = null;
            if(in_array($extesion, config('notify.channels.whatsapp.attachments_allowed.images'))) {
                $image = $this->attachments[0];
            }
        }
        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => $this->title,
                "body" => $this->message,
                "image" => $image
            ]
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);

        return;
    }

    public function sendTextToWhatsapp()
    {
        $params = array(
            'token' => config('notify.channels.whatsapp.ultramsg.token'),
            'to' => $this->customPhone ? $this->customPhone : $this->phone,
            'body' => $this->message
        );

        $this->sendToWhatsapp('chat', $params);
    }

    public function sendDocumentToWhatsapp() {
        $filename = pathinfo($this->attachments[0], PATHINFO_FILENAME);
        $params = array(
            'token' => config('notify.channels.whatsapp.ultramsg.token'),
            'to' => $this->customPhone ? $this->customPhone : $this->phone,
            'filename' => $filename,
            'document' => $this->attachments[0],
            'caption' => $this->message
        );

        $this->sendToWhatsapp('document', $params);
    }

    public function sendImageToWhatsapp() {
        $params = array(
            'token' => config('notify.channels.whatsapp.ultramsg.token'),
            'to' => $this->customPhone ? $this->customPhone : $this->phone,
            'image' => $this->attachments[0],
            'caption' => $this->message
        );

        $this->sendToWhatsapp('image', $params);
    }

    public function sendToWhatsapp($type, $params) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/".config('notify.channels.whatsapp.ultramsg.instance_id')."/messages/".$type,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function sendSms() {
        // Twilio Account SID and Auth Token
        $account_sid = config('notify.channels.sms.twilio.sid');
        $auth_token = config('notify.channels.sms.twilio.auth_token'); // Replace 'your_auth_token' with your actual Auth Token

        // Twilio API endpoint
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $account_sid . '/Messages.json';

        // Data to be sent
        $data = array(
            'To' => $this->customPhone ? $this->customPhone : $this->phone,
            'From' => config('notify.channels.sms.twilio.from'),
            'Body' => $this->message
        );

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$account_sid:$auth_token");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Added line to disable SSL certificate verification
        // Execute cURL session
        $response = curl_exec($ch);

        // Close cURL session
        curl_close($ch);

        // Check for errors
        if ($response === false) {
            echo 'Error: ' . curl_error($ch);
        } else {
            echo 'Response: ' . $response;
        }
    }


    public function saveNotifications() {
        $guards = config('notify.receivers');
        $model = null;
        foreach($guards as $guardKey => $guard){
            if($this instanceof $guard) {
                $model = $guard;
            }
        }
        Notification::create([
            'instance'=>$model,
            'user_id'=>$this->id,
            'title'=>$this->title,
            'message'=>$this->message,
            'redirect_to'=>$this->redirectTo,
            'is_seen'=>false
        ]);
        return;
    }


    public function validateNotification() {
        if(in_array('mail', $this->channel)) {
            if(!$this->title) {
                throw new \RuntimeException("Title is required to send mail, use : title(string)");
            }
            if(!$this->mailMessage) {
                throw new \RuntimeException("Message body is required to send mail, use : mailMsg(string)");
            }
        }

        if(in_array('fcm', $this->channel)) {
            if(!$this->title) {
                throw new \RuntimeException("Title is required to send push notification, use : title(string)");
            }

            if(!$this->message) {
                throw new \RuntimeException("Message is required to send push notification, use : message(string)");
            }

            if(!$this->fcmToken) {
                throw new \RuntimeException("fcm tokens required to send push notification, you can pass with fcmToken(array)");
            }
        }

        if(in_array('sms', $this->channel)) {
            if(!$this->message) {
                throw new \RuntimeException("Message is required to send sms notification, use : message(string)");
            }
        }

        if(in_array('whatsapp', $this->channel)) {
            if(!$this->message) {
                throw new \RuntimeException("Message is required to send whatsapp notification, use : message(string)");
            }
        }
        if($this->saveToDB) {
            $guards = config('notify.receivers');
            $model = null;
            foreach($guards as $guardKey => $guard){
                if($this instanceof $guard) {
                    $model = $guardKey;
                }
            }
            if(!$model) {
                throw new \RuntimeException("Undefined receiver please set the receiver in notify.php config file");
            }
        }

    }


    public function notifications() {
        $guards = config('notify.receivers');
        $instance = null;
        foreach($guards as $guardKey => $guard){
            if($this instanceof $guard) {
                $instance = $guard;
            }
        }
        $this->notifications = Notification::select('id', 'title', 'message', 'redirect_to', 'is_seen')->where('instance', $instance)->where('user_id', $this->id)->get();
        return $this->notifications;
    }

    public function seen() {
        return $this->notifications()->where('is_seen', 1)->get();
    }


}
