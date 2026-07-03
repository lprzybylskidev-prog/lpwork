<?php

declare(strict_types=1);

namespace LPWork\Frontend;

/**
 * Represents the framework mail template framework component.
 */
final readonly class FrameworkMailTemplate
{
    /**
     * Renders this component into its output representation.
     */
    public function render(string $subject, string $body): string
    {
        if (str_contains(strtolower($body), '<html')) {
            return $body;
        }

        return sprintf(
            <<<'HTML'
                <!doctype html>
                <html lang="en">
                <head>
                  <meta charset="utf-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1">
                  <title>%s</title>
                </head>
                <body style="margin:0;background:#090b0f;color:#e7edf6;font-family:Helvetica Neue,helvetica,arial,sans-serif;">
                  <table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#090b0f;">
                    <tr>
                      <td style="padding:28px 16px;">
                        <table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;max-width:640px;margin:0 auto;border:1px solid #263241;background:#0d141d;">
                          <tr>
                            <td style="background:#0b1118;border-bottom:1px solid #263241;padding:16px 18px;">
                              <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                  <td style="padding-right:10px;"><img src="%s" width="32" height="32" alt="LPWork" style="display:block;height:32px;width:32px;"></td>
                                  <td>
                                    <div style="color:#ffffff;font-size:13px;font-weight:800;letter-spacing:.08em;line-height:1;text-transform:uppercase;">LPWORK</div>
                                    <div style="color:#65a9ed;font-size:12px;font-weight:700;line-height:1.2;margin-top:4px;">Mail</div>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                          <tr>
                            <td style="padding:24px 24px 14px;">
                              <h1 style="color:#f1f5f9;font-size:24px;font-weight:800;line-height:1.24;margin:0;">%s</h1>
                            </td>
                          </tr>
                          <tr>
                            <td style="color:#cbd5e1;font-size:15px;line-height:1.68;padding:0 24px 26px;">%s</td>
                          </tr>
                          <tr>
                            <td style="background:#0b1118;border-top:1px solid #263241;color:#64748b;font-size:12px;padding:14px 24px;">
                              Sent by LPWork
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </body>
                </html>
                HTML,
            $this->escape($subject),
            $this->escape(FrameworkAssets::logoDataUri()),
            $this->escape($subject),
            $body,
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
