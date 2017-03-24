<?php

namespace PHPKit\Email\Adapter;

class SendCloud implements \PHPKit\EmailInterface
{
    protected $apiurl = 'http://api.sendcloud.net/apiv2/';

    protected $config = [];
    
    protected $apiuser = '';
    protected $apikey = '';
    protected $from = '';

    protected $to = '';
    protected $subject = '';
    protected $content = '';

    protected $type = 'html'; // html / plain (text)

    protected $response = '';
    
    public function __construct($config=[])
    {
        if ($config) {
            $this->setConfig($config);
        }
    }

    public function setConfig($config)
    {
        $this->config = $config;

        if (isset($config['api-user']) && isset($config['api-key'])) {
            $this->setAuth($config['api-user'], $config['api-key']);
        }

        if (isset($config['from'])) {
            $this->from($config['from']);
        }
    }

    public function setAuth()
    {
        $this->apiuser = func_get_arg(0);
        $this->apikey = func_get_arg(1);
    }

    public function from($from)
    {
        $this->from = $from;
    }

    public function to($to)
    {
        $this->to = $to;
    }

    public function subject($subject)
    {
        $this->subject = $subject;
    }
    
    public function content($content)
    {
        $this->content = $content;
    }

    public function type($type)
    {
        if ($type!=='html' || $type=='text') {
            $type = 'plain';
        }
        $this->type = $type;
    }

    public function send($to=false, $subject=false, $content=false)
    {
        if ($to!==false) {
            $this->to($to);
        }
        if ($subject!==false) {
            $this->subject($subject);
        }
        if ($content!==false) {
            $this->content($content);
        }

        $url = $this->apiurl.'mail/send';

        $params = [
            'apiUser' => $this->apiuser,
            'apiKey' => $this->apikey,
            'from' => $this->from,
            'to' => $this->to,
            'subject' => $this->subject,
            $this->type => $this->content
        ];

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            ]
        ];

        if ($response = file_get_contents($url, false, stream_context_create($options))) {
            $this->response = $response;
            if ( $rs = json_decode($response, true)) {
                $this->response = $rs;
                if ($rs['statusCode']=='200') {
                    return true;
                }
            }
        }

        return false;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
