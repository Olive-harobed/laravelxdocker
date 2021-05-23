<?php

namespace App\Jwt;

use App\Contracts\JwtGenerator;
use App\Contracts\JwToken;
use App\Contracts\JwtSubject;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class Generator implements JwtGenerator
{
    public static function signature(JwToken $token): string
    {
        $secret = config('app.key');

        if ($secret === null) {
            throw new InvalidArgumentException('No APP_KEY specified.');
        }

        $encodedData = self::encodeData($token);

        return hash_hmac('sha256', $encodedData, $secret);
    }

    public static function token(JwtSubject $user): string
    {
        $now = Carbon::now();
        $expiresIn = (int) config('jwt.expiration');
        $expiresAt = $now->addSeconds($expiresIn);

        $token = Builder::build()
            ->subject($user)
            ->issuedAt($now->getTimestamp())
            ->expiresAt($expiresAt->getTimestamp())
            ->getToken();

        $parts = [
            self::encodeData($token),
            base64_encode(self::signature($token)),
        ];

        return implode('.', $parts);
    }

    /**
     * Encode JwToken headers and payload.
     *
     * @param \App\Contracts\JwToken $token
     * @return string
     */
    private static function encodeData(JwToken $token): string
    {
        $jsonParts = [
            $token->headers()->toJson(),
            $token->claims()->toJson(),
        ];

        $encoded = array_map('base64_encode', $jsonParts);

        return implode('.', $encoded);
    }
}