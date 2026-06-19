<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class CentralAuditClient
{
    public function __construct(private readonly CentralAuthService $authService)
    {
    }

    public function send(string $activityName, array $logContent): array
    {
        $xml = $this->buildEnvelope($activityName, $logContent);

        $response = Http::withToken($this->authService->getMachineToken())
            ->withHeaders(['Accept' => 'text/xml'])
            ->timeout(20)
            ->withBody($xml, 'text/xml; charset=UTF-8')
            ->post($this->url('/soap/v1/audit'));

        if (!$response->successful()) {
            throw new RuntimeException('SOAP audit gagal dikirim ke server dosen.');
        }

        return $this->parseResponse($response->body());
    }

    public function buildEnvelope(string $activityName, array $logContent): string
    {
        $teamId = htmlspecialchars((string) config('iae.team_id'), ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $activity = htmlspecialchars($activityName, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $json = json_encode($logContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $cdata = str_replace(']]>', ']]]]><![CDATA[>', $json ?: '{}');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$teamId}</iae:TeamID>
      <iae:ActivityName>{$activity}</iae:ActivityName>
      <iae:LogContent><![CDATA[{$cdata}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function parseResponse(string $xml): array
    {
        // Suppress libxml errors agar tidak bocor ke output, tangkap manual
        libxml_use_internal_errors(true);

        try {
            $document = new SimpleXMLElement($xml);
        } catch (\Throwable $e) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $detail = !empty($errors) ? $errors[0]->message : $e->getMessage();
            throw new RuntimeException('Response SOAP tidak bisa diparse sebagai XML: ' . trim($detail));
        }

        libxml_clear_errors();

        // Pakai local-name() agar tidak tergantung namespace prefix yang dipakai server dosen
        $statusNodes  = $document->xpath('//*[local-name()="Status"]');
        $receiptNodes = $document->xpath('//*[local-name()="ReceiptNumber"]');

        $status        = !empty($statusNodes)  ? (string) $statusNodes[0]  : null;
        $receiptNumber = !empty($receiptNodes) ? (string) $receiptNodes[0] : null;

        if ($status !== 'SUCCESS' || !$receiptNumber) {
            throw new RuntimeException(
                'Response SOAP audit tidak berisi receipt sukses. Status: ' . ($status ?? 'null')
            );
        }

        return [
            'status'         => $status,
            'receipt_number' => $receiptNumber,
        ];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('iae.central_base_url'), '/') . $path;
    }
}
