<?php

namespace App\Mail;

use App\Models\Padre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PadreRegistered extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $padre;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Padre $padre)
    {
        $this->padre = $padre;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Credencial de Acceso para Registro de Asistencia')
            ->view('emails.padre_registered');
    }
}
