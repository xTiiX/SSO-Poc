<?php

declare(strict_types=1);

namespace App\Application\Actions\Login;

use Psr\Http\Message\ResponseInterface as Response;

class LoggingAction extends LoginAction
{
	protected function action(): Response
	{
		return $this->respondWithData('Hello World !');
	}
}