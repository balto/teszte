<?php

class Mail {


    public static function sendPlainTextMail($from, $to, $subject, $body)
    {
        // Get mailer
        $SM = Yii::app()->swiftMailer;

        // Get config
        $mailHost = Yii::app()->params['email']['host'];
        $mailPort = Yii::app()->params['email']['port'];

        // New transport
        $Transport = $SM->smtpTransport($mailHost, $mailPort);

        // Mailer
        $Mailer = $SM->mailer($Transport);

        // New message
        $Message = $SM
            ->newMessage($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body);

        // Send mail, return how many recipient successfully sent (0 == error)
        $sent = $Mailer->send($Message);

        if (!$sent) Yii::log('Sending plain text mail was failed to ' . implode(',', (array)$to) . ' subject: ' .$subject, 'warning', 'mail.send');

        return $sent == 0 ? false : true;
    }

    public static function sendHtmlMail($from, $to, $subject, $body)
    {
        // Get mailer
        $SM = Yii::app()->swiftMailer;

        // Get config
        $mailHost = Yii::app()->params['email']['host'];
        $mailPort = Yii::app()->params['email']['port'];

        // New transport
        $Transport = $SM->smtpTransport($mailHost, $mailPort);

        // Mailer
        $Mailer = $SM->mailer($Transport);

        // New message
        $Message = $SM
            ->newMessage($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/html');

        // Send mail, return how many recipient successfully sent (0 == error)
        $sent = $Mailer->send($Message);

        if (!$sent) Yii::log('Sending HTML mail was failed to ' . implode(',', (array)$to) . ' subject: ' .$subject, 'warning', 'mail.send');

        return $sent == 0 ? false : true;
    }

}

