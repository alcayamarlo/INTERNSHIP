<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by jobs whose caller must know that an email was not delivered.
 * The underlying transport error is already logged by MailSender.
 */
class MailDeliveryFailed extends RuntimeException {}
