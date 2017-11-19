<?php return '<?php

namespace Modules\\Blog\\Emails;

use Illuminate\\Bus\\Queueable;
use Illuminate\\Mail\\Mailable;
use Illuminate\\Queue\\SerializesModels;
use Illuminate\\Contracts\\Queue\\ShouldQueue;

class SomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view(\'view.name\');
    }
}
';
