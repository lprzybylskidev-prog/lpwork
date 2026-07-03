<?php

declare(strict_types=1);

namespace LPWork\Mail\Transports;

use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\Exceptions\MailTransportException;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailMessageRenderer;
use LPWork\Mail\MailSendResult;
use LPWork\Shared\Http\HttpClient;
use Throwable;

/**
 * Represents the ses mail transport framework component.
 */
final readonly class SesMailTransport implements MailTransport
{
    /**
     * Creates a new SesMailTransport instance.
     */
    public function __construct(
        private string $name,
        private string $region,
        private string $accessKey,
        private string $secretKey,
        private MailMessageRenderer $renderer = new MailMessageRenderer(),
        private HttpClient $http = new HttpClient(),
    ) {}

    /**
     * Runs send.
     */
    public function send(MailMessage $message): MailSendResult
    {
        $messageId = bin2hex(random_bytes(16)) . '@lpwork.local';
        $body = json_encode([
            'Content' => ['Raw' => ['Data' => base64_encode($this->renderer->render($message, $messageId))]],
        ], JSON_THROW_ON_ERROR);
        $endpoint = "https://email.{$this->region}.amazonaws.com/v2/email/outbound-emails";

        try {
            $response = $this->http->request('POST', $endpoint, $this->headers($endpoint, $body), $body);
        } catch (Throwable $throwable) {
            throw MailTransportException::sendFailed($this->name, $throwable);
        }

        if (!$response->successful()) {
            throw MailTransportException::unexpectedResponse($this->name, $response->body);
        }

        return new MailSendResult($this->name, $messageId);
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $endpoint, string $body): array
    {
        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $parsedHost = parse_url($endpoint, PHP_URL_HOST);
        $host = is_string($parsedHost) ? $parsedHost : '';
        $payloadHash = hash('sha256', $body);
        $canonicalHeaders = "content-type:application/json\nhost:{$host}\nx-amz-date:{$now}\n";
        $signedHeaders = 'content-type;host;x-amz-date';
        $canonicalRequest = implode("\n", ['POST', '/v2/email/outbound-emails', '', $canonicalHeaders, $signedHeaders, $payloadHash]);
        $scope = "{$date}/{$this->region}/ses/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $scope, hash('sha256', $canonicalRequest)]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($date));

        return [
            'Authorization' => sprintf('AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s', $this->accessKey, $scope, $signedHeaders, $signature),
            'Content-Type' => 'application/json',
            'Host' => $host,
            'x-amz-date' => $now,
        ];
    }

    private function signingKey(string $date): string
    {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, binary: true);
        $regionKey = hash_hmac('sha256', $this->region, $dateKey, binary: true);
        $serviceKey = hash_hmac('sha256', 'ses', $regionKey, binary: true);

        return hash_hmac('sha256', 'aws4_request', $serviceKey, binary: true);
    }
}
