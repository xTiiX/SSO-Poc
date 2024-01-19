<?php

declare(strict_types=1);

namespace App\Application\Actions\Login;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class LoginAction extends Action
{
	public function __construct(LoggerInterface $logger)
	{
		parent::__construct($logger);
	}

	protected function action(): Response
	{
		return $this->respondWithData('Hello World !');
	}
}
