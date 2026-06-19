<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JwksJwtVerifier
{
    public function verify(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new RuntimeException('Format JWT tidak valid.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeJson($encodedHeader);
        $claims = $this->decodeJson($encodedPayload);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException('JWT harus memakai algoritma RS256.');
        }

        $key = $this->findKey((string) ($header['kid'] ?? ''));
        $publicKey = $this->jwkToPem($key);
        $signature = $this->base64UrlDecodeRaw($encodedSignature);
        $signedValue = $encodedHeader . '.' . $encodedPayload;

        $verified = openssl_verify($signedValue, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new RuntimeException('Signature JWT tidak valid.');
        }

        $now = time();
        if (isset($claims['exp']) && (int) $claims['exp'] < $now) {
            throw new RuntimeException('JWT sudah expired.');
        }

        if (isset($claims['nbf']) && (int) $claims['nbf'] > $now) {
            throw new RuntimeException('JWT belum boleh dipakai.');
        }

        $expectedIssuer = config('iae.jwt_issuer');
        if ($expectedIssuer && ($claims['iss'] ?? null) !== $expectedIssuer) {
            throw new RuntimeException('Issuer JWT tidak sesuai.');
        }

        return $claims;
    }

    private function findKey(string $kid): array
    {
        $jwks = Cache::remember('iae.central.jwks', (int) config('iae.jwks_cache_seconds', 3600), function () {
            $response = Http::timeout(15)->get($this->url('/api/v1/auth/jwks'));

            if (!$response->successful()) {
                throw new RuntimeException('Gagal mengambil JWKS dari SSO dosen.');
            }

            return $response->json();
        });

        foreach (($jwks['keys'] ?? []) as $key) {
            if (($key['kid'] ?? '') === $kid) {
                return $key;
            }
        }

        throw new RuntimeException('Public key JWT tidak ditemukan di JWKS.');
    }

    private function decodeJson(string $value): array
    {
        $decoded = json_decode($this->base64UrlDecodeRaw($value), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('JWT berisi JSON yang tidak valid.');
        }

        return $decoded;
    }

    private function base64UrlDecodeRaw(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw new RuntimeException('Base64URL JWT/JWK tidak valid.');
        }

        return $decoded;
    }

    private function jwkToPem(array $jwk): string
    {
        if (($jwk['kty'] ?? null) !== 'RSA' || empty($jwk['n']) || empty($jwk['e'])) {
            throw new RuntimeException('JWK RSA tidak lengkap.');
        }

        $modulus = $this->base64UrlDecodeRaw($jwk['n']);
        $exponent = $this->base64UrlDecodeRaw($jwk['e']);

        $rsaPublicKey = $this->derSequence(
            $this->derInteger($modulus) . $this->derInteger($exponent)
        );

        $algorithmIdentifier = hex2bin('300d06092a864886f70d0101010500');
        $subjectPublicKeyInfo = $this->derSequence(
            $algorithmIdentifier . $this->derBitString($rsaPublicKey)
        );

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '') {
            $bytes = "\x00";
        }

        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . $this->derLength(strlen($bytes)) . $bytes;
    }

    private function derSequence(string $bytes): string
    {
        return "\x30" . $this->derLength(strlen($bytes)) . $bytes;
    }

    private function derBitString(string $bytes): string
    {
        $bytes = "\x00" . $bytes;

        return "\x03" . $this->derLength(strlen($bytes)) . $bytes;
    }

    private function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('iae.central_base_url'), '/') . $path;
    }
}
