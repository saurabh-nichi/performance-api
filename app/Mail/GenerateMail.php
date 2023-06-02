<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenerateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $view, $subject, $data;

    /**
     * Create a new message instance.
     */
    public function __construct(string $view, string $subject, array $data = [])
    {
        $this->view($view, $data);
        $this->subject($subject);
    }
    
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = config('constants.MAIL_FROM_ADDRESS');
        $name = config('constants.MAIL_FROM_NAME');
        return $this->from($address, $name)
            ->cc($address, $name)
            ->replyTo($address, $name);
    }
}
