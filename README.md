# notify

## Introduction

Notify is a package that lets you send template based or custom notifications mutli-channel for your project. Channels included : mail, fcm, sms, whatsapp

## Installation

You can install the package using composer

```sh
$ composer require byteblitz/notify
```


Then add the service provider to `config/app.php`. In Laravel versions 5.5 and beyond, this step can be skipped if package auto-discovery is enabled.

```php
'providers' => [
    ...
    ByteBlitz\Notify\NotifyServiceProvider::class
    ...
];
```


You can publish the configuration file and assets by running:

```sh
$ php artisan vendor:publish --provider="ByteBlitz\Notify\NotifyServiceProvider"
```

This will create a notify.php file in config folder, and update channel details and other.


Now that we have published a few new files to our application we need to reload them with the following command:

```sh
$ composer dump-autoload
```

Now we have to migrate our notify_templates & notifications tables, migrate by running:

```sh
$ php artisan migrate
```

## Basic

use this facade in your controller

```php
use ByteBlitz\Notify\Facades\Notify;
```


let's create templates for notifications, you can use seeder to copy the below code :

```php
$templates = [
  [
      'session'=>'New Registration To Admin',
      'receiver'=> 'admin',
      'slug' => 'new_registration_to_admin',
      'title' => 'New Registration',
      'mail_msg' => "<p>Hy Admin,</p><p>&nbsp; {name} is registered in ByteBlitz, Please review and verify. </p>",
      'notification_msg'=>"",
      'variables' => ['name'],
      'channels'=>['mail', 'fcm']
  ],
  [
      'session'=>'Registration success to customer',
      'receiver'=> 'customer',
      'slug' => 'registration_success_to_customer',
      'title' => 'Welcome to ByteBlitz',
      'notification_msg'=>"",
      'mail_msg' => "<p>Hy {name},</p><p>&nbsp;We'd like to welcome you to <strong>ByteBlitz</strong>, and thank you for joining us.</p>",
      'variables' => ['name'],
      'channels'=>['mail']
  ],
];

foreach($templates as $template) {
    Notify::createTemplate($template);
}
```

other template functions : 

```php
//To get template
Notify::getTemplate('slug');
//To update template
Notify::updateTemplate($templateData, $id);
//To delete template
Notify::dropTemplate($id);
//To restore deleted template
Notify::restoreTemplate('slug');
```

Now let's start to send notifications with created template:

First use this trait in all guard user model to receive notification
```php
use ByteBlitz\Notify\Trait\NotifyBlitz;
```
Send notification with use of template, it will send to all the channels we defined on that template:
Note : the user object have email and phone fields.
```php
//variables need to be override so define template variables :
$variables = [
  'name'=>'Jhon'
];
$user->template('registration_success_to_customer', $variables)->notify();

```

if you want save the notification to db, it will stored in notifications table, add these parameters:
```php
$user->template('registration_success_to_customer', $variables, true, 'redirection route or url')->notify();
```

Sending attachments with template, we can send multiple attachments. and that will be sent to mail, and whatsapp if it turn on
```php
$user->template('registration_success_to_customer', $variables)->->attachments(['../attachment/path'])->notify();
```

If you want to use custom :
```php
//mailable class
$user
->customMail(new \App\Mail\CustomMail())
//custom to mail
->toMail('custom@gmail.com')
//custom phone number to send sms or whatsapp notification
->toPhone('+9198776*****')

//send custom messages
->via(['mail', 'fcm', 'whatsapp', 'sms'])
->title('Hellooooo')
->mailMsg('Mail Msg')
->message('notification message')
->notify();
```

## Template Usages

Want to send button to mail, to override with variable:
```php
notifyButton('https://www.facebook.com/', 'Create Account');
```

Want to send image to mail, to override with variable:
```php
notifyImage('https://picperf.io/https://laravelnews.s3.amazonaws.com/images/laravel-featured.png');
```

## Config
Config file are located at `config/notify.php` after publishing provider element.

Make sure to add all user guards to receive notifications:
```php
'receivers'=>[
    'user'=>App\Models\User::class,
    'admin'=>App\Models\Admin::class
],
```

Change the email template title, logo or primary colour:
```php
'mail'=>[
    'title'=>'ByteBlitz',
    'logo'=>'',
    'primary_clr'=>'red'
],
```
Update channel values or add in `.env` file 
```php
//For firebase
'fcm'=> [
    'server_key'=>env('FCM_SERVER_KEY')
],

//For Whatsapp use ultramsg api provider
'whatsapp'=> [
    'ultramsg'=>[
        'instance_id'=> env('ULTRAMSG_INSTANCE_ID'),
        'token'=> env('ULTRAMSG_TOKEN'),
    ],
    'attachments_allowed'=>[
        'documents'=>['pdf'],
        'images'=>['png', 'jpg']
    ]
],

//For SMS, use twilio
'sms'=> [
    'twilio'=>[
        'sid'=>env('TWILIO_SID'),
        'auth_token'=>env('TWILIO_AUTH_TOKEN'),
        'from'=>env('TWILIO_FROM_NUMBER')
    ]
]
```

## Get Notifications
Get all received notifications of a user
```php
$user->notifications();
```

