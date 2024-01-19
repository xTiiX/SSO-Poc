<?php

declare(strict_types=1);

namespace App\Application\Actions\Login;

use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;

class JWTAction extends LoginAction
{
	protected function action(): Response
	{
		$secret_Key  = '68V0zWFrS72GbpPreidkQFLfj4v9m3Ti+DXc8OB0gcM=';
		$date   = new DateTimeImmutable();
		$expire_at     = $date->modify('+6 minutes')->getTimestamp();      // Add 60 seconds
		$domainName = "your.domain.name";
		$username   = "username";                                           // Retrieved from filtered POST data
		$request_data = [
			'iat'  => $date->getTimestamp(),         // Issued at: time when the token was generated
			'iss'  => $domainName,                       // Issuer
			'nbf'  => $date->getTimestamp(),         // Not before
			'exp'  => $expire_at,                           // Expire
			'userName' => $username,                     // User name
		];

		echo JWT::encode(
			$request_data,
			$secret_Key,
			'HS512'
		);


		return $this->respondWithData('Hello World !');
	}
}