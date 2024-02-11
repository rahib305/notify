<?php

namespace ByteBlitz\Notify\Trait;

use ByteBlitz\Notify\Models\NotifyTemplate;

trait TemplateTrait {


    protected $templateData = [];


    public function session(string $session) {
        $this->templateData['session'] = $session;
        return $this;
    }

    public function receiver(string $receiver) {
        $this->templateData['receiver'] = $receiver;
        return $this;
    }

    public function slug(string $slug) {
        $this->templateData['slug'] = $slug;
        return $this;
    }

    public function titleTag(string $title) {
        $this->templateData['title'] = $title;
        return $this;
    }

    public function mailMessage(string $mailMessage) {
        $this->templateData['mail_msg'] = $mailMessage;
        return $this;
    }

    public function messageBody(string $message) {
        $this->templateData['notification_msg'] = $message;
        return $this;
    }

    public function variables(array $variables) {
        $this->templateData['variables'] = '{' . implode('},{', $variables) . '}';
        return $this;
    }

    public function channels(array $channels) {
        $this->templateData['is_mail'] = false;
        $this->templateData['is_push'] = false;
        $this->templateData['is_sms'] = false;
        $this->templateData['is_whatsapp'] = false;

        if (count($channels)) {
            if(in_array('mail', $channels)) {
                $this->templateData['is_mail'] = true;
            }
            if(in_array('fcm', $channels)){
                $this->templateData['is_push'] = true;
            }
            if(in_array('sms', $channels)) {
                $this->templateData['is_sms'] = true;
            }
            if(in_array('whatsapp', $channels)) {
                $this->templateData['is_whatsapp'] = true;
            }
        } else {
            throw new \Exception("At least one channel must be provided.");
        }
        return $this;
    }

    public function createTemplate(array $array=null) {
        if($array) {
            foreach($array as $key => $data) {
                if($key == 'title') {
                    $this->titleTag($data);
                } else if($key == 'mail_msg') {
                    $this->mailMessage($data);
                } else if($key == 'notification_msg') {
                    $this->messageBody($data);
                } else {
                    $this->$key($data);
                }
            }
        }
        $requiredParams = ['session', 'receiver', 'slug', 'title'];

        $existingTemplate = NotifyTemplate::where('slug', $this->templateData['slug'])->first();
        if ($existingTemplate) {
            throw new \RuntimeException("Template with slug '".$this->templateData['slug']."' already exists.");
        }

        foreach ($requiredParams as $param) {
            if (!array_key_exists($param, $this->templateData)) {
                throw new \RuntimeException("'$param' is required to create a template.");
            }
        }
        $this->templateData['status'] = true;
        NotifyTemplate::create($this->templateData);
        return $this;
    }


    public function getTemplate(string $slug) {
        $templateExist = NotifyTemplate::where('slug', $slug)->first();
        if (!$templateExist) {
            throw new \RuntimeException("Template with slug '".$slug."' not exist");
        }
        return $templateExist;
    }

    public function updateTemplate(array $array=null, int $id) {
        if($array) {
            foreach($array as $key => $data) {
                if($key == 'mail_msg') {
                    $this->mailMessage($data);
                } else if($key == 'notification_msg') {
                    $this->message($data);
                } else {
                    $this->$key($data);
                }
            }
        }

        $requiredParams = ['session', 'receiver', 'slug'];

        $template = NotifyTemplate::find($id);
        if (!$template) {
            throw new \RuntimeException("Template with slug '".$this->templateData['slug']."' not exist.");
        }

        foreach ($requiredParams as $param) {
            if (!array_key_exists($param, $this->templateData)) {
                throw new \RuntimeException("'$param' is required to create a template.");
            }
        }
        $this->templateData['status'] = true;
        $template->update($this->templateData);
        return $this;
    }

    public function dropTemplate(int $id) {
        $template = NotifyTemplate::find($id);
        if (!$template) {
            throw new \RuntimeException("Template with slug '".$this->templateData['slug']."' not exist.");
        }

        $template->delete();
        return $this;
    }

    public function restoreTemplate(string $slug) {
        $template = NotifyTemplate::withTrashed()->where('slug', $slug)->first();
        if (!$template) {
            throw new \RuntimeException("Template with slug '".$slug."' not exist.");
        }

        $template->restore();
        return $this;
    }
}
