<?php

declare(strict_types=1);

namespace LPWork\Queue\Drivers;

use LPWork\Queue\Contracts\QueueDriver;
use LPWork\Queue\QueuedJobPayload;
use LPWork\Queue\ReservedJob;
use LPWork\Shared\Http\HttpClient;
use RuntimeException;

/**
 * Represents the sqs queue driver framework component.
 */
final readonly class SqsQueueDriver implements QueueDriver
{
    /**
     * Creates a new SqsQueueDriver instance.
     */
    public function __construct(
        private string $queueUrl,
        private string $region,
        private string $accessKey,
        private string $secretKey,
        private HttpClient $http = new HttpClient(),
    ) {}

    /**
     * Performs assert ready.
     */
    public function assertReady(): void
    {
        $this->request([
            'Action' => 'GetQueueAttributes',
            'AttributeName' => 'QueueArn',
            'Version' => '2012-11-05',
        ]);
    }

    /**
     * Registers or stores push.
     */
    public function push(QueuedJobPayload $payload): string
    {
        $this->request([
            'Action' => 'SendMessage',
            'MessageBody' => base64_encode(serialize($payload)),
            'DelaySeconds' => (string) max(0, $payload->availableAt - time()),
            'Version' => '2012-11-05',
        ]);

        return $payload->id;
    }

    /**
     * Performs the reserve operation.
     */
    public function reserve(string $queue, int $retryAfterSeconds): ?ReservedJob
    {
        $response = $this->request([
            'Action' => 'ReceiveMessage',
            'MaxNumberOfMessages' => '1',
            'VisibilityTimeout' => (string) $retryAfterSeconds,
            'Version' => '2012-11-05',
        ]);

        if (preg_match('/<Body>([^<]+)<\/Body>/', $response, $body) !== 1 || preg_match('/<ReceiptHandle>([^<]+)<\/ReceiptHandle>/', $response, $receipt) !== 1) {
            return null;
        }

        $decoded = base64_decode(html_entity_decode($body[1], ENT_QUOTES | ENT_XML1), strict: true);

        if (!is_string($decoded)) {
            return null;
        }

        $payload = unserialize($decoded);

        if (!$payload instanceof QueuedJobPayload) {
            return null;
        }

        return new ReservedJob($payload, 1, html_entity_decode($receipt[1], ENT_QUOTES | ENT_XML1));
    }

    /**
     * Removes or clears release.
     */
    public function release(ReservedJob $job, int $delaySeconds): void
    {
        $this->request([
            'Action' => 'ChangeMessageVisibility',
            'ReceiptHandle' => $job->driverId,
            'VisibilityTimeout' => (string) $delaySeconds,
            'Version' => '2012-11-05',
        ]);
    }

    /**
     * Performs the complete operation.
     */
    public function complete(ReservedJob $job): void
    {
        $this->request([
            'Action' => 'DeleteMessage',
            'ReceiptHandle' => $job->driverId,
            'Version' => '2012-11-05',
        ]);
    }

    /**
     * Performs the fail operation.
     */
    public function fail(ReservedJob $job, string $exception): void
    {
        $this->complete($job);
    }

    /**
     * Removes or clears prune completed.
     */
    public function pruneCompleted(int $olderThanSeconds): int
    {
        return 0;
    }

    /**
     * Removes or clears prune failed.
     */
    public function pruneFailed(int $olderThanSeconds): int
    {
        return 0;
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(string $queue): int
    {
        $this->request([
            'Action' => 'PurgeQueue',
            'Version' => '2012-11-05',
        ]);

        return 0;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function request(array $parameters): string
    {
        ksort($parameters);
        $body = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $headers = $this->headers($body);
        $response = $this->http->request('POST', $this->queueUrl, $headers, $body);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf('SQS request failed with status [%d].', $response->status));
        }

        return $response->body;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $body): array
    {
        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $parsedHost = parse_url($this->queueUrl, PHP_URL_HOST);
        $host = is_string($parsedHost) ? $parsedHost : '';
        $payloadHash = hash('sha256', $body);
        $canonicalHeaders = "content-type:application/x-www-form-urlencoded\nhost:{$host}\nx-amz-date:{$now}\n";
        $signedHeaders = 'content-type;host;x-amz-date';
        $parsedPath = parse_url($this->queueUrl, PHP_URL_PATH);
        $path = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : '/';
        $canonicalRequest = implode("\n", ['POST', $path, '', $canonicalHeaders, $signedHeaders, $payloadHash]);
        $scope = "{$date}/{$this->region}/sqs/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $scope, hash('sha256', $canonicalRequest)]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($date));

        return [
            'Authorization' => sprintf('AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s', $this->accessKey, $scope, $signedHeaders, $signature),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Host' => $host,
            'x-amz-date' => $now,
        ];
    }

    private function signingKey(string $date): string
    {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, binary: true);
        $regionKey = hash_hmac('sha256', $this->region, $dateKey, binary: true);
        $serviceKey = hash_hmac('sha256', 'sqs', $regionKey, binary: true);

        return hash_hmac('sha256', 'aws4_request', $serviceKey, binary: true);
    }
}
